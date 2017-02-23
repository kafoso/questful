<?php
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\QueryParser;

class QueryParserFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromUri()
    {
        $url = "http://www.example.com/path?foo=bar";
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri($url);
        $this->assertInstanceOf(QueryParser::class, $queryParser);
        $this->assertSame(["foo" => "bar"], $queryParser->getQuery());
    }
}
