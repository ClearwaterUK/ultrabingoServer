<?php
class ValidateModlist
{
    public $status;
    public $latestVersion;
    public $motd;

    public function __construct($status,$latestVersion,$motd)
    {
        $this->status = $status;
        $this->latestVersion = $latestVersion;
        $this->motd = $motd;
    }
}

?>