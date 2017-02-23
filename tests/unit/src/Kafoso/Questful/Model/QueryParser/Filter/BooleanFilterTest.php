<?php
use Kafoso\Questful\Model\QueryParser\Filter\BooleanFilter;

class BooleanFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $BooleanFilter = new BooleanFilter('foo=true', "foo", true, "=");
        $this->assertInstanceOf(BooleanFilter::class, $BooleanFilter);
    }

    public function testBasicGetters()
    {
        $BooleanFilter = new BooleanFilter('foo=true', "foo", true, "=");
        $this->assertSame("foo", $BooleanFilter->getKey());
        $this->assertSame("=", $BooleanFilter->getOperator());
        $this->assertTrue($BooleanFilter->getValue());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\InvalidArgumentException
     * @expectedExceptionMessage    Expects argument '$value' to be "boolean". Found: (string) true
     */
    public function testConstructorThrowsExceptionWhenValueIsOfWrongDataType()
    {
        new BooleanFilter('foo=true', "foo", "true");
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage    Invalid operator. Supported operators are ["<=",">=","!=","=",">","<"]. Found: (string) !==
     */
    public function testConstructorThrowsExceptionWhenInvalidOperatorIsProvided()
    {
        new BooleanFilter('foo!==true', "foo", true, "!==");
    }

    /**
     * @dataProvider    dataProvider_testConstructorThrowsExceptionWhenUnsupportedOperatorIsProvided
     * @expectedException   Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessageRegExp    /^Expected operator to be one of \["\="]\. Found: \(string\) (\!\=|\<(\=?)|\>(\=?))$/
     */
    public function testConstructorThrowsExceptionWhenUnsupportedOperatorIsProvided($operator)
    {
        $BooleanFilter = new BooleanFilter("foo{$operator}true", "foo", true, $operator);
        $this->assertSame($operator, $BooleanFilter->getOperator());
    }

    public function dataProvider_testConstructorThrowsExceptionWhenUnsupportedOperatorIsProvided()
    {
        return [
            ["!="],
            ["<"],
            ["<="],
            [">"],
            [">="],
        ];
    }

    /**
     * @dataProvider    dataProvider_testAllValueCombinations
     */
    public function testAllValueCombinations($expected, $value)
    {
        $BooleanFilter = new BooleanFilter('foo='.($value ? "true" : "false"), "foo", $value, "=");
        $this->assertSame($expected, $BooleanFilter->getValue());
    }

    public function dataProvider_testAllValueCombinations()
    {
        return [
            [true, true],
            [false, false],
        ];
    }
}
