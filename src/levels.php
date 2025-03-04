<?php

class LevelInformation
{
    public string $levelDisplayName;
    public string $sceneName;

    public string $levelName;

    public bool $isAngryLevel;

    public string $angryParentBundle; //GUID of the AngryBundleContainer needed to load this level.
    public string $angryLevelId;

    public function __construct($levelName, $sceneName, $isAngryLevel=false, $angryParentBundle="")
    {
        $this->levelDisplayName = $levelName;
        $this->sceneName = $sceneName;

        $this->levelName = $levelName;
        $this->isAngryLevel = $isAngryLevel;
        $this->angryParentBundle = ($isAngryLevel ? $angryParentBundle : "");
        $this->angryLevelId = ($isAngryLevel ? $sceneName : "");
    }
};

function generatePool($mapDataArray,$isAngryPool=false)
{
    $arr = array();
    foreach($mapDataArray as $mapData)
    {
        if($isAngryPool)
        {
            array_push($arr,new LevelInformation($mapData[0],$mapData[1],true,$mapData[2]));
        }
        else
        {
            array_push($arr,new LevelInformation($mapData[0],$mapData[1]));
        }
    }
    return $arr;
}

$mapPools = [];

//Campaign
$mapPools['campaign'] = generatePool(array(
    array("INTO THE FIRE","Level 0-1"),
    array("THE MEATGRINDER","Level 0-2"),
    array("DOUBLE DOWN","Level 0-3"),
    array("A ONE-MACHINE ARMY","Level 0-4"),
    array("CERBERUS","Level 0-5"),
    array("HEART OF THE SUNRISE","Level 1-1"),
    array("THE BURNING WORLD","Level 1-2"),
    array("HALL OF SACRED REMAINS","Level 1-3"),
    array("CLAIR DE LUNE","Level 1-4"),
    array("BRIDGEBURNER","Level 2-1"),
    array("DEATH AT 20,000 VOLTS","Level 2-2"),
    array("SHEER HEART ATTACK","Level 2-3"),
    array("COURT OF THE CORPSE KING","Level 2-4"),
    array("BELLY OF THE BEAST","Level 3-1"),
    array("IN THE FLESH","Level 3-2"),
    array("SLAVES TO POWER","Level 4-1"),
    array("GOD DAMN THE SUN","Level 4-2"),
    array("A SHOT IN THE DARK","Level 4-3"),
    array("CLAIR DE SOLEIL","Level 4-4"),
    array("IN THE WAKE OF POSEIDON","Level 5-1"),
    array("WAVES OF THE STARLESS SEA","Level 5-2"),
    array("SHIP OF FOOLS","Level 5-3"),
    array("LEVIATHAN","Level 5-4"),
    array("CRY FOR THE WEEPER","Level 6-1"),
    array("AESTHETICS OF HATE","Level 6-2"),
    array("GARDEN OF FORKING PARKS","Level 7-1"),
    array("LIGHT UP THE NIGHT","Level 7-2"),
    array("NO SOUND, NO MEMORY","Level 7-3"),
    array("...LIKE ANTENNAS TO HEAVEN","Level 7-4"),
));

// Prime Sanctums
$mapPools['primeSanctums'] = generatePool(array(
    array("SOUL SURVIVOR","Level P-1"),
    array("WAIT OF THE WORLD","Level P-2")
));

// Encore
$mapPools['encore'] = generatePool(array(
    array("THIS HEAT, AN EVIL HEAT","Level 0-E"),
    array("...THEN FELL THE ASHES","Level 1-E")
));

// Angry Standard
$mapPools['angryStandard'] = generatePool(array(
    array("DARKCASTLE","grapes.Castle","141b1f4fe8e90d24e8af04315eb83f66"),
    array("LIMBO'S VAULT","GGGamesXDlol.LimbosVault","84271207136e5b541a530d5fd042723d"),
    array("HALLS OF THE EXTREMIST","Spelunky.PRELUDE_ENCORE", "04a3b6ca5f371194e8a981a68057b305"),
    array("PRIMORDIAL HUNGER","william1321.primordialHunger", "5c8005296a43d9e42b7956a5f2744bf0"),
    array("BLOOD RUSH","dinky.bloodrush", "2e0a2834e182e6c4f9ab282ab83ef7c8"),
    array("SPELUNKY'S SANDBOX","roundcat.spelunkysandboxsdemo", "d8ed7e9d694a52a488d588ceab345a26"),
    array("CONVOY CATASTROPHE","ceo_of_gaming-convoycatastrophe", "ca20bbb92f5739944a1ea4863f8f3316"),
    array("THE TRAINING RANGE","frizou.rudejam2.attempt2", "54ee185e4e26c1a4c9bc12420fe85c28"),
    array("PARADISO - SACRED GROUNDS","frizou.paradiso.moonFirst", "bf1915814f5c0bd48a1b735cf4b3cc75"),
    array("WHITEBOX - BLANK SLATE","Smallkloon.whitebox", "d90ddfc949d091240a9ca886bc7785c2"),
    //Intentional space in Cania ID
    array("CANIA - THE LONE AND LEVEL SANDS","SmallSpelunky.CANIA_DIAPHONY ", "4837f9ca4a704014cb078b0a427dc546"),
    array("ALTWRATH - DEPTHS OF DESPAIR","draghtnim-altwrath1", "dc7d05beb4afba646b07078325fd9385"),
    array("MINECRAFT - MINE ODDITY","ceo_of_gaming.overworld.1", "0678443fd37076d418830d6af625e54b"),
    array("MINECRAFT - TAKE BACK THE NIGHT","ceo_of_gaming.overworld.2", "0678443fd37076d418830d6af625e54b"),
    array("MINECRAFT - NETHER REACHES","ceo_of_gaming.minecraft.nether", "0678443fd37076d418830d6af625e54b"),
    array("JOURNEY TO LAVANDRIA","teamdoodz.journeyToLavandaria", "598a033295097634cbad50651203c998"),
    array("WHAT LIES BENEATH","Woosp.TheBeginningOfEternity","598a033295097634cbad50651203c998"),
    array("GARDEN OF LOST DESIRES","Garden of lost desires", "00891f35080eebb41bd66b58ba2a3374"),
    array("SPECIAL DELIVERY","octo-special delivery", "ee29489432630961fc217"),
    array("EPITAPH","willem1321-epitaph", "a9137dc898362c44593878839cd899a6"),
    array("FUN SIZED HERESY","fnchannel.fsheresy.fireswithinfires", "ae6eeb9c8e3741441986985171f75b56"),
    array("REVOLT FROM THE ABYSS","robi-revolt from the abyss", "5022b89edd299f34685d89cb743ef2ef"),
    array("INFERNO - RUBBLE IN THE SKY","pixelpower.layer.i1", "e8607aa1b4abd4d4796c60e3c7dfbcb6"),
    array("DESCENT INTO EXILE","vvenvss.descentintoexile.descentintoexile", "b36df525a2569f04a803ccc921595ffe"),
    array("CULT OF DOPEFISH","willem1321.cultofdopefish", "e98fa83cf53614347ae81fcba72767de"),
    array("SINS OF MANKIND","sins.of_mankind.sam066", "818e61704523df04289de827015d778b"),
    array("IMMINENT COLLAPSE OF THE PAST","GGGamesXDlol.RudeJam2", "12db525d3ca45174fbb58fa9d620b112"),
    array("RE-WRATH - A MAN MADE SEA","funny.rewrath.level2", "d916f350fc8b1b64e91567180610db04"),
    array("ABANDONED IN PLACE","willem1321-abandonedinplace", "c5f273cc16751b64ea9c0a9b47b7ff82"),
    array("DEATH AROUND THE CORNER","lucy.space.deathAroundTheCorner", "87474b9c91deb124d86d323ab9db59c6")
),true);

// Angry Hardcore
$mapPools['angryHardcore'] = generatePool(array(
    array("CLIFFS OF WRATH","dubswrathlevel", "787851caafb9988439553a59e974f049"),
    array("FROM DUST TO DUST","gleedsecond", "e47e1a5bdb23e7143b03d70d9f511e7f"),
    array("REPRISE - ASHES TO ASHES","Smallkloon.PreludeReprise", "727cca746f27c4147b4d4bcc406209de"),
    array("REPRISE - A DREAM OF HOME","Smallkloon.LimboReprise", "727cca746f27c4147b4d4bcc406209de"),
    array("ALTWRATH - GEARS OF THE DROWNED","draghtnim-altwrath2", "dc7d05beb4afba646b07078325fd9385"),
    array("FRAUDULENCE - FOOLS GOLD","Spelunky.FRAUDULENCE_FIRST", "033ce9db13ba74d4aa07bdae343d49c2"),
    array("FRAUDULENCE - HIGH SOCIETY","Spelunky.FRAUDULENCE_SECOND", "033ce9db13ba74d4aa07bdae343d49c2"),
    array("MINI HELL","megacheb.minihell", "c10731c87bbd7924c8a8244b5cc402f5"),
    array("AND THE CROWD GOES WILD","Rude.Jam.cool.level", "e11a91fdcdd962245910c68dc25debea"),
    array("THE TIMESPLICER","RedNova.Willem.Collab", "8aa2fc0fb7912c44ba021571926f273b"),
    array("HEART OF THE MACHINE","bobot.hellfacility.hotm", "255ce156d5ae53c449106c1a31ed384a"),
    array("BLOODY TEARS","riko.uk.bloodytears", "6d50b188a4aa7164f8c49df7607eb274"),
    array("CASTLE ON THE HILL","holaSmallkloon.movingcastle.castleonthehill", "5f7897e6b97ec5449b4f0e4dc35f9e9f"),
    array("MINESHAFTS - INTO THE ABYSS","aggravateexe-IntoTheAbyss", "5615e26489d1cd746bb8f6103db7c7c2"),
    array("MINESHAFTS - RESONATING RESENTMENT","aggravateexe-ResonatingResentment", "5615e26489d1cd746bb8f6103db7c7c2"),
    array("FRAUD - HIGHER THAN THE BLACK SKY","82.Fraud.HigherTTBS", "9f26a2c3efa7ced4c94c5b5ded8fcd04"),
    array("WHERE THE STREETS HAVE NO NAME","pkpseudo-nonamestreets", "92728cc4176b1b842a754d08bbc5b23c")
),true);

// Project Purgatorio
$mapPools['purgatorio'] = generatePool(array(
    array("RUBICON - CARCASS","remphase.hydraxous.rubicon.first", "c787a9514436db941be69ef24af53010"),
    array("RUBICON - BOTTOMLESS PIT","remphase.hydraxous.rubicon.second", "c787a9514436db941be69ef24af53010"),
    array("ENVY - LOST FIELDS OF GOLD","RaifuLostFieldsenvyb", "515486d54abac2a4694e34cb1b31d18f"),
    array("ENVY - PALACE OF BLACK SPIRES","raifuenvypalaceforbundle", "515486d54abac2a4694e34cb1b31d18f"),
    array("ENVY - CASTLE MANIAC","RaifuCastleManiacTheReal", "515486d54abac2a4694e34cb1b31d18f"),
    array("ENVY - EVERY STAR IN THE SKY","RaifuEveryStarInTheSkyReal", "515486d54abac2a4694e34cb1b31d18f"),
    array("INDULGENCE - THE DEATH OF PARADIGM","mag.indulgence.thedeathofparadigm", "31cdd0834c3ac504c8313dcb76ec6545")
),true);

//Shitfest
$mapPools['shitfest'] = generatePool(array(
    array("FALSE ASCENDENCE 2","phantom.falsefdsafgsgsrga.adgsdf", "495300b85838d9246aad4d9f6c6393bc"),
    array("NOVA KAIZO - TALLADEGA","RedNova_NovaKaizo1", "f38753c95f382ad40b672e07f73ed298"),
    //Intentional space in this id, also what the fuck is this id name lmao
    array("SPECIAL CHRISTMAS GIFT","DavieJay2011.ChristmasGifttotheChristmasUltra-killCommunity.....SohappeytofinallybebackhomeForchristmasGiftswinter ", "868d6d05d8121b04981b9941ae32229f"),
    array("ROTAT E - DIZZY DILEMMA","fruitc.rotate1", "9837fa93d8761d84385f003ce997fe46"),
    array("ROTAT E - SPINNING STUPIDITY","fruitc.rotate2","9837fa93d8761d84385f003ce997fe46"),
),true);

?>