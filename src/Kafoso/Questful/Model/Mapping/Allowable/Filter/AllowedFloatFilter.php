<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Model\QueryParser\Filter\FloatFilter;

class AllowedFloatFilter extends AbstractAllowedNumericFilter
{
    public static function getFilterClassNamespace()
    {
        return FloatFilter::class;
    }
}
