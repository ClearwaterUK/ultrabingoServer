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
    array("FUN SIZED HERESY","fnchannel.fsheresy.fireswithinfires", "ae6eeb9c8e3741441986985171f75b56"),
    array("WALLS OF WICKED DREAMS","robi.heresy.wowd", "309b60fc131a95d49921d31c0ec7560f"),
    array("RE-LUDE","elequacity.relude.heatseeker","97220d9c4569778488734e80e0daa734"),
    array("A PRIMUM MOBILE","YoullTim.APrimumMobile1","fd4229c3005a73744ab5a4597cfaf75f"),
    array("EPITAPH","willem1321-epitaph", "a9137dc898362c44593878839cd899a6"),
    array("V3'S SHOWDOWN - PHASE 1","t.trinity.v3", "91a952cfd5574ef47bf624e09c311260")
),true);

$mapPools['angryHardcore'] = generatePool(array(
    array("FREEDOM DIVE","aaaa.aaaaaa.aaaaaeeeeeaaa","f907c5991d40a2a48941d1c1dde860c7"),
    array("FRAUDULENCE - FOOLS GOLD","Spelunky.FRAUDULENCE_FIRST", "033ce9db13ba74d4aa07bdae343d49c2"),
    array("FRAUDULENCE - HIGH SOCIETY","Spelunky.FRAUDULENCE_SECOND", "033ce9db13ba74d4aa07bdae343d49c2"),
    array("HEART OF THE MACHINE","bobot.hellfacility.hotm", "255ce156d5ae53c449106c1a31ed384a"),
    array("V3'S SHOWDOWN - PHASE 2","trinity.v3mech", "91a952cfd5574ef47bf624e09c311260")
),true);

$mapPools['testing'] = generatePool(array(
    array("MACHINATION - THE BLASTPIPE","MachinationM-1V2","a1f9bb7c418870d499158f9c2b55731e"),
    array("OPERETTAS - WHAT COULD HAVE BEEN","tuli.snowlimbo", "7b9762c4a51906e4ca36acfcbcdbde3e"),
    array("TOWER OF STEEL - TOTAL WAR","tos_1", "adb0ef3e5cc07c84889dd27f7898af96"),
),true);


?>