<?php
namespace Kafoso\Questful\Model\Bridge\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Kafoso\Questful\Model\Bridge\AbstractBridge;

abstract class AbstractDoctrine extends AbstractBridge
{
    protected $where = null;
    protected $parameters = [];
    protected $orderBy = [];

    abstract public function generateOnQueryBuilder(QueryBuilder $queryBuilder);

    public function toArray()
    {
        return [
            "orderBy" => $this->getOrderBy(),
            "parameters" => $this->getParameters(),
            "where" => $this->getWhere(),
        ];
    }

    /**
     * An array of where the index 0 is the columns and the index 1 is the direction.
     * To be used directly with Doctrine QueryBuilder methods "orderBy" or "addOrderBy".
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * An array of key-value pairs. To be used directly with Doctrine QueryBuilder method "setParameters".
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * A DQL string to be used within a "WHERE" expression. To be used directly with Doctrine QueryBuilder methods
     * "where" or "andWhere".
     * @return string
     */
    public function getWhere()
    {
        return $this->where;
    }
}
