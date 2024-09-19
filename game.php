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
        echo("Constructing grid\n");
        $this->populateGrid();
        echo("Grid made\n");
        //var_export($this->levelTable);
    }
}

//Represents a player currently connected to a game.
class GamePlayer
{
    //public $steamId;
    public $username;
    public $websocketConnection;
    public $team;

    public function __construct($playerName,$playerConnection)
    {
        $this->username = $playerName;
        $this->websocketConnection = $playerConnection;
    }

    public function setTeam($team)
    {

    }
}

class Game
{
    public $gameId;

    public $currentPlayers; //List of players. A player is represented by the GamePlayer class.

    public $grid; //Our NxN bingo grid.

    public $gameHost; //The player who is hosting the game. Represented by GamePlayer class.

    public $gameState; //Current state of the game, represented by GameState enum.

    public $criteriaType; //What criteria? 1 = time, 2 = style.

    public $teams; // Array of type <string, array(GamePlayer)> denoting the teams for a Game.

    public function __construct($hostSteamName,$hostConnection,$gameId)
    {
        $this->currentPlayers = [];
        $this->grid = [];
        $this->gameId = $gameId;

        $this->criteriaType = 1; //Time only for now, will add style support later

        $host = new GamePlayer($hostSteamName,$hostConnection);

        //When a game is created, create a GamePlayer representing the host and set them as gameHost.
        $this->addPlayerToGame($host,true);

        //Pre-generate the grid of levels. (3x3 for now, will move to 5x5 in future)
        $this->grid = new GameGrid(3);

        $this->gameState = GameState::inLobby;
    }

    //Adds a player to the current Game.
    // $player: A GamePlayer representing a player.
    // $isHost: Bool indicating if the $player being added is the host.
    public function addPlayerToGame(GamePlayer $player,bool $isHost=false): void
    {
        echo("Adding player ".$player->username. " to game ".$this->gameId."\n");
        array_push($this->currentPlayers,$player);
        if($isHost)
        {
            $this->gameHost = $player;
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

        //Randomise player order
        shuffle($this->currentPlayers);

        $colorMarker = 1;
        foreach($this->currentPlayers as &$player)
        {
            $player->team = $teamPointers[$colorMarker];
            $this->putPlayerInTeam($player->username,$colorMarker);
            if($colorMarker == $MAX_TEAMS) {$colorMarker == 1;}
            else{$colorMarker++;}
        }

        echo("Here are our teams:\n");
        var_export($this->teams);
    }
}

class GameController
{
    public $currentGames; //A list of current game's that are ongoing. Each entry is represented by an id and an associated Game object.

    public function createGame(Int $gameId, string $hostSteamName,WebSocket\Connection $hostConnection)
    {
        echo("Creating game with id ".$gameId.", host is ".$hostSteamName."\n");
        $gameToCreate = new Game($hostSteamName,$hostConnection,$gameId);
        $this->currentGames[$gameId] = $gameToCreate;
        return $gameToCreate;
    }

    public function joinGame(Int $gameId, string $playerName, WebSocket\Connection $playerConnection)
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
            $message = new JoinRoomNotification($playerName);
            $em = new EncapsulatedMessage("JoinRoomNotification",json_encode($message));

            //Send the message to the client first, then send it to everyone else.
            sendEncodedMessage($em,$playerConnection);
            foreach($this->currentGames[$gameId]->currentPlayers as &$player)
            {
                if($player->username <> $playerName)
                {
                    sendEncodedMessage($em,$player->websocketConnection);
                }
            }

            //Add the new player to the player list of the Game.
            $playerToAdd = new GamePlayer($playerName,$playerConnection);
            $this->currentGames[$gameId]->addPlayerToGame($playerToAdd);
            return $this->currentGames[$gameId];
        }
        else
        {
            return -3;
        }
    }

    public function startGame(Int $gameId)
    {
        if(array_key_exists($gameId,$this->currentGames))
        {
            echo("Game exists, splitting all current players into teams\n");
            
            $gameToStart = $this->currentGames[$gameId];
            $gameToStart->setTeams();
            $gameToStart->gameState = GameState::inGame;

            //Send the game start signal to all players in the game
            foreach($gameToStart->currentPlayers as &$player)
            {
                echo("Telling client ".$player->username." to start\n");

                $startSignal = new StartGameSignal($player->team,$gameToStart->teams[$player->team]);

                $message = new EncapsulatedMessage("StartGame",json_encode($startSignal));
                sendEncodedMessage($message,$player->websocketConnection);
            }
        }
        else
        {
            echo("Game with id ".$gameId." does not exist\n");
        }
    }

    public function checkPlayerBeforeRemoving(string $username, Int $gameId)
    {
        if(array_key_exists($gameId,$this->currentGames))
        {
            $currentGame = $this->currentGames[$gameId];
            foreach($currentGame->currentPlayers as &$player) {
                if ($player->username == $username) {
                    echo("Found our player\n");
                    if($player == $currentGame->gameHost)
                    {
                        echo("Player to remove is the host, deleting the whole game!\n");
                        return 1;
                    }
                    else
                    {
                        echo("Normal player, removing");
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

    public function disconnectPlayer(Int $gameid, string $playername)
    {
        $game = $this->currentGames[$gameid];
        $dcMessage = new DisconnectSignal();
        $dcMessage->disconnectCode = 1001;
        $dcMessage->disconnectMessage = "You have left the game.";

        $dcNotification = new DisconnectNotification();
        $dcNotification->username = $playername;

        $em = new EncapsulatedMessage("Disconnect",json_encode($dcMessage));
        $em2 = new EncapsulatedMessage("DisconnectNotification",json_encode($dcNotification));

        foreach($game->currentPlayers as &$player)
        {
            if($player->username == $playername)
            {
                sendEncodedMessage($em,$player->websocketConnection);
                $player->websocketconnection->close(1000,"Closing");
                unset($game->currentPlayers[$player]);
                return;
            }
            else
            {
                //Notify all other players of the player leaving the game.
                sendEncodedMessage($em2,$player->websocketConnection);
            }
        }
    }
    
    public function disconnectAllPlayers(Int $gameid)
    {
        $game = $this->currentGames[$gameid];
        $dcMessage = new DisconnectSignal();
        $dcMessage->disconnectCode = 1001;
        $dcMessage->disconnectMessage = "The host has left the game.";

        $em = new EncapsulatedMessage("Disconnect",json_encode($dcMessage));

        //var_export($game->currentPlayers);

        $index = 0;
        foreach($game->currentPlayers as &$player)
        {
            sendEncodedMessage($em,$player->websocketConnection);
            $player->websocketConnection->close(1000,"Closing");
            unset($game->currentPlayers[$index]);
            $index++;
        }
    }

    public function destroyGame(Int $gameId)
    {
        echo("Destroying game id ".$gameId . " from game coordinator\n");
        unset($this->currentGames[$gameId]);
    }

    public function verifyRunSubmission($submissionData)
    {
        //When receiving a rub submission request:
        // Check if the game id exists.
        $gameId = $submissionData['gameId'];
        if(array_key_exists($gameId,$this->currentGames))
        {
            echo("Game found in current list\n");
            $currentGame = $this->currentGames[$gameId];
            // Check if the player is connected to the currently connected game.
            foreach($currentGame->currentPlayers as &$player)
            {
                if($player->username == $submissionData['playerName'])
                {
                    echo("Found our player\n");
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
            echo("Couldn't find the player in the game's roster!\n");
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
            echo("Level is unclaimed, claiming for their team");

            $levelInCard->claimedBy = Team::tryFrom($submissionData['team']);
            $levelInCard->personToBeat = $submissionData['playerName'];
            $levelInCard->timeToBeat = $submissionData['time'];
            $levelInCard->styleToBeat = $submissionData['style'];

            return 0;
        }
        else
        {
            if(($currentGame->criteriaType == 1 && $submissionData['time'] < $levelInCard->timeToBeat) || ($currentGame->criteriaType == 2 && $submissionData['style'] > $levelInCard->styleToBeat))
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