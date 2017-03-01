<?php
namespace Kafoso\Questful\Model\Mapping\Allowable;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\ValidationException;
use Kafoso\Questful\Model\QueryParser\FilterExpression;
use Kafoso\Questful\Model\Mapping\AllowableInterface;
use Kafoso\Questful\Model\MappingInterface;

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
            try {
                $this->filterExpression = new FilterExpression($expression);
            } catch (BadRequestException $e) {
                /**
                 * Throws ValidationException instead because this is a server-side programming mistake, e.g. an allowed
                 * filter expression is malformed. It is not a client input mistake.
                 */
                throw new ValidationException(sprintf(
                    "Expression '%s' is invalid",
                    $this->expression
                ), MappingInterface::EXCEPTION_CODE, $e);
            }
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
