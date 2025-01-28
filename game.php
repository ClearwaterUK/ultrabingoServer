<?php

$MAX_TEAMS = 4;
$teamPointers = array(
    1 => "Red",
    2 => "Green",
    3 => "Blue",
    4 => "Yellow",
);

enum GameState: Int
{
    case inLobby = 0;
    case inGame = 1;
    case gameFinished = 2;
}

enum Team: string
{
    case NONE = "NONE";
    case RED = "Red";
    case YELLOW = "Yellow";
    case BLUE = "Blue";
    case GREEN = "Green";
}

//Represents a level in the Game grid of levels.
class GameLevel
{
    public string $levelName;
    public string $levelId;

    //Data relating to which team has claimed the level and requirements to beat it.
    public $claimedBy; //The team that currently claims the level. Can be any color or "None to signify not claimed yet.
    public string $personToBeat;
    public float $timeToBeat;
    public float $styleToBeat;

    //Coordinates in the GameGrid.
    public int $row;
    public int $column;

    //Relevant data if the level is an Angry custom level.
    public bool $isAngryLevel;
    public string $angryParentBundle; //GUID of the AngryBundleContainer needed to load this level.
    public string $angryLevelId;

    public function __construct($levelDisplayName,$levelId,$row,$column,$isAngryLevel,$angryParentBundle,$angryLevelId)
    {
        $this->levelName = $levelDisplayName;
        $this->levelId = $levelId;

        $this->claimedBy = Team::NONE;
        $this->personToBeat = "";
        $this->timeToBeat = 0;
        $this->styleToBeat = 0;

        $this->row = $row;
        $this->column = $column;

        $this->isAngryLevel = $isAngryLevel;
        $this->angryParentBundle = $angryParentBundle;
        $this->angryLevelId = $angryLevelId;
    }
}

//Represents the grid of levels selected to be played in a game.
class GameGrid
{
    public int $size; //n*n size of the grid.

    public array $levelTable; //Array containing the level id and the associated coordinates on the grid.

    public function populateGrid($mapPoolIds):void
    {
        global $mapPools;

        $levelPool = array();
        if(count($mapPoolIds) == 0)
        {
            return;
        }

        foreach($mapPoolIds as $id)
        {
            $levelPool = array_merge($levelPool,$mapPools[$id]);
        }

        for($x = 0; $x <= $this->size-1; $x++)
        {
            for($y = 0; $y <= $this->size-1; $y++)
            {

                //Pick a level from our level list, set it, and then remove to prevent duplicates
                $selectedIndex = array_rand($levelPool);
                $levelObj = $levelPool[$selectedIndex];

                $levelToInsert = new GameLevel($levelObj->levelDisplayName,$levelObj->sceneName,$x,$y,$levelObj->isAngryLevel,$levelObj->angryParentBundle,$levelObj->angryLevelId);

                //$levelToInsert = new GameLevel($rand,$x,$y);
                $this->levelTable[$x."-".$y] = $levelToInsert;
                unset($levelPool[$selectedIndex]);
            }
        }
    }

    public function __construct($size=3,$mapPoolIds="")
    {
        $this->size = $size;
        $this->populateGrid($mapPoolIds);
    }
}

//Represents a player currently connected to a game.
class GamePlayer
{
    public string $steamId;
    public string $username;
    public \WebSocket\Connection $websocketConnection;
    public $team;

    public function __construct($playerName,$playerSteamId,$playerConnection)
    {
        $this->username = $playerName;
        $this->websocketConnection = $playerConnection;
        $this->steamId = $playerSteamId;
    }
}

class GameSettings
{
    public int $maxPlayers;
    public int $maxTeams;
    public int $teamComposition;
    public int $gameType;
    public int $difficulty;
    public int $gridSize;
    public bool $requiresPRank;
    public bool $disableCampaignAltExits;
    public int $gameVisibility;

    public $selectedMapPools;

    public bool $hasManuallySetTeams;

    public $presetTeams;

    public function __construct()
    {
        $this->maxPlayers = 8;
        $this->maxTeams = 4;
        $this->teamComposition = 0;             //Randomised teams by default
        $this->gridSize = 0;                    //3x3 by default
        $this->gameType = 0;                    //Time by default
        $this->difficulty = 2;                  //Standard by default
        $this->requiresPRank = false;           //P-Rank not requried by default
        $this->disableCampaignAltExits = false; //Campaign alt exits not disabled by default
        $this->hasManuallySetTeams = false;
        $this->selectedMapPools = array();
    }
}

class Game
{
    public int $gameId; //Game ID, represented by a number.

    public array $currentPlayers; //List of players. Each player is represented via a <SteamID,GamePlayer> format.

    public $grid; //Our NxN bingo grid.

    public string $gameHost; //The player who is hosting the game. Represented by a string containing the SteamID of the host.

    public GameState $gameState; //Current state of the game, represented by GameState enum.

    public array $teams; // Array of type <string, array(GamePlayer)> denoting the teams for a Game.

    public GameSettings $gameSettings; //Settings for the game, represented by a GameSettings object.

    public string $firstMapClaimed;
    public string $lastMapClaimed;

    public int $numOfClaims;

    public $startTime;
    public $endTime;

    public string $bestStatMap;
    public string $bestStatValue;

    public function __construct($hostSteamName,$hostConnection,$gameId,$hostSteamId)
    {
        $this->currentPlayers = [];
        $this->grid = [];
        $this->gameId = $gameId;

        //When a game is created, create a GamePlayer representing the host and set them as gameHost.
        $host = new GamePlayer($hostSteamName,$hostSteamId,$hostConnection);
        $this->addPlayerToGame($host,$hostSteamId,true);

        //Set the default settings.
        $this->gameSettings = new GameSettings();

        //Pre-generate the grid of levels, pending a new dynamic generation on game start.
        $this->grid = new GameGrid(3,array());

        $this->gameState = GameState::inLobby;

        $this->numOfClaims = 0;
        $this->firstMapClaimed = "";
        $this->lastMapClaimed = "";

        $this->bestStatValue = 0;
        $this->bestStatMap = "";
    }

    //Adds a player to the current Game.
    // $player: A GamePlayer representing a player.
    // $isHost: Bool indicating if the $player being added is the host.
    public function addPlayerToGame(GamePlayer $player, string $playerSteamId, bool $isHost=false): void
    {
        logInfo("Adding ".$player->username. " (".$player->steamId.") to game ".$this->gameId);

        $this->currentPlayers[$playerSteamId] = $player;
        if($isHost)
        {
            $this->gameHost = $playerSteamId;
        }
    }

    //Removes a player from the specified game.
    //$playerSteamId: The SteamID of the player to remove.
    public function removePlayerFromGame($playerSteamId):void
    {
        unset($this->currentPlayers[$playerSteamId]);
    }

    public function putPlayerInTeam($player,$teamColor):void
    {
        global $teamPointers;

        array_push($this->teams[$teamPointers[$teamColor]],$player);
    }

    public function updateMapPool($mapPoolList)
    {
        $this->gameSettings->selectedMapPools = $mapPoolList;
    }

    public function updateTeams($teamDict):void
    {
        logInfo("Manually setting teams for room ".$this->gameId." and locking room");
        $this->gameSettings->presetTeams = $teamDict;
        $this->gameSettings->hasManuallySetTeams = true;

        updateRoomJoinPermission($this->gameId,0);

        $ud = new UpdateTeams(0);
        $em = new EncapsulatedMessage("UpdateTeamsNotif",json_encode($ud));

        foreach($this->currentPlayers as $playerSteamId => $playerObj)
        {
            sendEncodedMessage($em,$playerObj->websocketConnection);
        }
    }

    public function clearTeams():void
    {
        logInfo("Clearing set teams for room ".$this->gameId." and unlocking room");
        unset($this->gameSettings->presetTeams);
        $this->gameSettings->hasManuallySetTeams = false;
        updateRoomJoinPermission($this->gameId,1);

        $ud = new UpdateTeams(1);
        $em = new EncapsulatedMessage("UpdateTeamsNotif",json_encode($ud));

        foreach($this->currentPlayers as $playerSteamId => $playerObj)
        {
            sendEncodedMessage($em,$playerObj->websocketConnection);
        }
    }

    public function updateBestStatValue($statValue,$statMapName)
    {
        if($this->gameSettings->gameType == 0 && (($this->bestStatValue > 0 && $statValue < $this->bestStatValue) || $statValue < $this->bestStatValue))
        {
            $this->bestStatValue = $statValue;
            $this->bestStatMap = $statMapName;
        }
        else if ($this->gameSettings->gameType == 1 && $statValue > $this->bestStatValue)
        {
            $this->bestStatValue = $statValue;
            $this->bestStatMap = $statMapName;
        }
    }

    //Check if a team has obtained a bingo in the game grid.
    //$team: The team color to check.
    //$coordX: The row to check.
    //$coordY: The column to check.
    public function checkForBingo($team,$coordX,$coordY):bool
    {
        $horizontal = true;
        $vertical = true;
        $diagonalDown = true;
        $diagonalUp = true;
        $teamCol = Team::tryFrom($team);

        //Horizontal check
        for($x = 0; $x < $this->grid->size; $x++)
        {
            if($this->grid->levelTable[$x."-".$coordY]->claimedBy != $teamCol)
            {
                $horizontal = false;
                break;
            }
        }

        //Vertical check
        for($y = 0; $y < $this->grid->size; $y++)
        {
            if($this->grid->levelTable[$coordX."-".$y]->claimedBy != $teamCol)
            {
                $vertical = false;
                break;
            }
        }

        //Diagonal check down
        for($zDown = 0; $zDown < $this->grid->size; $zDown++)
        {
            if($this->grid->levelTable[$zDown."-".$zDown]->claimedBy != $teamCol)
            {
                $diagonalDown = false;
                break;
            }
        }
        //Diagonal check up
        $helper = 0;
        for($zUp = $this->grid->size-1; $zUp > -1; $zUp--)
        {
            if($this->grid->levelTable[$zUp."-".$helper]->claimedBy != $teamCol)
            {
                $diagonalUp = false;
                break;
            }
            $helper++;
        }

        if($horizontal || $vertical || $diagonalDown || $diagonalUp)
        {
            logWarn("BINGO!");
        }
        return($horizontal || $vertical || $diagonalUp || $diagonalDown);
    }

    public function setTeamsFromPreset($presetTeams):void
    {
        global $teamPointers;

        //Initialise team arrays
        foreach($teamPointers as $key => $val)
        {
            $this->teams[$val] = array();
        }

        //Loop through each SteamID, and put them in the associated team.
        foreach($presetTeams as $steamId => $team)
        {
            $plr = $this->currentPlayers[$steamId];

            $plr->team = $teamPointers[$team];
            $this->putPlayerInTeam($plr->username,$team);
        }

        logMessage("Set preset teams for game ".$this->gameId.":");
        var_export($this->teams);

    }

    //Split all Players connected to the Game into teams when the Game is started.
    public function setTeams():void
    {
        global $teamPointers;
        global $MAX_TEAMS;

        //Initialise team arrays
        foreach($teamPointers as $key => $val)
        {
            $this->teams[$val] = array();
        }

        //Randomise player order and set teams.
        $colorMarker = 1;
        $indexList = array_keys($this->currentPlayers);
        while(count($indexList) > 0)
        {
            $indice = array_rand($indexList);
            $plr = $this->currentPlayers[$indexList[$indice]];

            $plr->team = $teamPointers[$colorMarker];
            $this->putPlayerInTeam($plr->username,$colorMarker);
            if($colorMarker == $MAX_TEAMS) {$colorMarker == 1;}
            else{$colorMarker++;}

            unset($indexList[$indice]);
        }

        logInfo("Set teams for game ".$this->gameId.":");
        var_export($this->teams);
    }

    public function generateGrid($size=3):void
    {
        $this->grid = new GameGrid($size,$this->gameSettings->selectedMapPools);
    }
}

class GameController
{
    public array $currentGames; //A list of current game's that are ongoing. Each entry is represented by an id and an associated Game object.

    public function createGame(Int $gameId, string $hostSteamName,WebSocket\Connection $hostConnection, string $hostSteamId)
    {
        logInfo("Creating game with id ".$gameId.", host is ".$hostSteamName.", SteamID is ".$hostSteamId);
        $gameToCreate = new Game($hostSteamName,$hostConnection,$gameId,$hostSteamId);
        $this->currentGames[$gameId] = $gameToCreate;

        return $gameToCreate;
    }

    public function joinGame(int $gameId, string $playerName, string $steamId, WebSocket\Connection $playerConnection)
    {
        //Add the new player to the player list of the Game.
        $playerToAdd = new GamePlayer($playerName,$steamId,$playerConnection);
        $this->currentGames[$gameId]->addPlayerToGame($playerToAdd,$steamId);

        //Broadcast the new player joining to everyone else in the current Game.
        $message = new JoinRoomNotification($playerName,$steamId);
        $em = new EncapsulatedMessage("JoinRoomNotification",json_encode($message));

        //Send the message to the client first, then send it to everyone else.
        foreach($this->currentGames[$gameId]->currentPlayers as $playerSteamId => $playerObj)
        {
            if($steamId <> $playerSteamId)
            {
                sendEncodedMessage($em,$playerObj->websocketConnection);
            }
        }

    }

    public function kickPlayer($gameId,$playerToKick)
    {
        $game = $this->currentGames[$gameId];

        $nameToKick = $game->currentPlayers[$playerToKick]->username;
        $steamId = $game->currentPlayers[$playerToKick]->steamId;

        $kickNotification = new KickNotification($nameToKick,$steamId);
        $kickMessage = new KickMessage();

        $kick =  new EncapsulatedMessage("Kicked", json_encode($kickMessage));
        $kickNotif =  new EncapsulatedMessage("KickNotification", json_encode($kickNotification));

        if($game->gameHost == $playerToKick)
        {
            logError("Host is trying to kick themselves, preventing");
            return;
        }

        foreach($game->currentPlayers as $playerSteamId => $playerObj)
        {
            if($playerObj->steamId == $playerToKick)
            {
                //Remove the player from the game.
                sendEncodedMessage($kick,$playerObj->websocketConnection);
            }
            else
            {
                //Notify all other players of the player being kicked.
                sendEncodedMessage($kickNotif,$playerObj->websocketConnection);
            }
        }

        //Add the kicked player to the DB.
        addKickToDB($playerToKick,$gameId);

        unset($game->currentPlayers[$playerToKick]);
    }

    public function updateGameSettings($settings):void
    {
        $wereTeamsReset = false;
        if(array_key_exists($settings['roomId'],$this->currentGames))
        {
            $gameToUpdate = $this->currentGames[$settings['roomId']];

            $newSettings = new GameSettings();
            $newSettings->maxPlayers = $settings['maxPlayers'];
            $newSettings->maxTeams = $settings['maxTeams'];
            $newSettings->teamComposition = $settings['teamComposition'];
            $newSettings->gameType = $settings['gameType'];
            $newSettings->difficulty = $settings['difficulty'];
            $newSettings->gridSize = $settings['gridSize'];
            $newSettings->requiresPRank = $settings['PRankRequired'];
            $newSettings->disableCampaignAltExits = $settings['disableCampaignAltExits'];
            $newSettings->gameVisibility = $settings['gameVisibility'];
            $newSettings->selectedMapPools = $this->currentGames[$settings['roomId']]->gameSettings->selectedMapPools;
            $newSettings->hasManuallySetTeams = $this->currentGames[$settings['roomId']]->gameSettings->hasManuallySetTeams;
            $newSettings->presetTeams = $this->currentGames[$settings['roomId']]->gameSettings->presetTeams;

            if($newSettings->teamComposition == 0 && $this->currentGames[$settings['roomId']]->gameSettings->hasManuallySetTeams)
            {
                $newSettings->hasManuallySetTeams = false;
                $newSettings->presetTeams = null;
                updateRoomJoinPermission($settings['roomId'],1);
                $wereTeamsReset = true;
            }

            $this->currentGames[$settings['roomId']]->gameSettings = $newSettings;

            $run = new RoomUpdateNotification($settings['maxPlayers'],$settings['maxTeams'],$settings['teamComposition'],$settings['PRankRequired'],$settings['gameType'],$settings['difficulty'],$settings['gridSize'],$settings['disableCampaignAltExits'],$settings['gameVisibility'],$wereTeamsReset);
            $em = new EncapsulatedMessage("RoomUpdate",json_encode($run));

            updateGameSettings($settings['roomId'],$newSettings);

            foreach($gameToUpdate->currentPlayers as $playerSteamId => &$playerObj)
            {
                if($playerSteamId != $gameToUpdate->gameHost)
                {
                    sendEncodedMessage($em,$playerObj->websocketConnection);
                }
            }
        }
        else
        {
            logError("Trying to update settings for game id ".$settings['roomId']." but doesn't exist!");
        }
    }

    public function startGame(Int $gameId):void
    {
        if(array_key_exists($gameId,$this->currentGames))
        {
            $gameToStart = $this->currentGames[$gameId];

            if($gameToStart->gameSettings->hasManuallySetTeams)
            {
                $gameToStart->setTeamsFromPreset($gameToStart->gameSettings->presetTeams);
            }
            else
            {
                $gameToStart->setTeams();
            }


            $gameToStart->gameState = GameState::inGame;
            $gameToStart->generateGrid($gameToStart->gameSettings->gridSize+3);

            startGameInDB($gameId);

            //Mark the start time
            $gameToStart->startTime = new DateTime();
            logInfo("Game ".$gameToStart->gameId. " starting at " . $gameToStart->startTime->format("Y-m-d h:i:s A"));

            //Send the game start signal to all players in the game
            logInfo("Telling all players of game ".$gameToStart->gameId . " to start");
            foreach($gameToStart->currentPlayers as $playerSteamId => &$playerObj)
            {
                $startSignal = new StartGameSignal($gameToStart,$playerObj->team,$gameToStart->teams[$playerObj->team],$gameToStart->grid);

                $message = new EncapsulatedMessage("StartGame",json_encode($startSignal));
                sendEncodedMessage($message,$playerObj->websocketConnection);
            }
        }
        else
        {
            logError("Game with id ".$gameId." does not exist");
        }
    }

    public function checkPlayerBeforeRemoving(string $username, Int $gameId, string $steamId):int
    {
        if(array_key_exists($gameId,$this->currentGames))
        {
            $currentGame = $this->currentGames[$gameId];
            foreach($currentGame->currentPlayers as $playerSteamId => $playerObj) {
                if ($playerSteamId == $steamId) {
                    if($playerSteamId == $currentGame->gameHost)
                    {
                        logWarn("Player to remove is the host, deleting the whole game!");
                        return 1;
                    }
                    else
                    {
                        return 0;
                    }
                }
            }
            logError("Could not find the player to remove in specified game");
            return -1;
        }
        else
        {
            logError("Could not find the specified game");
            return -2;
        }
    }

    public function disconnectPlayer(Int $gameid, string $playername, string $steamId, $leavingConnection):void
    {
        $game = $this->currentGames[$gameid];
        $dcMessage = new DisconnectSignal();
        $dcMessage->disconnectCode = 1001;
        $dcMessage->disconnectMessage = "You have left the game.";

        $dcNotification = new DisconnectNotification();
        $dcNotification->username = $playername;
        $dcNotification->steamId = $steamId;

        if($game->gameSettings->hasManuallySetTeams)
        {
            $game->gameSettings->hasManuallySetTeams = false;
            unset($game->gameSettings->presetTeams);
            updateRoomJoinPermission($gameid,1);
        }

        $em = new EncapsulatedMessage("DisconnectNotification",json_encode($dcNotification));

        $indexToRemove = "";
        foreach($game->currentPlayers as $playerSteamId => $playerObj)
        {
            if($playerObj->username == $playername)
            {
                //Remove the player from the game.
                $indexToRemove = $playerSteamId;
            }
            else
            {
                //Notify all other players of the player leaving the game.
                if($playerObj->websocketConnection !== $leavingConnection)
                {
                    sendEncodedMessage($em,$playerObj->websocketConnection);
                }
            }
        }

        //Unregister the connection from the DB.
        unregisterConnection($dcNotification->steamId);

        unset($game->currentPlayers[$indexToRemove]);
    }
    
    public function disconnectAllPlayers(Int $gameid,$hostConnection,$disconnectReason="UNSPECIFIED"):void
    {
        $game = $this->currentGames[$gameid];
        $dcMessage = new DisconnectSignal();
        $dcMessage->disconnectCode = 1001;
        $dcMessage->disconnectMessage = $disconnectReason;

        $em = new EncapsulatedMessage("ServerDisconnection",json_encode($dcMessage));

        foreach($game->currentPlayers as $playerSteamId => $playerObj)
        {
            //Don't send dc message to the host as they've already DC'd before we clean up the game.
            if($playerObj->websocketConnection !== $hostConnection)
            {
                sendEncodedMessage($em,$playerObj->websocketConnection);
                $playerObj->websocketConnection->close(1000,$disconnectReason);
            }

            //Unregister the connection from the DB.
            unregisterConnection($playerSteamId);

            unset($game->currentPlayers[$playerSteamId]);
        }
    }

    public function destroyGame(Int $gameId):void
    {
        logWarn("Destroying game id ".$gameId . " from game coordinator");
        unset($this->currentGames[$gameId]);

        logWarn("Clearing kicks");
        clearKicks($gameId);

        logWarn("Removing entry from DB");
        removeGame($gameId);
    }

    public function verifyRunSubmission($submissionData): bool
    {
        //When receiving a rub submission request:
        // Check if the game id exists.
        $gameId = $submissionData['gameId'];
        if(array_key_exists($gameId,$this->currentGames))
        {
            $currentGame = $this->currentGames[$gameId];
            $submittedCoords = $submissionData['row']."-".$submissionData['column'];

            logInfo("Player is submitting at position ".$submittedCoords." which in our current card, level ID is ".$currentGame->grid->levelTable[$submittedCoords]->levelId);

            //Check that the submitted coords match.
            $levelInCard = $currentGame->grid->levelTable[$submittedCoords];
            if($levelInCard->levelId == $submissionData['levelId'])
            {
                logMessage("Level ID matches, pre-submission all validated");
                return true;
            }
            else
            {
                logWarn("Level ID doesn't match!");
                return false;
            }
        }
        else
        {
            logError("Room id " .$gameId. " was not found in list of current games");
        }
        return false;
    }

    public function isFirstMapClaimed(Game $game)
    {
        return $game->firstMapClaimed <> "";
    }



    /*
     * Returns 3 values:
     * -1: Submission did not beat the criteria
     * 0: Submission claimed an unclaimed map
     * 1: Submission improved an already claimed map
     * 2: Submission beat criteria
     */
    public function submitRun($submissionData):int
    {
        $submittedCoords = $submissionData['row']."-".$submissionData['column'];
        $gameId = $submissionData['gameId'];
        $currentGame = $this->currentGames[$gameId];

        //Get the level.
        $levelInCard = $currentGame->grid->levelTable[$submittedCoords];

        if($levelInCard->claimedBy == Team::NONE)
        {
            logInfo("Level is unclaimed, claiming for their team");
            $levelInCard->claimedBy = Team::tryFrom($submissionData['team']);
            $levelInCard->personToBeat = $submissionData['playerName'];
            $levelInCard->timeToBeat = $submissionData['time'];
            $levelInCard->styleToBeat = $submissionData['style'];

            $currentGame->numOfClaims++;
            if(!$this->isFirstMapClaimed($currentGame))
            {
                $currentGame->firstMapClaimed = $levelInCard->levelName;
            }

            $currentGame->lastMapClaimed = $levelInCard->levelName;

            $currentGame->updateBestStatValue(($currentGame->gameSettings->gameType == 0 ? $submissionData['time'] : $submissionData['style']),$levelInCard->levelName);

            return 0;
        }
        else
        {
            if(($currentGame->gameSettings->gameType == 0 && $submissionData['time'] < $levelInCard->timeToBeat) || ($currentGame->gameSettings->gameType == 1 && $submissionData['style'] > $levelInCard->styleToBeat))
            {
                //Same team/person
                if($levelInCard->claimedBy == Team::tryFrom($submissionData['team']))
                {
                    logInfo("Level already claimed by player/team, improving");
                    $levelInCard->personToBeat = $submissionData['playerName'];
                    $levelInCard->timeToBeat = $submissionData['time'];
                    $levelInCard->styleToBeat = $submissionData['style'];

                    $currentGame->numOfClaims++;
                    $currentGame->lastMapClaimed = $levelInCard->levelName;



                    return 1;
                }
                else
                {
                    logInfo("Reclaiming level from previous team");
                    $levelInCard->claimedBy = Team::tryFrom($submissionData['team']);
                    $levelInCard->personToBeat = $submissionData['playerName'];
                    $levelInCard->timeToBeat = $submissionData['time'];
                    $levelInCard->styleToBeat = $submissionData['style'];

                    $currentGame->numOfClaims++;
                    $currentGame->lastMapClaimed = $levelInCard->levelName;

                    return 2;
                }
            }
            else
            {
                logWarn("Run did not beat current criteria");
                return -1;
            }
        }
    }

    public function humiliatePlayer($gameId,$steamId)
    {
        $currentGame = $this->currentGames[$gameId];
        $playerToHumil = $currentGame->currentPlayers[$steamId]->username;
        foreach($currentGame->currentPlayers as $playerSteamId => $playerObj) {
            if ($playerSteamId != $steamId) {
                $message = new HumiliationMessage($playerToHumil);
                $em = new EncapsulatedMessage("CheatNotification",json_encode($message));
                sendEncodedMessage($em,$playerObj->websocketConnection);
            }
        }
    }

    public function __construct()
    {
        date_default_timezone_set("Europe/Paris");
        logMessage("Game coordinator started at: ".date("Y-m-d h:i:s A"));
        $this->currentGames = [];
    }
}

$gameCoordinator = new GameController();
?>