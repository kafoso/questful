<?php
namespace Kafoso\Questful\Model\Mapping\Allowable;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Model\QueryParser\FilterExpression;
use Kafoso\Questful\Model\Mapping\AllowableInterface;

class AllowedFilterExpression implements AllowableInterface
{
    const ALLOW_ALL = "*";

    private $filterExpression = null;

    /**
     * @param $expression
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     */
    public function __construct($expression)
    {
        if (false == is_string($expression)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$expression',
                FormattingHelper::found($expression)
            ));
        }
        if (self::ALLOW_ALL == $expression) {
            $this->expression = self::ALLOW_ALL;
        } else {
            $this->expression = $expression;
            $this->filterExpression = new FilterExpression($expression);
        }
    }

    /**
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @return object \Kafoso\Questful\Model\QueryParser\FilterExpression
     */
    public function getFilterExpression()
    {
        return $this->filterExpression;
    }
}
