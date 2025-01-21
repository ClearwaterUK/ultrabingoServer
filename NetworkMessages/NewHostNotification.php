<?php

class NewHostNotification
{
    public $hostUsername;
    public $hostSteamId;

    public function __construct($hostUsername, $hostSteamId)
    {
        $this->hostUsername = $hostUsername;
        $this->hostSteamId = $hostSteamId;
    }
}


?>