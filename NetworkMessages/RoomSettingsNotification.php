<?php

class RoomUpdateNotification
{
    public $updatedSettings;
    public $wereTeamsReset;

    public function __construct($settings, $wereTeamsReset=false)
    {
        $this->updatedSettings = $settings;
        $this->wereTeamsReset = $wereTeamsReset;
    }
}

?>