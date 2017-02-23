<?php
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;

class StringFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $stringFilter = new StringFilter('foo="bar"', "foo", "\"bar\"");
        $this->assertInstanceOf(StringFilter::class, $stringFilter);
    }

    public function testBasicGetters()
    {
        $stringFilter = new StringFilter('foo="bar"', "foo", "\"bar\"");
        $this->assertSame("foo", $stringFilter->getKey());
        $this->assertSame("bar", $stringFilter->getValue());
        $this->assertSame([], $stringFilter->getModifiers());
        $this->assertTrue($stringFilter->isCaseSensitive());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessageRegExp    /Expects value to match "\/(.+?)\/"\. Found: \(string\) bar/
     */
    public function testConstructorThrowsExceptionWhenValueIsInvalid()
    {
        new StringFilter('foo=bar', "foo", "bar");
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage String syntax in 'filter[foo]="bar""' is invalid: bar"
     */
    public function testConstructorThrowsExceptionWhenSyntaxOfInnerValueIsInvalid()
    {
        new StringFilter('foo="bar"', "foo", "\"bar\"\"");
    }

    /**
     * @dataProvider    dataProvider_testCaseSensitivity
     */
    public function testCaseSensitivity($expected, $expectedModifiers, $value)
    {
        $stringFilter = new StringFilter('foo='.$value, "foo", $value);
        $this->assertSame($expected, $stringFilter->isCaseSensitive());
        $this->assertSame($expectedModifiers, $stringFilter->getModifiers());
    }

    public function dataProvider_testCaseSensitivity()
    {
        return [
            [true, [], "\"bar\""],
            [false, ["i"], "\"bar\"/i"],
        ];
    }

    /**
     * @dataProvider    dataProvider_testValidOperator
     */
    public function testValidOperator($operator)
    {
        $stringFilter = new StringFilter("foo{$operator}\"bar\"", "foo", "\"bar\"", $operator);
        $this->assertSame($operator, $stringFilter->getOperator());
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
    public function testVariousValueCombinations($expectedValue, $expectedIsCaseSensitive, $value)
    {
        $stringFilter = new StringFilter('foo='.$value, "foo", $value);
        $this->assertSame($expectedValue, $stringFilter->getValue());
        $this->assertSame($expectedIsCaseSensitive, $stringFilter->isCaseSensitive());
    }

    public function dataProvider_testVariousValueCombinations()
    {
        return [
            ["3.14", true, "\"3.14\""],
            ["bar", false, "\"bar\"/i"],
        ];
    }
}
