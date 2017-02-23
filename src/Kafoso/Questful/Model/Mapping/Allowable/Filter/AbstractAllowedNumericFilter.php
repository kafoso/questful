<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractAllowedNumericFilter extends AbstractAllowedFilter
{
    public static function getAvailableConstraints()
    {
        return [
            // Number Constraints
            Assert\Range::class,
            // Comparison Constraints
            Assert\EqualTo::class,
            Assert\NotEqualTo::class,
            Assert\IdenticalTo::class,
            Assert\NotIdenticalTo::class,
            Assert\LessThan::class,
            Assert\LessThanOrEqual::class,
            Assert\GreaterThan::class,
            Assert\GreaterThanOrEqual::class,
            // Other Constraints
            Assert\Callback::class,
        ];
    }
}
