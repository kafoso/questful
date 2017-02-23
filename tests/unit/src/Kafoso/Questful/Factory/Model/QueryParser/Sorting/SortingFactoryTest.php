<?php
use Kafoso\Questful\Factory\Model\QueryParser\Sort\SortFactory;
use Kafoso\Questful\Model\QueryParser\Sort;

class SortFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateFromQuery()
    {
        $query = [
            "foo"
        ];
        $sortFactory = new SortFactory;
        $sorts = $sortFactory->createFromQuery($query);
        $this->assertCount(1, $sorts);
        $this->assertInstanceOf(Sort::class, $sorts[0]);
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Index in 'sort[1b]=foo' must be an integer. Found: (string) 1b
     */
    public function testCreateFromQueryThrowsExceptionWhenInvalidIndexIsProvided()
    {
        $query = [
            "1b" => "foo"
        ];
        $sortFactory = new SortFactory;
        $sortFactory->createFromQuery($query);
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    Index in 'sort[-1]=foo="bar"' is negative; all indexes must be >= 0
     */
    public function testCreateFromQueryThrowsExceptionWhenIndexIsNegative()
    {
        $query = [
            "-1" => "foo=\"bar\""
        ];
        $sortFactory = new SortFactory;
        $sortFactory->createFromQuery($query);
    }
}
