<?php

class LevelInformation
{
    public string $levelDisplayName;
    public string $sceneName;

    public string $levelName;

    public $levelType;

    public string|null $angryParentBundle; //If Angry level, GUID of the AngryBundleContainer needed to load this level.

    public string|null $UltraEditorLevelData; //If UltraEditor level, the URL of the level data to dowload from

    public function __construct($levelData)
    {
        $this->levelDisplayName = $levelData['levelName'];
        $this->sceneName = $levelData['levelId'];
        $this->levelName = $levelData['levelName'];
        $this->levelType = $levelData['levelType'];
        $this->angryParentBundle = $levelData['angryBundleId'];
        $this->UltraEditorLevelData = $levelData['UltraEditorLevelData'];
    }
};


?>