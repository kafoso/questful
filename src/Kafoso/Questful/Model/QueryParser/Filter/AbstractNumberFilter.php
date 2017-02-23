<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

abstract class AbstractNumberFilter extends AbstractFilter
{
    public static function getAvailableOperators()
    {
        return ["=", "!=", "<=", ">=", ">", "<"];
    }
}
