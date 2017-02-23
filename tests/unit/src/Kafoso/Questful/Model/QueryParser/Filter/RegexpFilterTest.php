<?php
use Kafoso\Questful\Model\QueryParser\Filter\RegexpFilter;

class RegexpFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $RegexpFilter = new RegexpFilter('foo=/bar/', "foo", "/bar/");
        $this->assertInstanceOf(RegexpFilter::class, $RegexpFilter);
    }

    public function testBasicGetters()
    {
        $RegexpFilter = new RegexpFilter('foo=/bar/', "foo", "/bar/");
        $this->assertSame("foo", $RegexpFilter->getKey());
        $this->assertSame("bar", $RegexpFilter->getValue());
        $this->assertSame([], $RegexpFilter->getModifiers());
        $this->assertTrue($RegexpFilter->isCaseSensitive());
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Expects argument '$value' to match regular expression 'foo=/bar'. Found: /^\/(.*)\/(i?)$/
     */
    public function testConstructorThrowsExceptionWhenValueIsInvalid()
    {
        new RegexpFilter('foo=/bar', "foo", "/bar");
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage 'filter=foo=/bar//': Regular expression is invalid
     */
    public function testConstructorThrowsExceptionWhenRegularExpressionIsOnInvalidForm()
    {
        new RegexpFilter('foo=/bar//', "foo", "/bar//");
    }

    /**
     * @dataProvider    dataProvider_testCaseSensitivity
     */
    public function testCaseSensitivity($expected, $expectedModifiers, $value)
    {
        $RegexpFilter = new RegexpFilter('foo=' . $value, "foo", $value);
        $this->assertSame($expected, $RegexpFilter->isCaseSensitive());
        $this->assertSame($expectedModifiers, $RegexpFilter->getModifiers());
    }

    public function dataProvider_testCaseSensitivity()
    {
        return [
            [true, [], "/bar/"],
            [false, ["i"], "/bar/i"],
        ];
    }

    /**
     * @dataProvider    dataProvider_testValidOperator
     */
    public function testValidOperator($operator)
    {
        $RegexpFilter = new RegexpFilter('foo=/bar/', "foo", "/bar/", $operator);
        $this->assertSame($operator, $RegexpFilter->getOperator());
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
     * @expectedExceptionMessageRegExp    /^Expected operator to be one of \[(.+)]\. Found: \(string\) (.+)$/
     */
    public function testConstructorThrowsExceptionWhenUnsupportedOperatorIsProvided($operator)
    {
        new RegexpFilter('foo=/bar/', "foo", "/bar/", $operator);
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
