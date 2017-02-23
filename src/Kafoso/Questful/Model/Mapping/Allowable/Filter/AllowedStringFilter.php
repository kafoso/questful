<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;

class AllowedStringFilter extends AbstractAllowedStringFilter
{
    public static function getFilterClassNamespace()
    {
        return StringFilter::class;
    }
}
