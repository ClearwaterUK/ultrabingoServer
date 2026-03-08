use ultrabingo;

drop table if exists allowedMods;

-- Mod whitelist table
create table allowedMods
(
    id INT PRIMARY KEY AUTO_INCREMENT,
    modId VARCHAR(128),
    modName VARCHAR(255),
    modLink VARCHAR(255) DEFAULT '',
    modHash VARCHAR(255) DEFAULT ''
);

insert into allowedMods (modId,modName,modLink) VALUES
("com.eternalUnion.angryLevelLoader","Angry Level Loader", "https://thunderstore.io/c/ultrakill/p/EternalsTeam/AngryLevelLoader/"),
("clearwater.ultrakillbingo.ultrakillbingo","Baphomet's BINGO", "https://thunderstore.io/c/ultrakill/p/Clearwater/BaphometsBingo/"),
("BetterWeaponHUDs","Better Weapon HUDs", "https://thunderstore.io/c/ultrakill/p/Jade_Harley/Better_Weapon_HUDs/"),
("Hydraxous.ULTRAKILL.Configgy","Configgy","https://thunderstore.io/c/ultrakill/p/Hydraxous/Configgy/"),
("DamageStyleHUD.adry.ultrakill","DamageStyleHUD","https://github.com/MrRaposinha/DamageStyleHUD/"),
("dev.flazhik.handpaint","HandPaint","https://thunderstore.io/c/ultrakill/p/Flazhik/HandPaint/"),
("Healthbars","Healthbars","https://thunderstore.io/c/ultrakill/p/EladNLG/Healthbars/"),
("JadeLib","JadeLib","https://thunderstore.io/c/ultrakill/p/Jade_Harley/JadeLib/"),
("IntroSkip","IntroSkip","https://thunderstore.io/c/ultrakill/p/The0x539/IntroSkip/"),
("com.exmagikguy.ukNewTitles","New Titles","https://thunderstore.io/c/ultrakill/p/notrlguyyah/UK_NewTitles_Rank_Title_Editor/"),
("com.eternalUnion.pluginConfigurator","PluginConfigurator","https://thunderstore.io/c/ultrakill/p/EternalsTeam/PluginConfigurator/"),
("eternalUnion.ultrakill.styleEditor","UltrakillStyleEditor","https://thunderstore.io/c/ultrakill/p/EternalsTeam/UltrakillStyleEditor/"),
("StyleToasts","StyleToasts","https://thunderstore.io/c/ultrakill/p/The0x539/StyleToasts/"),
("com.sinai.unityexplorer","UnityExplorer","https://thunderstore.io/c/ultrakill/p/sinai-dev/UnityExplorer/"),
("dev.zeddevstuff.ustmanager","USTManager","https://thunderstore.io/c/ultrakill/p/ZedDev/USTManager/"),
("duviz.ultrakill.ultraeditor","UltraEditor","https://thunderstore.io/c/ultrakill/p/duviz/UltraEditor")
