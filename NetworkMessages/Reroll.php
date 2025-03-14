<?php

class RerollVoteNotification
{
    public $mapName;
    public $voteStarter;
    public $voteStarterSteamId;
    public $numVotes;
    public $votesRequired;
    public $notifType;
    public $timer;

    public function __construct($steamId,$mapName,$voteStarter,$numVotes,$votesRequired,$timer,$notifType=0)
    {
        $this->mapName = $mapName;
        $this->voteStarter = $voteStarter;
        $this->voteStarterSteamId = $steamId;
        $this->numVotes = $numVotes;
        $this->votesRequired = $votesRequired;
        $this->timer = $timer;
        $this->notifType = $notifType;
    }
}

class RerollSuccessNotification
{
    public $oldMapId;
    public $oldMapName;
    public $mapData;
    public $locationX;
    public $locationY;

    public function __construct($oldMapId, $oldMapName,$mapData,$locationX,$locationY)
    {
        $this->oldMapId = $oldMapId;
        $this->oldMapName = $oldMapName;
        $this->mapData = $mapData;
        $this->locationX = $locationX;
        $this->locationY = $locationY;
    }
}

class RerollExpireNotification
{
    public $mapName;

    public function __construct($mapName)
    {
        $this->mapName = $mapName;
    }
}

?>