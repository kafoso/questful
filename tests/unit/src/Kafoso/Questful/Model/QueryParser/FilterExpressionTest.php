<?php
use Kafoso\Questful\Model\QueryParser\FilterExpression;

class FilterExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $filterExpression = new FilterExpression("0");
        $this->assertInstanceOf(FilterExpression::class, $filterExpression);
    }

    public function testBasicGetters()
    {
        $filterExpression = new FilterExpression("0");
        $this->assertSame("0", $filterExpression->getExpression());
        $this->assertEquals([0], $filterExpression->getIndexes());
        $expected = [
            "expressionNormalized" => "0",
            "expressionOriginal" => "0",
            "indexes" => [0],
        ];
        $this->assertEquals($expected, $filterExpression->toArray());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Malformed expression. Unexpected token ' ' at position 1 in expression: ( 0
     */
    public function testConstructorThrowsExceptionWhenExpressionHasMismatchingNumberOfParentheses()
    {
        new FilterExpression("( 0");
    }

    /**
     * @dataProvider dataProvider_testConstructorThrowsExceptionWhenExpressionHasInvalidIdentifiers
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessageRegExp    /Malformed expression\. Unexpected token '.' at position \d+ in expression: /
     */
    public function testConstructorThrowsExceptionWhenExpressionHasInvalidIdentifiers($value)
    {
        new FilterExpression($value);
    }

    public function dataProvider_testConstructorThrowsExceptionWhenExpressionHasInvalidIdentifiers()
    {
        return [
            ["0 nad 1"],
            ["0&1"]
        ];
    }

    public function testCanCorrectlyIdentifyIndexes()
    {
        $filterExpression = new FilterExpression("42and((5and42)or0)");
        $this->assertEquals([0, 5, 42], $filterExpression->getIndexes());
    }
}
