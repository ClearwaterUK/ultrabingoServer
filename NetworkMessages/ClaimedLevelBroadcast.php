<?php

class ClaimedLevelBroadcast
{
    public $username;
    public $team;

    public $levelname;
    public $claimType;

    public $row;
    public $column;

    public $newTimeRequirement;
    public $newStyleRequirement;

    public function __construct($username,$team,$levelname,$claimType,$row,$column,$time,$style)
    {
        $this->username = $username;
        $this->team = $team;
        $this->levelname = $levelname;
        $this->claimType = $claimType;

        $this->row = $row;
        $this->column = $column;

        $this->newTimeRequirement = $time;
        $this->newStyleRequirement = $style;
    }
}

?>