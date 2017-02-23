<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\QueryParser;

class RegexpFilter extends AbstractStringFilter
{
    const MATCH_PATTERN = '/^\/(.*)\/(i?)$/';

    /**
     * @override
     */
    public function __construct($expression, $key, $value, $operator = null)
    {
        preg_match(self::MATCH_PATTERN, $value, $match);
        if (!$match) {
            throw new UnexpectedValueException(sprintf(
                "Expects argument '%s' to match regular expression '%s'. Found: %s",
                '$value',
                $expression,
                self::MATCH_PATTERN,
                FormattingHelper::found($value)
            ), QueryParser::EXCEPTION_CODE);
        }
        $innerValue = $match[1]; // Without modifiers
        if (false === @preg_match("/$innerValue/", null)) {
            throw new BadRequestException(sprintf(
                "'filter=%s': Regular expression is invalid",
                $expression
            ), QueryParser::EXCEPTION_CODE);
        }
        parent::__construct($expression, $key, $innerValue, $operator);
        if (isset($match[2]) && $match[2]) {
            $this->modifiers = str_split($match[2]);
            $this->isCaseSensitive = (false == in_array("i", $this->modifiers));
        }
    }

    /**
     * @override
     */
    public static function getAvailableOperators()
    {
        return ["=", "!="];
    }

    public static function getIdentifier()
    {
        return "regexp";
    }
}
