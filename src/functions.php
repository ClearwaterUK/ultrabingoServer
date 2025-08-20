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

    public $gamemode;
    public $roomMaxPlayers;
    public $pRankRequired;

    public function __construct($data)
    {
        $this->roomName = $data["roomName"];
        $this->roomPassword = $data["roomPassword"];
        $this->roomHostedBy = $data["hostSteamName"];
        $this->roomMaxPlayers = intval($data["maxPlayers"]);
        $this->gamemode = $data["gamemode"];
        $this->pRankRequired = $data["pRankRequired"];
    }
}

function createRoomInDatabase($roomData)
{
    global $dbc;

    $roomPassword = bin2hex(random_bytes(3));

    try {
        $request = $dbc->prepare('INSERT INTO currentGames(R_HOSTEDBY,R_PASSWORD,R_CURRENTPLAYERS,R_HASSTARTED,R_MAXPLAYERS,R_MAXTEAMS,R_TEAMCOMPOSITION,R_GAMEMODE,R_JOINABLE,R_GRIDSIZE,R_DIFFICULTY,R_PRANKREQUIRED,R_DISABLECAMPAIGNALTEXIT,R_HASENDED) VALUES (?,?,0,0,8,4,0,0,1,0,2,0,0,0)');
        $request->bindParam(1,$roomData['hostSteamId'],PDO::PARAM_STR);
        $request->bindParam(2,$roomPassword,PDO::PARAM_STR);
        $request->execute();

        $request2 = $dbc->prepare("SELECT R_ID,R_PASSWORD FROM currentGames ORDER BY R_ID DESC LIMIT 1");
        $request2->execute();

        return $request2->fetch();
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
        [   "AngryLevelLoader",
            "Baphomet's BINGO",
            "BetterWeaponHUDs",
            "Configgy",
            "Damage Style HUD",
            "EasyPZ",
            "HandPaint",
            "Healthbars",
            "JadeLib",
            "IntroSkip",
            "PluginConfigurator",
            "StyleEditor",
            "StyleToasts",
            "UnityExplorer",
            "USTManager"];

    $userUnauthorisedMods = array();

    foreach($modList as $mod)
    {
        if($mod == "UnityExplorer" && !($steamId == "76561198128998723"))
        {
            logWarn("Client has UnityExplorer but isn't dev!");
            array_push($userUnauthorisedMods,$mod);
        }
        else if(!in_array($mod,$whitelistedMods))
        {
            array_push($userUnauthorisedMods,$mod);
        }
    }

    if(count($userUnauthorisedMods) > 0) {logWarn("Client is using non-whitelisted mods: ");var_export($userUnauthorisedMods);}
    return $userUnauthorisedMods;
}

function lookForGame($roomPassword)
{
    global $dbc;

    $request = $dbc->prepare("SELECT R_ID,R_CURRENTPLAYERS,R_MAXPLAYERS,R_TEAMCOMPOSITION,R_JOINABLE,R_HASSTARTED, R_ALLOWREJOIN FROM currentGames WHERE R_PASSWORD = ?");
    $request->bindParam(1,$roomPassword,PDO::PARAM_STR);
    $request->execute();

    $res = $request->fetchAll();

    if(count($res) > 0)
    {
        return $res[0];
    }
    else
    {
        return 0;
    }
}

function checkJoinEligibility($game,$steamId,$ip)
{
    global $dbc;

    // Make sure the steamID wasn't already kicked from the game
    if(checkKick($game['R_ID'],$steamId)) {
        logWarn("This SteamID was kicked from this game!");
        return -6;
    }

    if(checkBan($steamId,$ip))
    {
        logWarn("This SteamID or IP address is banned");
        return -5;
    }

    if($game['R_CURRENTPLAYERS'] == $game['R_MAXPLAYERS'])
    {
        logWarn("Game is already full");
        return -4;
    }

    if($game['R_JOINABLE'] == 0)
    {
        logWarn("Game not accepting new players");
        return -3;
    }
    if($game['R_HASSTARTED'] == 1)
    {
        if($game['R_ALLOWREJOIN'] == 1)
        {
            logInfo("Player will be joining mid-game");
            return 1;
        }
        else
        {
            logWarn("Game has already started");
            return -2;
        }
    }

    return 0;
}

function removeGame($roomId)
{
    global $dbc;
    $request = $dbc->prepare("DELETE FROM currentGames WHERE R_ID = ?");
    $request->bindParam(1,$roomId,PDO::PARAM_INT);
    $request->execute();
}

function updateHostForGame($gameId,$newHostSteamID)
{
    global $dbc;

    $request = $dbc->prepare("UPDATE currentGames set R_HOSTEDBY = ? WHERE R_ID = ?");
    $request->bindParam(1,$newHostSteamID,PDO::PARAM_STR);
    $request->bindParam(2,$gameId,PDO::PARAM_INT);
    $request->execute();

    $request2 = $dbc->prepare("UPDATE activeconnections set C_ISHOST = CASE WHEN C_STEAMID = ? THEN 1 ELSE 0 END WHERE C_ROOMID = ?");
    $request2->bindParam(1,$newHostSteamID,PDO::PARAM_STR);
    $request2->bindParam(2,$gameId,PDO::PARAM_INT);

}

function startGameInDB(int $roomId)
{
    global $dbc;
    $request = $dbc->prepare("UPDATE currentGames 
    SET R_HASSTARTED = 1
    WHERE R_ID = ?");

    $request->bindParam(1, $roomId, PDO::PARAM_INT);
    $request->execute();

}

function getGameFromPassword($roomPassword)
{
    global $dbc;
    $request = $dbc->prepare("SELECT R_ID FROM currentGames where R_PASSWORD = ?");
    $request->bindParam(1,$roomPassword,PDO::PARAM_STR);
    $request->execute();

    $res = $request->fetch();
    if ($res && count($res) > 0)
    {
        return intval($res[0]);
    }
    else
    {
        return -1;
    }
}

function updateGameSettings(Int $roomId,GameSettings $newSettings)
{
    global $dbc;
    $request = $dbc->prepare("UPDATE currentGames 
    SET R_MAXPLAYERS = ?,
    R_MAXTEAMS = ?,
    R_TEAMCOMPOSITION = ?,
    R_GRIDSIZE = ?,
    R_GAMEMODE = ?,
    R_DIFFICULTY = ?,
    R_PRANKREQUIRED = ?,
    R_DISABLECAMPAIGNALTEXIT = ?,
    R_ISPUBLIC = ?,
    R_MODIFIER = ?,
    R_ALLOWREJOIN = ?
    WHERE R_ID = ?");

    $request->bindParam(1,$newSettings->maxPlayers,PDO::PARAM_INT);
    $request->bindParam(2,$newSettings->maxTeams,PDO::PARAM_INT);
    $request->bindParam(3,$newSettings->teamComposition,PDO::PARAM_INT);
    $request->bindParam(4,$newSettings->gridSize,PDO::PARAM_INT);
    $request->bindParam(5,$newSettings->gamemode,PDO::PARAM_INT);
    $request->bindParam(6,$newSettings->difficulty,PDO::PARAM_INT);
    $request->bindParam(7,$newSettings->requiresPRank,PDO::PARAM_BOOL);
    $request->bindParam(8,$newSettings->disableCampaignAltExits,PDO::PARAM_BOOL);
    $request->bindParam(9,$newSettings->gameVisibility,PDO::PARAM_INT);
    $request->bindParam(10,$newSettings->gameModifier,PDO::PARAM_INT);
    $request->bindParam(11,$newSettings->allowRejoin,PDO::PARAM_BOOL);
    $request->bindParam(12,$roomId,PDO::PARAM_INT);
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
    $steamUsername = sanitiseUsername($steamUsername);

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

function updateConnection($connection,$steamId)
{
    global $dbc;

    $connectionHash = md5(strval($connection));

    $request = $dbc->prepare("UPDATE activeConnections SET C_CONNECTION_HASH = ? WHERE C_STEAMID = ?");
    $request->bindParam(1,$connectionHash,PDO::PARAM_STR);
    $request->bindParam(2,$steamId,PDO::PARAM_STR);

    $request->execute();

}

function verifyConnection($steamTicket,$checkHost=false)
{
    global $dbc;
    $ticketRequest = $dbc->prepare("SELECT C_TICKET, C_STEAMID, C_ROOMID, C_ISHOST from activeConnections WHERE C_STEAMID = ? AND C_ROOMID = ?");
    $ticketRequest->bindParam(1,$steamTicket['steamId'],PDO::PARAM_STR);
    $ticketRequest->bindParam(2,$steamTicket['gameId']);
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

        $check = $ticketMatch && $gameMatch && $hostMatch;
        if($check) {return true;}
        else
        {
            logError("Connection invalid!");
            logError("Steam ticket match: ".$ticketMatch);
            logError("Game match: ".$gameMatch);
            if($checkHost){logWarn("Host match: ".$hostMatch);}
            return false;
        }
    }
    else
    {
        logError("Connection invalid, no result!");
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

function updatePlayerCount($gameId, $increment=1)
{
    global $dbc;

    $request = $dbc->prepare("UPDATE currentGames SET R_CURRENTPLAYERS = R_CURRENTPLAYERS + ? WHERE R_ID = ?");
    $request->bindParam(1,$increment,PDO::PARAM_INT);
    $request->bindParam(2,$gameId,PDO::PARAM_INT);

    $request->execute();
}

//Borrowed from SO: https://stackoverflow.com/questions/61481567/remove-emojis-from-string
function sanitiseUsername($inputUsername)
{
    $sanitisedUsername = "";
    // Match Emoticons
    $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
    $sanitisedUsername = preg_replace($regexEmoticons, '', $inputUsername);

    // Match Miscellaneous Symbols and Pictographs
    $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
    $sanitisedUsername = preg_replace($regexSymbols, '', $sanitisedUsername);

    // Match Transport And Map Symbols
    $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
    $sanitisedUsername = preg_replace($regexTransport, '', $sanitisedUsername);

    // Match flags (iOS)
    $regexTransport = '/[\x{1F1E0}-\x{1F1FF}]/u';
    $sanitisedUsername = preg_replace($regexTransport, '', $sanitisedUsername);

    return $sanitisedUsername;

}

function addKickToDB($steamId,$roomId)
{
    global $dbc;

    $request = $dbc->prepare("INSERT INTO kickedPlayers(K_STEAMID, K_ROOMID) VALUES (?,?)");

    $request->bindParam(1,$steamId,PDO::PARAM_STR);
    $request->bindParam(2,$roomId,PDO::PARAM_INT);

    $request->execute();
}

function clearKicks($roomId)
{
    global $dbc;

    $request = $dbc->prepare("DELETE FROM kickedPlayers WHERE K_ROOMID = ?");
    $request->bindParam(1,$roomId,PDO::PARAM_INT);

    $request->execute();
}

function checkBan($steamId,$ipAddress):bool
{
    global $dbc;

    $request = $dbc->prepare("SELECT B_STEAMID, B_IP FROM bannedPlayers WHERE B_STEAMID = ? OR B_IP = ?");
    $request->bindParam(1,$steamId,PDO::PARAM_STR);
    $request->bindParam(2,$ipAddress,PDO::PARAM_STR);

    $request->execute();
    $res = $request->fetchAll();

    return (count($res) > 0);
}

function checkKick($gameId, $steamId)
{
    global $dbc;
    $request = $dbc->prepare("SELECT K_ROOMID, K_STEAMID FROM kickedPlayers WHERE K_ROOMID = ? AND K_STEAMID = ?");
    $request->bindParam(1,$gameId,PDO::PARAM_INT);
    $request->bindParam(2,$steamId,PDO::PARAM_STR);

    $request->execute();
    $res = $request->fetchAll();

    return (count($res) > 0);
}

function checkPlayerCountOfGame($gameId)
{
    global $dbc;
    $request = $dbc->prepare("SELECT COUNT(*) FROM activeConnections WHERE C_ROOMID = ? AND C_ISHOST = 0");
    $request->bindParam(1,$gameId,PDO::PARAM_INT);

    $request->execute();
    $res = $request->fetch();

    return $res[0];
}

function markGameEnd($gameId)
{
    global $dbc;
    $request = $dbc->prepare("UPDATE currentGames SET R_HASENDED = 1 WHERE R_ID = ?");
    $request->bindParam(1,$gameId,PDO::PARAM_INT);

    $request->execute();
}

function getPublicBingoGames()
{
    global $dbc;

    $request = $dbc->prepare("select DISTINCT R_ID, R_CURRENTPLAYERS, R_MAXPLAYERS, R_DIFFICULTY, R_PASSWORD, C_USERNAME from currentGames 
    LEFT JOIN activeConnections ON currentGames.R_HOSTEDBY = activeConnections.C_STEAMID 
    WHERE currentGames.R_HASSTARTED = 0 AND currentGames.R_ISPUBLIC = 1 AND activeConnections.C_USERNAME IS NOT NULL AND activeConnections.C_ROOMID = currentGames.R_ID");
    $request->execute();

    $res = $request->fetchAll();

    return $res;
}

function fetchAvailableRanks($steamId)
{
    global $dbc;

    $request = $dbc->prepare("SELECT GROUP_CONCAT(R_RANKNAME) AS rankNames FROM ranks r LEFT JOIN userRanks ur ON r.R_ID = ur.U_RANKID WHERE ur.U_STEAMID = ?");
    $request->bindParam(1,$steamId,PDO::PARAM_STR);

    $request->execute();
    $res = $request->fetch();

    if($res['rankNames'] == null) {return "";}
    return $res['rankNames'];

}

function broadcastToAllPlayers(Game $game,$message,callable $callback = null)
{
    foreach($game->currentPlayers as $playerSteamId => &$playerObj)
    {
        if($callback == null)
        {
            sendEncodedMessage($message,$playerObj->websocketConnection);
        }
        else
        {
            if($callback)
            {
                sendEncodedMessage($message,$playerObj->websocketConnection);
            }
        }
    }
}

function buildNetworkMessage($header,$messageObj)
{
    return new EncapsulatedMessage($header,json_encode($messageObj));
}

function loadEnvFile($path):void
{
    $dotenv = Dotenv\Dotenv::createImmutable($path);
    $dotenv->load();
}

function verifyMessageSanity($message)
{
    //Strip special characters, spaces, etc from original message
    $strippedMessage = str_replace(' ','',$message);

    $badWordFilter = array(
        'gook',
        'fag',
        'faggot',
        'negro',
        'nig',
        'nigga',
        'nigger',
        'nig nog',
        'poof',
        'poofter'
    );

    return array_reduce($badWordFilter, fn($a, $n) => $a || str_contains($strippedMessage, $n), false) == false;

}

function hasChatAccess($steamId)
{
    global $dbc;

    $request = $dbc->prepare("SELECT B_WARNLEVEL FROM chatBlock WHERE B_STEAMID = ?");
    $request->bindParam(1,$steamId,PDO::PARAM_STR);

    $request->execute();
    $res = $request->fetch();

    if($res == false || $res['B_WARNLEVEL'] == null) {return true;}
    return $res['B_WARNLEVEL'] != 2;
}

function getWarnLevel($steamId)
{
    global $dbc;

    $request = $dbc->prepare("SELECT B_WARNLEVEL FROM chatBlock WHERE B_STEAMID = ?");
    $request->bindParam(1,$steamId,PDO::PARAM_STR);

    $request->execute();
    $res = $request->fetch();

    if($res == false) {return -1;}
    return $res['B_WARNLEVEL'];
}

function setWarnLevel($steamId,$newWarnLevel)
{
    global $dbc;

    if($newWarnLevel == 0)
    {
        $request = $dbc->prepare("INSERT INTO chatBlock (B_STEAMID, B_WARNLEVEL) VALUES (?,?)");
        $request->bindParam(1,$steamId,PDO::PARAM_STR);
        $request->bindParam(2,$newWarnLevel,PDO::PARAM_INT);
    }

    else
    {
        $request = $dbc->prepare("UPDATE chatBlock SET B_WARNLEVEL = ? WHERE B_STEAMID = ?");
        $request->bindParam(1,$newWarnLevel,PDO::PARAM_INT);
        $request->bindParam(2,$steamId,PDO::PARAM_STR);

    }

    $request->execute();
}

function getMapPools()
{
    global $dbc;

    $request = $dbc->prepare("SELECT mp.MP_ID AS MapPoolId, mp.MP_NAME AS MapPoolName, mp.MP_DESCRIPTION AS MapPoolDescription, COUNT(l.L_MPID) AS MapPoolLevelCount FROM levels l LEFT JOIN mappools mp ON l.L_MPID = mp.MP_ID GROUP BY l.L_MPID;");
    $request->execute();

    return $request->fetchAll();
}

function fetchSelectedMapData($mapIds)
{
    global $CATALOG;

    $levelData = array();

    foreach($mapIds as $id)
    {
        //Campaign
        if(str_contains($id,'Level '))
        {
            $levelData[$id] = array($id,$id,false,"");
        }
        //Custom level
        else
        {
            $data = $CATALOG->levelInfo[$id];
            $levelData[$id] = array($data[0],$data[1],true,$data[3]);
        }
    }
    return $levelData;
}

?>