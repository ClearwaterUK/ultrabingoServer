<?php

class DisconnectSignal
{
    public $disconnectCode;
    public $disconnectMessage;
}

class DisconnectNotification
{
    public $username;
    public $steamId;

    public function __construct($username,$steamId)
    {
        $this->username = $username;
        $this->steamId = $steamId;
    }
}