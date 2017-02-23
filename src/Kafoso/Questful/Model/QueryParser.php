<?php
namespace Kafoso\Questful\Model;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Factory\Model\QueryParser\Filter\FilterFactory;
use Kafoso\Questful\Factory\Model\QueryParser\FilterExpression\FilterExpressionFactory;
use Kafoso\Questful\Factory\Model\QueryParser\Sort\SortFactory;

class QueryParser
{
    const EXCEPTION_CODE = 1;

    protected $query;

    protected $filters = [];
    protected $filterExpression = null;
    protected $sorts = [];

    public function __construct(array $query)
    {
        $this->query = $query;
    }

    /**
     * @throws Kafoso\Questful\Exception\BadRequestException
     * @return object $this
     */
    public function parse()
    {
        $this->filters = [];
        $this->filterExpression = null;
        $this->sorts = [];
        if (isset($this->query['filter'])) {
            $filterFactory = new FilterFactory;
            $this->filters = $filterFactory->createFromQuery($this->query['filter']);
        }
        if (isset($this->query['filterExpression'])) {
            $filterExpressionFactory = new FilterExpressionFactory;
            $this->filterExpression = $filterExpressionFactory->createFromQuery($this->query['filterExpression']);
        }
        if (isset($this->query['sort'])) {
            $sortFactory = new SortFactory;
            $this->sorts = $sortFactory->createFromQuery($this->query['sort']);
        }
        if ($this->filterExpression) {
            if (!$this->filters) {
                throw new BadRequestException(
                    "Parameter 'filter' is missing and is required when specifying parameter 'filterExpression'",
                    self::EXCEPTION_CODE
                );
            }
            $filterExpressionIndexes = $this->filterExpression->getIndexes();
            $filterIndexes = array_map("intval", array_keys($this->filters));
            $diffA = array_diff($filterIndexes, $filterExpressionIndexes);
            if ($diffA) {
                throw new BadRequestException(sprintf(
                    "Parameter 'filter' contains indexes [%s], which are not represented in parameter "
                    . "'filterExpression=%s' (indexes: [%s])",
                    implode(', ', $diffA),
                    $this->filterExpression->getExpression(),
                    implode(', ', $filterExpressionIndexes)
                ), self::EXCEPTION_CODE);
            }
            $diffB = array_diff($filterExpressionIndexes, $filterIndexes);
            if ($diffB) {
                throw new BadRequestException(sprintf(
                    "Parameter 'filterExpression=%s' contains indexes [%s], which are not represented in parameter "
                    . "'filter' (indexes: [%s])",
                    $this->filterExpression->getExpression(),
                    implode(', ', $diffB),
                    implode(', ', $filterIndexes)
                ), self::EXCEPTION_CODE);
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $filterExpression = $this->getFilterExpression();
        if ($filterExpression) {
            $filterExpression = $filterExpression->toArray();
        } else {
            $filterExpression = null;
        }
        $filters = $this->getFilters();
        if ($filters) {
            $filters = array_map(function($filter){
                return $filter->toArray();
            }, $filters);
        } else {
            $filters = [];
        }
        $sorts = $this->getSorts();
        if ($sorts) {
            $sorts = array_map(function($sort){
                return $sort->toArray();
            }, $sorts);
        } else {
            $sorts = [];
        }
        return [
            "filterExpression" => $filterExpression,
            "filters" => $filters,
            "sorts" => $sorts,
        ];
    }

    /**
     * @return string
     */
    public function getFilterExpression()
    {
        return $this->filterExpression;
    }

    /**
     * @return ?object \Kafoso\Questful\Model\QueryParser\Filter\AbstractFilter
     */
    public function getFilter($key)
    {
        if ($this->getFilters()) {
            foreach ($this->getFilters() as $filter) {
                if ($filter->getKey() == $key) {
                    return $filter;
                }
            }
        }
        return null;
    }

    /**
     * @return array (\Kafoso\Questful\Model\QueryParser\Filter\AbstractFilter[])
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return array (\Kafoso\Questful\Model\QueryParser\Sort[])
     */
    public function getSorts()
    {
        return $this->sorts;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return boolean
     */
    public function hasFilter($key)
    {
        if ($this->getFilters()) {
            foreach ($this->getFilters() as $filter) {
                if ($filter->getKey() == $key) {
                    return true;
                }
            }
        }
        return false;
    }
}
