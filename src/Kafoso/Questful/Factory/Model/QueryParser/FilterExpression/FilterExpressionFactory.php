<?php
namespace Kafoso\Questful\Factory\Model\QueryParser\FilterExpression;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Model\QueryParser;
use Kafoso\Questful\Model\QueryParser\FilterExpression;

class FilterExpressionFactory
{
    /**
     * @param $filterExpressionRaw string
     * @throws \Kafoso\Questful\Exception\BadRequestException
     * @return array (\Kafoso\Questful\Model\QueryParser\FilterExpression[])
     */
    public function createFromQuery($filterExpressionRaw)
    {
        if (0 == strlen(trim($filterExpressionRaw))) {
            return null;
        }
        $filterExpression = new FilterExpression($filterExpressionRaw);
        return $filterExpression;
    }
}
