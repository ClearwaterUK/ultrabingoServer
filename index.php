<?php

require __DIR__ . '/vendor/autoload.php';

use Codedungeon\PHPCliColors\Color;

//Can't do 8080 on local because 8080 is reserved by Steam.
$PORT = 2052;

$MAX_CONCURRENT_CONNECTIONS = 64;
$TIMEOUT = 1440;

$connectionLog = array();

$steamIdToUsernameTable = array();

require_once('functions.php');

//Load all the NetworkMessage classes from the folder
$networkMessageFolder = glob('./NetworkMessages/*.php');
foreach($networkMessageFolder as $file)
{
    require_once $file;
}

function decodeMessage($message)
{
    return json_decode(base64_decode($message),true);
}

function sendEncodedMessage($messageToSend,$connection)
{
    $encodedMessage = base64_encode(json_encode($messageToSend));
    //echo("Sending base64 message:\n");
    //echo($encodedMessage."\n");
    $connection->text($encodedMessage);
}

function handleError(\WebSocket\Connection $connection,\WebSocket\Exception\Exception $exception)
{
    global $gameCoordinator;

    echo(Color::RED() . "Client was dropped - lost connection or was alt-tabbed for too long?" . Color::reset() . "\n");

    echo(Color::RED() . $exception->getMessage() . " (".$exception->getCode().")". Color::reset() . "\n");

    //Remove the dropped connection from the game that it was in.
    $gameDetails = getPlayerFromConnectionTable($connection);
    var_export($gameDetails);
    if($gameDetails != null)
    {
        //Go into the room id
        $associatedGame = $gameCoordinator->currentGames[$gameDetails[0]];
        $username = $gameDetails[1];

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
                echo("Sending timeout notice to ".$playerObj->username."\n");
                $timeoutNotif = new TimeoutNotification($username);
                $em = new EncapsulatedMessage("TimeoutNotification",json_encode($timeoutNotif));
                sendEncodedMessage($em,$playerObj->websocketConnection);
            }
        }
        unset($associatedGame->currentPlayers[$indexToUnset]);
    }
    dropFromConnectionTable($connection);
}

function onMessageRecieved($message,$connection)
{
    global $gameCoordinator;
    global $connectionLog;

    echo("Incoming message\n");

    $receivedJson = decodeMessage($message);
    $messageType = $receivedJson["messageType"];
    echo($messageType."\n");

    switch($messageType)
    {
        case "CreateRoom":
        {
            echo("Received request to create room\n");
            $roomId = createRoomInDatabase($receivedJson);
            $status = "";
            $game = "";
            if($roomId <> null && $roomId <> 0)
            {
                //Create the room
                echo("Room created in DB, got room id: ".$roomId."\n");
                $status = "ok";
                echo("Setting up game ".$roomId." in game coordinator\n");
                $game = $gameCoordinator->createGame($roomId,$receivedJson["hostSteamName"],$connection,$receivedJson["hostSteamId"]);
                echo("Game created\n");
                $crr = new CreateRoomResponse($status,$roomId,$game);
                $em = new EncapsulatedMessage("CreateRoomResponse",json_encode($crr));

                addToConnectionTable($connection,$roomId,$receivedJson["hostSteamName"]);
            }
            else{
                echo("Failed to create room for whatever reason\n");
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
            echo("Recieved request to join room id ".$receivedJson['roomId']."\n");

            $gameToJoin = $gameCoordinator->joinGame($receivedJson['roomId'],$receivedJson['username'],$receivedJson['steamId'],$connection);

            $status = (gettype($gameToJoin) == "integer") ? $gameToJoin : 0;
            $roomId = $receivedJson['roomId'];
            $room = ($status == 0) ? $gameCoordinator->currentGames[$receivedJson['roomId']] : null;

            $jrr = new JoinRoomResponse($status,$roomId,$room);
            $em = new EncapsulatedMessage("JoinRoomResponse",json_encode($jrr));
            sendEncodedMessage($em,$connection);

            if($status == 0)
            {
                echo("Adding connection to current connection log\n");
                addToConnectionTable($connection,$roomId,$receivedJson["username"]);
            }

            break;
        }

        case "UpdateRoomSettings":
        {
            echo("Recieved update room settings signal for room id ".$receivedJson['roomId']."\n");
            $gameCoordinator->updateGameSettings($receivedJson);
            break;
        }

        case "StartGame":
        {
            echo("Recieved request to start game id ".$receivedJson['roomId']."\n");
            $gameCoordinator->startGame($receivedJson['roomId']);
            break;
        }

        case "LeaveGame":
        {
            echo("Client wants to leave game id ".$receivedJson['roomId']." \n");
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
                    $gameCoordinator->disconnectAllPlayers($receivedJson['roomId']);

                    //Then delete the game.
                    $gameCoordinator->destroyGame($receivedJson['roomId']);
                }
                //If player is not the host, simply disconnect and remove just the player.
                else
                {
                    $gameCoordinator->disconnectPlayer($receivedJson['roomId'],$receivedJson['username'],$receivedJson['steamId']);
                }
            }
            break;
        }

        case "SubmitRun":
        {
            $gameId = $receivedJson['gameId'];
            echo("Recieved submission request for game id ".$receivedJson['gameId']."\n");
            if($gameCoordinator->verifyRunSubmission($receivedJson))
            {
                $submitResult = $gameCoordinator->submitRun($receivedJson);
                if($submitResult >= 0)
                {
                    //Check if the claimed level resulted in a bingo.
                    $hasObtainedBingo = $gameCoordinator->currentGames[$gameId]->checkForBingo($receivedJson['team'],$receivedJson['row'],$receivedJson['column']);

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
                    else
                    {
                        $claimBroadcast = new ClaimedLevelBroadcast($receivedJson['playerName'],$receivedJson['team'],$receivedJson['mapName'],$submitResult,$receivedJson['row'],$receivedJson['column']);

                        foreach($gameCoordinator->currentGames[$gameId]->currentPlayers as $playerSteamId => &$playerObj)
                        {
                            $message = new EncapsulatedMessage("LevelClaimed",json_encode($claimBroadcast));
                            echo("Sending level claim msg to ".$playerObj->username."\n");
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

function onClientConnect()
{
    echo("Incoming connection\n");
}

function onClientDisconnect()
{
    echo("Client has disconnected\n");
}

function loadEnvFile()
{
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo(Color::light_blue(). "Loading environment variables".Color::reset()."\n");
loadEnvFile();

echo(Color::light_blue() . "Loading level list".Color::reset()."\n");
require_once ('./levels.php');

echo(Color::light_blue() . "Initialising DB configuration".Color::reset()."\n");
require_once ('./DB.php');

echo(Color::light_blue() . "Starting up game coordinator".Color::reset()."\n");
require_once('./game.php');

echo(Color::green() . "Starting webserver on port ".$PORT.Color::reset()."\n");

$server = new WebSocket\Server($PORT,false);
$server->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ->setMaxConnections($MAX_CONCURRENT_CONNECTIONS)
    ->setTimeout($TIMEOUT)
    ->onConnect(function()
    {
        onClientConnect();
    })
    ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Text $message)
    {
        onMessageRecieved($message->getContent(),$connection);
    })
    ->onPing(function ($client, $connection, $message)
    {
        echo("Pong\n");
    })

    ->onError(function ($server, $connection, $exception)
    {
        if($server <> null && $connection <> null && $exception <> null)
        {
            handleError($connection,$exception);
        }

    })
    ->onDisconnect(function (WebSocket\Server $server, WebSocket\Connection $connection)
    {
        onClientDisconnect();
    })
    ->start();


?>