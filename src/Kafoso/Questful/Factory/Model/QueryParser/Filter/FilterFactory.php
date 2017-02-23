<?php
namespace Kafoso\Questful\Factory\Model\QueryParser\Filter;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\RuntimeException;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\QueryParser;
use Kafoso\Questful\Model\QueryParser\Filter\AbstractFilter;
use Kafoso\Questful\Model\QueryParser\Filter\BooleanFilter;
use Kafoso\Questful\Model\QueryParser\Filter\FloatFilter;
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;
use Kafoso\Questful\Model\QueryParser\Filter\LikeFilter;
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;
use Kafoso\Questful\Model\QueryParser\Filter\RegexpFilter;
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;

class FilterFactory
{
    /**
     * @param $filterSrc mixed
     * @throws \Kafoso\Questful\Exception\BadRequestException
     * @throws \Kafoso\Questful\Exception\RuntimeException
     * @return array (Filter[])
     */
    public function createFromQuery($filterSrc)
    {
        if (false == is_array($filterSrc)) {
            throw new BadRequestException(sprintf(
                "Parameter 'filter[]' must be an array. Found: %s",
                FormattingHelper::found($filterSrc)
            ), QueryParser::EXCEPTION_CODE);
        }
        $filterSrc = array_unique($filterSrc);
        $operatorCharactersEscaped = array_map(function($v){
            return preg_quote($v, '/');
        }, AbstractFilter::getAllAvailableOperators());
        $regexInner = implode('|', $operatorCharactersEscaped);
        $regex = "/^(.*?)({$regexInner})(.*)$/";
        $filters = [];
        foreach ($filterSrc as $index => $expression) {
            preg_match('/^(\-?)\d+$/', $index, $match);
            if (!$match) {
                throw new BadRequestException(sprintf(
                    "Index in 'filter[%s]=%s' must be an integer. Found: %s",
                    $index,
                    $expression,
                    FormattingHelper::found($index)
                ), QueryParser::EXCEPTION_CODE);
            } elseif ("-" == $match[1]) {
                throw new BadRequestException(sprintf(
                    "Index in 'filter[%s]=%s' is negative; all indexes must be >= 0",
                    $index,
                    $expression,
                    FormattingHelper::found($index)
                ), QueryParser::EXCEPTION_CODE);
            }
            $index = intval($index); // Force-convert since indexes may later be used for parameterized SQL queries
            preg_match($regex, $expression, $match);
            if (!$match) {
                throw new BadRequestException(
                    $this->_getExceptionMessageUnsupported(null, $index, $expression),
                    QueryParser::EXCEPTION_CODE
                );
            }
            $key = trim($match[1]);
            $operator = trim($match[2]);
            $value = trim($match[3]);
            $isCaseSensitive = true;
            if (!$key) {
                throw new BadRequestException(sprintf(
                    "Filter 'filter[%s]=%s' is missing key",
                    $index,
                    $expression
                ), QueryParser::EXCEPTION_CODE);
            }
            if (!$operator) {
                 // Someone messed up bad if this ever happens.
                throw new RuntimeException(sprintf(
                    "Unreachable statement. Filter 'filter[%s]=%s' is missing operator",
                    $index,
                    $expression
                ), QueryParser::EXCEPTION_CODE);
            }
            if ("" === $value) {
                $value = "null";
            }
            $exception = null;
            try {
                if (preg_match(IntegerFilter::MATCH_PATTERN, $value)) {
                    $filters[$index] = new IntegerFilter($expression, $key, intval($value), $operator);
                } elseif (preg_match(FloatFilter::MATCH_PATTERN, $value)) {
                    $filters[$index] = new FloatFilter($expression, $key, floatval($value), $operator);
                } elseif ("null" === $value) {
                    $filters[$index] = new NullFilter($expression, $key, null, $operator);
                } elseif ("true" === $value || "false" === $value) {
                    $filters[$index] = new BooleanFilter($expression, $key, ("true" === $value), $operator);
                } elseif (preg_match(StringFilter::MATCH_PATTERN, $value, $match)) {
                    $filters[$index] = new StringFilter($expression, $key, strval($value), $operator);
                } elseif (preg_match(InFilter::MATCH_PATTERN, $value, $match)) {
                    $filters[$index] = new InFilter($expression, $key, $value, $operator);
                } elseif (preg_match(LikeFilter::MATCH_PATTERN, $value, $match)) {
                    $filters[$index] = new LikeFilter($expression, $key, strval($value), $operator);
                } elseif (preg_match(RegexpFilter::MATCH_PATTERN, $value, $match)) {
                    $filters[$index] = new RegexpFilter($expression, $key, strval($value), $operator);
                } else {
                    throw new BadRequestException(
                        $this->_getExceptionMessageUnsupported($value, $index, $expression),
                        QueryParser::EXCEPTION_CODE
                    );
                }
            } catch (InvalidArgumentException $e)  {
                $exception = $e;
            } catch (UnexpectedValueException $e)  {
                $exception = $e;
            } catch (BadRequestException $e)  {
                $exception = $e;
            }
            if ($exception) {
                throw new BadRequestException(sprintf(
                    "'filter[%s]=%s' is malformed: %s",
                    $index,
                    $expression,
                    $exception->getMessage()
                ), QueryParser::EXCEPTION_CODE, $exception);
            }
        }
        ksort($filters);
        return $filters;
    }

    private function _getExceptionMessageUnsupported($value, $index, $expression)
    {
        return sprintf(
            "Filter value '%s' (in 'filter[%s]=%s') does not match a supported pattern.",
            $value,
            $index,
            $expression
        );
    }
}
