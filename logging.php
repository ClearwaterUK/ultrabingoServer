<?php

use Codedungeon\PHPCliColors\Color;

function logError($message)
{
    echo(Color::red() . $message . Color::reset() . "\n");
}

function logWarn($message)
{
    echo(Color::yellow() . $message . Color::reset() . "\n");
}

function logInfo($message)
{
    echo(Color::light_blue() . $message . Color::reset() . "\n");
}


function logMessage($message)
{
    echo(Color::green() . $message . Color::reset() . "\n");
}