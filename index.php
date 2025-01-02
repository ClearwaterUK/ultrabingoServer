<?php

require __DIR__ . '/vendor/autoload.php';

//Error reporting level
error_reporting(E_ALL);
ini_set('display_errors', 1);

//Can't do 8080 on local because 8080 is reserved by Steam.
$PORT = 2052;

$MAX_CONCURRENT_CONNECTIONS = 512;
$TIMEOUT = 90;

$CLIENT_VERSION = '1.0.0';

$connectionLog = array();
$steamIdToUsernameTable = array();

require_once('functions.php');

//Load all the NetworkMessage classes from the folder
$networkMessageFolder = glob(__DIR__.'/NetworkMessages/*.php');
foreach($networkMessageFolder as $file)
{
    require_once $file;
}

function decodeMessage($message)
{
    return json_decode(base64_decode($message),true);
}

function sendEncodedMessage($messageToSend,$connection):void
{
    $encodedMessage = base64_encode(json_encode($messageToSend));
    $connection->text($encodedMessage);
}

function loadEnvFile():void
{
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

require_once (__DIR__.'/logging.php');

logInfo("Loading environment variables");
loadEnvFile();

logInfo("Loading level list");
require_once (__DIR__.'/levels.php');

logInfo("Initialising DB configuration");
require_once (__DIR__.'/DB.php');
clearTables();

logInfo("Starting up game coordinator");
require_once(__DIR__.'/game.php');

logInfo("Setting up WebSocket router");
require_once (__DIR__.'/websocketRouter.php');

logMessage("Starting webserver on port ".$PORT);
$server = new WebSocket\Server($PORT,false);
try {
    $server->addMiddleware(new WebSocket\Middleware\CloseHandler())
        ->addMiddleware(new WebSocket\Middleware\PingResponder())
        ->setMaxConnections($MAX_CONCURRENT_CONNECTIONS)
        ->setTimeout($TIMEOUT)
        ->setFrameSize(2048)
        ->onHandshake(function($client,$connection,$request)
        {
            if($client <> null) {onClientConnect($client);}
        })
        ->onText(function (WebSocket\Server $server, WebSocket\Connection $connection, WebSocket\Message\Text $message) {
            if($message <> null && $connection <> null)
            {
                onMessageRecieved($message->getContent(), $connection);
            }
        })
        ->onPing(function ($client, $connection, $message) {
            if ($client <> null && $connection <> null && $message <> null) {
                $pong = new Pong();
                $em = new EncapsulatedMessage("Pong", json_encode($pong));
                sendEncodedMessage($em, $connection);
            }
        })
        ->onError(function ($server, $connection, $exception) {
            if ($server <> null && $connection <> null && $exception <> null) {
                handleError($connection, $exception);
            }
        })
        ->onDisconnect(function (WebSocket\Server $server, WebSocket\Connection $connection) {
            onClientDisconnect($server, $connection);
        });

    $server->setLogger(new BingoLogger());
    $server->start();
} catch (Throwable $e) {
    logError("Server error!");
    logError($e->getMessage()."(".$e->getFile().", ".$e->getLine().")");
}

?>
