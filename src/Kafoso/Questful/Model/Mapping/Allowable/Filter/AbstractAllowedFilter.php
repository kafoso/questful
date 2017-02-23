<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\Mapping\AllowableInterface;
use Kafoso\Questful\Model\QueryParser\Filter\AbstractFilter;
use Symfony\Component\Validator\Constraint;

abstract class AbstractAllowedFilter implements AbstractAllowedFilterInterface, AllowableInterface
{
    protected $key;
    protected $operators = null;
    protected $constraints = null;

    /**
     * @param $key string
     * @param $operators ?array (string[])
     * @param $constraints ?array (\Symfony\Component\Validator\Constraint[])
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     * @throws \Kafoso\Questful\Exception\UnexpectedValueException
     */
    public function __construct(
        $key,
        array $operators = null,
        array $constraints = null
    )
    {
        self::validateKey($key);
        self::validateOperators($operators);
        $this->key = $key;
        if ($operators) {
            foreach ($operators as $operator) {
                self::validateOperator($operator);
                $this->operators[] = $operator;
            }
        } else {
            $filterClassNamespace = static::getFilterClassNamespace();
            $this->operators = $filterClassNamespace::getAvailableOperators();
        }
        if ($constraints) {
            foreach ($constraints as $index => $constraint) {
                if (false == ($constraint instanceof Constraint)) {
                    throw new UnexpectedValueException(sprintf(
                        "Expects argument '%s' to contain instances of \\%s, exclusively. Found (at index %s): %s",
                        '$constraint',
                        Constraint::class,
                        $index,
                        FormattingHelper::found($constraint)
                    ));
                }
                if (false == in_array(get_class($constraint), static::getAvailableConstraints())) {
                    throw new UnexpectedValueException(sprintf(
                        "Constraint \\%s is not available for \\%s",
                        get_class($constraint),
                        get_class($this)
                    ));
                }
            }
            $this->constraints = array_values($constraints);
        }
    }

    /**
     * @return ?array
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array (string[])
     */
    public function getOperators()
    {
        return $this->operators;
    }

    final public static function getAvailableOperators()
    {
        $filterClassNamespace = static::getFilterClassNamespace();
        return $filterClassNamespace::getAvailableOperators();
    }

    protected static function validateKey($key)
    {
        if (false == is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$key',
                FormattingHelper::found($key)
            ));
        }
        if ("" === $key) {
            throw new UnexpectedValueException(sprintf(
                "Expects argument '%s' to not be an empty string. Found: %s",
                '$key',
                FormattingHelper::found($key)
            ));
        }
    }

    protected static function validateOperator($operator)
    {
        if (false == is_string($operator)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$operator',
                FormattingHelper::found($operator)
            ));
        }
        if (false == in_array($operator, static::getAvailableOperators())) {
            throw new UnexpectedValueException(sprintf(
                "Invalid operator. Expected one of [\"%s\"]. Found: %s",
                implode('","', static::getAvailableOperators()),
                FormattingHelper::found($operator)
            ));
        }
    }

    protected static function validateOperators($operators)
    {
        if ((null !== $operators && !is_array($operators)) || (is_array($operators) && 0 == count($operators))) {
            throw new UnexpectedValueException(sprintf(
                "Expects argument '%s' to be null or a non-empty array. Found: []",
                '$operators'
            ));
        }
    }
}
