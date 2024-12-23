<?php

use Codedungeon\PHPCliColors\Color;

function logError($message)
{
    echo(Color::red() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
}

function logWarn($message)
{
    echo(Color::yellow() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
}

function logInfo($message)
{
    echo(Color::light_blue() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
}


function logMessage($message)
{
    echo(Color::green() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
}