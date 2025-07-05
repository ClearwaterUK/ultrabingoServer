<?php

class ChatMessage
{
    public $status;
    public $username;
    public $message;
    public $channelType;

    public function __construct($username,$message,$channelType)
    {
        $this->username = $username;
        $this->message = $message;
        $this->channelType = $channelType;
    }
}

class ChatWarn
{
    public $warnLevel;

    public function __construct($warnLevel)
    {
        $this->warnLevel = $warnLevel;
    }
}

?>