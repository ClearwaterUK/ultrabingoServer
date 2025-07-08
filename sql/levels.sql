use ultrabingo;

truncate levels;
truncate mapPools;

ALTER TABLE levels AUTO_INCREMENT = 0;
ALTER TABLE mapPools AUTO_INCREMENT = 0;

insert into mapPools(MP_NAME,MP_DESCRIPTION) values
('Campaign','All official campaign levels.\nPrime Sanctums and Encores are available as separate map pools.'),
('Campaign - Prime Sanctums','Prime Sanctums from the official campaign.'),
('Campaign - Encore','Encore levels from the official campaign.'),
('Angry - Starter','A collection of Angry levels suitable for newer players.\nMaps are shorter in length and easier in combat difficulty.'),
('Angry - Hardcore','A collection of Angry levels which are greater in combat difficulty and/or length.\nRecommended for players looking for longer games.'),
('Testing','A collection of maps available for testing.\nMaps in this pool may vary greatly in length & difficulty, and aren''t guaranteed to be added in future.\n\n<color=orange>Use this map pool with the intention of testing & giving feedback.</color>');

insert into levels(L_LEVELNAME, L_LEVELID, L_LEVELISCUSTOM, L_ANGRYBUNDLE, L_MPID) values
    -- Campaign
    ('INTO THE FIRE','Level 0-1',false,'',1),
    ('THE MEATGRINDER','Level 0-2',false,'',1),
    ('DOUBLE DOWN','Level 0-3',false,'',1),
    ('A ONE-MACHINE ARMY','Level 0-4',false,'',1),
    ('CERBERUS','Level 0-5',false,'',1),
    ('HEART OF THE SUNRISE','Level 1-1',false,'',1),
    ('THE BURNING WORLD','Level 1-2',false,'',1),
    ('HALL OF SACRED REMAINS','Level 1-3',false,'',1),
    ('CLAIR DE LUNE','Level 1-4',false,'',1),
    ('BRIDGEBURNER','Level 2-1',false,'',1),
    ('DEATH AT 20,000 VOLTS','Level 2-2',false,'',1),
    ('SHEER HEART ATTACK','Level 2-3',false,'',1),
    ('COURT OF THE CORPSE KING','Level 2-4',false,'',1),
    ('BELLY OF THE BEAST','Level 3-1',false,'',1),
    ('IN THE FLESH','Level 3-2',false,'',1),
    ('SLAVES TO POWER','Level 4-1',false,'',1),
    ('GOD DAMN THE SUN','Level 4-2',false,'',1),
    ('A SHOT IN THE DARK','Level 4-3',false,'',1),
    ('CLAIR DE SOLEIL','Level 4-4',false,'',1),
    ('IN THE WAKE OF POSEIDON','Level 5-1',false,'',1),
    ('WAVES OF THE STARLESS SEA','Level 5-2',false,'',1),
    ('SHIP OF FOOLS','Level 5-3',false,'',1),
    ('LEVIATHAN','Level 5-4',false,'',1),
    ('CRY FOR THE WEEPER','Level 6-1',false,'',1),
    ('AESTHETICS OF HATE','Level 6-2',false,'',1),
    ('GARDEN OF FORKING PARKS','Level 7-1',false,'',1),
    ('LIGHT UP THE NIGHT','Level 7-2',false,'',1),
    ('NO SOUND, NO MEMORY','Level 7-3',false,'',1),
    ('...LIKE ANTENNAS TO HEAVEN','Level 7-4',false,'',1),

    -- Campaign Prime Sanctums
    ('SOUL SURVIVOR','Level P-1',false,'',2),
    ('WAIT OF THE WORLD','Level P-2',false,'',2),

    -- Campaign Encore
    ('THIS HEAT, AN EVIL HEAT','Level 0-E',false,'',3),
    ('...THEN FELL THE ASHES','Level 1-E',false,'',3),

    -- Angry Starter
    ('FUN SIZED HERESY','fnchannel.fsheresy.fireswithinfires',true, 'ae6eeb9c8e3741441986985171f75b56',4),
    ('WALLS OF WICKED DREAMS','robi.heresy.wowd',true, '309b60fc131a95d49921d31c0ec7560f',4),
    ('RE-LUDE','elequacity.relude.heatseeker',true,'97220d9c4569778488734e80e0daa734',4),
    ('A PRIMUM MOBILE','YoullTim.APrimumMobile1',true,'fd4229c3005a73744ab5a4597cfaf75f',4),
    ('EPITAPH','willem1321-epitaph', true, 'a9137dc898362c44593878839cd899a6',4),
    ('V3\'S SHOWDOWN - PHASE 1','t.trinity.v3', true, '91a952cfd5574ef47bf624e09c311260',4),

    -- Angry Hardcore
    ('FREEDOM DIVE','aaaa.aaaaaa.aaaaaeeeeeaaa',true,'f907c5991d40a2a48941d1c1dde860c7',5),
    ('FRAUDULENCE - FOOLS GOLD','Spelunky.FRAUDULENCE_FIRST', true,'033ce9db13ba74d4aa07bdae343d49c2',5),
    ('FRAUDULENCE - HIGH SOCIETY','Spelunky.FRAUDULENCE_SECOND', true,'033ce9db13ba74d4aa07bdae343d49c2',5),
    ('HEART OF THE MACHINE','bobot.hellfacility.hotm', true,'255ce156d5ae53c449106c1a31ed384a',5),
    ('V3\'S SHOWDOWN - PHASE 2','trinity.v3mech', true,'91a952cfd5574ef47bf624e09c311260',5),

    -- Testing
    ('MACHINATION - THE BLASTPIPE','MachinationM-1V2',true,'a1f9bb7c418870d499158f9c2b55731e',6),
    ('OPERETTAS - WHAT COULD HAVE BEEN','tuli.snowlimbo', true,'7b9762c4a51906e4ca36acfcbcdbde3e',6),
    ('TOWER OF STEEL - TOTAL WAR','tos_1', true,'adb0ef3e5cc07c84889dd27f7898af96',6);