<?php

interface IGameMode
{
    public function setup(Game $game): void;

    public function onMapClaim(Game $game, $recievedJson,$submitResult,$mapIsBeingVoted): void;

    public function endGame(Game $game,$receivedJson=""):void;

    public function timeRemaining():int;
}

class BaseGamemode implements IGameMode
{

    public function setup(Game $game):void
    {
        logWarn("Setting up BaseGamemode (this should never happen)");
    }

    public function onMapClaim(Game $game,$recievedJson,$submitResult,$mapIsBeingVoted):void
    {

    }


    public function endGame(Game $game,$receivedJson=""):void
    {

    }

    public function timeRemaining(): int
    {
        return 0;
    }
}

class DominationGamemode extends BaseGamemode implements IGameMode
{
    public EvTimer $time;

    public function timeRemaining():int
    {
        return intval($this->time->remaining);
    }

    public function setup(Game $game):void
    {
        logInfo("Setting up Domination gamemode");

        $this->time = new EvTimer($game->gameSettings->timeLimit*60, 0, function() use ($game) {
            $this->timeUp($game);
        });
        logInfo("Timer started at ".$game->gameSettings->timeLimit
            ."minutes");
    }
    public function onMapClaim(Game $game,$recievedJson,$submitResult,$mapIsBeingVoted):void
    {
        logInfo("Map claimed in Domination mode");

        $levelDisplayName = $game->grid->levelTable[$recievedJson['row']."-".$recievedJson['column']]->levelName;

        $message = buildNetworkMessage("LevelClaimed",new ClaimedLevelBroadcast($recievedJson['playerName'],$recievedJson['team'],$levelDisplayName,$submitResult,$recievedJson['row'],$recievedJson['column'],$recievedJson['time'],$mapIsBeingVoted));

        logMessage("Notifying all players in game");
        broadcastToAllPlayers($game,$message);
    }
    public function timeUp(Game $game):void
    {
        logMessage("TIME UP!");

        $this->endGame($game);
    }

    public function endGame(Game $game, $recievedJson=""):void
    {
        $tracker = array(
            "Red" => 0,
            "Blue" => 0,
            "Green" => 0,
            "Yellow" => 0
        );

        //Loop across the grid, and count the amount of claimed maps for each team.
        foreach($game->grid->levelTable as $level)
        {
            if($level->claimedBy->name != "NONE")
            {
                $teamString = $level->claimedBy->value;
                $tracker[$teamString]++;
            }
        }
        $winningTeam = array_search(max($tracker),$tracker);
        $claims = $game->numOfClaims;

        $endStatus = 0;
        $tiedTeams = array();

        //If no claims were made in the game, no winner.
        if($claims == 0)
        {
            logWarn("No claims were made during this game!");
            $winningTeam = "NONE";
            $endStatus = 1;
            $winningPlayers = array();
        }
        //Check if there is a tie between 2 or more teams.
        //Get the max value from $tracker, then check if count(array_keys($tracker)) > 1
        else if (array_count_values($tracker)[max($tracker)] > 1)
        {
            logMessage("TIE");
            $maxVal = max($tracker);
            $tiedTeams = array_keys($tracker,$maxVal);

            $endStatus = 2;
            $winningPlayers = array();
        }
        else
        {
            logMessage("Winning team: ".$winningTeam);
            $winningPlayers = array_values($game->teams[$winningTeam]);
        }
        $endTime = new DateTime();
        logMessage("Ending game ".$game->gameId." at ".$endTime->format("Y-m-d h:i:s A"));

        $elapsedTime = $game->startTime->diff($endTime)->format(("%H:%I:%S"));
        logMessage("Elapsed time of game: ".$elapsedTime);

        markGameEnd($game->gameId);

        $message = buildNetworkMessage("GameEnd", new EndGameSignal($winningTeam,$winningPlayers,$elapsedTime,$claims,$game->firstMapClaimed,$game->lastMapClaimed,$game->bestStatValue,$game->bestStatMap,$endStatus,$tiedTeams));

        broadcastToAllPlayers($game,$message);

    }
}

class BingoGamemode extends BaseGamemode implements IGameMode
{
    public function setup(Game $game): void
    {
        logInfo("Setting up Bingo gamemode");
    }

    public function timeRemaining():int
    {
        return 0;
    }

    public function onMapClaim(Game $game, $recievedJson, $submitResult, $mapIsBeingVoted): void
    {
        $hasObtainedBingo = $game->checkForBingo($recievedJson['team'],$recievedJson['row'],$recievedJson['column']);

        $levelDisplayName = $game->grid->levelTable[$recievedJson['row']."-".$recievedJson['column']]->levelName;

        $message = buildNetworkMessage("LevelClaimed",new ClaimedLevelBroadcast($recievedJson['playerName'],$recievedJson['team'],$levelDisplayName,$submitResult,$recievedJson['row'],$recievedJson['column'],$recievedJson['time'],$mapIsBeingVoted));

        logMessage("Notifying all players in game");
        broadcastToAllPlayers($game,$message);

        if($hasObtainedBingo)
        {
            $this->endGame($game,$recievedJson);
        }
    }


    public function endGame(Game $game,$receivedJson=""):void
    {
        $gameToEnd = $game;
        $gameToEnd->hasEnded = true;

        $gameId = $game->gameId;

        //Get all the necessary endgame stats to send to each player.
        $winningPlayers = array_values($gameToEnd->teams[$receivedJson['team']]);

        $endTime = new DateTime();
        logMessage("Ending game ".$gameId." at ".$endTime->format("Y-m-d h:i:s A"));

        $elapsedTime = $gameToEnd->startTime->diff($endTime)->format(("%H:%I:%S"));
        logMessage("Elapsed time of game: ".$elapsedTime);

        markGameEnd($gameId);

        $claims = $gameToEnd->numOfClaims;

        $message = buildNetworkMessage("GameEnd",new EndGameSignal($receivedJson['team'],$winningPlayers,$elapsedTime,$claims,$gameToEnd->firstMapClaimed,$gameToEnd->lastMapClaimed,$gameToEnd->bestStatValue,$gameToEnd->bestStatMap));

        broadcastToAllPlayers($game,$message);
    }
}

enum BingoGamemodeTypes: Int
{
    case Bingo = 0;
    case Domination = 1;
}

function makeGamemode($gamemodeId)
{
    switch(BingoGamemodeTypes::tryFrom($gamemodeId))
    {
        case null:
        {
            logError("Invalid gamemode ID passed!");
            return null;
        }
        case BingoGamemodeTypes::Bingo: {return new BingoGamemode();}
        case BingoGamemodeTypes::Domination: {return new DominationGamemode();}
        default: {return new BaseGamemode();}
    }
}

?>