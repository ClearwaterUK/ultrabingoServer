<?php

class StartGameSignal
{
    public $game;
    public $teamColor;
    public $teammates;
    public $grid;

    public $difficultyOverride;

    public function __construct($game,$teamColor,$teammates,$grid,$difficultyOverride)
    {
        $this->game = $game;
        $this->teamColor = $teamColor;
        $this->teammates = $teammates;
        $this->grid = $grid;
        $this->difficultyOverride = $difficultyOverride;
    }
}


?>