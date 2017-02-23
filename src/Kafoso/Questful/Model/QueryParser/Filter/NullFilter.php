<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

class NullFilter extends AbstractFilter
{
    public static function getAvailableOperators()
    {
        return ["=", "!="];
    }

    public static function getIdentifier()
    {
        return "null";
    }

    public static function getValueDataTypeConstraint()
    {
        return "NULL";
    }
}
