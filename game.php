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
    public $levelName;
    public $claimedBy; //The team that currently claims the level. Can be any color or "None to signify not claimed yet.
    public $personToBeat;
    public $timeToBeat;
    public $styleToBeat;

    //Coordinates in the GameGrid.
    public $row;
    public $column;

    public function __construct($level,$row,$column)
    {
        $this->levelName = $level;
        $this->claimedBy = Team::NONE;
        $this->personToBeat = "";
        $this->timeToBeat = 0;
        $this->styleToBeat = 0;
        $this->row = $row;
        $this->column = $column;
    }
}

//Represents the grid of levels selected to be played in a game.
class GameGrid
{
    public $size; //We'll only do 3x3 for now for testing, but will have to bump up to 5x5

    public $levelTable; //Array containing the level id and the associated coordinates on the grid.

    public function populateGrid()
    {
        global $levels;
        $levelList = $levels;

        for($x = 0; $x <= $this->size-1; $x++)
        {
            for($y = 0; $y <= $this->size-1; $y++)
            {
                //Pick a level from our level list, set it, and then remove to prevent duplicates
                $selectedIndex = array_rand($levelList);
                $rand = $levelList[$selectedIndex];
                $levelToInsert = new GameLevel($rand,$x,$y);
                $this->levelTable[$x."-".$y] = $levelToInsert;
                unset($levelList[$selectedIndex]);
            }
        }
    }

    public function __construct($size=3)
    {
        $this->size = $size;
        echo("Constructing grid of size ".$size."x".$size."\n");
        $this->populateGrid();
        echo("Grid made\n");
        //var_export($this->levelTable);
    }
}

//Represents a player currently connected to a game.
class GamePlayer
{
    public $steamId;
    public $username;
    public $websocketConnection;
    public $team;

    public function __construct($playerName,$playerSteamId,$playerConnection)
    {
        $this->username = $playerName;
        $this->websocketConnection = $playerConnection;
        $this->steamId = $playerSteamId;
    }

    public function setTeam($team)
    {

    }
}

class GameSettings
{
    public $maxPlayers;
    public $maxTeams;
    public $requiresPRank;
    public $gameType;
    public $difficulty;
    public $levelRotation;
    public $gridSize;

    public function __construct()
    {
        $this->maxPlayers = 8;
        $this->maxTeams = 4;
        $this->requiresPRank = false;
        $this->gameType = 0; //Time by default
        $this->difficulty = 0; //Harmless by default
        $this->levelRotation = 0; //Campaign only by default
        $this->gridSize = 0; // 3x3 by default
    }
}

class Game
{
    public $gameId;

    public $currentPlayers; //List of players. Each player is represented via a <SteamID,GamePlayer> format.

    public $grid; //Our NxN bingo grid.

    public $gameHost; //The player who is hosting the game. Represented by a string containing the SteamID of the host.

    public $gameState; //Current state of the game, represented by GameState enum.

    public $teams; // Array of type <string, array(GamePlayer)> denoting the teams for a Game.

    public $gameSettings; //Settings for the game, represented by a GameSettings object.

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

        //Pre-generate the grid of levels. (NOTE: switching to dynamic generation, delete this)
        $this->grid = new GameGrid($this->gameSettings->gridSize+3);

        $this->gameState = GameState::inLobby;
    }

    //Adds a player to the current Game.
    // $player: A GamePlayer representing a player.
    // $isHost: Bool indicating if the $player being added is the host.
    public function addPlayerToGame(GamePlayer $player, string $playerSteamId, bool $isHost=false): void
    {
        //Because PHP likes to autoconvert SteamIDs to int, resulting in loss of data when accessed later, we prepend
        // something to it to forcibly store it as a string. Later we cut out the prepended token when accessing.
        echo("Adding player ".$player->username. " to game ".$this->gameId."\n");

        $this->currentPlayers[$playerSteamId] = $player;
        if($isHost)
        {
            $this->gameHost = $playerSteamId;
        }
    }

    //Removes a player from the specified game.
    public function removePlayerFromGame($player)
    {
        unset($this->currentPlayers[$player]);
    }

    public function putPlayerInTeam($player,$teamColor)
    {
        global $teamPointers;

        array_push($this->teams[$teamPointers[$teamColor]],$player);
    }

    public function checkForBingo($team,$coordX,$coordY)
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

        //TODO: Randomise player order
        $colorMarker = 1;
        foreach($this->currentPlayers as $playerSteamId => &$playerObj)
        {
            $playerObj->team = $teamPointers[$colorMarker];
            $this->putPlayerInTeam($playerObj->username,$colorMarker);
            if($colorMarker == $MAX_TEAMS) {$colorMarker == 1;}
            else{$colorMarker++;}
        }

        echo("Here are our teams:\n");
        var_export($this->teams);
    }

    public function generateGrid($size=3)
    {
        //$this->grid = new GameGrid($size);
    }
}

class GameController
{
    public $currentGames; //A list of current game's that are ongoing. Each entry is represented by an id and an associated Game object.

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

    public function updateGameSettings($settings)
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

            echo("Grid size is now raw ".$newSettings->gridSize."\n");
            echo("Notifying all non-host players of changed settings\n");
            $run = new RoomUpdateNotification($settings['maxPlayers'],$settings['maxTeams'],$settings['PRankRequired'],$settings['gameType'],$settings['difficulty'],$settings['levelRotation'],$settings['gridSize']);
            $em = new EncapsulatedMessage("RoomUpdate",json_encode($run));

            foreach($gameToUpdate->currentPlayers as $playerSteamId => &$playerObj)
            {
                if($playerSteamId != $gameToUpdate->gameHost)
                {
                    sendEncodedMessage($em,$playerObj->websocketConnection);
                }
            }
            return;
        }
        else
        {
            echo("Trying to update settings for game id ".$settings['roomId']." but doesn't exist!`\n");
            return;
        }
    }

    public function startGame(Int $gameId)
    {
        if(array_key_exists($gameId,$this->currentGames))
        {
            $gameToStart = $this->currentGames[$gameId];
            echo("Game exists, splitting all current players into teams\n");
            $gameToStart->setTeams();
            $gameToStart->gameState = GameState::inGame;

            echo("Constructing grid of size ".($gameToStart->gameSettings->gridSize+3)."x".($gameToStart->gameSettings->gridSize+3)."\n");
            $gameToStart->generateGrid($gameToStart->gameSettings->gridSize);

            //Send the game start signal to all players in the game
            foreach($gameToStart->currentPlayers as $playerSteamId => &$playerObj)
            {
                echo("Telling client ".$playerObj->username."(".$playerObj->steamId.") to start\n");

                $startSignal = new StartGameSignal($playerObj->team,$gameToStart->teams[$playerObj->team],$gameToStart->grid);

                $message = new EncapsulatedMessage("StartGame",json_encode($startSignal));
                sendEncodedMessage($message,$playerObj->websocketConnection);
            }
        }
        else
        {
            echo("Game with id ".$gameId." does not exist\n");
        }
    }

    public function checkPlayerBeforeRemoving(string $username, Int $gameId, string $steamId)
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

    public function disconnectPlayer(Int $gameid, string $playername, string $steamId, $leavingConnection)
    {
        $game = $this->currentGames[$gameid];
        $dcMessage = new DisconnectSignal();
        $dcMessage->disconnectCode = 1001;
        $dcMessage->disconnectMessage = "You have left the game.";

        $dcNotification = new DisconnectNotification();
        $dcNotification->username = $playername;
        $dcNotification->steamId = $steamId;

        $em = new EncapsulatedMessage("Disconnect",json_encode($dcMessage));
        $em2 = new EncapsulatedMessage("DisconnectNotification",json_encode($dcNotification));

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
                //Make sure we're not sending to the player who just left as their connection was closed before removing
                //on the server-side.
                if($playerObj->websocketConnection !== $leavingConnection)
                {
                    sendEncodedMessage($em2,$playerObj->websocketConnection);
                }
            }
        }
        unset($game->currentPlayers[$indexToRemove]);
    }
    
    public function disconnectAllPlayers(Int $gameid,$hostConnection)
    {
        $game = $this->currentGames[$gameid];
        $dcMessage = new DisconnectSignal();
        $dcMessage->disconnectCode = 1001;
        $dcMessage->disconnectMessage = "The host has left the game.";

        $em = new EncapsulatedMessage("HostLeftGame",json_encode($dcMessage));

        //var_export($game->currentPlayers);

        foreach($game->currentPlayers as $playerSteamId => $playerObj)
        {
            //Don't send dc message to the host as they've already DC'd before we clean up the game.
            if($playerObj->websocketConnection !== $hostConnection)
            {
                sendEncodedMessage($em,$playerObj->websocketConnection);
                $playerObj->websocketConnection->close(1000,"Host has left game");
            }

            dropFromConnectionTable($playerObj->websocketConnection);
            unset($game->currentPlayers[$playerSteamId]);
        }
    }

    public function destroyGame(Int $gameId)
    {
        echo("Destroying game id ".$gameId . " from game coordinator\n");
        unset($this->currentGames[$gameId]);
    }

    public function verifyRunSubmission($submissionData): bool
    {
        //When receiving a rub submission request:
        // Check if the game id exists.
        $gameId = $submissionData['gameId'];
        if(array_key_exists($gameId,$this->currentGames))
        {
            echo("Game found in current list\n");
            $currentGame = $this->currentGames[$gameId];
            // Check if the player is connected to the currently connected game.
            foreach($currentGame->currentPlayers as $playerSteamId => &$player)
            {
                echo($playerSteamId."\n");
                if($playerSteamId == $submissionData['steamId'])
                {
                    echo("Found our player's SteamID\n");
                    $submittedCoords = $submissionData['row']."-".$submissionData['column'];

                    echo("Player is submitting at position ".$submittedCoords." which in our current card is ".$currentGame->grid->levelTable[$submittedCoords]->levelName."\n");

                    //Check that the submitted coords match.
                    $levelInCard = $currentGame->grid->levelTable[$submittedCoords];
                    if($levelInCard->levelName == $submissionData['mapName'])
                    {
                        echo("Level name matches, pre-submission all validated\n");
                        return true;
                    }
                    else
                    {
                        echo("Level name doesn't match!\n");
                        return false;
                    }
                }
            }
            echo("Couldn't find the player SteamID in the game's roster!\n");
            return false;
        }
        else{
            echo("Room id " .$gameId. " was not found in list of current games\n");
            return false;
        }
    }

    /*
     * Returns 3 values:
     * -1: Submission did not beat the criteria
     * 0: Submission claimed an unclaimed map
     * 1: Submission improved an already claimed map
     * 2: Submission beat criteria
     */
    public function submitRun($submissionData)
    {
        global $gameCoordinator;

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

            return 0;
        }
        else
        {
            echo("Gametype:".$currentGame->gameSettings->gameType);
            echo("Current level requirement is " . $levelInCard->timeToBeat . "\n");
            echo("Submitted time was " . $submissionData['time'] . "\n");
            var_export($submissionData['time'] < $levelInCard->timeToBeat);
            if(($currentGame->gameSettings->gameType == 0 && $submissionData['time'] < $levelInCard->timeToBeat) || ($currentGame->gameSettings->gameType == 1 && $submissionData['style'] > $levelInCard->styleToBeat))
            {
                //Same team/person
                if($levelInCard->claimedBy == Team::tryFrom($submissionData['team']))
                {
                    echo("Level already claimed by player/team, improving\n");
                    $levelInCard->personToBeat = $submissionData['playerName'];
                    $levelInCard->timeToBeat = $submissionData['time'];
                    $levelInCard->styleToBeat = $submissionData['style'];

                    return 1;
                }
                else
                {
                    $levelInCard->claimedBy = Team::tryFrom($submissionData['team']);
                    $levelInCard->personToBeat = $submissionData['playerName'];
                    $levelInCard->timeToBeat = $submissionData['time'];
                    $levelInCard->styleToBeat = $submissionData['style'];
                    echo("Claiming level from another team\n");
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

    public function __construct()
    {
        echo("Game coordinator started at: ".date("Y-m-d h:i:s")."\n");
        $this->currentGames = [];
    }
}

$gameCoordinator = new GameController();
?>