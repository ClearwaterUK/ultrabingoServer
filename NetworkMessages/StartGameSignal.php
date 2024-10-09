<?php

class StartGameSignal
{
    public $game;
    public $teamColor;
    public $teammates;
    public $grid;

    public function __construct($game,$teamColor,$teammates,$grid)
    {
        $this->game = $game;
        $this->teamColor = $teamColor;
        $this->teammates = $teammates;
        $this->grid = $grid;
    }
}


?>