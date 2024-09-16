<?php

class ClaimedLevelBroadcast
{
    public $username;
    public $team;

    public $levelname;
    public $claimType;

    public $row;
    public $column;

    public function __construct($username,$team,$levelname,$claimType,$row,$column)
    {
        $this->username = $username;
        $this->team = $team;
        $this->levelname = $levelname;
        $this->claimType = $claimType;

        $this->row = $row;
        $this->column = $column;


    }
}

?>