<?php
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Bridge\Doctrine\Doctrine2_1;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;
use Kafoso\Questful\Model\QueryParser\Filter\BooleanFilter;
use Kafoso\Questful\Model\QueryParser\Filter\FloatFilter;
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;
use Kafoso\Questful\Model\QueryParser\Filter\LikeFilter;
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;

class Doctrine2_1Test extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $mapping = $this
            ->getMockBuilder(Mapping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = new Doctrine2_1($mapping);
        $this->assertInstanceOf(Doctrine2_1::class, $doctrine);
    }

    public function testBasicGetters()
    {
        $mapping = $this
            ->getMockBuilder(Mapping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine = new Doctrine2_1($mapping);
        $this->assertNull($doctrine->getWhere());
        $this->assertSame([], $doctrine->getOrderBy());
        $this->assertSame([], $doctrine->getParameters());
        $this->assertSame(["orderBy" => [], "parameters" => [], "where" => null], $doctrine->toArray());
    }

    public function testGenerate()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3E"bar"&sort%5B%5D=foo');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedStringFilter("foo"))
            ->allow(new Allowable\AllowedSort("foo"))
            ->validate();
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertSame("(t.foo > BINARY(:filter_0))", $doctrine->getWhere());
        $this->assertSame(["filter_0" => "bar"], $doctrine->getParameters());
        $this->assertSame([["t.foo", "ASC"]], $doctrine->getOrderBy());
    }

    public function testGenerateSilentlyDisregardsUnallowedFilters()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3E"bar"&sort%5B%5D=foo');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertNull($doctrine->getWhere());
        $this->assertSame([], $doctrine->getParameters());
        $this->assertSame([], $doctrine->getOrderBy());
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
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertSame("(t.foo LIKE BINARY(:filter_0) ESCAPE '\\')", $doctrine->getWhere());
        $this->assertSame(["filter_0" => "%\\_\\\\\\%%"], $doctrine->getParameters());
    }

    /**
     * @dataProvider   dataProvider_testGenerateForAllFilterTypesAndCases
     */
    public function testGenerateForAllFilterTypesAndCases($classNamespace, $filterQuery, $expectedWhere, $expectedParameters)
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter[]=' . urlencode($filterQuery));
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new $classNamespace("foo"))
            ->validate();
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertSame($expectedWhere, $doctrine->getWhere());
        $this->assertSame($expectedParameters, $doctrine->getParameters());
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
            [Allowable\Filter\AllowedFloatFilter::class, "foo<3.14", "(t.foo < :filter_0)", ["filter_0" => 3.14]],
            [Allowable\Filter\AllowedFloatFilter::class, "foo<=3.14", "(t.foo <= :filter_0)", ["filter_0" => 3.14]],
            [Allowable\Filter\AllowedFloatFilter::class, "foo>3.14", "(t.foo > :filter_0)", ["filter_0" => 3.14]],
            [Allowable\Filter\AllowedFloatFilter::class, "foo>=3.14", "(t.foo >= :filter_0)", ["filter_0" => 3.14]],

            [Allowable\Filter\AllowedStringFilter::class, "foo=\"bar\"", "(t.foo = BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo=\"bar\"/i", "(LOWER(t.foo) = BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo!=\"bar\"", "(t.foo != BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo!=\"bar\"/i", "(LOWER(t.foo) != BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>\"bar\"", "(t.foo > BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>\"bar\"/i", "(LOWER(t.foo) > BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>=\"bar\"", "(t.foo >= BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo>=\"bar\"/i", "(LOWER(t.foo) >= BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<\"bar\"", "(t.foo < BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<\"bar\"/i", "(LOWER(t.foo) < BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<=\"bar\"", "(t.foo <= BINARY(:filter_0))", ["filter_0" => "bar"]],
            [Allowable\Filter\AllowedStringFilter::class, "foo<=\"bar\"/i", "(LOWER(t.foo) <= BINARY(:filter_0))", ["filter_0" => "bar"]],

            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"bar\"%", "(t.foo LIKE BINARY(:filter_0) ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"bar\"%/i", "(LOWER(t.foo) LIKE BINARY(:filter_0) ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"bar\"%", "(t.foo NOT LIKE BINARY(:filter_0) ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo!=%\"bar\"%/i", "(LOWER(t.foo) NOT LIKE BINARY(:filter_0) ESCAPE '\\')", ["filter_0" => "%bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"bar\"", "(t.foo LIKE BINARY(:filter_0) ESCAPE '\\')", ["filter_0" => "%bar"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=%\"bar\"/i", "(LOWER(t.foo) LIKE BINARY(:filter_0) ESCAPE '\\')", ["filter_0" => "%bar"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=\"bar\"%", "(t.foo LIKE BINARY(:filter_0) ESCAPE '\\')", ["filter_0" => "bar%"]],
            [Allowable\Filter\AllowedLikeFilter::class, "foo=\"bar\"%/i", "(LOWER(t.foo) LIKE BINARY(:filter_0) ESCAPE '\\')", ["filter_0" => "bar%"]],

            [Allowable\Filter\AllowedInFilter::class, "foo=[\"BAR\"]", "(t.foo IN (BINARY(:filter_0_0)))", ["filter_0_0" => "BAR"]],
            [Allowable\Filter\AllowedInFilter::class, "foo=[\"BAR\"]/i", "(LOWER(t.foo) IN (BINARY(:filter_0_0)))", ["filter_0_0" => "bar"]],
            [Allowable\Filter\AllowedInFilter::class, "foo=[null,true,false,1,3.14,\"bar\"]", "(t.foo IN (BINARY(:filter_0_0), BINARY(:filter_0_1), BINARY(:filter_0_2), BINARY(:filter_0_3), BINARY(:filter_0_4), BINARY(:filter_0_5)))", ["filter_0_0" => null, "filter_0_1" => true, "filter_0_2" => false, "filter_0_3" => 1, "filter_0_4" => 3.14, "filter_0_5" => "bar"]],
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
            ->allow(new Allowable\AllowedSort("bar"))
            ->validate();
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertSame([["t.foo", "ASC"], ["t.bar", "DESC"]], $doctrine->getOrderBy());
    }

    public function testGenerateAppliesFilterExpressionCorrectly()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3D"bar"&filter%5B%5D=baz%3D"bim"&filterExpression=1or0');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->relate("baz", "t.baz")
            ->allow(new Allowable\Filter\AllowedStringFilter("foo"))
            ->allow(new Allowable\Filter\AllowedStringFilter("baz"))
            ->allow(new Allowable\AllowedFilterExpression("(1or0)"))
            ->validate();
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertSame("(t.baz = BINARY(:filter_1) OR t.foo = BINARY(:filter_0))", $doctrine->getWhere());
        $this->assertSame(["filter_0" => "bar", "filter_1" => "bim"], $doctrine->getParameters());
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
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertSame("(t.foo = BINARY(:filter_0) OR t.foo = :filter_1)", $doctrine->getWhere());
        $this->assertSame(["filter_0" => "bar", "filter_1" => 1], $doctrine->getParameters());
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
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertSame("(t.foo = :filter_0 XOR t.foo = :filter_1)", $doctrine->getWhere());
        $this->assertSame(["filter_0" => 1, "filter_1" => 2], $doctrine->getParameters());
    }

    public function testGenerateQueryBuilder()
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
        $doctrine = new Doctrine2_1($mapping);
        $queryBuilder = $this
            ->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $object = $doctrine->generateOnQueryBuilder($queryBuilder); // Doesn't actually modify anything
        $this->assertSame($doctrine, $object);
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
        $doctrine = new Doctrine2_1($mapping);
        $doctrine->generate();
        $this->assertSame("(t.foo = BINARY(:filter_42) AND t.baz = :filter_99)", $doctrine->getWhere());
        $this->assertSame(["filter_42" => "bar", "filter_99" => 1], $doctrine->getParameters());
    }
}
