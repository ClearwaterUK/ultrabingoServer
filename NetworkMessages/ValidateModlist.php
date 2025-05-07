<?php
class ValidateModlist
{
    public $nonWhitelistedMods;
    public $latestVersion;
    public $motd;
    public $availableRanks;

    public function __construct($nonWhitelistedMods,$latestVersion,$motd,$availableRanks)
    {
        $this->nonWhitelistedMods = $nonWhitelistedMods;
        $this->latestVersion = $latestVersion;
        $this->motd = $motd;
        $this->availableRanks = $availableRanks;
    }
}

?>