<?php
namespace Kafoso\Questful\Model\QueryParser;

use Kafoso\Questful\Exception\FormattingHelper;

interface SortInterface
{
    const VALIDATION_REGEX = "/^(\+|-)?((\w+)(\.\w+)*)$/";
}
