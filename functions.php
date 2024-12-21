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
        $request = $dbc->prepare('INSERT INTO currentGames(R_HOSTEDBY,R_CURRENTPLAYERS,R_HASSTARTED,R_MAXPLAYERS,R_MAXTEAMS,R_TEAMCOMPOSITION,R_JOINABLE,R_GRIDSIZE,R_GAMETYPE,R_DIFFICULTY,R_PRANKREQUIRED,R_DISABLECAMPAIGNALTEXIT) VALUES (?,1,0,8,4,0,0,0,0,2,0,0)');
        $request->bindParam(1,$roomData['hostSteamId'],PDO::PARAM_STR);
        $request->execute();

        $request2 = $dbc->prepare("SELECT R_ID FROM currentGames ORDER BY R_ID DESC LIMIT 1");
        $request2->execute();

        return intval($request2->fetch()["R_ID"]);
    }
    catch(Exception $e)
    {
        logError($e->getMessage());
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
    R_PRANKREQUIRED = ?,
    R_DISABLECAMPAIGNALTEXIT = ?
    WHERE R_ID = ?");

    $request->bindParam(1,$newSettings->maxPlayers,PDO::PARAM_INT);
    $request->bindParam(2,$newSettings->maxTeams,PDO::PARAM_INT);
    $request->bindParam(3,$newSettings->teamComposition,PDO::PARAM_INT);
    $request->bindParam(4,$newSettings->gridSize,PDO::PARAM_INT);
    $request->bindParam(5,$newSettings->gameType,PDO::PARAM_INT);
    $request->bindParam(6,$newSettings->difficulty,PDO::PARAM_INT);
    $request->bindParam(7,$newSettings->requiresPRank,PDO::PARAM_BOOL);
    $request->bindParam(8,$newSettings->disableCampaignAltExits,PDO::PARAM_BOOL);
    $request->bindParam(9,$roomId,PDO::PARAM_INT);
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


function getPlayerFromConnectionTable($connectionHash)
{
    global $dbc;

    $request = $dbc->prepare("SELECT C_ROOMID, C_USERNAME,C_STEAMID FROM activeConnections WHERE C_CONNECTION_HASH = ?");
    $request->bindParam(1,$connectionHash,PDO::PARAM_STR);

    $request->execute();
    $res = $request->fetch();

    if($res && count($res) > 0)
    {
        return array($res[0],$res[1],$res[2]);
    }
    else
    {
        logWarn("Connection was not registered?");
        return null;
    }
}


function registerConnection($connection,$steamTicket,$steamId,$steamUsername,$roomId)
{
    global $dbc;

    $connectionHash = md5(strval($connection));
    $ticketHash = password_hash($steamTicket,PASSWORD_BCRYPT);

    $isHostReq = $dbc->prepare("SELECT R_ID, R_HOSTEDBY from currentGames where R_ID = ?");
    $isHostReq->bindParam(1,$roomId,PDO::PARAM_INT);
    $isHostReq->execute();
    $res = $isHostReq->fetch();
    $isHost = ($res[1] == $steamId);

    $request = $dbc->prepare("INSERT INTO activeConnections
    (C_CONNECTION_HASH,C_TICKET, C_STEAMID,C_USERNAME,C_ROOMID,C_ISHOST) VALUES (?,?,?,?,?,?)");

    $request->bindParam(1,$connectionHash,PDO::PARAM_STR);
    $request->bindParam(2,$ticketHash,PDO::PARAM_STR);
    $request->bindParam(3,$steamId,PDO::PARAM_STR);
    $request->bindParam(4,$steamUsername,PDO::PARAM_STR);
    $request->bindParam(5,$roomId,PDO::PARAM_INT);
    $request->bindParam(6,$isHost,PDO::PARAM_BOOL);

    $request->execute();
}

function verifyConnection($steamTicket,$checkHost=false)
{
    global $dbc;
    $ticketRequest = $dbc->prepare("SELECT C_TICKET, C_STEAMID, C_ROOMID, C_ISHOST from activeconnections WHERE C_STEAMID = ?");
    $ticketRequest->bindParam(1,$steamTicket['steamId'],PDO::PARAM_STR);
    $ticketRequest->execute();
    $res = $ticketRequest->fetch();
    if($res && count($res) > 0)
    {
        //Verify ticket hash matches what's in DB.
        $ticketMatch = password_verify($steamTicket['steamTicket'],$res[0]);
        //Verify given steamID is in the game id that it's sending messages to.
        $gameMatch = $steamTicket['gameId'] == $res[2];
        //If requested, verify if the steamID is the host of the game we're sending messages to.
        $hostMatch = ($checkHost ? ($res[3] == 1) : true);

        return ($ticketMatch && $gameMatch && $hostMatch);

    }
    else
    {
        return false;
    }

    return true;
}

function unregisterConnection($steamId)
{
    global $dbc;

    $request = $dbc->prepare("DELETE FROM activeConnections WHERE C_STEAMID = ?");
    $request->bindParam(1,$steamId,PDO::PARAM_STR);
    $request->execute();
}

function clearTables()
{
    global $dbc;
    $tables = ["currentGames","activeConnections"];
    foreach($tables as $table)
    {
        $request = $dbc->prepare("TRUNCATE ".$table);
        $request->execute();
    }
}

?>