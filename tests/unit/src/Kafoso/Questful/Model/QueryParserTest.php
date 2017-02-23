<?php
use Kafoso\Questful\Model\QueryParser;
use Kafoso\Questful\Model\QueryParser\Filter\AbstractFilter;
use Kafoso\Questful\Model\QueryParser\FilterExpression;

class QueryParserTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $query = ["foo" => "bar"];
        $queryParser = new QueryParser($query);
        $this->assertInstanceOf(QueryParser::class, $queryParser);
    }

    public function testBasicGetters()
    {
        $query = ["foo" => "bar"];
        $queryParser = new QueryParser($query);
        $this->assertEquals($query, $queryParser->getQuery());
    }

    public function testParseWorksWithNoMatchingCredentials()
    {
        $query = ["foo" => "bar"];
        $queryParser = new QueryParser($query);
        $queryParser->parse();
        $this->assertSame([], $queryParser->getFilters());
        $this->assertNull($queryParser->getFilterExpression());
        $this->assertSame([], $queryParser->getSorts());
    }

    public function testParseWorksWithMatchingFilterCredential()
    {
        $query = ["filter" => ["foo=\"bar\""]];
        $queryParser = new QueryParser($query);
        $queryParser->parse();
        $this->assertCount(1, $queryParser->getFilters());
        $this->assertInstanceOf(AbstractFilter::class, $queryParser->getFilters()[0]);
    }

    public function testParseWorksWithMatchingFilterAndFilterExpressionCredential()
    {
        $query = [
            "filter" => [
                "foo=\"bar\"",
                "foo!=\"Bar\"",
            ],
            "filterExpression" => "0and1"
        ];
        $queryParser = new QueryParser($query);
        $queryParser->parse();
        $this->assertCount(2, $queryParser->getFilters());
        $this->assertInstanceOf(AbstractFilter::class, $queryParser->getFilters()[0]);
        $this->assertInstanceOf(AbstractFilter::class, $queryParser->getFilters()[1]);
        $this->assertInstanceOf(FilterExpression::class, $queryParser->getFilterExpression());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    'filter[0]=foo=bar' is malformed
     */
    public function testParseThrowsExceptionWithInvalidFilterCredential()
    {
        $query = ["filter" => ["foo=bar"]];
        $queryParser = new QueryParser($query);
        $queryParser->parse();
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Parameter 'filter' is missing and is required when specifying parameter 'filterExpression'
     */
    public function testParseThrowsExceptionWhenFilterExpressionIsProvidedButFilterIsNot()
    {
        $query = ["filterExpression" => "0and1"];
        $queryParser = new QueryParser($query);
        $queryParser->parse();
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Parameter 'filter' contains indexes [0], which are not represented in parameter 'filterExpression=1' (indexes: [1])
     */
    public function testParseThrowsExceptionWhenFilterExpressionHasIndexesThatDoNotMatchInFilterdexes()
    {
        $query = [
            "filter" => [
                "foo=\"bar\"",
            ],
            "filterExpression" => "1"
        ];
        $queryParser = new QueryParser($query);
        $queryParser->parse();
    }
}
