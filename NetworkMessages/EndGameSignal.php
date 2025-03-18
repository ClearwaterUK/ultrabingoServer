<?php

class EndGameSignal
{
    public $winningTeam;
    public $winningPlayers;
    public $timeElapsed;
    public $claims;
    public $firstMapClaimed;
    public $lastMapClaimed;

    public $bestStatValue;
    public $bestStatMap;

    public $endStatus;
    public $tiedTeams;

    public function __construct($team,$winningPlayers,$timeElapsed,$claims,$firstMapClaimed,$lastMapClaimed,$bestStatValue,$bestStatMap,$endStatus=0,$tiedTeams=array())
    {
        $this->winningTeam = $team;
        $this->winningPlayers = $winningPlayers;
        $this->timeElapsed = $timeElapsed;
        $this->claims = $claims;
        $this->firstMapClaimed = $firstMapClaimed;
        $this->lastMapClaimed = $lastMapClaimed;

        $this->bestStatValue = $bestStatValue;
        $this->bestStatMap = $bestStatMap;

        $this->endStatus = $endStatus;
        $this->tiedTeams = $tiedTeams;
    }
}

?>