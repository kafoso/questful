<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;

class AllowedIntegerFilter extends AbstractAllowedNumericFilter
{
    public static function getFilterClassNamespace()
    {
        return IntegerFilter::class;
    }
}
