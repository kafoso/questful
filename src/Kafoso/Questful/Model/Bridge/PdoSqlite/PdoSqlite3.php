<?php
namespace Kafoso\Questful\Model\Bridge\PdoSqlite;

use Kafoso\Questful\Exception\FormattingHelper;
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
 * A bridge for SQLite version 3.
 * Notice: Usage requires handling of BINARY.
 */
class PdoSqlite3 extends AbstractPdoSqlite
{
    public function generate()
    {
        $this->where = null;
        $this->parameters = [];
        $this->orderBy = null;
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
                                    $sql .= "LOWER({$column}) {$operator} :{$parameterName}";
                                    $value = mb_strtolower($value, $this->getEncoding());
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
                            $sql .= "LOWER({$column}) {$not}LIKE :{$parameterName} ESCAPE '\\'";
                            $value = mb_strtolower($value, $this->getEncoding());
                        } else {
                            $sql .= "{$column} {$not}LIKE :{$parameterName} ESCAPE '\\'";
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
                        $logicalOperatorParameters = [];
                        $value = $filter->getValue();
                        $value = array_values($value);
                        $value = $this->arrayUniqueStrict(array_values($value));
                        foreach ($value as $subIndex => $item) {
                            $parameterNameItem = "{$parameterName}_{$subIndex}";
                            if (is_null($item)) {
                                $logicalOperatorParameters[$parameterNameItem] = ":{$parameterNameItem}";
                            } else {
                                if (is_string($item)) {
                                    if (false == $filter->isCaseSensitive()) {
                                        $item = mb_strtolower($item, $this->getEncoding());
                                    }
                                } elseif (is_bool($item)) {
                                    $item = ($item ? 1 : 0);
                                }
                                $inParameters[$parameterNameItem] = ":{$parameterNameItem}";
                            }
                            $this->parameters[$parameterNameItem] = $item;
                        }
                        if ($inParameters) {
                            if (1 == count($inParameters)) {
                                reset($inParameters);
                                $logicalOperatorParameters[key($inParameters)] = current($inParameters);
                                $inParameters = [];
                            } else {
                                $inParametersStr = implode(", ", $inParameters);
                                if (false == $filter->isCaseSensitive()) {
                                    $sql .= "LOWER({$column}) {$not}IN ($inParametersStr)";
                                } else {
                                    $sql .= "{$column} {$not}IN ($inParametersStr)";
                                }
                            }
                        }
                        if ($logicalOperatorParameters) {
                            $sqlSegments = [];
                            foreach ($logicalOperatorParameters as $parameterNameItem => $partialSql) {
                                $item = $this->parameters[$parameterNameItem];
                                if (is_string($item)) {
                                    if (false == $filter->isCaseSensitive()) {
                                        $sqlSegments[] = "LOWER({$column}) {$operator} {$partialSql}";
                                    } else {
                                        $sqlSegments[] = "{$column} {$operator} {$partialSql}";
                                    }
                                } elseif (is_null($item)) {
                                    $sqlSegments[] = "{$column} IS {$not}NULL";
                                    unset($this->parameters[$parameterNameItem]);
                                } elseif (is_int($item) || is_float($item)) {
                                    $sqlSegments[] = "{$column} {$operator} {$partialSql}";
                                } else {
                                    throw new RuntimeException(sprintf(
                                        "Uncovered case for item (parameter name: %s). Unexpected data type. Found: %s",
                                        $parameterNameItem,
                                        FormattingHelper::found($item)
                                    ));
                                }
                            }
                            if ($sqlSegments) {
                                $logicalOperator = "OR";
                                if ("!=" == $operator) {
                                    $logicalOperator = "AND";
                                }
                                if ($inParameters) {
                                    $sql = "({$sql} {$logicalOperator} " . implode(" {$logicalOperator} ", $sqlSegments) . ")";
                                } else {
                                    $sql = "(" . implode(" {$logicalOperator} ", $sqlSegments) . ")";
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
                    $this->where = $this->_syntaxTreeToSql(
                        $this->getMapping()->getQueryParser()->getFilterExpression()->getLexer()->getSyntaxTree(),
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
        if ($this->getMapping()->getQueryParser()->getSorts()) {
            $sorts = [];
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
