<?php

class StartGameSignal
{
    public $teamColor;
    public $teammates;

    public function __construct($teamColor,$teammates)
    {
        $this->teamColor = $teamColor;
        $this->teammates = $teammates;
    }
}


?>