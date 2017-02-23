<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\UnexpectedValueException;

/**
 * Immutable.
 */
abstract class AbstractFilter implements AbstractFilterInterface
{
    private $expression;
    private $key;
    private $value;
    private $operator = "=";

    /**
     * @param $expression string
     * @param $key string
     * @param $value string
     * @param $operator ?string
     */
    public function __construct($expression, $key, $value, $operator = null)
    {
        $this->expression = $expression;
        $this->key = $key;
        $this->setValue($value);
        if (null !== $operator) {
            $this->setOperator($operator);
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'expression' => $this->getExpression(),
            'extra' => [],
            'key' => $this->getKey(),
            'operator' => $this->getOperator(),
            'type' => static::getIdentifier(),
            'value' => $this->getValue(),
            'valueDataType' => $this->getValueDataType(),
        ];
    }

    /**
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getValueDataType()
    {
        return gettype($this->value);
    }

    /**
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     * @throws \Kafoso\Questful\Exception\UnexpectedValueException
     * @return object $this
     */
    private function setOperator($operator)
    {
        if (false == is_string($operator)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$operator',
                FormattingHelper::found($operator)
            ));
        }
        if (false == in_array($operator, static::getAllAvailableOperators())) {
            throw new UnexpectedValueException(sprintf(
                "Invalid operator. Supported operators are [\"%s\"]. Found: %s",
                implode('","', static::getAllAvailableOperators()),
                FormattingHelper::found($operator)
            ));
        }
        if (false == in_array($operator, static::getAvailableOperators())) {
            throw new UnexpectedValueException(sprintf(
                "Expected operator to be one of [\"%s\"]. Found: %s",
                implode('","', static::getAvailableOperators()),
                FormattingHelper::found($operator)
            ));
        }
        $this->operator = $operator;
        return $this;
    }

    /**
     * @param $value mixed
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     * @return object $this
     */
    private function setValue($value)
    {
        if (gettype($value) !== static::getValueDataTypeConstraint()) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be \"%s\". Found: %s",
                '$value',
                static::getValueDataTypeConstraint(),
                FormattingHelper::found($value)
            ));
        }
        $this->value = $value;
        return $this;
    }

    /**
     * @return array (string[])
     */
    final public static function getAllAvailableOperators()
    {
        return ['<=', '>=', '!=', '=', '>', '<'];
    }
}
