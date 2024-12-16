<?php

// Room information stored in the database:
// The id of the room
// The room name
// The room password
// The host (name)
// The max, and current, amount of players
// The amount of teams
// If P-rank is required or not
// If the game has already started or not
class RoomDataDB
{
    public $roomName;
    public $roomPassword;
    public $roomHostedBy;

    public $gameType;
    public $roomMaxPlayers;
    public $pRankRequired;

    public function __construct($data)
    {
        $this->roomName = $data["roomName"];
        $this->roomPassword = $data["roomPassword"];
        $this->roomHostedBy = $data["hostSteamName"];
        $this->roomMaxPlayers = intval($data["maxPlayers"]);
        $this->gameType = $data["gameType"];
        $this->pRankRequired = $data["pRankRequired"];
    }
}

function createRoomInDatabase($roomData)
{
    global $dbc;
    try {
        $data = new RoomDataDB($roomData);

        $testPass = "testPassword";
        $testPlayer = "testPlayer";

        $request = $dbc->prepare('INSERT INTO currentGames(R_PASSWORD,R_HOSTEDBY,R_CURRENTPLAYERS,R_HASSTARTED,R_MAXPLAYERS,R_MAXTEAMS,R_TEAMCOMPOSITION,R_JOINABLE,R_GRIDSIZE,R_GAMETYPE,R_DIFFICULTY,R_PRANKREQUIRED) VALUES (?,?,1,0,8,4,0,0,0,0,2,0)');
        $request->bindParam(1,$testPass,PDO::PARAM_STR);
        $request->bindParam(2,$testPlayer,PDO::PARAM_STR);
        $request->execute();

        $request2 = $dbc->prepare("SELECT R_ID FROM currentGames ORDER BY R_ID DESC LIMIT 1");
        $request2->execute();

        return intval($request2->fetch()["R_ID"]);
    }
    catch(Exception $e)
    {
        echo($e->getMessage());
        return 0;
    }
}

function verifyModList($modList,$steamId)
{
    $whitelistedMods =
        ["Baphomet's BINGO","AngryLevelLoader","PluginConfigurator","UnityExplorer"];

    logMessage($steamId);
    foreach($modList as $mod)
    {
        logWarn($mod);
        if($mod == "UnityExplorer" && !($steamId == "76561198128998723"))
        {
            logWarn("Client has UnityExplorer but isn't dev!");
            return false;
        }
        else if(!in_array($mod,$whitelistedMods))
        {
            logWarn("Client is using non-whitelisted mod: ".$mod);
            return false;
        }
    }
    logMessage("Mod list check ok");
    return true;

}


//TODO: Move this to DB.php
function lookForGame($roomId)
{
    global $dbc;

    $request = $dbc->prepare("SELECT R_ID,R_CURRENTPLAYERS,R_MAXPLAYERS,R_TEAMCOMPOSITION,R_JOINABLE,R_HASSTARTED FROM currentGames WHERE R_ID = ?");
    $request->bindParam(1,$roomId,PDO::PARAM_INT);
    $request->execute();

    $res = $request->fetchAll();

    if(count($res) > 0)
    {
        return $res[0];
    }
    else
    {
        return null;
    }
}

function removeGame($roomId)
{
    global $dbc;
    $request = $dbc->prepare("DELETE FROM currentGames WHERE R_ID = ?");
    $request->bindParam(1,$roomId,PDO::PARAM_INT);
    $request->execute();
}


function startGameInDB(int $roomId)
{
    global $dbc;
    $request = $dbc->prepare("UPDATE currentGames 
    SET R_HASSTARTED = 1
    WHERE R_ID = ?");

    $request->bindParam(1,$roomId,PDO::PARAM_INT);
    $request->execute();
}

function updateGameSettings(Int $roomId,GameSettings $newSettings)
{
    global $dbc;
    $request = $dbc->prepare("UPDATE currentGames 
    SET R_MAXPLAYERS = ?,
    R_MAXTEAMS = ?,
    R_TEAMCOMPOSITION = ?,
    R_GRIDSIZE = ?,
    R_GAMETYPE = ?,
    R_DIFFICULTY = ?,
    R_PRANKREQUIRED = ?
    WHERE R_ID = ?");

    $request->bindParam(1,$newSettings->maxPlayers,PDO::PARAM_INT);
    $request->bindParam(2,$newSettings->maxTeams,PDO::PARAM_INT);
    $request->bindParam(3,$newSettings->teamComposition,PDO::PARAM_INT);
    $request->bindParam(4,$newSettings->gridSize,PDO::PARAM_INT);
    $request->bindParam(5,$newSettings->gameType,PDO::PARAM_INT);
    $request->bindParam(6,$newSettings->difficulty,PDO::PARAM_INT);
    $request->bindParam(7,$newSettings->requiresPRank,PDO::PARAM_BOOL);
    $request->bindParam(8,$roomId,PDO::PARAM_INT);
    $request->execute();
}

function updateRoomJoinPermission(int $roomId, int $joinable)
{
    global $dbc;
    $request = $dbc->prepare("UPDATE currentGames 
    SET R_JOINABLE = ?
    WHERE R_ID = ?");

    $request->bindParam(1,$joinable,PDO::PARAM_INT);
    $request->bindParam(2,$roomId,PDO::PARAM_INT);
    $request->execute();
}

function addToConnectionTable($connection, $roomId,$username="defaultUser")
{
    global $connectionLog;

    $connectionHash = spl_object_hash($connection);
    if(isset($connectionLog[$connectionHash]))
    {
        logWarn("Associated SteamID already exists in our log, overwriting");
    }

    $connectionLog[$connectionHash] = array($roomId,$username);

    logWarn("Concurrent connections is now: ".count($connectionLog));
    print_r($connectionLog);
}

function dropFromConnectionTable($connection)
{
    global $connectionLog;

    $connectionHash = spl_object_hash($connection);

    if(!isset($connectionLog[$connectionHash]))
    {
        logWarn("Associated SteamID doesn't exist in our log...");
    }

    unset($connectionLog[$connectionHash]);
    logWarn("Concurrent connections is now: ".count($connectionLog));
    print_r($connectionLog);
}

function getPlayerFromConnectionTable($connection)
{
    global $connectionLog;

    $connectionHash = spl_object_hash($connection);
    if(isset($connectionLog[$connectionHash]))
    {
       return $connectionLog[$connectionHash];
    }
    else
    {
        logWarn("Connection was not registered?");
        return null;
    }
}

function addToUsernameLookupTable($steamId,$username)
{
    global $steamIdToUsernameTable;

    if(isset($steamIdToUsernameTable[$steamId]))
    {
        logWarn("Associated SteamID already exists in our log, overwriting");
    }

    $steamIdToUsernameTable[$steamId] = $username;
}

function dropFromUsernameLookupTable($steamId)
{
    global $steamIdToUsernameTable;

    if(!isset($steamIdToUsernameTable[$steamId]))
    {
        logWarn("Associated SteamID doesn't exist in our log...");
    }

    unset($steamIdToUsernameTable[$steamId]);
}

?>