<?php
namespace Kafoso\Questful\Model\Mapping\Allowable\Filter;

interface AbstractAllowedFilterInterface
{
    /**
     * @return string (string[])
     */
    public static function getFilterClassNamespace();

    /**
     * @return array (string[])
     */
    public static function getAvailableConstraints();
}
