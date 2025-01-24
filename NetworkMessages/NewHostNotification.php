<?php

class NewHostNotification
{
    public $oldHost;
    public $hostUsername;
    public $hostSteamId;

    public function __construct($oldHost,$hostUsername, $hostSteamId)
    {
        $this->oldHost = $oldHost;
        $this->hostUsername = $hostUsername;
        $this->hostSteamId = $hostSteamId;
    }
}


?>