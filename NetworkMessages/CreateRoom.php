<?php

class CreateRoomResponse
{
    public $status;
    public $roomId;

    public $roomDetails;
    public $roomPassword;

    public function __construct($status,$roomId,$roomPassword="",$roomDetails=null)
    {
        $this->status = $status;
        $this->roomId = $roomId;
        $this->roomPassword = $roomPassword;
        $this->roomDetails = $roomDetails;
    }
}
?>