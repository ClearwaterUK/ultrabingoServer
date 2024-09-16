<?php

$dbc = null;

function initialiseDatabase()
{

    //$dbc = new mysqli($_ENV['DB_ADDRESS'],$_ENV['DB_USER'],$_ENV['DB_PASSWORD'],$_ENV['DB_DATABASE']);

    $dbAddress = $_ENV['DB_ADDRESS'];
    $dbName = $_ENV['DB_DATABASE'];
    $dbUsername = $_ENV['DB_USER'];
    $dbPassword = $_ENV['DB_PASSWORD'];

    try {
        global $dbc;

        $dbc = new PDO("mysql:host=$dbAddress;dbname=$dbName",$dbUsername,$dbPassword);
        $dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo("DB connection up \n");
    }
    catch(PDOException $e)
    {
        echo($e->getMessage()."\n");
        die("DB connection failed\n");
    }
}
initialiseDatabase();

?>