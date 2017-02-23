<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedNullFilter extends AbstractAllowedFilter
{
    public static function getAvailableConstraints()
    {
        return [
            Assert\IsNull::class,
        ];
    }

    public static function getFilterClassNamespace()
    {
        return NullFilter::class;
    }
}
