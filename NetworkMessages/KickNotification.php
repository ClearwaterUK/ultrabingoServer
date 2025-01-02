<?php

class KickNotification
{
    public $playerToKick;

    public function __construct($playerToKick)
    {
        $this->playerToKick = $playerToKick;
    }
}

class KickMessage
{
    public function __construct()
    {

    }
}

?>