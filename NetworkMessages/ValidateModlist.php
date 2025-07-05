<?php
class ValidateModlist
{
    public $nonWhitelistedMods;
    public $latestVersion;
    public $motd;
    public $availableRanks;
    public $canUseChat;

    public function __construct($nonWhitelistedMods,$latestVersion,$motd,$availableRanks,$canUseChat)
    {
        $this->nonWhitelistedMods = $nonWhitelistedMods;
        $this->latestVersion = $latestVersion;
        $this->motd = $motd;
        $this->availableRanks = $availableRanks;
        $this->canUseChat = $canUseChat;
    }
}

?>