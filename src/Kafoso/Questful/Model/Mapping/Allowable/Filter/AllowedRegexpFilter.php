<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Model\QueryParser\Filter\RegexpFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedRegexpFilter extends AbstractAllowedFilter
{
    public static function getAvailableConstraints()
    {
        return [
            // Comparison Constraints
            Assert\EqualTo::class,
            Assert\NotEqualTo::class,
            Assert\IdenticalTo::class,
            Assert\NotIdenticalTo::class,
            Assert\LessThan::class,
            Assert\LessThanOrEqual::class,
            Assert\GreaterThan::class,
            Assert\GreaterThanOrEqual::class,
            // String Constraints
            Assert\Length::class,
            Assert\Regex::class,
            // Other Constraints
            Assert\Callback::class,
        ];
    }

    public static function getFilterClassNamespace()
    {
        return RegexpFilter::class;
    }
}
