<?php

namespace ReactphpX\Orm;

use Illuminate\Database\Query\Processors\MySqlProcessor as BaseMySqlProcessor;
use Illuminate\Database\Query\Builder;

class MySqlProcessor extends BaseMySqlProcessor
{
    /**
     * Process an  "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $id = $query->getConnection()->insert($sql, $values);

        return is_numeric($id) ? (int) $id : $id;
    }
}