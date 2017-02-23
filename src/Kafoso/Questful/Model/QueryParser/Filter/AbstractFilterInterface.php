<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

interface AbstractFilterInterface
{
    /**
     * @param $expression string
     * @param $key string
     * @param $value string
     * @param $operator null|string         Optional.
     */
    public function __construct($expression, $key, $value, $operator = null);

    /**
     * @return array (string[])
     */
    public static function getAvailableOperators();

    /**
     * @return string
     */
    public static function getIdentifier();

    /**
     * @return string                           As produced by function "gettype".
     */
    public static function getValueDataTypeConstraint();
}
