-- Global config
SET foreign_key_checks = 0;

-- Add user
DROP USER IF EXISTS 'ultrabingoUser'@'localhost';
CREATE USER 'ultrabingoUser'@'localhost';
GRANT PROCESS, SELECT, ALTER, DELETE, INSERT, UPDATE, DROP, LOCK TABLES ON *.* TO 'ultrabingoUser'@'localhost';
SET PASSWORD FOR 'ultrabingoUser'@'localhost' = 'ultrabingo';

-- Full reset of DB
drop database if exists ultrabingo;
create database if not exists ultrabingo;
use ultrabingo;

-- Table creation
create table currentGames
(
    R_ID int NOT NULL AUTO_INCREMENT primary key,
    R_PASSWORD varchar(6),

    R_HOSTEDBY varchar(64),
    R_CURRENTPLAYERS int NOT NULL,
    R_HASSTARTED BOOLEAN NOT NULL,

    R_MAXPLAYERS int NOT NULL,
    R_MAXTEAMS int NOT NULL,
    R_TEAMCOMPOSITION int NOT NULL,
    R_JOINABLE BOOLEAN NOT NULL,
    R_GRIDSIZE int NOT NULL,
    R_GAMEMODE int NOT NULL DEFAULT 0,
    R_GAMETYPE int NOT NULL DEFAULT 0, -- Todo: Remove this in 1.1
    R_ALLOWREJOIN int NOT NULL DEFAULT 0,
    R_DIFFICULTY int NOT NULL,
    R_PRANKREQUIRED BOOLEAN NOT NULL,
    R_DISABLECAMPAIGNALTEXIT BOOLEAN NOT NULL,
    R_MODIFIER int NOT NULL DEFAULT 0,
    R_ISPUBLIC int NOT NULL DEFAULT 0,
    R_HASENDED BOOLEAN NOT NULL DEFAULT 0

);

create table activeConnections
(
    C_ID int NOT NULL AUTO_INCREMENT primary key,
    C_CONNECTION_HASH varchar(64),
    C_TICKET varchar(255),
    C_STEAMID varchar(64),
    C_USERNAME varchar(64),
    C_ROOMID int,
    C_ISHOST BOOLEAN NOT NULL
);

create table kickedPlayers
(
    K_ID int NOT NULL AUTO_INCREMENT primary key,
    K_STEAMID varchar(64),
    K_ROOMID int
);

create table bannedPlayers
(
    B_ID int NOT NULL AUTO_INCREMENT primary key,
    B_STEAMID varchar(64),
    B_IP varchar(32)
);

create table ranks
(
    R_ID int NOT NULL AUTO_INCREMENT primary key,
    R_RANKNAME varchar(64)
);

insert into ranks(R_RANKNAME) values
    ('<color=#e74c3c>DEVELOPER</color>'),
    ('<color=#f1c40f>TESTER</color>'),
    ('<color=green>DONATOR</color>'),
    ('<color=#35a8ff>LEGACY MAPPER</color>'),
    ('<color=#35a8ff>MAPPER</color>'),
    ('<color=#ffce84>MODDER</color>'),
    ('<color=#aea8ff>SPEEDRUNNER</color>'),
    ('<color=red>NEW BLOOD</color>');

create table userRanks
(
    U_ID int NOT NULL AUTO_INCREMENT primary key,
    U_STEAMID varchar(64),
    U_RANKID int NOT NULL,
    FOREIGN KEY (U_RANKID) REFERENCES ranks(R_ID)
);

source ./ranks.sql;

create table chatBlock
(
    B_ID int NOT NULL AUTO_INCREMENT primary key,
    B_STEAMID varchar(64),
    B_WARNLEVEL tinyint -- 1 = warn, 2 == final warn, 3 == barred from chat use
);

create table mapPools
(
    MP_ID int NOT NULL AUTO_INCREMENT primary key,
    MP_NAME varchar(64),
    MP_DESCRIPTION varchar(512)
);

create table levels
(
    L_ID int NOT NULL AUTO_INCREMENT primary key,
    L_LEVELNAME varchar(256),
    L_LEVELID varchar(256),
    L_LEVELISCUSTOM BOOLEAN NOT NULL DEFAULT 0,
    L_ANGRYBUNDLE varchar(256),
    L_MPID int NOT NULL,
    FOREIGN KEY (L_MPID) REFERENCES mapPools(MP_ID)
);

source ./levels.sql;