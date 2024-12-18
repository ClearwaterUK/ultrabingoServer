<?php

class RoomUpdateNotification
{
    public $maxPlayers;
    public $maxTeams;
    public $teamComposition;
    public $gameType;
    public $gridSize;
    public $difficulty;
    public $PRankRequired;
    public $disableCampaignAltExits;

    public $wereTeamsReset;

    public function __construct($maxPlayers,$maxTeams,$teamComposition,$PRankRequired,$gameType,$difficulty,$gridSize,$disableCampaignAltExits,$wereTeamsReset=false)
    {
        $this->maxPlayers = $maxPlayers;
        $this->maxTeams = $maxTeams;
        $this->teamComposition = $teamComposition;
        $this->PRankRequired = $PRankRequired;
        $this->gameType = $gameType;
        $this->difficulty = $difficulty;
        $this->gridSize = $gridSize;
        $this->disableCampaignAltExits = $disableCampaignAltExits;

        $this->wereTeamsReset = $wereTeamsReset;

    }
}

?>