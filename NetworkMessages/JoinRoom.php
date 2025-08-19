<?php

class JoinRoomResponse
{
    public $status;
    public $roomId;

    public $roomDetails;

    public $joinMidgameTeam;
    public $joinMidgameTeammates;

    public $joinMidGameDominationTime;

    public $needsTeam;
    public $joinMidGameTeam;

    public function __construct($status,$roomId,$roomDetails=null,$joinMidgameTeam=null,$joinMidgameTeammates=array(),$joinMidGameDominationTime=0,$needsTeam=false,$teamName="")
    {
        $this->status = $status;
        $this->roomId = $roomId;
        $this->roomDetails = $roomDetails;
        $this->joinMidgameTeam = $joinMidgameTeam;
        $this->joinMidgameTeammates = $joinMidgameTeammates;

        $this->joinMidGameDominationTime = $joinMidGameDominationTime;

        $this->needsTeam = $needsTeam;
        $this->joinMidGameTeam = $teamName;
    }
}

class JoinRoomNotification
{
    public $username;
    public $steamId;
    public $rank;

    public function __construct($playerName,$steamId,$rank="")
    {
        $this->username = $playerName;
        $this->steamId = $steamId;
        $this->rank = $rank;
    }
}

?>