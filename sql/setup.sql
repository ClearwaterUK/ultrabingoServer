-- Global config
SET foreign_key_checks = 0;

-- Add user
DROP USER IF EXISTS 'ultrabingoUser'@'localhost';
CREATE USER 'ultrabingoUser'@'localhost';
GRANT PROCESS, SELECT, ALTER, DELETE, INSERT, UPDATE, LOCK TABLES ON *.* TO 'ultrabingoUser'@'localhost';
SET PASSWORD FOR 'ultrabingoUser'@'localhost' = 'ultrabingo';

-- Full reset of DB
drop database if exists ultrabingo;
create database if not exists ultrabingo;
use ultrabingo;

-- Table creation
create table currentGames
(
    R_ID int NOT NULL AUTO_INCREMENT primary key,

    R_PASSWORD varchar(255),
    R_HOSTEDBY varchar(32),
    R_CURRENTPLAYERS int NOT NULL,
    R_HASSTARTED BOOLEAN NOT NULL,

    R_MAXPLAYERS int NOT NULL,
    R_MAXTEAMS int NOT NULL,
    R_GRIDSIZE int NOT NULL,
    R_GAMETYPE int NOT NULL,
    R_DIFFICULTY int NOT NULL,
    R_LEVELROTATION int NOT NULL,
    R_PRANKREQUIRED BOOLEAN NOT NULL

);