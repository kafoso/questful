<?php
namespace Kafoso\Questful\Model\Bridge\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Kafoso\Questful\Exception\RuntimeException;
use Kafoso\Questful\Model\QueryParser\Filter\BooleanFilter;
use Kafoso\Questful\Model\QueryParser\Filter\FloatFilter;
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;
use Kafoso\Questful\Model\QueryParser\Filter\LikeFilter;
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;
use Kafoso\Questful\Model\QueryParser\Filter\ScalarFilterInterface;
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;

/**
 * Bridge for Doctrine versions 2.1 and above.
 * Producing DQL, comsumable by the Doctrine DBAL: http://www.doctrine-project.org/
 * Notice that BINARY is being utilized. Therefore, you need to provide logic for handling these
 * extensions. This is done by writing handlers yourself or simply by using
 * https://github.com/beberlei/DoctrineExtensions (recommended).
 */
class Doctrine2_1 extends AbstractDoctrine
{
    public function generate()
    {
        $this->where = null;
        $this->parameters = [];
        $this->orderBy = [];
        if ($this->getMapping()->getQueryParser()->getFilters()) {
            $conditions = [];
            $allowedFilters = $this->getMapping()->getAllowedFilters();
            if ($allowedFilters) {
                foreach ($this->getMapping()->getQueryParser()->getFilters() as $index => $filter) {
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
                    $column = $this->getMapping()->getColumnFromKey($allowedFilter->getKey());

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
                                    $sql .= "LOWER({$column}) {$operator} BINARY(:{$parameterName})";
                                    $value = mb_strtolower($value, $this->getEncoding());
                                } else {
                                    $sql .= "{$column} {$operator} BINARY(:{$parameterName})";
                                }
                            } else {
                                $sql .= "{$column} {$operator} :{$parameterName}";
                            }
                            $this->parameters[$parameterName] = $value;
                        }
                    } elseif ($filter instanceof LikeFilter) {
                        $value = $filter->getValue();
                        if (false == $filter->isCaseSensitive()) {
                            $sql .= "LOWER({$column}) {$not}LIKE BINARY(:{$parameterName}) ESCAPE '\'";
                            $value = mb_strtolower($value, $this->getEncoding());
                        } else {
                            $sql .= "{$column} {$not}LIKE BINARY(:{$parameterName}) ESCAPE '\'";
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
                        $inParameters = [];
                        $orParameters = [];
                        $value = $filter->getValue();
                        $value = array_values($value);
                        foreach ($value as $subIndex => $item) {
                            $parameterNameItem = "{$parameterName}_{$subIndex}";
                            if (is_null($item)) {
                                $orParameters[$parameterNameItem] = ":{$parameterNameItem}";
                            } else {
                                if (is_string($item)) {
                                    if (false == $filter->isCaseSensitive()) {
                                        $item = mb_strtolower($item, $this->getEncoding());
                                    }
                                    $inParameters[$parameterNameItem] = "BINARY(:{$parameterNameItem})";
                                } else {
                                    $inParameters[$parameterNameItem] = ":{$parameterNameItem}";
                                }
                            }
                            $this->parameters[$parameterNameItem] = $item;
                        }
                        if ($inParameters) {
                            if (1 == count($inParameters)) {
                                reset($inParameters);
                                $orParameters[key($inParameters)] = current($inParameters);
                                $inParameters = [];
                            } else {
                                $inParametersStr = implode(", ", $inParameters);
                                if (false == $filter->isCaseSensitive()) {
                                    $sql .= "LOWER({$column}) IN ($inParametersStr)";
                                } else {
                                    $sql .= "{$column} IN ($inParametersStr)";
                                }
                            }
                        }
                        if ($orParameters) {
                            $orSql = [];
                            foreach ($orParameters as $parameterNameItem => $partialSql) {
                                $item = $this->parameters[$parameterNameItem];
                                if (is_string($item)) {
                                    if (false == $filter->isCaseSensitive()) {
                                        $orSql[] = "LOWER({$column}) = {$partialSql}";
                                    } else {
                                        $orSql[] = "{$column} = {$partialSql}";
                                    }
                                } elseif (is_null($item)) {
                                    $orSql[] = "{$column} IS NULL";
                                } elseif (is_int($item) || is_float($item)) {
                                    $orSql[] = "{$column} = {$partialSql}";
                                }
                            }
                            if ($orSql) {
                                if ($inParameters) {
                                    $sql = "({$sql} OR " . implode(" OR ", $orSql) . ")";
                                } else {
                                    $sql = "(" . implode(" OR ", $orSql) . ")";
                                }
                            }
                        }
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
                if ($this->getMapping()->getQueryParser()->getFilterExpression()) {
                    $sql = [];
                    foreach ($this->getMapping()->getQueryParser()->getFilterExpression()->getLexer()->getTokensNormalized() as $token) {
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
        if ($this->getMapping()->getQueryParser()->getSorts()) {
            $orderBy = [];
            $AllowedSortings = $this->getMapping()->getAllowedSorts();
            if ($AllowedSortings) {
                foreach ($this->getMapping()->getQueryParser()->getSorts() as $index => $sort) {
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
                    $column = $this->getMapping()->getColumnFromKey($AllowedSorting->getKey());
                    $orderBy[$index] = [
                        $column,
                        ($sort->isAscending() ? "ASC" : "DESC"),
                    ];
                }
                if ($orderBy) {
                    $this->orderBy = $orderBy;
                }
            }
        }
        return $this;
    }

    /**
     * Generates all conditions, sorting, and parameters, and applies them to an instance of \Doctrine\ORM\QueryBuilder.
     * @return object $this
     */
    public function generateOnQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->generate();
        if ($this->getWhere()) {
            $queryBuilder->andWhere($this->getWhere());
        }
        if ($this->getOrderBy()) {
            foreach ($this->getOrderBy() as $order) {
                $queryBuilder->addOrderBy($order[0], $order[1]);
            }
        }
        if ($this->getParameters()) {
            foreach ($this->getParameters() as $key => $value) {
                $queryBuilder->setParameter($key, $value);
            }
        }
        return $this;
    }
}
