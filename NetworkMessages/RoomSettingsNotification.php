<?php

class RoomUpdateNotification
{
    public $maxPlayers;
    public $maxTeams;
    public $teamComposition;
    public $gameType;
    public $gridSize;
    public $difficulty;
    public $levelRotation;
    public $PRankRequired;

    public $wereTeamsReset;

    public function __construct($maxPlayers,$maxTeams,$teamComposition,$PRankRequired,$gameType,$difficulty,$levelRotation,$gridSize,$wereTeamsReset=false)
    {
        $this->maxPlayers = $maxPlayers;
        $this->maxTeams = $maxTeams;
        $this->teamComposition = $teamComposition;
        $this->PRankRequired = $PRankRequired;
        $this->gameType = $gameType;
        $this->difficulty = $difficulty;
        $this->levelRotation = $levelRotation;
        $this->gridSize = $gridSize;

        $this->wereTeamsReset = $wereTeamsReset;

    }
}

?>