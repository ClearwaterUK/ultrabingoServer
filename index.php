<?php
//Can't do 8080 on local because 8080 is reserved by Steam.
$PORT = 2052;

/*
 * Check Kanri for todo list
 *
 */

require __DIR__ . '/vendor/autoload.php';

require_once('functions.php');

//Load all the NetworkMessage classes from the folder
$networkMessageFolder = glob('./NetworkMessages/*.php');
foreach($networkMessageFolder as $file)
{
    echo("Including: ".$file."\n");
    require_once $file;
}

function decodeMessage($message)
{
    return json_decode(base64_decode($message),true);
}

function sendEncodedMessage($messageToSend,$connection)
{
    $encodedMessage = base64_encode(json_encode($messageToSend));
    echo("Sending base64 message:\n");
    echo($encodedMessage."\n");
    $connection->text($encodedMessage);
}

function onMessageRecieved($message,$connection)
{
    global $gameCoordinator;

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
                $game = $gameCoordinator->createGame($roomId,$receivedJson["hostSteamName"],$connection);
                var_export($game);
                $crr = new CreateRoomResponse($status,$roomId,$game);
                $em = new EncapsulatedMessage("CreateRoomResponse",json_encode($crr));
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
            echo("Recieved request to join room id ".$receivedJson['roomId']);

            $gameToJoin = $gameCoordinator->joinGame($receivedJson['roomId'],$receivedJson['username'],$connection);

            $status = (gettype($gameToJoin) == "integer") ? $gameToJoin : 0;
            $roomId = $receivedJson['roomId'];
            $room = ($status == 0) ? $gameCoordinator->currentGames[$receivedJson['roomId']] : null;

            $jrr = new JoinRoomResponse($status,$roomId,$room);
            $em = new EncapsulatedMessage("JoinRoomResponse",json_encode($jrr));

            sendEncodedMessage($em,$connection);

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
            $checkResult = $gameCoordinator->checkPlayerBeforeRemoving($receivedJson['username'],$receivedJson['roomId']);
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
                    $gameCoordinator->disconnectPlayer($receivedJson['roomId'],$receivedJson['username']);
                }
            }

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
                    $claimBroadcast = new ClaimedLevelBroadcast($receivedJson['playerName'],$receivedJson['team'],$receivedJson['mapName'],$submitResult,$receivedJson['row'],$receivedJson['column']);

                    foreach($gameCoordinator->currentGames[$gameId]->currentPlayers as &$player)
                    {
                        $message = new EncapsulatedMessage("LevelClaimed",json_encode($claimBroadcast));
                        echo("Sending level claim msg to ".$player->username."\n");
                        sendEncodedMessage($message,$player->websocketConnection);
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

echo("Loading environment variables\n");
loadEnvFile();

echo("Loading level list\n");
require_once ('./levels.php');

echo("Initialising DB configuration\n");
require_once ('./DB.php');

echo("Starting up game coordinator\n");
require_once('./game.php');

echo("Starting webserver on port ".$PORT."\n");

$server = new WebSocket\Server($PORT,false);
$server->addMiddleware(new WebSocket\Middleware\CloseHandler())
    ->addMiddleware(new WebSocket\Middleware\PingResponder())
    ->setMaxConnections(8)
    ->setTimeout(10)
    ->onConnect(function()
    {
        onClientConnect();
    })
    ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Text $message)
    {
        onMessageRecieved($message->getContent(),$connection);
    })
    ->onDisconnect(function (WebSocket\Server $server, WebSocket\Connection $connection)
    {
        onClientDisconnect();
    })
    ->start();


?>