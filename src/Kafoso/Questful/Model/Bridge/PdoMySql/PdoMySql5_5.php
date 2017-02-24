<?php
namespace Kafoso\Questful\Model\Bridge\PdoMySql;

use Kafoso\Questful\Exception\RuntimeException;
use Kafoso\Questful\Model\QueryParser\Filter\BooleanFilter;
use Kafoso\Questful\Model\QueryParser\Filter\FloatFilter;
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;
use Kafoso\Questful\Model\QueryParser\Filter\LikeFilter;
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;
use Kafoso\Questful\Model\QueryParser\Filter\RegexpFilter;
use Kafoso\Questful\Model\QueryParser\Filter\ScalarFilterInterface;
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;

/**
 * A bridge for MySQL versions 5.5 and above.
 */
class PdoMySql5_5 extends AbstractPdoMySql
{
    public function generate()
    {
        $this->where = null;
        $this->parameters = [];
        $this->orderBy = null;
        if ($this->mapping->getQueryParser()->getFilters()) {
            $conditions = [];
            $allowedFilters = $this->mapping->getAllowedFilters();
            if ($allowedFilters) {
                foreach ($this->mapping->getQueryParser()->getFilters() as $index => $filter) {
                    $allowedFilter = null;
                    foreach ($allowedFilters as $k => $af) {
                        if ($af->getKey() === $filter->getKey()) {
                            if (get_class($filter) == $af->getFilterClassNamespace()) {
                                $allowedFilter = $af;
                                break;
                            }
                        }
                    }
                    if (!$allowedFilter) {
                        continue;
                    }
                    $column = $this->mapping->getColumnFromKey($allowedFilter->getKey());

                    $sql = "";
                    $parameterName = "filter_{$index}";
                    $operator = $filter->getOperator();

                    $not = "";
                    if ("!=" == $operator) {
                        $not = "NOT ";
                    }

                    if ($filter instanceof NullFilter) {
                        $sql .= " {$column}";
                        if ("=" == $filter->getOperator()) {
                            $sql .= " IS NULL";
                        } else {
                            $sql .= " IS NOT NULL";
                        }
                    } elseif ($filter instanceof ScalarFilterInterface) {
                        if ($filter instanceof BooleanFilter) {
                            $sql .= "{$column} {$operator} " . ($filter->getValue() ? "1" : "0");
                        } else {
                            $value = $filter->getValue();
                            if ($filter instanceof StringFilter) {
                                if (false == $filter->isCaseSensitive()) {
                                    $sql .= "LOWER({$column}) {$operator} BINARY :{$parameterName}";
                                    $value = mb_strtolower($value);
                                } else {
                                    $sql .= "{$column} {$operator} BINARY :{$parameterName}";
                                }
                            } else {
                                $sql .= "{$column} {$operator} :{$parameterName}";
                            }
                            $this->parameters[$parameterName] = $value;
                        }
                    } elseif ($filter instanceof LikeFilter) {
                        $value = $filter->getValue();
                        if (false == $filter->isCaseSensitive()) {
                            $sql .= "LOWER({$column}) {$not}LIKE BINARY :{$parameterName} ESCAPE '\'";
                            $value = mb_strtolower($value);
                        } else {
                            $sql .= "{$column} {$not}LIKE BINARY :{$parameterName} ESCAPE '\'";
                        }
                        $value = addcslashes($value, "%_\\");
                        if ($filter->hasWildcardLeft()) {
                            $value = "%" . $value;
                        }
                        if ($filter->hasWildcardRight()) {
                            $value .= "%";
                        }
                        $this->parameters[$parameterName] = $value;
                    } elseif ($filter instanceof InFilter) {
                        if (false == $filter->isCaseSensitive()) {
                            $sql .= "LOWER({$column}) IN (";
                        } else {
                            $sql .= "{$column} IN (";
                        }
                        $value = array_values($filter->getValue());
                        $sqlPlaceholders = [];
                        foreach ($value as $index => $item) {
                            $parameterNameItem = "{$parameterName}_{$index}";
                            if (is_string($item) && false == $filter->isCaseSensitive()) {
                                $item = mb_strtolower($item);
                            }
                            $sqlPlaceholders[] = ":{$parameterNameItem}";
                            $this->parameters[$parameterNameItem] = $item;
                        }
                        $sql .= implode(", ", $sqlPlaceholders);
                        $sql .= ")";
                    } elseif ($filter instanceof RegexpFilter) {
                        $value = $filter->getValue();
                        if (false == $filter->isCaseSensitive()) {
                            $sql .= "LOWER({$column}) {$not}REGEXP BINARY :{$parameterName}";
                            $value = mb_strtolower($value);
                        } else {
                            $sql .= "{$column} {$not}REGEXP BINARY :{$parameterName}";
                        }
                        $this->parameters[$parameterName] = $value;
                    } else {
                        throw new RuntimeException(sprintf(
                            "Uncovered case for filter with type '%s' and expression: %s",
                            get_class($filter),
                            $filter->getExpression()
                        ), static::EXCEPTION_CODE);
                    }
                    $conditions[$index] = $sql;
                }
            }
            if ($conditions) {
                if ($this->mapping->getQueryParser()->getFilterExpression()) {
                    $sql = [];
                    foreach ($this->mapping->getQueryParser()->getFilterExpression()->getLexer()->getTokensNormalized() as $token) {
                        if (preg_match("/^\d+$/", $token)) {
                            $sql[] = $conditions[$token];
                        } elseif (in_array($token, ["and", "or", "xor"])) {
                            $sql[] = strtoupper($token);
                        } else {
                            $sql[] = $token;
                        }
                    }
                    $this->where = implode(" ", $sql);
                } else {
                    $this->where = implode(" AND ", $conditions);
                }
                $this->where = $this->trimAndWrapInParentheses($this->where);
                if (!$this->where) {
                    $this->where = null;
                }
            }
        }
        if ($this->mapping->getQueryParser()->getSorts()) {
            $sorts = [];
            $AllowedSortings = $this->mapping->getAllowedSorts();
            if ($AllowedSortings) {
                foreach ($this->mapping->getQueryParser()->getSorts() as $index => $sort) {
                    $AllowedSorting = null;
                    foreach ($AllowedSortings as $k => $ms) {
                        if ($ms->getKey() === $sort->getKey()) {
                            $AllowedSorting = $ms;
                            break;
                        }
                    }
                    if (!$AllowedSorting) {
                        continue;
                    }
                    $column = $this->mapping->getColumnFromKey($AllowedSorting->getKey());
                    $sorts[$index] = " {$column} " . ($sort->isAscending() ? "ASC" : "DESC");
                }
                $this->orderBy = preg_replace('/\s+/', ' ', trim(implode(", ", $sorts)));
            }
        }
        return $this;
    }
}
