<?php

class TimeoutNotification
{
    public $player;
    public $steamId;

    public function __construct($player,$steamId)
    {
        $this->player = $player;
        $this->steamId = $steamId;
    }
}

?>