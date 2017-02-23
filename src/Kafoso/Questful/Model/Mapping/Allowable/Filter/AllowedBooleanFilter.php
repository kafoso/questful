<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Model\QueryParser\Filter\BooleanFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedBooleanFilter extends AbstractAllowedFilter
{
    public static function getAvailableConstraints()
    {
        return [
            Assert\Callback::class,
            Assert\IsFalse::class,
            Assert\IsTrue::class,
        ];
    }

    public static function getFilterClassNamespace()
    {
        return BooleanFilter::class;
    }
}
