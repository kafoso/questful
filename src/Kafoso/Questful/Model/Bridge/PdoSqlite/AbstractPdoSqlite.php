<?php
namespace Kafoso\Questful\Model\Bridge\PdoSqlite;

use Kafoso\Questful\Model\Bridge\AbstractBridge;

abstract class AbstractPdoSqlite extends AbstractBridge
{
    protected $where = null;
    protected $parameters = [];
    protected $orderBy = null;

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            "orderBy" => $this->getOrderBy(),
            "parameters" => $this->getParameters(),
            "where" => $this->getWhere(),
        ];
    }

    /**
     * An SQL string to be used consecutively with "ORDER BY".
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * An array of key-value pairs.
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * An SQL string to be used within a "WHERE" expression.
     * @return string
     */
    public function getWhere()
    {
        return $this->where;
    }
}
