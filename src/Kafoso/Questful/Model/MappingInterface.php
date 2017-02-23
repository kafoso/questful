<?php
namespace Kafoso\Questful\Model;

interface MappingInterface
{
    const EXCEPTION_CODE = 4;

    const DEFAULT_COLUMN_VALIDATION_REGEXP = '/^[a-z][0-9a-z]*([_\.]+[0-9a-z]+)*$/i';
    const DEFAULT_KEY_VALIDATION_REGEXP = '/^[a-z][0-9a-z]*([_\.][0-9a-z]+)*$/i';
}
