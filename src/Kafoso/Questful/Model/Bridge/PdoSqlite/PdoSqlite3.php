<?php
namespace Kafoso\Questful\Model\Bridge\PdoSqlite;

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
 * A bridge for SQLite version 3.
 * Notice: Usage requires handling of BINARY and REGEXP.
 */
class PdoSqlite3 extends AbstractPdoSqlite
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
                                    $sql .= "LOWER({$column}) {$operator} :{$parameterName}";
                                    $value = mb_strtolower($value);
                                } else {
                                    $sql .= "{$column} {$operator} :{$parameterName}";
                                }
                            } else {
                                $sql .= "{$column} {$operator} :{$parameterName}";
                            }
                            $this->parameters[$parameterName] = $value;
                        }
                    } elseif ($filter instanceof LikeFilter) {
                        $value = $filter->getValue();
                        if (false == $filter->isCaseSensitive()) {
                            $sql .= "LOWER({$column}) {$not}LIKE :{$parameterName} ESCAPE '\'";
                            $value = mb_strtolower($value);
                        } else {
                            $sql .= "{$column} {$not}LIKE :{$parameterName} ESCAPE '\'";
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
                            $sql .= "LOWER({$column}) {$not}IN (";
                        } else {
                            $sql .= "{$column} {$not}IN (";
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
                            $sql .= "LOWER({$column}) {$not}REGEXP :{$parameterName}";
                            $value = mb_strtolower($value);
                        } else {
                            $sql .= "{$column} {$not}REGEXP :{$parameterName}";
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
                    $this->where = $this->_syntaxTreeToSql(
                        $this->mapping->getQueryParser()->getFilterExpression()->getLexer()->getSyntaxTree(),
                        $conditions
                    );
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

    private function _syntaxTreeToSql(\PhpParser\Node $node, array $conditions)
    {
        $sql = "";
        if ($node instanceof \PhpParser\Node\Expr\BinaryOp) {
            if ($node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalAnd) {
                $sql .= sprintf(
                    "(%s AND %s)",
                    $this->_syntaxTreeToSql($node->left, $conditions),
                    $this->_syntaxTreeToSql($node->right, $conditions)
                );
            } elseif ($node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalOr) {
                $sql .= sprintf(
                    "(%s OR %s)",
                    $this->_syntaxTreeToSql($node->left, $conditions),
                    $this->_syntaxTreeToSql($node->right, $conditions)
                );
            } elseif ($node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalXor) {
                $sql .= sprintf(
                    "XOR(%s, %s)",
                    $this->_syntaxTreeToSql($node->left, $conditions),
                    $this->_syntaxTreeToSql($node->right, $conditions)
                );
            }
        } elseif ($node instanceof \PhpParser\Node\Scalar\LNumber) {
            $sql .= $conditions[$node->value];
        }
        return $sql;
    }
}
