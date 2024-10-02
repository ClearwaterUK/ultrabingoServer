<?php

class RoomUpdateNotification
{
    public $maxPlayers;
    public $maxTeams;
    public $PRankRequired;
    public $gameType;
    public $difficulty;
    public $levelRotation;

    public function __construct($maxPlayers,$maxTeams,$PRankRequired,$gameType,$difficulty,$levelRotation)
    {
        $this->maxPlayers = $maxPlayers;
        $this->maxTeams = $maxTeams;
        $this->PRankRequired = $PRankRequired;
        $this->gameType = $gameType;
        $this->difficulty = $difficulty;
        $this->levelRotation = $levelRotation;
    }
}

?>