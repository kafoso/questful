<?php
use Kafoso\Questful\Factory\Model\QueryParser\Filter\FilterFactory;
use Kafoso\Questful\Model\QueryParser\Filter\AbstractFilter;
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;

class FilterFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromQuery()
    {
        $query = [
            "foo=\"bar\""
        ];
        $filterFactory = new FilterFactory;
        $filters = $filterFactory->createFromQuery($query);
        $this->assertCount(1, $filters);
        $this->assertInstanceOf(AbstractFilter::class, $filters[0]);
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Parameter 'filter[]' must be an array. Found: (null) null
     */
    public function testCreateFromQueryThrowsExceptionWhenInvalidArgumentIsProvided()
    {
        $filterFactory = new FilterFactory;
        $filterFactory->createFromQuery(null);
    }

    public function testEmptyValueIsConsideredToBeNull()
    {
        $query = [
            "foo="
        ];
        $filterFactory = new FilterFactory;
        $filters = $filterFactory->createFromQuery($query);
        $this->assertInstanceOf(NullFilter::class, $filters[0]);
        $this->assertNull($filters[0]->getValue());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Index in 'filter[1b]=foo="bar"' must be an integer. Found: (string) 1b
     */
    public function testCreateFromQueryThrowsExceptionWhenInvalidIndexIsProvided()
    {
        $query = [
            "1b" => "foo=\"bar\""
        ];
        $filterFactory = new FilterFactory;
        $filterFactory->createFromQuery($query);
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Index in 'filter[-1]=foo="bar"' is negative; all indexes must be >= 0
     */
    public function testCreateFromQueryThrowsExceptionWhenIndexIsNegative()
    {
        $query = [
            "-1" => "foo=\"bar\""
        ];
        $filterFactory = new FilterFactory;
        $filterFactory->createFromQuery($query);
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Filter value '"bar' (in 'filter[0]=foo="bar') does not match a supported pattern.
     */
    public function testCreateFromQueryThrowsExceptionWhenExpressionIsMalformed()
    {
        $query = [
            "foo=\"bar"
        ];
        $filterFactory = new FilterFactory;
        $filterFactory->createFromQuery($query);
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Filter 'filter[0]=="bar"' is missing key
     */
    public function testCreateFromQueryThrowsExceptionWhenKeyIsMissing()
    {
        $query = [
            "=\"bar\""
        ];
        $filterFactory = new FilterFactory;
        $filterFactory->createFromQuery($query);
    }

    /**
     * @dataProvider    dataProvider_testCreateFromQueryThrowsExceptionWhenNullHasWrongOperators
     */
    public function testCreateFromQueryThrowsExceptionWhenNullHasWrongOperators($operator, $expectedExceptionMessage)
    {
        $query = [
            "foo{$operator}null"
        ];
        $filterFactory = new FilterFactory;
        try {
            $filterFactory->createFromQuery($query);
        } catch (\Kafoso\Questful\Exception\BadRequestException $e) {
            $this->assertSame($expectedExceptionMessage, $e->getMessage());
            if (0 === strcmp($e->getMessage(), $expectedExceptionMessage)) {
                return;
            }
        }
        $this->fail();
    }

    public function dataProvider_testCreateFromQueryThrowsExceptionWhenNullHasWrongOperators()
    {
        return [
            [">", "'filter[0]=foo>null' is malformed: Expected operator to be one of [\"=\",\"!=\"]. Found: (string) >"],
            [">=", "'filter[0]=foo>=null' is malformed: Expected operator to be one of [\"=\",\"!=\"]. Found: (string) >="],
            ["<", "'filter[0]=foo<null' is malformed: Expected operator to be one of [\"=\",\"!=\"]. Found: (string) <"],
            ["<=", "'filter[0]=foo<=null' is malformed: Expected operator to be one of [\"=\",\"!=\"]. Found: (string) <="],
        ];
    }

    /**
     * @dataProvider    dataProvider_testCreateFromQueryThrowsExceptionWhenTrueHasWrongOperators
     */
    public function testCreateFromQueryThrowsExceptionWhenTrueHasWrongOperators($operator, $expectedExceptionMessage)
    {
        $query = [
            "foo{$operator}true"
        ];
        $filterFactory = new FilterFactory;
        try {
            $filterFactory->createFromQuery($query);
        } catch (\Kafoso\Questful\Exception\BadRequestException $e) {
            $this->assertSame($expectedExceptionMessage, $e->getMessage());
            if (0 === strcmp($e->getMessage(), $expectedExceptionMessage)) {
                return;
            }
        }
        $this->fail();
    }

    public function dataProvider_testCreateFromQueryThrowsExceptionWhenTrueHasWrongOperators()
    {
        return [
            ["!=", "'filter[0]=foo!=true' is malformed: Expected operator to be one of [\"=\"]. Found: (string) !="],
            [">", "'filter[0]=foo>true' is malformed: Expected operator to be one of [\"=\"]. Found: (string) >"],
            [">=", "'filter[0]=foo>=true' is malformed: Expected operator to be one of [\"=\"]. Found: (string) >="],
            ["<", "'filter[0]=foo<true' is malformed: Expected operator to be one of [\"=\"]. Found: (string) <"],
            ["<=", "'filter[0]=foo<=true' is malformed: Expected operator to be one of [\"=\"]. Found: (string) <="],
        ];
    }

    /**
     * @dataProvider    dataProvider_testCreateFromQueryThrowsExceptionWhenFalseHasWrongOperators
     */
    public function testCreateFromQueryThrowsExceptionWhenFalseHasWrongOperators($operator, $expectedExceptionMessage)
    {
        $query = [
            "foo{$operator}false"
        ];
        $filterFactory = new FilterFactory;
        try {
            $filterFactory->createFromQuery($query);
        } catch (\Kafoso\Questful\Exception\BadRequestException $e) {
            $strcmp = strcmp($e->getMessage(), $expectedExceptionMessage);
            $this->assertSame(0, $strcmp);
            if (0 === $strcmp) {
                return;
            }
        }
        $this->fail();
    }

    public function dataProvider_testCreateFromQueryThrowsExceptionWhenFalseHasWrongOperators()
    {
        return [
            ["!=", "'filter[0]=foo!=false' is malformed: Expected operator to be one of [\"=\"]. Found: (string) !="],
            [">", "'filter[0]=foo>false' is malformed: Expected operator to be one of [\"=\"]. Found: (string) >"],
            [">=", "'filter[0]=foo>=false' is malformed: Expected operator to be one of [\"=\"]. Found: (string) >="],
            ["<", "'filter[0]=foo<false' is malformed: Expected operator to be one of [\"=\"]. Found: (string) <"],
            ["<=", "'filter[0]=foo<=false' is malformed: Expected operator to be one of [\"=\"]. Found: (string) <="],
        ];
    }

    /**
     * @dataProvider    dataProvider_testCreateFromQueryThrowsExceptionWhenLikeHasWrongOperators
     */
    public function testCreateFromQueryThrowsExceptionWhenLikeHasWrongOperators($operator, $expectedExceptionMessage)
    {
        $query = [
            "foo{$operator}%\"bar\"%"
        ];
        $filterFactory = new FilterFactory;
        try {
            $filterFactory->createFromQuery($query);
        } catch (\Kafoso\Questful\Exception\BadRequestException $e) {
            $strcmp = strcmp($e->getMessage(), $expectedExceptionMessage);
            $this->assertSame(0, $strcmp);
            if (0 === $strcmp) {
                return;
            }
        }
        $this->fail();
    }

        public function dataProvider_testCreateFromQueryThrowsExceptionWhenLikeHasWrongOperators()
    {
        return [
            [">", "'filter[0]=foo>%\"bar\"%' is malformed: Expected operator to be one of [\"=\",\"!=\"]. Found: (string) >"],
            [">=", "'filter[0]=foo>=%\"bar\"%' is malformed: Expected operator to be one of [\"=\",\"!=\"]. Found: (string) >="],
            ["<", "'filter[0]=foo<%\"bar\"%' is malformed: Expected operator to be one of [\"=\",\"!=\"]. Found: (string) <"],
            ["<=", "'filter[0]=foo<=%\"bar\"%' is malformed: Expected operator to be one of [\"=\",\"!=\"]. Found: (string) <="],
        ];
    }
}
