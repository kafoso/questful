<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

class FloatFilter extends AbstractNumberFilter implements ScalarFilterInterface
{
    const MATCH_PATTERN = '/^\d+\.\d+$/';

    public static function getIdentifier()
    {
        return "float";
    }

    public static function getValueDataTypeConstraint()
    {
        return "double";
    }
}
