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

    public function __construct($team,$winningPlayers,$timeElapsed,$claims,$firstMapClaimed,$lastMapClaimed,$bestStatValue,$bestStatMap)
    {
        $this->winningTeam = $team;
        $this->winningPlayers = $winningPlayers;
        $this->timeElapsed = $timeElapsed;
        $this->claims = $claims;
        $this->firstMapClaimed = $firstMapClaimed;
        $this->lastMapClaimed = $lastMapClaimed;

        $this->bestStatValue = $bestStatValue;
        $this->bestStatMap = $bestStatMap;
    }
}

?>