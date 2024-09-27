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

// Eventually: If the game uses custom levels via Angry or any other level loader

class RoomDataDB
{
    public $roomName;
    public $roomPassword;
    public $roomHostedBy;

    public $gameType;
    public $roomMaxPlayers;
    //public $numTeams;
    public $pRankRequired;

    public function __construct($data)
    {
        $this->roomName = $data["roomName"];
        $this->roomPassword = $data["roomPassword"];
        $this->roomHostedBy = $data["hostSteamName"];
        $this->roomMaxPlayers = intval($data["maxPlayers"]);
        $this->gameType = $data["gameType"];
        //$this->numTeams = $data["roomName"];
        $this->pRankRequired = $data["pRankRequired"];
    }
}

function createRoomInDatabase($roomData)
{
    global $dbc;
    try {
        $data = new RoomDataDB($roomData);

        $request = $dbc->prepare("INSERT INTO currentGames(R_NAME,R_PASSWORD,R_HOSTEDBY,R_GAMETYPE,R_MAXPLAYERS,R_CURRENTPLAYERS,R_NUMTEAMS,R_PRANKREQUIRED,R_HASSTARTED) VALUES (?,?,?,?,?,1,2,?,0)");
        $request->bindParam(1,$data->roomName,PDO::PARAM_STR);
        $request->bindParam(2,$data->roomPassword,PDO::PARAM_STR);
        $request->bindParam(3,$data->roomHostedBy,PDO::PARAM_STR);
        $request->bindParam(4,$data->gameType,PDO::PARAM_INT);
        $request->bindParam(5,$data->roomMaxPlayers,PDO::PARAM_INT);
        $request->bindParam(6,$data->pRankRequired,PDO::PARAM_BOOL);
        $request->execute();

        $request2 = $dbc->prepare("SELECT R_ID FROM currentGames WHERE R_NAME = ? ORDER BY R_ID DESC LIMIT 1");
        $request2->bindParam(1,$data->roomName);
        $request2->execute();

        return intval($request2->fetch()["R_ID"]);
    }
    catch(Exception $e)
    {
        echo($e->getMessage());
        return 0;
    }

}

//TODO: Move this to DB.php
function lookForGame($roomId)
{
    global $dbc;

    $request = $dbc->prepare("SELECT * FROM currentGames WHERE R_ID = ?");
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

function addToConnectionTable($connection, $roomId,$username="defaultUser")
{
    global $connectionLog;

    $connectionHash = spl_object_hash($connection);
    if(isset($connectionLog[$connectionHash]))
    {
        echo(\Codedungeon\PHPCliColors\Color::yellow() . "Connection already exists in our log, overwriting\n");
    }

    $connectionLog[$connectionHash] = array($roomId,$username);
}

function dropFromConnectionTable($connection)
{
    global $connectionLog;

    $connectionHash = spl_object_hash($connection);
    echo("Given connection hash: ".$connectionHash);
    print_r($connectionLog);

    if(!isset($connectionLog[$connectionHash]))
    {
        echo(\Codedungeon\PHPCliColors\Color::yellow() . "Connection doesn't exist in our log...\n");
    }

    unset($connectionLog[$connectionHash]);
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
        echo(\Codedungeon\PHPCliColors\Color::yellow() . "Connection was not registered?\n");
        return null;
    }
}

?>