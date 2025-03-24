<?php
class ValidateModlist
{
    public $status;
    public $latestVersion;
    public $motd;
    public $availableRanks;

    public function __construct($status,$latestVersion,$motd,$availableRanks)
    {
        $this->status = $status;
        $this->latestVersion = $latestVersion;
        $this->motd = $motd;
        $this->availableRanks = $availableRanks;
    }
}

?>