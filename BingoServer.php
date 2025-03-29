<?php

require __DIR__ . '/vendor/autoload.php';

//Error reporting level
error_reporting(E_ALL);
ini_set('display_errors', 1);

$MAX_CONCURRENT_CONNECTIONS = 512;
$TIMEOUT = 90;
$CLIENT_VERSION = '1.0.6';
$VOTE_TIMER = 25;
$DOMINATION_TIME_MINUTES = 30;

$connectionLog = array();
$steamIdToUsernameTable = array();

// Structure: gameId -> EvTimer
$voteTimers = array();

require_once(__DIR__.'/src/functions.php');
require_once (__DIR__.'/src/logging.php');

logInfo("Loading environment variables");
loadEnvFile(__DIR__);
$PORT = $_ENV['SERVER_PORT'];

//Load all the NetworkMessage classes from the folder
$networkMessageFolder = glob(__DIR__.'/NetworkMessages/*.php');
foreach($networkMessageFolder as $file) { require_once $file;}

logInfo("Loading level list");
require_once (__DIR__.'/src/levels.php');

logInfo("Initialising DB configuration");
require_once (__DIR__.'/src/DB.php');
if(!isset($_ENV['PERSIST_TABLES']) || $_ENV['PERSIST_TABLES'] == 0) {logWarn("Clearing tables");clearTables();}

logInfo("Starting up game coordinator");
require_once(__DIR__.'/src/game.php');
require_once (__DIR__.'/src/Gamemode.php');

logInfo("Setting up WebSocket router");
require_once (__DIR__.'/src/websocketRouter.php');



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
        ->onTick(function ($client) {
            Ev::run(EV::RUN_NOWAIT);
        })
        ->onPing(function ($client, $connection, $message) {
            if ($client <> null && $connection <> null && $message <> null) {
                $message = buildNetworkMessage("Pong",new Pong());
                sendEncodedMessage($message, $connection);
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

    //$server->setLogger(new BingoLogger());
    $server->start();
} catch (Throwable $e) {
    logError("Server error!");
    logError($e->getMessage()."(".$e->getFile().", ".$e->getLine().")");
}

?>
