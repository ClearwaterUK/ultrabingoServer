<?php

class EndGameSignal
{
    public $winningTeam;

    public function __construct($team)
    {
        $this->winningTeam = $team;
    }
}

?>