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

$mapPools = [];

//Campaign
$mapPools['campaign'] = array(
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

);

// Prime Sanctums
$mapPools['primeSanctums'] = array(
    new LevelInformation("SOUL SURVIVOR","Level P-1"),
    new LevelInformation("WAIT OF THE WORLD","Level P-2")
);

// Angry Standard
$mapPools['angryStandard'] = array(
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
    new LevelInformation("WHITEBOX - BLANK SLATE","Smallkloon.whitebox",true,
        "d90ddfc949d091240a9ca886bc7785c2","Smallkloon.whitebox"),
    //Intentional space for Cania ID, don't remove!
    new LevelInformation("CANIA - THE LONE AND LEVEL SANDS","SmallSpelunky.CANIA_DIAPHONY ",true,
        "4837f9ca4a704014cb078b0a427dc546","SmallSpelunky.CANIA_DIAPHONY "),
    new LevelInformation("ALTWRATH - DEPTHS OF DESPAIR","draghtnim-altwrath1",true,
        "dc7d05beb4afba646b07078325fd9385","draghtnim-altwrath1"),
    new LevelInformation("MINECRAFT - MINE ODDITY","ceo_of_gaming.overworld.1",true,
        "0678443fd37076d418830d6af625e54b","ceo_of_gaming.overworld.1"),
    new LevelInformation("MINECRAFT - TAKE BACK THE NIGHT","ceo_of_gami ng.overworld.2",true,
        "0678443fd37076d418830d6af625e54b","ceo_of_gaming.overworld.2"),
    new LevelInformation("MINECRAFT - NETHER REACHES","ceo_of_gaming.minecraft.nether",true,
        "0678443fd37076d418830d6af625e54b","ceo_of_gaming.minecraft.nether"),
    new LevelInformation("JOURNEY TO LAVANDRIA","teamdoodz.journeyToLavandaria",true,
        "598a033295097634cbad50651203c998","teamdoodz.journeyToLavandaria"),
    new LevelInformation("WHAT LIES BENEATH","Woosp.TheBeginningOfEternity",true,
        "0e19d59986aedcc4bae950d32382c28a","Woosp.TheBeginningOfEternity"),
    new LevelInformation("GARDEN OF LOST DESIRES","Garden of lost desires",true,
        "00891f35080eebb41bd66b58ba2a3374","Garden of lost desires"),
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

// Angry Hardcore
$mapPools['angryHardcore'] = array(
    new LevelInformation("CLIFFS OF WRATH","dubswrathlevel",true,
        "787851caafb9988439553a59e974f049","dubswrathlevel"),
    new LevelInformation("FROM DUST TO DUST","gleedsecond",true,
        "e47e1a5bdb23e7143b03d70d9f511e7f","gleedsecond"),
    new LevelInformation("REPRISE - ASHES TO ASHES","Smallkloon.PreludeReprise",true,
        "727cca746f27c4147b4d4bcc406209de","Smallkloon.PreludeReprise"),
    new LevelInformation("REPRISE - A DREAM OF HOME","Smallkloon.LimboReprise",true,
        "727cca746f27c4147b4d4bcc406209de","Smallkloon.LimboReprise"),
    new LevelInformation("ALTWRATH - GEARS OF THE DROWNED","draghtnim-altwrath2",true,
        "dc7d05beb4afba646b07078325fd9385","draghtnim-altwrath2"),
    new LevelInformation("FRAUDULENCE - FOOLS GOLD","Spelunky.FRAUDULENCE_FIRST",true,
        "033ce9db13ba74d4aa07bdae343d49c2","Spelunky.FRAUDULENCE_FIRST"),
    new LevelInformation("FRAUDULENCE - HIGH SOCIETY","Spelunky.FRAUDULENCE_SECOND",true,
        "033ce9db13ba74d4aa07bdae343d49c2","Spelunky.FRAUDULENCE_SECOND"),
    new LevelInformation("MINI HELL","megacheb.minihell",true,
        "c10731c87bbd7924c8a8244b5cc402f5","megacheb.minihell"),
    new LevelInformation("ENVY - LOST FIELDS OF GOLD","RaifuLostFieldsenvyb",true,
        "515486d54abac2a4694e34cb1b31d18f","RaifuLostFieldsenvyb"),
    new LevelInformation("ENVY - CASTLE MANIAC","RaifuCastleManiacTheReal",true,
        "515486d54abac2a4694e34cb1b31d18f","RaifuCastleManiacTheReal"),
    new LevelInformation("ENVY - EVERY STAR IN THE SKY","RaifuEveryStarInTheSkyReal",true,
        "515486d54abac2a4694e34cb1b31d18f","RaifuEveryStarInTheSkyReal"),
    new LevelInformation("AND THE CROWD GOES WILD","Rude.Jam.cool.level",true,
        "e11a91fdcdd962245910c68dc25debea","Rude.Jam.cool.level"),
    new LevelInformation("THE TIMESPLICER","RedNova.Willem.Collab",true,
        "8aa2fc0fb7912c44ba021571926f273b","RedNova.Willem.Collab"),

);


?>