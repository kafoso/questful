<?php
use Kafoso\Questful\Model\QueryParser\Filter\FloatFilter;

class FloatFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $FloatFilter = new FloatFilter('foo=3.14', "foo", 3.14, "=");
        $this->assertInstanceOf(FloatFilter::class, $FloatFilter);
    }

    public function testBasicGetters()
    {
        $FloatFilter = new FloatFilter('foo=3.14', "foo", 3.14, "=");
        $this->assertSame("foo", $FloatFilter->getKey());
        $this->assertSame("=", $FloatFilter->getOperator());
        $this->assertSame(3.14, $FloatFilter->getValue());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\InvalidArgumentException
     * @expectedExceptionMessage    Expects argument '$value' to be "double". Found: (string) 3.14
     */
    public function testConstructorThrowsExceptionWhenValueIsOfWrongDataType()
    {
        new FloatFilter('foo=3.14', "foo", "3.14");
    }

    /**
     * @dataProvider    dataProvider_testValidOperator
     */
    public function testValidOperator($operator)
    {
        $FloatFilter = new FloatFilter("foo{$operator}3.14", "foo", 3.14, $operator);
        $this->assertSame($operator, $FloatFilter->getOperator());
    }

    public function dataProvider_testValidOperator()
    {
        return [
            ["="],
            ["!="],
            ["<"],
            ["<="],
            [">"],
            [">="],
        ];
    }

    /**
     * @dataProvider    dataProvider_testVariousValueCombinations
     */
    public function testVariousValueCombinations($expected)
    {
        $FloatFilter = new FloatFilter('foo='.$expected, "foo", $expected, "=");
        $this->assertSame($expected, $FloatFilter->getValue());
    }

    public function dataProvider_testVariousValueCombinations()
    {
        return [
            [3.14],
            [-0.0001],
        ];
    }
}
