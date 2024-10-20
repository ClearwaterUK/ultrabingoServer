<?php

class LevelInformation
{
    public string $levelDisplayName;
    public string $sceneName;

    public string $levelName;

    public bool $isAngryLevel;

    public string $angryParentBundle; //GUID of the AngryBundleContainer needed to load this level.
    public string $angryLevelId;

    public function __construct($levelName, $sceneName, $isAngryLevel=false, $angryParentBundle="", $angryLevelId="")
    {
        $this->levelDisplayName = $levelName;
        $this->sceneName = $sceneName;

        $this->levelName = $levelName;
        $this->isAngryLevel = $isAngryLevel;
        $this->angryParentBundle = $angryParentBundle;
        $this->angryLevelId = $angryLevelId;
    }
};

$angryLevels = array(
    new LevelInformation("DARKCASTLE","grapes.Castle",true,
        "141b1f4fe8e90d24e8af04315eb83f66","grapes.Castle"),
    new LevelInformation("LIMBO'S VAULT","GGGamesXDlol.LimbosVault",true,
        "84271207136e5b541a530d5fd042723d","GGGamesXDlol.LimbosVault"),
    new LevelInformation("HALLS OF THE EXTREMIST","Spelunky.PRELUDE_ENCORE",true,
        "04a3b6ca5f371194e8a981a68057b305","Spelunky.PRELUDE_ENCORE"),
    new LevelInformation("PRIMORDIAL HUNGER","william1321.primordialHunger",true,
        "5c8005296a43d9e42b7956a5f2744bf0", "william1321.primordialHunger"),
    new LevelInformation("BLOOD RUSH","dinky.bloodrush",true,
        "2e0a2834e182e6c4f9ab282ab83ef7c8", "dinky.bloodrush"),
    new LevelInformation("SPELUNKY'S SANDBOX","roundcat.spelunkysandboxsdemo",true,
        "d8ed7e9d694a52a488d588ceab345a26", "roundcat.spelunkysandboxsdemo"),
    new LevelInformation("CONVOY CATASTROPHE","ceo_of_gaming-convoycatastrophe",true,
        "ca20bbb92f5739944a1ea4863f8f3316","ceo_of_gaming-convoycatastrophe"),
    new LevelInformation("THE TRAINING RANGE","frizou.rudejam2.attempt2",true,
        "54ee185e4e26c1a4c9bc12420fe85c28","frizou.rudejam2.attempt2"),
    new LevelInformation("PARADISO - SACRED GROUNDS","frizou.paradiso.moonFirst",true,
        "bf1915814f5c0bd48a1b735cf4b3cc75","frizou.paradiso.moonFirst"),
    new LevelInformation("WHITEBOX: BLANK SLATE","Smallkloon.whitebox",true,
        "d90ddfc949d091240a9ca886bc7785c2","Smallkloon.whitebox"),
    new LevelInformation("CAVIA: THE LONE AND LEVEL SANDS","SmallSpelunky.CANIA_DIAPHONY",true,
        "4837f9ca4a704014cb078b0a427dc546","SmallSpelunky.CANIA_DIAPHONY"),
    new LevelInformation("DEPTHS OF DESPAIR","draghtnim-altwrath1",true,
        "dc7d05beb4afba646b07078325fd9385","draghtnim-altwrath1"),
    new LevelInformation("ALTGREED: ENEMY OF THE SUN","fnchannel.altgreed.enemyofsun",true,
        "03f5dc1f43772c345a05b2d5fb758462","fnchannel.altgreed.enemyofsun"),
    new LevelInformation("MINECRAFT: MINE ODDITY","ceo_of_gaming.overworld.1",true,
        "0678443fd37076d418830d6af625e54b","ceo_of_gaming.overworld.1"),
    new LevelInformation("MINECRAFT: TAKE BACK THE NIGHT","ceo_of_gami ng.overworld.2",true,
        "0678443fd37076d418830d6af625e54b","ceo_of_gaming.overworld.2"),
    new LevelInformation("MINECRAFT: NETHER REACHES","ceo_of_gaming.minecraft.nether",true,
        "0678443fd37076d418830d6af625e54b","ceo_of_gaming.minecraft.nether"),
    new LevelInformation("JOURNEY TO LAVANDRIA","teamdoodz.journeyToLavandaria",true,
        "598a033295097634cbad50651203c998","teamdoodz.journeyToLavandaria"),
    new LevelInformation("WHAT LIES BENEATH","Woosp.TheBeginningOfEternity",true,
        "0e19d59986aedcc4bae950d32382c28a","Woosp.TheBeginningOfEternity"),
    new LevelInformation("GARDEN OF LOST DESIRES","Garden of lost desires",true,
        "00891f35080eebb41bd66b58ba2a3374","Garden of lost desires"),
    new LevelInformation("CLIFFS OF WRATH","dubswrathlevel",true,
        "787851caafb9988439553a59e974f049","dubswrathlevel"),
    new LevelInformation("SPECIAL DELIVERY","octo-special delivery",true,
        "2d099b87cc7ee29489432630961fc217","octo-special delivery"),
    new LevelInformation("REQUIEM","lazy.lust.requiem",true,
        "0226026e4545b77408cb64ee23f2d163","lazy.lust.requiem"),
    new LevelInformation("EPITAPH","willem1321-epitaph",true,
        "a9137dc898362c44593878839cd899a6","willem1321-epitaph"),
    new LevelInformation("FUN SIZED HERESY","fnchannel.fsheresy.fireswithinfires",true,
        "ae6eeb9c8e3741441986985171f75b56","fnchannel.fsheresy.fireswithinfires"),
    new LevelInformation("REVOLT FROM THE ABYSS","robi-revolt from the abyss",true,
            "5022b89edd299f34685d89cb743ef2ef","robi-revolt from the abyss")
);

$campaignLevels = array(
    new LevelInformation("INTO THE FIRE","Level 0-1"),
    new LevelInformation("THE MEATGRINDER","Level 0-2"),
    new LevelInformation("DOUBLE DOWN","Level 0-3"),
    new LevelInformation("A ONE-MACHINE ARMY","Level 0-4"),
    new LevelInformation("CERBERUS","Level 0-5"),

    new LevelInformation("HEART OF THE SUNRISE","Level 1-1"),
    new LevelInformation("THE BURNING WORLD","Level 1-2"),
    new LevelInformation("HALL OF SACRED REMAINS","Level 1-3"),
    new LevelInformation("CLAIR DE LUNE","Level 1-4"),

    new LevelInformation("BRIDGEBURNER","Level 2-1"),
    new LevelInformation("DEATH AT 20,000 VOLTS","Level 2-2"),
    new LevelInformation("SHEER HEART ATTACK","Level 2-3"),
    new LevelInformation("COURT OF THE CORPSE KING","Level 2-4"),

    new LevelInformation("BELLY OF THE BEAST","Level 3-1"),
    new LevelInformation("IN THE FLESH","Level 3-2"),

    new LevelInformation("SLAVES TO POWER","Level 4-1"),
    new LevelInformation("GOD DAMN THE SUN","Level 4-2"),
    new LevelInformation("A SHOT IN THE DARK","Level 4-3"),
    new LevelInformation("CLAIR DE SOLEIL","Level 4-4"),

    new LevelInformation("IN THE WAKE OF POSEIDON","Level 5-1"),
    new LevelInformation("WAVES OF THE STARLESS SEA","Level 5-2"),
    new LevelInformation("SHIP OF FOOLS","Level 5-3"),
    new LevelInformation("LEVIATHAN","Level 5-4"),

    new LevelInformation("CRY FOR THE WEEPER","Level 6-1"),
    new LevelInformation("AESTHETICS OF HATE","Level 6-2"),

    new LevelInformation("GARDEN OF FORKING PARKS","Level 7-1"),
    new LevelInformation("LIGHT UP THE NIGHT","Level 7-2"),
    new LevelInformation("SUFFERING LEAVES SUFFERING LEAVES","Level 7-3"),
    new LevelInformation("...LIKE ANTENNAS TO HEAVEN","Level 7-4"),

    new LevelInformation("SOUL SURVIVOR","Level P-1"),
    new LevelInformation("WEIGHT OF THE WORLD","Level P-2"),
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

$campaignLevelsCount = count($campaignLevels);
$angryLevelsCount = count($angryLevels);
$totalLevelsCount = $campaignLevelsCount+$angryLevelsCount;

echo("Loading ".$campaignLevelsCount." campaign levels and ".$angryLevelsCount." Angry levels (".$totalLevelsCount." total levels)\n");

?>