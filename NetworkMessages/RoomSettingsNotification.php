<?php

class RoomUpdateNotification
{
    public $maxPlayers;
    public $maxTeams;
    public $timeLimit;
    public $teamComposition;
    public $gamemode;
    public $gridSize;
    public $difficulty;
    public $PRankRequired;
    public $disableCampaignAltExits;
    public $gameVisibility;

    public $wereTeamsReset;

    public function __construct($maxPlayers,$maxTeams,$teamComposition,$PRankRequired,$timeLimit,$difficulty,$gridSize,$disableCampaignAltExits,$gameVisibility,$gamemode,$wereTeamsReset=false)
    {
        $this->maxPlayers = $maxPlayers;
        $this->maxTeams = $maxTeams;
        $this->teamComposition = $teamComposition;
        $this->PRankRequired = $PRankRequired;
        $this->gamemode = $gamemode;
        $this->timeLimit = $timeLimit;
        $this->difficulty = $difficulty;
        $this->gridSize = $gridSize;
        $this->disableCampaignAltExits = $disableCampaignAltExits;
        $this->gameVisibility = $gameVisibility;

        $this->wereTeamsReset = $wereTeamsReset;

    }
}

?>