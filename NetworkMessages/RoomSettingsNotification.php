<?php

class RoomUpdateNotification
{
    public $maxPlayers;
    public $maxTeams;
    public $gameType;
    public $gridSize;
    public $difficulty;
    public $levelRotation;
    public $PRankRequired;

    public function __construct($maxPlayers,$maxTeams,$PRankRequired,$gameType,$difficulty,$levelRotation,$gridSize)
    {
        $this->maxPlayers = $maxPlayers;
        $this->maxTeams = $maxTeams;
        $this->PRankRequired = $PRankRequired;
        $this->gameType = $gameType;
        $this->difficulty = $difficulty;
        $this->levelRotation = $levelRotation;
        $this->gridSize = $gridSize;
    }
}

?>