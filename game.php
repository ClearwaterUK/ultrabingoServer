<?php

$MAX_TEAMS = 4;
$teamPointers = array(
    1 => "Green",
    2 => "Red",
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
    public int $size; //We'll only do 3x3 for now for testing, but will have to bump up to 5x5

    public array $levelTable; //Array containing the level id and the associated coordinates on the grid.

    public function populateGrid($levelPoolSetting=0):void
    {
        global $campaignLevels;
        global $angryLevels;

        $levelPool = array();

        switch($levelPoolSetting)
        {
            case 0: {echo("Using campaign levels only\n"); $levelPool = $campaignLevels; break;}
            case 1: {echo("Using Angry levels only\n"); $levelPool = $angryLevels;  break;}
            case 2: {echo("Using campaign and Angry levels \n"); $levelPool = array_merge($campaignLevels,$angryLevels); break;}
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

    public function __construct($size=3,$levelRotation=0)
    {
        $this->size = $size;
        $this->populateGrid($levelRotation);
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
    public bool $requiresPRank;
    public int $gameType;
    public int $difficulty;
    public int $levelRotation;
    public int $gridSize;

    public function __construct()
    {
        $this->maxPlayers = 8;
        $this->maxTeams = 4;
        $this->gridSize = 0; //3x3 by default
        $this->gameType = 0; //Time by default
        $this->difficulty = 2; //Standard by default
        $this->levelRotation = 0; //Campaign only by default
        $this->requiresPRank = false;
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
        $this->grid = new GameGrid($this->gameSettings->gridSize+3);

        $this->gameState = GameState::inLobby;

        $this->numOfClaims = 0;
        $this->firstMapClaimed = "";
        $this->lastMapClaimed = "";
    }

    //Adds a player to the current Game.
    // $player: A GamePlayer representing a player.
    // $isHost: Bool indicating if the $player being added is the host.
    public function addPlayerToGame(GamePlayer $player, string $playerSteamId, bool $isHost=false): void
    {
        echo("Adding ".$player->username. " (".$player->steamId.") to game ".$this->gameId."\n");

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
            echo("WE GOT A BINGO!!\n");
        }
        return($horizontal || $vertical || $diagonalUp || $diagonalDown);
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

        echo("Set teams for game ".$this->gameId.":\n");
        var_export($this->teams);
    }

    public function generateGrid($size=3):void
    {
        $this->grid = new GameGrid($size,$this->gameSettings->levelRotation);
    }
}

class GameController
{
    public array $currentGames; //A list of current game's that are ongoing. Each entry is represented by an id and an associated Game object.

    public function createGame(Int $gameId, string $hostSteamName,WebSocket\Connection $hostConnection, string $hostSteamId)
    {
        echo("Creating game with id ".$gameId.", host is ".$hostSteamName.", SteamID is ".$hostSteamId."\n");
        $gameToCreate = new Game($hostSteamName,$hostConnection,$gameId,$hostSteamId);
        $this->currentGames[$gameId] = $gameToCreate;

        return $gameToCreate;
    }

    public function joinGame(Int $gameId, string $playerName, string $plrSteamId, WebSocket\Connection $playerConnection)
    {
        // Lookup the game id in the DB and see if it exists.
        $gameLookup = lookForGame($gameId);
        if(lookForGame($gameId) <> null)
        {
            echo("Game with given id found\n");
            //Make sure the game isn't already full or hasn't already started.
            if($gameLookup['R_CURRENTPLAYERS'] == $gameLookup['R_MAXPLAYERS'])
            {
                echo("Game is already full\n");
                return -2;
            }
            if($gameLookup['R_HASSTARTED'] == 1)
            {
                echo("Game has already started\n");
                return -1;
            }

            //Broadcast the new player joining to everyone else in the current Game.
            $message = new JoinRoomNotification($playerName,$plrSteamId);
            $em = new EncapsulatedMessage("JoinRoomNotification",json_encode($message));

            //Send the message to the client first, then send it to everyone else.
            //May have to move this til after the player has joined, otherwise the joining player gets the notif firsts and then panics
            //Because game data wasn't sent to them first.
            foreach($this->currentGames[$gameId]->currentPlayers as $playerSteamId => $playerObj)
            {
                if($plrSteamId <> $playerSteamId)
                {
                    echo("Sending join notif to ".$playerObj->username."\n");
                    sendEncodedMessage($em,$playerObj->websocketConnection);
                }
            }

            //Add the new player to the player list of the Game.
            $playerToAdd = new GamePlayer($playerName,$plrSteamId,$playerConnection);
            $this->currentGames[$gameId]->addPlayerToGame($playerToAdd,$plrSteamId);
            return $this->currentGames[$gameId];
        }
        else
        {
            return -3;
        }
    }

    public function updateGameSettings($settings):void
    {
        if(array_key_exists($settings['roomId'],$this->currentGames))
        {
            $gameToUpdate = $this->currentGames[$settings['roomId']];

            $newSettings = new GameSettings();
            $newSettings->maxPlayers = $settings['maxPlayers'];
            $newSettings->maxTeams = $settings['maxTeams'];
            $newSettings->requiresPRank = $settings['PRankRequired'];
            $newSettings->gameType = $settings['gameType'];
            $newSettings->difficulty = $settings['difficulty'];
            $newSettings->levelRotation = $settings['levelRotation'];
            $newSettings->gridSize = $settings['gridSize'];
            $this->currentGames[$settings['roomId']]->gameSettings = $newSettings;


            $run = new RoomUpdateNotification($settings['maxPlayers'],$settings['maxTeams'],$settings['PRankRequired'],$settings['gameType'],$settings['difficulty'],$settings['levelRotation'],$settings['gridSize']);
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
            echo("Trying to update settings for game id ".$settings['roomId']." but doesn't exist!`\n");
        }
    }

    public function startGame(Int $gameId):void
    {
        if(array_key_exists($gameId,$this->currentGames))
        {
            $gameToStart = $this->currentGames[$gameId];
            echo("Splitting all current players into teams\n");
            $gameToStart->setTeams();
            $gameToStart->gameState = GameState::inGame;
            $gameToStart->generateGrid($gameToStart->gameSettings->gridSize+3);

            startGameInDB($gameId);

            //Mark the start time
            $gameToStart->startTime = new DateTime();
            echo("Game ".$gameToStart->gameId. " starting at " . $gameToStart->startTime->format("Y-m-d h:i:s A") . "\n");

            //Send the game start signal to all players in the game
            echo("Telling all players of game ".$gameToStart->gameId . " to start\n");
            foreach($gameToStart->currentPlayers as $playerSteamId => &$playerObj)
            {
                $startSignal = new StartGameSignal($gameToStart,$playerObj->team,$gameToStart->teams[$playerObj->team],$gameToStart->grid);

                $message = new EncapsulatedMessage("StartGame",json_encode($startSignal));
                sendEncodedMessage($message,$playerObj->websocketConnection);
            }
        }
        else
        {
            echo("Game with id ".$gameId." does not exist\n");
        }
    }

    public function checkPlayerBeforeRemoving(string $username, Int $gameId, string $steamId):int
    {
        if(array_key_exists($gameId,$this->currentGames))
        {
            $currentGame = $this->currentGames[$gameId];
            foreach($currentGame->currentPlayers as $playerSteamId => $playerObj) {
                if ($playerSteamId == $steamId) {
                    echo("Found our player's steamID\n");
                    if($playerSteamId == $currentGame->gameHost)
                    {
                        echo("Player to remove is the host, deleting the whole game!\n");
                        return 1;
                    }
                    else
                    {
                        echo("Normal player, removing\n");
                        return 0;
                    }
                }
            }
            echo("Could not find the player to remove in specified game\n");
            return -1;
        }
        else
        {
            echo("Could not find the specified game\n");
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

        $em = new EncapsulatedMessage("DisconnectNotification",json_encode($dcNotification));

        $indexToRemove = "";
        foreach($game->currentPlayers as $playerSteamId => $playerObj)
        {
            if($playerObj->username == $playername)
            {
                //Remove the player from the game.
                $indexToRemove = $playerSteamId;
                dropFromConnectionTable($playerObj->websocketConnection);
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
        unset($game->currentPlayers[$indexToRemove]);
    }
    
    public function disconnectAllPlayers(Int $gameid,$hostConnection,$disconnectReason="UNSPECIFIED"):void
    {
        $game = $this->currentGames[$gameid];
        $dcMessage = new DisconnectSignal();
        $dcMessage->disconnectCode = 1001;
        $dcMessage->disconnectMessage = $disconnectReason;

        $em = new EncapsulatedMessage("ServerDisconnection",json_encode($dcMessage));

        //var_export($game->currentPlayers);

        foreach($game->currentPlayers as $playerSteamId => $playerObj)
        {
            //Don't send dc message to the host as they've already DC'd before we clean up the game.
            if($playerObj->websocketConnection !== $hostConnection)
            {
                sendEncodedMessage($em,$playerObj->websocketConnection);
                $playerObj->websocketConnection->close(1000,$disconnectReason);
            }

            dropFromConnectionTable($playerObj->websocketConnection);
            unset($game->currentPlayers[$playerSteamId]);
        }
    }

    public function destroyGame(Int $gameId):void
    {
        echo("Destroying game id ".$gameId . " from game coordinator\n");
        unset($this->currentGames[$gameId]);

        echo("Removing entry from DB\n");
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
            // Check if the player is connected to the currently connected game.
            foreach($currentGame->currentPlayers as $playerSteamId => &$player)
            {
                if($playerSteamId == $submissionData['steamId'])
                {
                    $submittedCoords = $submissionData['row']."-".$submissionData['column'];

                    echo("Player is submitting at position ".$submittedCoords." which in our current card, level ID is ".$currentGame->grid->levelTable[$submittedCoords]->levelId."\n");

                    //Check that the submitted coords match.
                    $levelInCard = $currentGame->grid->levelTable[$submittedCoords];
                    if($levelInCard->levelId == $submissionData['levelId'])
                    {
                        echo("Level ID matches, pre-submission all validated\n");
                        return true;
                    }
                    else
                    {
                        echo("Level ID doesn't match!\n");
                        return false;
                    }
                }
            }
            echo("Couldn't find the player SteamID in the game's roster!\n");
        }
        else{
            echo("Room id " .$gameId. " was not found in list of current games\n");
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
            echo("Level is unclaimed, claiming for their team\n");
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

            return 0;
        }
        else
        {
            if(($currentGame->gameSettings->gameType == 0 && $submissionData['time'] < $levelInCard->timeToBeat) || ($currentGame->gameSettings->gameType == 1 && $submissionData['style'] > $levelInCard->styleToBeat))
            {
                //Same team/person
                if($levelInCard->claimedBy == Team::tryFrom($submissionData['team']))
                {
                    echo("Level already claimed by player/team, improving\n");
                    $levelInCard->personToBeat = $submissionData['playerName'];
                    $levelInCard->timeToBeat = $submissionData['time'];
                    $levelInCard->styleToBeat = $submissionData['style'];

                    $currentGame->numOfClaims++;
                    $currentGame->lastMapClaimed = $levelInCard->levelName;

                    return 1;
                }
                else
                {
                    echo("Reclaiming level from previous team\n");
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
                echo("Run did not beat current criteria\n");
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
        echo("Game coordinator started at: ".date("Y-m-d h:i:s A")."\n");
        $this->currentGames = [];
    }
}

$gameCoordinator = new GameController();
?>