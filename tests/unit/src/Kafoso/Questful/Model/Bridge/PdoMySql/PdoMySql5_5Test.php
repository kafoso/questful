<?php
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Bridge\PdoMySql\PdoMySql5_5;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;
use Kafoso\Questful\Model\QueryParser;

class PdoMySql5_5Test extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $mapping = $this
            ->getMockBuilder(Mapping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pdoMySql = new PdoMySql5_5($mapping);
        $this->assertInstanceOf(PdoMySql5_5::class, $pdoMySql);
    }

    public function testBasicGetters()
    {
        $mapping = $this
            ->getMockBuilder(Mapping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pdoMySql = new PdoMySql5_5($mapping);
        $this->assertNull($pdoMySql->getOrderBy());
        $this->assertSame([], $pdoMySql->getParameters());
        $this->assertNull($pdoMySql->getWhere());
        $this->assertSame(["orderBy" => null, "parameters" => [], "where" => null], $pdoMySql->toArray());
    }

    public function testGenerate()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3D"bar"&sort%5B%5D=foo');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedStringFilter("foo"))
            ->allow(new Allowable\AllowedSort("foo"))
            ->validate();
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertSame("(t.foo = BINARY :filter_0)", $pdoMySql->getWhere());
        $this->assertSame("t.foo ASC", $pdoMySql->getOrderBy());
        $this->assertSame(["filter_0" => "bar"], $pdoMySql->getParameters());
    }

    public function testGenerateProducesNothingWhenNothingHasBeenMapped()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3D"bar"&sort%5B%5D=foo');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertNull($pdoMySql->getWhere());
        $this->assertCount(0, $pdoMySql->getParameters());
        $this->assertNull($pdoMySql->getOrderBy());
    }

    public function testGenerateEscapesLikeSearchCorrectly()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3D%25"_\\\\%"%25');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedLikeFilter("foo"))
            ->validate();
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertSame("(t.foo LIKE BINARY :filter_0 ESCAPE '\\')", $pdoMySql->getWhere());
        $this->assertSame(["filter_0" => "%\\_\\\\\\%%"], $pdoMySql->getParameters());
    }

    /**
     * @dataProvider   dataProvider_testGenerateForAllFilterTypesAndCases
     */
    public function testGenerateForAllFilterTypesAndCases(
        $classNamespace,
        $filterQuery,
        $expectedWhere,
        $expectedParameters)
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=' . $filterQuery);
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new $classNamespace("foo"))
            ->validate();
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertSame($expectedWhere, $pdoMySql->getWhere());
        $this->assertSame($expectedParameters, $pdoMySql->getParameters());
    }

    public function dataProvider_testGenerateForAllFilterTypesAndCases()
    {
        return [
            [Allowable\Filter\AllowedNullFilter::class, "foo=null", "(t.foo IS NULL)", []],
            [Allowable\Filter\AllowedNullFilter::class, "foo!=null", "(t.foo IS NOT NULL)", []],

            [Allowable\Filter\AllowedBooleanFilter::class, "foo=true", "(t.foo = 1)", []],
            [Allowable\Filter\AllowedBooleanFilter::class, "foo=false", "(t.foo = 0)", []],

            [Allowable\Filter\AllowedIntegerFilter::class, "foo=42", "(t.foo = :filter_0)", ["filter_0" => 42]],
            [Allowable\Filter\AllowedIntegerFilter::class, "foo!=42", "(t.foo != :filter_0)", ["filter_0" => 42]],
            [Allowable\Filter\AllowedIntegerFilter::class, "foo>42", "(t.foo > :filter_0)", ["filter_0" => 42]],
            [Allowable\Filter\AllowedIntegerFilter::class, "foo>=42", "(t.foo >= :filter_0)", ["filter_0" => 42]],
            [Allowable\Filter\AllowedIntegerFilter::class, "foo<42", "(t.foo < :filter_0)", ["filter_0" => 42]],
            [Allowable\Filter\AllowedIntegerFilter::class, "foo<=42", "(t.foo <= :filter_0)", ["filter_0" => 42]],

            [Allowable\Filter\AllowedFloatFilter::class, "foo=3.14", "(t.foo = :filter_0)", ["filter_0" => 3.14]],
            [Allowable\Filter\AllowedFloatFilter::class, "foo!=3.14", "(t.foo != :filter_0)", ["filter_0" => 3.14]],
            [Allowable\Filter\AllowedFloatFilter::class, "foo>3.14", "(t.foo > :filter_0)", ["filter_0" => 3.14]],
            [Allowable\Filter\AllowedFloatFilter::class, "foo>=3.14", "(t.foo >= :filter_0)", ["filter_0" => 3.14]],
            [Allowable\Filter\AllowedFloatFilter::class, "foo<3.14", "(t.foo < :filter_0)", ["filter_0" => 3.14]],
            [Allowable\Filter\AllowedFloatFilter::class, "foo<=3.14", "(t.foo <= :filter_0)", ["filter_0" => 3.14]],

            [Allowable\Filter\AllowedStringFilter::class, "foo=\"bar\"", "(t.foo = BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo=\"BAR\"/i", "(LOWER(t.foo) = BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo!=\"bar\"", "(t.foo != BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo!=\"BAR\"/i", "(LOWER(t.foo) != BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>\"bar\"", "(t.foo > BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>\"BAR\"/i", "(LOWER(t.foo) > BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>=\"bar\"", "(t.foo >= BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>=\"BAR\"/i", "(LOWER(t.foo) >= BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<\"bar\"", "(t.foo < BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<\"BAR\"/i", "(LOWER(t.foo) < BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<=\"bar\"", "(t.foo <= BINARY :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<=\"BAR\"/i", "(LOWER(t.foo) <= BINARY :filter_0)", ["filter_0" => "bar"]],

            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"bar\"%", "(t.foo LIKE BINARY :filter_0 ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"BAR\"%/i", "(LOWER(t.foo) LIKE BINARY :filter_0 ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"bar\"%", "(t.foo NOT LIKE BINARY :filter_0 ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"BAR\"%/i", "(LOWER(t.foo) NOT LIKE BINARY :filter_0 ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"bar\"", "(t.foo LIKE BINARY :filter_0 ESCAPE '\\')", ["filter_0" => "%bar"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"BAR\"/i", "(LOWER(t.foo) LIKE BINARY :filter_0 ESCAPE '\\')", ["filter_0" => "%bar"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"bar\"", "(t.foo NOT LIKE BINARY :filter_0 ESCAPE '\\')", ["filter_0" => "%bar"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"BAR\"/i", "(LOWER(t.foo) NOT LIKE BINARY :filter_0 ESCAPE '\\')", ["filter_0" => "%bar"]],

            [Allowable\Filter\AllowedInFilter::class, "foo=[\"BAR\"]", "(t.foo IN (:filter_0_0))", ["filter_0_0" => "BAR"]],
            [Allowable\Filter\AllowedInFilter::class, "foo=[\"BAR\"]/i", "(LOWER(t.foo) IN (:filter_0_0))", ["filter_0_0" => "bar"]],
            [Allowable\Filter\AllowedInFilter::class, "foo=[null,true,false,1,3.14,\"bar\"]", "(t.foo IN (:filter_0_0, :filter_0_1, :filter_0_2, :filter_0_3, :filter_0_4, :filter_0_5))", ["filter_0_0" => null, "filter_0_1" => true, "filter_0_2" => false, "filter_0_3" => 1, "filter_0_4" => 3.14, "filter_0_5" => "bar"]],
        ];
    }

    public function testGenerateProcudesCorrectOrderByStatement()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?sort%5B%5D=foo&sort%5B%5D=-bar');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->relate("bar", "t.bar")
            ->allow(new Allowable\AllowedSort("foo"))
            ->allow(new Allowable\AllowedSort("bar"));
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertSame("t.foo ASC, t.bar DESC", $pdoMySql->getOrderBy());
    }

    public function testGenerateAppliesFilterExpressionCorrectly()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo="bar"&filter%5B%5D=baz="bim"&filterExpression=(1or0)');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->relate("baz", "t.baz")
            ->allow(new Allowable\Filter\AllowedStringFilter("foo"))
            ->allow(new Allowable\Filter\AllowedStringFilter("baz"))
            ->allow(new Allowable\AllowedFilterExpression("(1or0)"))
            ->validate();
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertSame("(t.baz = BINARY :filter_1 OR t.foo = BINARY :filter_0)", $pdoMySql->getWhere());
        $this->assertSame(["filter_0" => "bar", "filter_1" => "bim"], $pdoMySql->getParameters());
    }

    public function testGenerateWillCorrectlyHandleMultipleAllowedFiltersWithTheSameKey()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo="bar"&filter%5B%5D=foo=1&filterExpression=(0or1)');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo"))
            ->allow(new Allowable\Filter\AllowedStringFilter("foo"))
            ->allow(new Allowable\AllowedFilterExpression("(0or1)"))
            ->validate();
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertSame("(t.foo = BINARY :filter_0 OR t.foo = :filter_1)", $pdoMySql->getWhere());
        $this->assertSame(["filter_0" => "bar", "filter_1" => 1], $pdoMySql->getParameters());
    }

    public function testGenerateWillApplyXorCorrectly()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=1&filter%5B%5D=foo=2&filterExpression=(0xor1)');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo"))
            ->allow(new Allowable\AllowedFilterExpression("(0xor1)"))
            ->validate();
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertSame("(t.foo = :filter_0 XOR t.foo = :filter_1)", $pdoMySql->getWhere());
        $this->assertSame(["filter_0" => 1, "filter_1" => 2], $pdoMySql->getParameters());
    }

    public function testGenerateWillCorrectlyIdentifyAndReuseIndexes()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B42%5D=foo="bar"&filter%5B99%5D=baz=1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->relate("baz", "t.baz")
            ->allow(new Allowable\Filter\AllowedStringFilter("foo"))
            ->allow(new Allowable\Filter\AllowedIntegerFilter("baz"))
            ->validate();
        $pdoMySql = new PdoMySql5_5($mapping);
        $pdoMySql->generate();
        $this->assertSame("(t.foo = BINARY :filter_42 AND t.baz = :filter_99)", $pdoMySql->getWhere());
        $this->assertSame(["filter_42" => "bar", "filter_99" => 1], $pdoMySql->getParameters());
    }
}
