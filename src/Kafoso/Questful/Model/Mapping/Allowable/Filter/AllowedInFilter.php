<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedInFilter extends AbstractAllowedFilter
{
    protected $subContraints = [];

    /**
     * @param $dataType string
     * @param $constraints array (\Symfony\Component\Validator\Constraint[])
     * @throws Kafoso\Questful\Exception\InvalidArgumentException
     * @throws Kafoso\Questful\Exception\UnexpectedValueException
     * @return object $this
     */
    public function setSubConstraintsForDatatype($dataType, array $constraints = null)
    {
        if (false == is_string($dataType)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$dataType',
                FormattingHelper::found($dataType)
            ));
        }
        if (false == in_array($dataType, self::getAvailableConstaintDataTypes())) {
            throw new UnexpectedValueException(sprintf(
                "Expects argument '%s' to be one of [\"%s\"]. Found: %s",
                '$dataType',
                implode('","', self::getAvailableConstaintDataTypes()),
                FormattingHelper::found($dataType)
            ));
        }
        if (!$constraints) {
            unset($this->subContraints[$dataType]);
        } else {
            if (false == array_key_exists($dataType, $this->subContraints)) {
                $this->subContraints[$dataType] = [];
            }
            foreach ($constraints as $index => $constraint) {
                if (false == ($constraint instanceof Constraint)) {
                    throw new UnexpectedValueException(sprintf(
                        "Expects argument '%s[%s]' to contain instances of \\%s, exclusively. Found (at index %s): %s",
                        '$constraints',
                        $index,
                        Constraint::class,
                        $index,
                        FormattingHelper::found($constraint)
                    ));
                } else {
                    $this->subContraints[$dataType][] = $constraint;
                }
            }
        }
        return $this;
    }

    /**
     * @param $dataType string
     * @throws Kafoso\Questful\Exception\InvalidArgumentException
     * @throws Kafoso\Questful\Exception\UnexpectedValueException
     * @return array (\Symfony\Component\Validator\Constraint[])
     */
    public function getSubConstraintsForDatatype($dataType)
    {
        if (false == is_string($dataType)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$dataType',
                FormattingHelper::found($dataType)
            ));
        }
        if (false == in_array($dataType, self::getAvailableConstaintDataTypes())) {
            throw new UnexpectedValueException(sprintf(
                "Expects argument '%s' to be one of [\"%s\"]. Found: %s",
                '$dataType',
                implode('","', self::getAvailableConstaintDataTypes()),
                FormattingHelper::found($dataType)
            ));
        }
        if (array_key_exists($dataType, $this->subContraints)) {
            return $this->subContraints[$dataType];
        }
        return [];
    }

    public function hasSubConstraints()
    {
        return (count($this->subContraints) > 0);
    }

    /**
     * @return array (string[])
     */
    public static function getAvailableConstaintDataTypes()
    {
        return [
            "null",
            "boolean",
            "double",
            "integer",
            "string",
        ];
    }

    public static function getAvailableConstraints()
    {
        return [
            // Collection Constraints
            Assert\Count::class,
        ];
    }


    public static function getFilterClassNamespace()
    {
        return InFilter::class;
    }
}
