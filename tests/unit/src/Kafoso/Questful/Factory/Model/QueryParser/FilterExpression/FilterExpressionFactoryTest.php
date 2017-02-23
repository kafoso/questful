<?php
use Kafoso\Questful\Factory\Model\QueryParser\FilterExpression\FilterExpressionFactory;
use Kafoso\Questful\Model\QueryParser\FilterExpression;

class FilterExpressionFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromQuery()
    {
        $filterExpressionFactory = new FilterExpressionFactory;
        $filterExpression = $filterExpressionFactory->createFromQuery("0");
        $this->assertInstanceOf(FilterExpression::class, $filterExpression);
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Malformed expression. Unexpected token '.' at position 2 in expression: (0.or1)
     */
    public function testCreateFromQueryThrowsExceptionWhenPatternIsInvalid()
    {
        $filterExpressionFactory = new FilterExpressionFactory;
        $filterExpressionFactory->createFromQuery("(0.or1)");
    }
}
