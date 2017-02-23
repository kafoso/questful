<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Symfony\Component\Validator\Constraints as Assert;

abstract class AbstractAllowedStringFilter extends AbstractAllowedFilter
{
    public static function getAvailableConstraints()
    {
        return [
            // Basic Constraints
            Assert\Blank::class,
            Assert\NotBlank::class,
            // String Constraints
            Assert\Email::class,
            Assert\Length::class,
            Assert\Url::class,
            Assert\Regex::class,
            Assert\Ip::class,
            Assert\Uuid::class,
            // Comparison Constraints
            Assert\EqualTo::class,
            Assert\NotEqualTo::class,
            Assert\IdenticalTo::class,
            Assert\NotIdenticalTo::class,
            Assert\LessThan::class,
            Assert\LessThanOrEqual::class,
            Assert\GreaterThan::class,
            Assert\GreaterThanOrEqual::class,
            // Date Constraints
            Assert\Date::class,
            Assert\DateTime::class,
            Assert\Time::class,
            // Other Constraints
            Assert\Callback::class,
        ];
    }
}
