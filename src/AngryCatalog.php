<?php

class AngryCatalog
{
    public $catalogURL = "https://raw.githubusercontent.com/eternalUnion/AngryLevels/release/V2/LevelCatalog.json";

    public $catalog;

    public $levelInfo = array();

    public function fetchCatalog()
    {
        $response = file_get_contents($this->catalogURL);

        $this->catalog = json_decode($response, true)["Levels"];

    }

    public function buildlevelParentDictionary()
    {
        foreach($this->catalog as $bundle)
        {
            foreach($bundle['Levels'] as $level)
            {
                $this->levelInfo[$level['LevelId']] = array($level['LevelName'],$level["LevelId"],true,$bundle["Guid"]);
                //logWarn($level['LevelName']);
            }
        }
        //var_export($this->levelInfo);
    }

    public function __construct()
    {
        $this->fetchCatalog();
        $this->buildlevelParentDictionary();
    }
}


