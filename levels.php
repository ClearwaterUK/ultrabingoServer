<?php

class LevelInformation
{
    public $levelName;

    public $isAngryLevel;

    public $angryParentBundle; //GUID of the AngryBundleContainer needed to load this level.
    public $angryLevelId;

    public function __construct($levelName,$isAngryLevel=false,$angryParentBundle="",$angryLevelId="")
    {
        $this->levelName = $levelName;
        $this->isAngryLevel = $isAngryLevel;
        $this->angryParentBundle = $angryParentBundle;
        $this->angryLevelId = $angryLevelId;
    }
};

$angryLevels = array(
    new LevelInformation("DARKCASTLE",true,
        "141b1f4fe8e90d24e8af04315eb83f66","grapes.Castle"),
    new LevelInformation("LIMBO'S VAULT",true,
        "84271207136e5b541a530d5fd042723d","GGGamesXDlol.LimbosVault"),
    new LevelInformation("HALLS OF THE EXTREMIST",true,
        "04a3b6ca5f371194e8a981a68057b305","Spelunky.PRELUDE_ENCORE"),
    new LevelInformation("MINI HELL",true,
        "c10731c87bbd7924c8a8244b5cc402f5","megacheb.minihell"),
    new LevelInformation("I WONDER",true,
        "d8ed7e9d694a52a488d588ceab345a26","roundcat.spelunkysandboxsdemo"),
    new LevelInformation("CONVOY CATASTROPHE",true,
        "ca20bbb92f5739944a1ea4863f8f3316","ceo_of_gaming-convoycatastrophe"),
    new LevelInformation("THE TRAINING RANGE",true,
        "54ee185e4e26c1a4c9bc12420fe85c28","frizou.rudejam2.attempt2"),
    new LevelInformation("MOON - SACRED GROUNDS",true,
        "bf1915814f5c0bd48a1b735cf4b3cc75","frizou.paradiso.moonFirst"),
    new LevelInformation("0-R: ASHES TO ASHES",true,
        "727cca746f27c4147b4d4bcc406209de","Smallkloon.PreludeReprise"),

);

$campaignLevels = array(
    new LevelInformation("Level 0-1"),
    new LevelInformation("Level 0-2"),
    new LevelInformation("Level 0-3"),
    new LevelInformation("Level 0-4"),
    new LevelInformation("Level 0-5"),

    new LevelInformation("Level 1-1"),
    new LevelInformation("Level 1-2"),
    new LevelInformation("Level 1-3"),
    new LevelInformation("Level 1-4"),

    new LevelInformation("Level 2-1"),
    new LevelInformation("Level 2-2"),
    new LevelInformation("Level 2-3"),
    new LevelInformation("Level 2-4"),

    new LevelInformation("Level 3-1"),
    new LevelInformation("Level 3-2"),

    new LevelInformation("Level 4-1"),
    new LevelInformation("Level 4-2"),
    new LevelInformation("Level 4-3"),
    new LevelInformation("Level 4-4"),

    new LevelInformation("Level 5-1"),
    new LevelInformation("Level 5-2"),
    new LevelInformation("Level 5-3"),
    new LevelInformation("Level 5-4"),

    new LevelInformation("Level 6-1"),
    new LevelInformation("Level 6-2"),

    new LevelInformation("Level 7-1"),
    new LevelInformation("Level 7-2"),
    new LevelInformation("Level 7-3"),
    new LevelInformation("Level 7-4"),

    new LevelInformation("Level P-1"),
    new LevelInformation("Level P-2"),
);

$levels = array(
    "Level 0-1",
    "Level 0-2",
    "Level 0-3",
    "Level 0-4",
    "Level 0-5",

    "Level 1-1",
    "Level 1-2",
    "Level 1-3",
    "Level 1-4",

    "Level 2-1",
    "Level 2-2",
    "Level 2-3",
    "Level 2-4",

    "Level 3-1",
    "Level 3-2",

    "Level 4-1",
    "Level 4-2",
    "Level 4-3",
    "Level 4-4",

    "Level 5-1",
    "Level 5-2",
    "Level 5-3",
    "Level 5-4",

    "Level 6-1",
    "Level 6-2",

    "Level 7-1",
    "Level 7-2",
    "Level 7-3",
    "Level 7-4",

    "Level P-1",
    "Level P-2"
);


?>