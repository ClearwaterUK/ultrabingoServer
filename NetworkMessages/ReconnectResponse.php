<?php

class ReconnectResponse
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