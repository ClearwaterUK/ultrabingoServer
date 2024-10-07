<?php

require __DIR__ . '/vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;

//Can't do 8080 on local because 8080 is reserved by Steam.
$PORT = 2052;

$MAX_CONCURRENT_CONNECTIONS = 64;
$TIMEOUT = 60;

$connectionLog = array();

$steamIdToUsernameTable = array();

require_once('functions.php');

//Load all the NetworkMessage classes from the folder
$networkMessageFolder = glob(__DIR__.'/NetworkMessages/*.php');
foreach($networkMessageFolder as $file)
{
    echo("Loading ".$file."\n");
    require_once $file;
}

function decodeMessage($message)
{
    return json_decode(base64_decode($message),true);
}

function sendEncodedMessage($messageToSend,$connection):void
{
    $encodedMessage = base64_encode(json_encode($messageToSend));
    //echo("Sending base64 message:\n");
    //echo($encodedMessage."\n");
    $connection->text($encodedMessage);
}

function handleError(\WebSocket\Connection $connection,\WebSocket\Exception\Exception $exception):void
{
    global $gameCoordinator;
    global $steamIdToUsernameTable;

    echo(Color::RED() . "Client was dropped - lost connection or alt-f4'd?" . Color::reset() . "\n");

    echo(Color::RED() . $exception->getMessage() . " (".$exception->getCode().")". Color::reset() . "\n");

    //Remove the dropped connection from the game that it was in.
    $gameDetails = getPlayerFromConnectionTable($connection);
    if($gameDetails != null)
    {
        print_r($gameDetails);
        //Go into the room id
        $associatedGame = $gameCoordinator->currentGames[$gameDetails[0]];
        $username = $gameDetails[1];

        echo("Player who timed out:".$username);

        print_r($steamIdToUsernameTable);
        $steamId = array_search($username,$steamIdToUsernameTable);
        echo("Associated SteamID: ".$steamId."\n");

        //If the SteamID of the player who dropped is the host of the associated game, end the game for all players and remove
        //the game from the current game list.

        if($associatedGame->gameHost === $steamId)
        {
            echo("Client who dropped was the host of game ".$gameDetails[0]. " , ending game for all connected players");
            $gameCoordinator->disconnectAllPlayers($gameDetails[0],$connection,"HOSTDROPPED");
            $gameCoordinator->destroyGame($gameDetails[0]);
        }
        else
        {
            $indexToUnset = "";
            //print_r($associatedGame->currentPlayers);
            foreach($associatedGame->currentPlayers as $playerSteamId => $playerObj)
            {
                if($playerObj->username === $username)
                {
                    $indexToUnset = $playerSteamId;
                }
                else
                {
                    echo("Sending timeout notice to ".$playerObj->username." that ".$username." dropped\n");
                    $timeoutNotif = new TimeoutNotification($username,$steamId);
                    $em = new EncapsulatedMessage("TimeoutNotification",json_encode($timeoutNotif));
                    sendEncodedMessage($em,$playerObj->websocketConnection);
                }
            }
            unset($associatedGame->currentPlayers[$indexToUnset]);
        }

    }
    dropFromConnectionTable($connection);
}

function onMessageRecieved($message,$connection):void
{
    global $gameCoordinator;

    $receivedJson = decodeMessage($message);
    $messageType = $receivedJson["messageType"];
    switch($messageType)
    {
        case "CreateRoom":
        {
            echo("Received request to create room\n");
            $roomId = createRoomInDatabase($receivedJson);

            if($roomId <> null && $roomId <> 0)
            {
                //Create the room
                $status = "ok";
                $game = $gameCoordinator->createGame($roomId,$receivedJson["hostSteamName"],$connection,$receivedJson["hostSteamId"]);
                echo("Game created and set up with id ".$roomId."\n");
                $crr = new CreateRoomResponse($status,$roomId,$game);
                $em = new EncapsulatedMessage("CreateRoomResponse",json_encode($crr));

                addToConnectionTable($connection,$roomId,$receivedJson["hostSteamName"]);
                addToUsernameLookupTable($receivedJson['hostSteamId'],$receivedJson["hostSteamName"]);
            }
            else{
                echo("Failed to create room!\n");
                $status = "err";
                $crr = new CreateRoomResponse($status,$roomId);
                $em = new EncapsulatedMessage("CreateRoomResponse",json_encode($crr));
            }

            //Send back the response to the client
            sendEncodedMessage($em,$connection);
            break;
        }

        case "JoinRoom":
        {
            echo($receivedJson['username']." wants to join game ".$receivedJson['roomId']."\n");

            $gameToJoin = $gameCoordinator->joinGame($receivedJson['roomId'],$receivedJson['username'],$receivedJson['steamId'],$connection);

            $status = (gettype($gameToJoin) == "integer") ? $gameToJoin : 0;
            $roomId = $receivedJson['roomId'];
            $room = ($status == 0) ? $gameCoordinator->currentGames[$receivedJson['roomId']] : null;

            $jrr = new JoinRoomResponse($status,$roomId,$room);
            $em = new EncapsulatedMessage("JoinRoomResponse",json_encode($jrr));
            sendEncodedMessage($em,$connection);

            if($status == 0)
            {
                echo("Adding to connection log\n");
                addToConnectionTable($connection,$roomId,$receivedJson['username']);
                addToUsernameLookupTable($receivedJson['steamId'],$receivedJson['username']);
            }
            break;
        }

        case "UpdateRoomSettings":
        {
            echo("Updating settings for room ".$receivedJson['roomId']."\n");
            $gameCoordinator->updateGameSettings($receivedJson);
            break;
        }

        case "StartGame":
        {
            echo("Starting game ".$receivedJson['roomId']."\n");
            $gameCoordinator->startGame($receivedJson['roomId']);
            break;
        }

        case "LeaveGame":
        {
            echo("Player wants to leave game ".$receivedJson['roomId']." \n");
            $checkResult = $gameCoordinator->checkPlayerBeforeRemoving($receivedJson['username'],$receivedJson['roomId'],$receivedJson['steamId']);
            if($checkResult < 0)
            {
                echo("Unable to remove the specified player\n");
                break;
            }
            else
            {
                //If the player is the host...
                if($checkResult == 1)
                {
                    //...disconnect all players before deleting the game
                    $gameCoordinator->disconnectAllPlayers($receivedJson['roomId'],$connection,"HOSTLEFTGAME");

                    //Then delete the game.
                    $gameCoordinator->destroyGame($receivedJson['roomId']);
                }
                //If player is not the host, simply disconnect and remove just the player.
                else
                {
                    $gameCoordinator->disconnectPlayer($receivedJson['roomId'],$receivedJson['username'],$receivedJson['steamId'],$connection);
                }
            }
            break;
        }

        case "SubmitRun":
        {
            $gameId = $receivedJson['gameId'];
            echo("Player is submitting run in game ".$receivedJson['gameId']."\n");
            if($gameCoordinator->verifyRunSubmission($receivedJson))
            {
                $submitResult = $gameCoordinator->submitRun($receivedJson);
                if($submitResult >= 0)
                {
                    //Check if the claimed level resulted in a bingo.
                    $hasObtainedBingo = $gameCoordinator->currentGames[$gameId]->checkForBingo($receivedJson['team'],$receivedJson['row'],$receivedJson['column']);

                    $levelDisplayName = $gameCoordinator->currentGames[$gameId]->grid->levelTable[$receivedJson['row']."-".$receivedJson['column']]->levelName;

                    $claimBroadcast = new ClaimedLevelBroadcast($receivedJson['playerName'],$receivedJson['team'],$levelDisplayName,$submitResult,$receivedJson['row'],$receivedJson['column'],$receivedJson['time'],$receivedJson['style']);

                    foreach($gameCoordinator->currentGames[$gameId]->currentPlayers as $playerSteamId => &$playerObj)
                    {
                        $message = new EncapsulatedMessage("LevelClaimed",json_encode($claimBroadcast));
                        echo("Sending level claim msg to ".$playerObj->username."\n");
                        sendEncodedMessage($message,$playerObj->websocketConnection);
                    }
                    if($hasObtainedBingo)
                    {
                        $bingoSignal = new EndGameSignal($receivedJson['team']);
                        foreach($gameCoordinator->currentGames[$gameId]->currentPlayers as $playerSteamId => &$playerObj)
                        {
                            $message = new EncapsulatedMessage("GameEnd",json_encode($bingoSignal));
                            echo("Sending end game signal to ".$playerObj->username."\n");
                            sendEncodedMessage($message,$playerObj->websocketConnection);
                        }
                    }
                }
            }
            else
            {
                echo("Run submission was invalid - rejecting\n");
            }
            break;
        }

        default: {echo "Unknown message, discarding\n"; break;}
    }
}

function onClientConnect():void
{
    echo("New connection\n");
}

function onClientDisconnect($server,$connection):void
{
    echo("Client has disconnected\n");
}

function loadEnvFile():void
{
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo(Color::light_blue(). "Loading environment variables".Color::reset()."\n");
loadEnvFile();

echo(Color::light_blue() . "Loading level list".Color::reset()."\n");
require_once (__DIR__.'/levels.php');

echo(Color::light_blue() . "Initialising DB configuration".Color::reset()."\n");
require_once (__DIR__.'/DB.php');

echo(Color::light_blue() . "Starting up game coordinator".Color::reset()."\n");
require_once(__DIR__.'/game.php');

echo(Color::green() . "Starting webserver on port ".$PORT.Color::reset()."\n");

$server = new WebSocket\Server($PORT,false);
try {
    $server->addMiddleware(new WebSocket\Middleware\CloseHandler())
        ->addMiddleware(new WebSocket\Middleware\PingResponder())
        ->setMaxConnections($MAX_CONCURRENT_CONNECTIONS)
        ->setTimeout($TIMEOUT)
        ->onConnect(function () {
            onClientConnect();
        })
        ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Text $message) {
            onMessageRecieved($message->getContent(), $connection);
        })
        ->onPing(function ($client, $connection, $message) {
            $pong = new Pong();
            $em = new EncapsulatedMessage("Pong", json_encode($pong));
            sendEncodedMessage($em, $connection);
        })
        ->onError(function ($server, $connection, $exception) {
            if ($server <> null && $connection <> null && $exception <> null) {
                handleError($connection, $exception);
            }

        })
        ->onDisconnect(function (WebSocket\Server $server, WebSocket\Connection $connection) {
            onClientDisconnect($server, $connection);
        })
        ->start();
} catch (Throwable $e) {
    echo(Color::red() . "Server error!".Color::reset()."\n");
    echo($e->getMessage()."\n");
}


?>
