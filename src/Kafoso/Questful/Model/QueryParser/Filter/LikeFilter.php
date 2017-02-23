<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\RuntimeException;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\QueryParser;

class LikeFilter extends AbstractStringFilter
{
    const MATCH_PATTERN = '/^((\%"(.*)"(\%?))|("(.*)"\%))(\/(i))?$/';

    private $hasWildcardLeft = false;
    private $hasWildcardRight = false;

    /**
     * @override
     */
    public function __construct($expression, $key, $value, $operator = null)
    {
        preg_match(self::MATCH_PATTERN, $value, $match);
        if (!$match) {
            throw new UnexpectedValueException(sprintf(
                "Expects LIKE value to match \"%s\". Found: %s",
                self::MATCH_PATTERN,
                FormattingHelper::found($value)
            ), QueryParser::EXCEPTION_CODE);
        }
        $innerValue = null; // Without percentage symbol and double quote wrappers
        if (isset($match[3]) && "" !== $match[3]) {
            $innerValue = $match[3];
            $this->hasWildcardLeft = true;
            $this->hasWildcardRight = (bool)$match[4];
        } elseif (isset($match[6]) && "" !== $match[6]) {
            $innerValue = $match[6];
            $this->hasWildcardRight = true;
        } else {
            throw new RuntimeException(sprintf(
                "Reached unexpected case for regular expression %s on: %s",
                self::MATCH_PATTERN,
                $value
            ), QueryParser::EXCEPTION_CODE);
        }
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
        if (isset($match[8]) && $match[8]) {
            $this->modifiers = array_unique(str_split($match[8]));
            $this->isCaseSensitive = (false == in_array("i", $this->modifiers));
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['extra']['hasWildcardLeft'] = $this->hasWildcardLeft();
        $array['extra']['hasWildcardRight'] = $this->hasWildcardRight();
        return $array;
    }

    /**
     * @return boolean
     */
    public function hasWildcardLeft()
    {
        return $this->hasWildcardLeft;
    }

    /**
     * @return boolean
     */
    public function hasWildcardRight()
    {
        return $this->hasWildcardRight;
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
        return "like";
    }
}
