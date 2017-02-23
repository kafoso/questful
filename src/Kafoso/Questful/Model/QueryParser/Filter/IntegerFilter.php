<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

class IntegerFilter extends AbstractNumberFilter implements ScalarFilterInterface
{
    const MATCH_PATTERN = '/^\d+$/';

    public static function getIdentifier()
    {
        return "integer";
    }

    public static function getValueDataTypeConstraint()
    {
        return "integer";
    }
}
