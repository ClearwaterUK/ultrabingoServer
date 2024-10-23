<?php

class EndGameSignal
{
    public $winningTeam;
    public $winningPlayers;
    public $timeElapsed;
    public $claims;
    public $firstMapClaimed;
    public $lastMapClaimed;

    public function __construct($team,$winningPlayers,$timeElapsed,$claims,$firstMapClaimed,$lastMapClaimed)
    {
        $this->winningTeam = $team;
        $this->winningPlayers = $winningPlayers;
        $this->timeElapsed = $timeElapsed;
        $this->claims = $claims;
        $this->firstMapClaimed = $firstMapClaimed;
        $this->lastMapClaimed = $lastMapClaimed;
    }
}

?>