<?php

class KickNotification
{
    public $playerToKick;
    public $steamId;

    public function __construct($playerToKick,$steamId)
    {
        $this->playerToKick = $playerToKick;
        $this->steamId = $steamId;
    }
}

class KickMessage
{
    public function __construct()
    {

    }
}

?>