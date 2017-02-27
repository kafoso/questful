<?php
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Bridge\PdoSqlite\PdoSqlite3;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;
use Kafoso\Questful\Model\QueryParser;
use Kafoso\Questful\Model\QueryParser\Filter\BooleanFilter;
use Kafoso\Questful\Model\QueryParser\Filter\FloatFilter;
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;
use Kafoso\Questful\Model\QueryParser\Filter\LikeFilter;
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;

class PdoSqlite3Test extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $mapping = $this
            ->getMockBuilder(Mapping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pdoSqlite = new PdoSqlite3($mapping);
        $this->assertInstanceOf(PdoSqlite3::class, $pdoSqlite);
    }

    public function testBasicGetters()
    {
        $mapping = $this
            ->getMockBuilder(Mapping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pdoSqlite = new PdoSqlite3($mapping);
        $this->assertNull($pdoSqlite->getOrderBy());
        $this->assertSame([], $pdoSqlite->getParameters());
        $this->assertNull($pdoSqlite->getWhere());
        $this->assertSame(["orderBy" => null, "parameters" => [], "where" => null], $pdoSqlite->toArray());
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
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $this->assertSame("(t.foo = :filter_0)", $pdoSqlite->getWhere());
        $this->assertSame("t.foo ASC", $pdoSqlite->getOrderBy());
        $this->assertSame(["filter_0" => "bar"], $pdoSqlite->getParameters());
    }

    public function testGenerateProducesNothingWhenNothingHasBeenMapped()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3D"bar"&sort%5B%5D=foo');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $this->assertNull($pdoSqlite->getWhere());
        $this->assertCount(0, $pdoSqlite->getParameters());
        $this->assertNull($pdoSqlite->getOrderBy());
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
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $this->assertSame("(t.foo LIKE :filter_0 ESCAPE '\\')", $pdoSqlite->getWhere());
        $this->assertSame(["filter_0" => "%\\_\\\\\\%%"], $pdoSqlite->getParameters());
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
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $this->assertSame($expectedWhere, $pdoSqlite->getWhere());
        $this->assertSame($expectedParameters, $pdoSqlite->getParameters());
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

            [Allowable\Filter\AllowedStringFilter::class, "foo=\"bar\"", "(t.foo = :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo=\"BAR\"/i", "(LOWER(t.foo) = :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo!=\"bar\"", "(t.foo != :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo!=\"BAR\"/i", "(LOWER(t.foo) != :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>\"bar\"", "(t.foo > :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>\"BAR\"/i", "(LOWER(t.foo) > :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>=\"bar\"", "(t.foo >= :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>=\"BAR\"/i", "(LOWER(t.foo) >= :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<\"bar\"", "(t.foo < :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<\"BAR\"/i", "(LOWER(t.foo) < :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<=\"bar\"", "(t.foo <= :filter_0)", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<=\"BAR\"/i", "(LOWER(t.foo) <= :filter_0)", ["filter_0" => "bar"]],

            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"bar\"%", "(t.foo LIKE :filter_0 ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"BAR\"%/i", "(LOWER(t.foo) LIKE :filter_0 ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"bar\"%", "(t.foo NOT LIKE :filter_0 ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"BAR\"%/i", "(LOWER(t.foo) NOT LIKE :filter_0 ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"bar\"", "(t.foo LIKE :filter_0 ESCAPE '\\')", ["filter_0" => "%bar"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"BAR\"/i", "(LOWER(t.foo) LIKE :filter_0 ESCAPE '\\')", ["filter_0" => "%bar"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"bar\"", "(t.foo NOT LIKE :filter_0 ESCAPE '\\')", ["filter_0" => "%bar"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"BAR\"/i", "(LOWER(t.foo) NOT LIKE :filter_0 ESCAPE '\\')", ["filter_0" => "%bar"]],

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
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $this->assertSame("t.foo ASC, t.bar DESC", $pdoSqlite->getOrderBy());
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
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $this->assertSame("(t.baz = :filter_1 OR t.foo = :filter_0)", $pdoSqlite->getWhere());
        $this->assertSame(["filter_0" => "bar", "filter_1" => "bim"], $pdoSqlite->getParameters());
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
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $this->assertSame("(t.foo = :filter_0 OR t.foo = :filter_1)", $pdoSqlite->getWhere());
        $this->assertSame(["filter_0" => "bar", "filter_1" => 1], $pdoSqlite->getParameters());
    }

    public function testGenerateWillApplyXorCorrectly()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=0&filter%5B%5D=foo=1&filter%5B%5D=foo=2&filterExpression=(0xor1xor2)');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo"))
            ->allow(new Allowable\AllowedFilterExpression("(0xor1xor2)"))
            ->validate();
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $expected = "(XOR(XOR(t.foo = :filter_0, t.foo = :filter_1), t.foo = :filter_2))";
        $this->assertSame($expected, $pdoSqlite->getWhere());
        $this->assertSame(["filter_0" => 0, "filter_1" => 1, "filter_2" => 2], $pdoSqlite->getParameters());
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
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $this->assertSame("(t.foo = :filter_42 AND t.baz = :filter_99)", $pdoSqlite->getWhere());
        $this->assertSame(["filter_42" => "bar", "filter_99" => 1], $pdoSqlite->getParameters());
    }
}
