<?php

use Codedungeon\PHPCliColors\Color;

class BingoLogger implements \Psr\Log\LoggerInterface
{
    public function emergency($message, array $context = array()):void
    {
        echo(Color::red() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
    }

    public function critical($message, array $context = array()):void
    {
        echo(Color::red() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
    }

    public function alert($message, array $context = array()):void
    {
        echo(Color::red() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
    }

    public function error($message, array $context = array()):void
    {
        echo(Color::red() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
    }

    public function warning($message, array $context = array()):void
    {
        echo(Color::yellow() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
    }

    public function notice($message, array $context = array()):void
    {
        echo(Color::yellow() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
    }

    public function info($message, array $context = array()):void
    {
        echo(Color::light_blue() . "[" . date("h:i:s A") .  "] ". $message . Color::reset() . "\n");
    }

    public function debug($message, array $context = array()):void
    {
        echo("[" . date("h:i:s A") .  "] ". $message . "\n");
    }

    public function log($level, $message, array $context = array()):void
    {
        switch($level)
        {
            case "emergency":
                $this->emergency($message,$context);
                break;
            case "critical":
                $this->critical($message,$context);
                break;
            case "alert":
                $this->alert($message,$context);
                break;
            case "error":
                $this->error($message,$context);
                break;
            case "warning":
                $this->warning($message,$context);
                break;
            case "notice":
                $this->notice($message,$context);
                break;
            case "info":
                $this->info($message,$context);
                break;
            case "debug":
                $this->debug($message,$context);
                break;
        }
    }
}


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