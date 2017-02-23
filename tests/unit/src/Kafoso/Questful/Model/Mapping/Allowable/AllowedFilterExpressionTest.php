<?php
use Kafoso\Questful\Model\Mapping\Allowable\AllowedFilterExpression;
use Kafoso\Questful\Model\QueryParser\FilterExpression;

class AllowedFilterExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $AllowedFilterExpression = new AllowedFilterExpression("0");
        $this->assertInstanceOf(AllowedFilterExpression::class, $AllowedFilterExpression);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expects argument '$expression' to be a string. Found: (null) null
     */
    public function testConstructorThrowsExceptionWhenExpressionIsInvalid()
    {
        new AllowedFilterExpression(null);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage Malformed expression. Unexpected token 'f' at position 0 in expression: foo
     */
    public function testConstructorThrowsExceptionWhenExpressionArgumentIsMalformed()
    {
        new AllowedFilterExpression("foo");
    }

    public function testBasicGetters()
    {
        $AllowedFilterExpression = new AllowedFilterExpression("0");
        $this->assertSame("0", $AllowedFilterExpression->getExpression());
        $this->assertInstanceOf(FilterExpression::class, $AllowedFilterExpression->getFilterExpression());
    }
}
