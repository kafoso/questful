<?php
namespace Kafoso\Questful\Factory\Model\QueryParser\Sort;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Model\QueryParser;
use Kafoso\Questful\Model\QueryParser\Sort;
use Kafoso\Questful\Model\QueryParser\SortInterface;

class SortFactory
{
    /**
     * @param $sortSrc mixed
     * @throws \Kafoso\Questful\Exception\BadRequestException
     * @return array (\Kafoso\Questful\Model\QueryParser\Sort[])
     */
    public function createFromQuery($sortSrc)
    {
        if (false == is_array($sortSrc)) {
            throw new BadRequestException(sprintf(
                "Parameter 'sort[]' must be an array. Found: sort=%s",
                FormattingHelper::found($sortSrc)
            ), QueryParser::EXCEPTION_CODE);
        }
        $sortSrc = array_unique($sortSrc);
        $sorts = [];
        foreach ($sortSrc as $index => $expression) {
            preg_match('/^(\-?)\d+$/', $index, $match);
            if (!$match) {
                throw new BadRequestException(sprintf(
                    "Index in 'sort[%s]=%s' must be an integer. Found: %s",
                    $index,
                    $expression,
                    FormattingHelper::found($index)
                ), QueryParser::EXCEPTION_CODE);
            } elseif ("-" == $match[1]) {
                throw new BadRequestException(sprintf(
                    "Index in 'sort[%s]=%s' is negative; all indexes must be >= 0",
                    $index,
                    $expression,
                    FormattingHelper::found($index)
                ), QueryParser::EXCEPTION_CODE);
            }
            $index = intval($index);
            preg_match(SortInterface::VALIDATION_REGEX, $expression, $match);
            if (!$match) {
                throw new BadRequestException(sprintf(
                    "Sort expression '%s' (in 'sort[%s]=%s') does not match a supported pattern.",
                    $expression,
                    $index,
                    $expression
                ), QueryParser::EXCEPTION_CODE);
            }
            $direction = "+";
            if ($match[1]) {
                $direction = $match[1];
            }
            $sorts[$index] = new Sort($match[2], ("+" == $direction));
        }
        ksort($sorts);
        return $sorts;
    }
}
