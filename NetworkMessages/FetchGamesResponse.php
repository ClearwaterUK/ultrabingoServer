<?php

class FetchGamesResponse
{
    public $status;
    public $gameData;

    public function __construct($status,$gameData)
    {
        $this->status = $status;
        $this->gameData = $gameData;
    }
}

?>