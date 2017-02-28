<?php
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;

class InFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $inFilter = new InFilter('foo=["bar"]', "foo", "[\"bar\"]");
        $this->assertInstanceOf(InFilter::class, $inFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllSupportedTokens
     */
    public function testConstructorWorksWithAllSupportedTokens($expectedValue, $innerArrayValue)
    {
        $inFilter = new InFilter("foo=[$innerArrayValue]", "foo", "[$innerArrayValue]");
        $this->assertSame($expectedValue, $inFilter->getValue());
    }

    public function dataProvider_testConstructorWorksWithAllSupportedTokens()
    {
        return [
            [[null], "null"],
            [[true], "true"],
            [[false], "false"],
            [[1], "1"],
            [[3.14], "3.14"],
            [["bar"], '"bar"'],
        ];
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessageRegExp /Expects value to match regular expression '\/(.+?)\/'\. Found: \(string\) bar/
     */
    public function testConstructorThrowsExceptionWhenValueIsInvalid()
    {
        new InFilter('foo=bar', "foo", "bar");
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Supported operators are ["<=",">=","!=","=",">","<"]. Found: (string) -
     */
    public function testConstructorThrowsExceptionWhenOperatorIsInvalid()
    {
        new InFilter('foo=[\"bar\"]', "foo", "[\"bar\"]", "-");
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage Array in 'filter[]=foo=[ ]' is empty
     */
    public function testConstructorThrowsExceptionWhenContentsOfSquareBracketsInValueArgumentIsEmpty()
    {
        new InFilter('foo=[ ]', "foo", "[ ]");
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage 'filter[]=foo=[bar"]' is malformed: [bar"]
     */
    public function testConstructorThrowsExceptionWhenContentsOfSquareBracketsInValueArgumentIsMalformed()
    {
        new InFilter('foo=[bar"]', "foo", "[bar\"]");
    }

    /**
     * @dataProvider dataProvider_testConstructorThrowsExceptionWhenContentsOfSquareBracketsInValueArgumentContainsIllegalToken
     */
    public function testConstructorThrowsExceptionWhenContentsOfSquareBracketsInValueArgumentContainsIllegalToken($expectedExceptionMessage, $token)
    {
        try {
            new InFilter("foo=[$token]", "foo", "[$token]");
        } catch (\Kafoso\Questful\Exception\BadRequestException $e) {
            $this->assertSame($expectedExceptionMessage, $e->getMessage());
            return;
        }
        $this->fail();
    }

    public function dataProvider_testConstructorThrowsExceptionWhenContentsOfSquareBracketsInValueArgumentContainsIllegalToken()
    {
        return [
            ["'filter[]=foo=[PHP_URL_SCHEME]': Illegal token (\\PhpParser\\Node\\Expr\\ConstFetch) 'PHP_URL_SCHEME' at index 0", "PHP_URL_SCHEME"],
            ["'filter[]=foo=[[]]': Illegal token (\\PhpParser\\Node\\Expr\\Array_) at index 0", "[]"],
            ["'filter[]=foo=[new \stdClass]': Illegal token (\\PhpParser\\Node\\Expr\\New_) at index 0", "new \stdClass"],
            ["'filter[]=foo=[\PDO::FETCH_ASSOC]': Illegal token (\\PhpParser\\Node\\Expr\\ClassConstFetch) at index 0", "\PDO::FETCH_ASSOC"],
            ["'filter[]=foo=[!true]': Illegal token (\\PhpParser\\Node\\Expr\\BooleanNot) at index 0", "!true"],
        ];
    }
}
