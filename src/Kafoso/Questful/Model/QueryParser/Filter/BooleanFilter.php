<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

class BooleanFilter extends AbstractFilter implements ScalarFilterInterface
{
    public static function getAvailableOperators()
    {
        return ["="];
    }

    public static function getIdentifier()
    {
        return "boolean";
    }

    public static function getValueDataTypeConstraint()
    {
        return "boolean";
    }
}
