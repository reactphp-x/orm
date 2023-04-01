<?php

namespace Wpjscc\React\Orm;

class Query
{
    public $sql;
    public $bindings = [];

    public function toSql()
    {
        return $this->sql;
    }

    public function getBindings()
    {
        return $this->bindings;
    }
}