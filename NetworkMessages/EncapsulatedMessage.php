<?php

class EncapsulatedMessage
{
    public $header;
    public $contents;

    public function __construct($header,$message)
    {
        $this->header = $header;
        $this->contents = $message;
    }
}


?>