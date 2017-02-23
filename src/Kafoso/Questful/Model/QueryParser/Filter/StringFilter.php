<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\RuntimeException;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\QueryParser;

class StringFilter extends AbstractStringFilter implements ScalarFilterInterface
{
    const MATCH_PATTERN = '/^"(.*)"(\/([i]))?$/';

    /**
     * @override
     */
    public function __construct($expression, $key, $value, $operator = null)
    {
        preg_match(self::MATCH_PATTERN, $value, $match);
        if (!$match) {
            throw new UnexpectedValueException(sprintf(
                "Expects value to match \"%s\". Found: %s",
                self::MATCH_PATTERN,
                FormattingHelper::found($value)
            ), QueryParser::EXCEPTION_CODE);
        }
        $innerValue = $match[1]; // Without modifiers and double quotes
        try {
            $this->validateSyntax($innerValue);
        } catch (\PhpParser\Error $e) {
            throw new BadRequestException(sprintf(
                "String syntax in 'filter[%s]=%s' is invalid: %s",
                $key,
                $value,
                $innerValue
            ), QueryParser::EXCEPTION_CODE);
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf(
                "Unexpected error occurred during syntax validation of string 'filter[%s]=%s' for string: %s",
                $key,
                $value,
                $innerValue
            ), QueryParser::EXCEPTION_CODE, $e);
        }
        parent::__construct($expression, $key, $innerValue, $operator);
        if (isset($match[3]) && $match[3]) {
            $this->modifiers = array_unique(str_split($match[3]));
            $this->isCaseSensitive = (false == in_array("i", $this->modifiers));
        }
    }

    public static function getIdentifier()
    {
        return "string";
    }
}
