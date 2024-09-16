<?php

class JoinRoomResponse
{
    public $status;
    public $roomId;

    public $roomDetails;

    public function __construct($status,$roomId,$roomDetails=null)
    {
        $this->status = $status;
        $this->roomId = $roomId;
        $this->roomDetails = $roomDetails;
    }
}

class JoinRoomNotification
{
    public $username;

    public function __construct($playerName)
    {
        $this->username = $playerName;
    }
}

?>