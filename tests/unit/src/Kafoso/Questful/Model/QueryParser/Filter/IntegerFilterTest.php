<?php
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;

class IntegerFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $IntegerFilter = new IntegerFilter('foo=42', "foo", 42, "=");
        $this->assertInstanceOf(IntegerFilter::class, $IntegerFilter);
    }

    public function testBasicGetters()
    {
        $IntegerFilter = new IntegerFilter('foo=42', "foo", 42, "=");
        $this->assertSame("foo", $IntegerFilter->getKey());
        $this->assertSame("=", $IntegerFilter->getOperator());
        $this->assertSame(42, $IntegerFilter->getValue());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\InvalidArgumentException
     * @expectedExceptionMessage    Expects argument '$value' to be "integer". Found: (string) 42
     */
    public function testConstructorThrowsExceptionWhenValueIsOfWrongDataType()
    {
        new IntegerFilter('foo="42"', "foo", "42");
    }

    /**
     * @dataProvider    dataProvider_testValidOperator
     */
    public function testValidOperator($operator)
    {
        $IntegerFilter = new IntegerFilter("foo{$operator}42", "foo", 42, $operator);
        $this->assertSame($operator, $IntegerFilter->getOperator());
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
        $IntegerFilter = new IntegerFilter("foo={$expected}", "foo", $expected, "=");
        $this->assertSame($expected, $IntegerFilter->getValue());
    }

    public function dataProvider_testVariousValueCombinations()
    {
        return [
            [42],
            [-42],
        ];
    }
}
