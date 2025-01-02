<?php
class ValidateModlist
{
    public $status;
    public $latestVersion;

    public function __construct($status,$latestVersion)
    {
        $this->status = $status;
        $this->latestVersion = $latestVersion;
    }
}

?>