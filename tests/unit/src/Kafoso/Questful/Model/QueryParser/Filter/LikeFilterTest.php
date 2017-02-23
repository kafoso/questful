<?php
use Kafoso\Questful\Model\QueryParser\Filter\LikeFilter;

class LikeFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $LikeFilter = new LikeFilter('foo=%"bar"%', "foo", "%\"bar\"%", "=");
        $this->assertInstanceOf(LikeFilter::class, $LikeFilter);
    }

    public function testBasicGetters()
    {
        $LikeFilter = new LikeFilter('foo=%"bar"%', "foo", "%\"bar\"%", "=");
        $this->assertSame("foo", $LikeFilter->getKey());
        $this->assertTrue($LikeFilter->hasWildcardLeft());
        $this->assertTrue($LikeFilter->hasWildcardRight());
        $this->assertTrue($LikeFilter->isCaseSensitive());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage    Expects LIKE value to match "/^((\%"(.*)"(\%?))|("(.*)"\%))(\/(i))?$/". Found: (string) "bar"
     */
    public function testConstructorThrowsExceptionWhenValueIsInvalid()
    {
        new LikeFilter('foo="bar"', "foo", "\"bar\"");
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage String syntax in 'filter[foo]=%"bar""' is invalid: bar"
     */
    public function testConstructorThrowsExceptionWhenSyntaxOfInnerValueIsInvalid()
    {
        new LikeFilter('foo=%"bar"', "foo", "%\"bar\"\"");
    }

    /**
     * @dataProvider    dataProvider_testWildcards
     */
    public function testWildcards($expectedLeft, $expectedRight, $value)
    {
        $LikeFilter = new LikeFilter('foo='.$value, "foo", $value);
        $this->assertSame($expectedLeft, $LikeFilter->hasWildcardLeft());
        $this->assertSame($expectedRight, $LikeFilter->hasWildcardRight());
    }

    public function dataProvider_testWildcards()
    {
        return [
            [true, false, "%\"bar\""],
            [true, true, "%\"bar\"%"],
            [false, true, "\"bar\"%"],
        ];
    }

    /**
     * @dataProvider    dataProvider_testCaseSensitivity
     */
    public function testCaseSensitivity($expected, $value)
    {
        $LikeFilter = new LikeFilter('foo='.$value, "foo", $value);
        $this->assertSame($expected, $LikeFilter->isCaseSensitive());
    }

    public function dataProvider_testCaseSensitivity()
    {
        return [
            [true, "%\"bar\""],
            [true, "%\"bar\"%"],
            [true, "\"bar\"%"],
            [false, "%\"bar\"/i"],
            [false, "%\"bar\"%/i"],
            [false, "\"bar\"%/i"],
        ];
    }

    /**
     * @dataProvider    dataProvider_testValidOperator
     */
    public function testValidOperator($operator)
    {
        $LikeFilter = new LikeFilter("foo{$operator}%\"bar\"", "foo", "%\"bar\"", $operator);
        $this->assertSame($operator, $LikeFilter->getOperator());
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
        new LikeFilter("foo{$operator}%\"bar\"", "foo", "%\"bar\"", $operator);
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
