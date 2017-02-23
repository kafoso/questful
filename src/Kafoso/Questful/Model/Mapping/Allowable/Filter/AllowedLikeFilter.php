<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Model\QueryParser\Filter\LikeFilter;

class AllowedLikeFilter extends AbstractAllowedStringFilter
{
    public static function getFilterClassNamespace()
    {
        return LikeFilter::class;
    }
}
