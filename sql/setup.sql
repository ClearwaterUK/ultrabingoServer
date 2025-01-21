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
    R_GAMETYPE int NOT NULL,
    R_DIFFICULTY int NOT NULL,
    R_PRANKREQUIRED BOOLEAN NOT NULL,
    R_DISABLECAMPAIGNALTEXIT BOOLEAN NOT NULL,
    R_HASENDED BOOLEAN NOT NULL

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