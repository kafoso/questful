<?php
namespace Kafoso\Questful\Model;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\RuntimeException;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\Mapping\AllowableInterface;
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AbstractAllowedFilter;
use Kafoso\Questful\Model\Mapping\Allowable\AllowedFilterExpression;
use Kafoso\Questful\Model\Mapping\Allowable\AllowedSort;
use Symfony\Component\Validator\Validation;

class Mapping implements MappingInterface
{
    private $queryParser;

    private $allowedFilters = [];
    private $allowedFilterExpressions = [];
    private $allowedSorts = [];
    private $columnValidationRegexp = null;
    private $keyValidationRegexp = null;
    private $relations = [];

    public function __construct(QueryParser $queryParser)
    {
        $this->queryParser = $queryParser;
    }

    /**
     * @throws \Kafoso\Questful\Exception\RuntimeException
     * @throws \Kafoso\Questful\Exception\UnexpectedValueException
     * @return object $this
     */
    public function allow(AllowableInterface $allowable)
    {
        if ($allowable instanceof AbstractAllowedFilter) {
            if (false == array_key_exists($allowable->getKey(), $this->relations)) {
                throw new UnexpectedValueException(sprintf(
                    "Cannot allow filter with key \"%s\"; no relation exists",
                    $allowable->getKey()
                ));
            }
            $this->allowedFilters[] = $allowable;
        } elseif ($allowable instanceof AllowedFilterExpression) {
            $this->allowedFilterExpressions[] = $allowable;
        } elseif ($allowable instanceof AllowedSort) {
            if (false == array_key_exists($allowable->getKey(), $this->relations)) {
                throw new UnexpectedValueException(sprintf(
                    "Cannot allow sort with key \"%s\"; no relation exists",
                    $allowable->getKey()
                ));
            }
            $this->allowedSorts[] = $allowable;
        } else {
            throw new RuntimeException(sprintf(
                "Uncovered case for \\%s",
                get_class($allowable)
            ));
        }
        return $this;
    }

    /**
     * @param $key string
     * @param $column string
     * @return $this
     */
    public function relate($key, $column)
    {
        try {
            $this->validateKey($key);
            $this->validateColumn($column);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf(
                "Could not relate \"%s\" to \"%s\": " . $e->getMessage(),
                $key,
                $column
            ), 0, $e);
        }
        $this->relations[$key] = $column;
        return $this;
    }

    /**
     * @throws \Kafoso\Questful\Exception\BadRequestException
     */
    public function validate()
    {
        $validator = Validation::createValidator();
        $filters = $this->queryParser->getFilters();
        reset($filters);
        if ($filters) {
            if ($this->allowedFilters) {
                foreach ($this->allowedFilters as $allowedFilter) {
                    foreach ($filters as $i => $filter) {
                        if ($filter->getKey() == $allowedFilter->getKey()) {
                            if (get_class($filter) == $allowedFilter->getFilterClassNamespace()) {
                                if (false == in_array($filter->getOperator(), $allowedFilter->getOperators())) {
                                    throw new BadRequestException(sprintf(
                                        "'filter=%s': Disallowed operator \"%s\"; allowed operators are: [\"%s\"]",
                                        addcslashes($filter->getExpression(), "'"),
                                        $filter->getOperator(),
                                        implode('","', $allowedFilter->getOperators())
                                    ));
                                }
                                if ($allowedFilter->getConstraints()) {
                                    $violations = $validator->validateValue(
                                        $filter->getValue(),
                                        $allowedFilter->getConstraints()
                                    );
                                    if ($violations->count() > 0) {
                                        $firstErrorMessage = null;
                                        foreach ($violations as $violation) {
                                            $firstErrorMessage = $violation->getMessage();
                                            break;
                                        }
                                        throw new BadRequestException(sprintf(
                                            "'filter=%s': %d validation(s) failed. First error: %s",
                                            addcslashes($filter->getExpression(), "'"),
                                            count($violations),
                                            $firstErrorMessage
                                        ));
                                    }
                                }
                                unset($filters[$i]);
                            }
                        }
                    }
                }
            }
            if ($filters) {
                $expressions = array_map(function($filter){
                    return $filter->getExpression();
                }, $filters);
                throw new BadRequestException(sprintf(
                    "%d filter(s) is/are not allowed. These are: %s",
                    count($filters),
                    implode('","', $expressions)
                ));
            }
        }
        $sorts = $this->queryParser->getSorts();
        reset($sorts);
        if ($sorts) {
            if ($this->allowedSorts) {
                foreach ($this->allowedSorts as $allowedSort) {
                    foreach ($sorts as $i => $sort) {
                        if ($sort->getKey() == $allowedSort->getKey()) {
                            unset($sorts[$i]);
                            break;
                        }
                    }
                }
            }
            if ($sorts) {
                $keys = array_map(function($sort){
                    return $sort->getKey();
                }, $sorts);
                throw new BadRequestException(sprintf(
                    "%d sorts are not allowed. These are: %s",
                    count($sorts),
                    implode('","', $keys)
                ));
            }
        }
        if ($this->queryParser->getFilterExpression()) {
            if (!$this->allowedFilterExpressions) {
                throw new BadRequestException("An allowed filter expression was not provided");
            }
            $isAllowingAll = false;
            foreach ($this->allowedFilterExpressions as $allowedFilterExpression) {
                if (AllowedFilterExpression::ALLOW_ALL == $allowedFilterExpression->getExpression()) {
                    $isAllowingAll = true;
                    break;
                }
            }
            if (false == $isAllowingAll) {
                $hasValidMatch = false;
                foreach ($this->allowedFilterExpressions as $allowedFilterExpression) {
                    $sourceNormalized = $this->queryParser->getFilterExpression()->getLexer()->getExpressionNormalized();
                    $mappingNormalized = $allowedFilterExpression->getFilterExpression()->getLexer()->getExpressionNormalized();
                    if ($sourceNormalized === $mappingNormalized) {
                        $hasValidMatch = true;
                        break;
                    }
                }
                if (false == $hasValidMatch) {
                    $allowedFilterExpressionsNormalized = array_map(function(AllowedFilterExpression $allowedFilterExpression){
                        return $allowedFilterExpression->getFilterExpression()->getLexer()->getExpressionNormalized();
                    }, $this->allowedFilterExpressions);
                    throw new BadRequestException(sprintf(
                        "Denied filterExpression \"%s\" (raw: \"%s\"); does not match any allowed filter expressions: [%s]",
                        $this->queryParser->getFilterExpression()->getLexer()->getExpressionNormalized(),
                        $this->queryParser->getFilterExpression()->getExpression(),
                        '"' . implode('", "', $allowedFilterExpressionsNormalized) . '"'
                    ));
                }
            }
        }
    }

    /**
     * @param $columnValidationRegexp string
     * @return object $this
     */
    public function setColumnValidationRegexp($columnValidationRegexp)
    {
        $this->columnValidationRegexp = $columnValidationRegexp;
        return $this;
    }

    /**
     * @param $keyValidationRegexp string
     * @return object $this
     */
    public function setKeyValidationRegexp($keyValidationRegexp)
    {
        $this->keyValidationRegexp = $keyValidationRegexp;
        return $this;
    }

    /**
     * @return array (\Kafoso\Questful\Model\QueryParser\Filter\AbstractFilter[])
     */
    public function getAllowedFilters()
    {
        return $this->allowedFilters;
    }

    /**
     * @return array (\Kafoso\Questful\Model\QueryParser\Sort[])
     */
    public function getAllowedSorts()
    {
        return $this->allowedSorts;
    }

    /**
     * @throws \Kafoso\Questful\Exception\UnexpectedValueException
     * @return string
     */
    public function getColumnFromKey($key)
    {
        $this->validateKey($key);
        if (false == array_key_exists($key, $this->relations)) {
            throw new UnexpectedValueException(sprintf(
                "No relation exists for the specified key: %s",
                $key
            ));
        }
        return $this->relations[$key];
    }

    /**
     * @return string
     */
    public function getColumnValidationRegexp()
    {
        if (null === $this->columnValidationRegexp) {
            $this->columnValidationRegexp = self::DEFAULT_COLUMN_VALIDATION_REGEXP;
        }
        return $this->columnValidationRegexp;
    }

    /**
     * @return string
     */
    public function getKeyValidationRegexp()
    {
        if (null === $this->keyValidationRegexp) {
            $this->keyValidationRegexp = self::DEFAULT_KEY_VALIDATION_REGEXP;
        }
        return $this->keyValidationRegexp;
    }

    /**
     * @return object \Kafoso\Questful\Model\QueryParser
     */
    public function getQueryParser()
    {
        return $this->queryParser;
    }

    /**
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @param $column string
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     * @throws \Kafoso\Questful\Exception\UnexpectedValueException
     */
    public function validateColumn($column)
    {
        if (false == is_string($column)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$column',
                FormattingHelper::found($column)
            ));
        }
        if (!preg_match($this->getColumnValidationRegexp(), $column)) {
            throw new UnexpectedValueException(sprintf(
                "Expects argument '%s' to match regular expression '%s'. Found: %s",
                '$column',
                self::DEFAULT_COLUMN_VALIDATION_REGEXP,
                FormattingHelper::found($column)
            ));
        }
    }

    /**
     * @param $key string
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     * @throws \Kafoso\Questful\Exception\UnexpectedValueException
     */
    public function validateKey($key)
    {
        if (false == is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$key',
                FormattingHelper::found($key)
            ));
        }
        if (!preg_match($this->getKeyValidationRegexp(), $key)) {
            throw new UnexpectedValueException(sprintf(
                "Expects argument '%s' to match regular expression '%s'. Found: %s",
                '$key',
                self::DEFAULT_KEY_VALIDATION_REGEXP,
                FormattingHelper::found($key)
            ));
        }
    }
}
