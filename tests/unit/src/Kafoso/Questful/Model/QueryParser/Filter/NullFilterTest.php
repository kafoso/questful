<?php
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;

class NullFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $NullFilter = new NullFilter('foo=null', "foo", null, "=");
        $this->assertInstanceOf(NullFilter::class, $NullFilter);
    }

    public function testBasicGetters()
    {
        $NullFilter = new NullFilter('foo=null', "foo", null, "=");
        $this->assertSame("foo", $NullFilter->getKey());
        $this->assertSame("=", $NullFilter->getOperator());
        $this->assertNull($NullFilter->getValue());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\InvalidArgumentException
     * @expectedExceptionMessage    Expects argument '$value' to be "NULL". Found: (string) null
     */
    public function testConstructorThrowsExceptionWhenValueIsOfWrongDataType()
    {
        new NullFilter('foo="null"', "foo", "null");
    }

    /**
     * @dataProvider    dataProvider_testValidOperator
     */
    public function testValidOperator($operator)
    {
        $NullFilter = new NullFilter("foo{$operator}null", "foo", null, $operator);
        $this->assertSame($operator, $NullFilter->getOperator());
    }

    public function dataProvider_testValidOperator()
    {
        return [
            ["="],
            ["!="],
        ];
    }

    /**
     * @dataProvider    dataProvider_testConstructorThrowsExceptionWhenUnsupportedOperatorIsProvided
     * @expectedException   Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessageRegExp    /^Expected operator to be one of \["\=","\!\="]\. Found: \(string\) (\<(\=?)|\>(\=?))$/
     */
    public function testConstructorThrowsExceptionWhenUnsupportedOperatorIsProvided($operator)
    {
        new NullFilter("foo{$operator}null", "foo", null, $operator);
    }

    public function dataProvider_testConstructorThrowsExceptionWhenUnsupportedOperatorIsProvided()
    {
        return [
            ["<"],
            ["<="],
            [">"],
            [">="],
        ];
    }
}
