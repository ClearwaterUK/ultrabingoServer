<?php

class StartGameSignal
{
    public $teamColor;
    public $teammates;
    public $grid;

    public function __construct($teamColor,$teammates,$grid)
    {
        $this->teamColor = $teamColor;
        $this->teammates = $teammates;
        $this->grid = $grid;
    }
}


?>