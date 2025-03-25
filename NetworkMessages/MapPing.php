<?php

class MapPingNotification
{
    public $row;
    public $column;

    public function __construct($row,$column)
    {
        $this->row = $row;
        $this->column = $column;
    }
}

?>