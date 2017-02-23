<?php
use Kafoso\Questful\Model\QueryParser\Sort;

class SortTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $sort = new Sort("foo", true);
        $this->assertInstanceOf(Sort::class, $sort);
    }

    public function testBasicGetters()
    {
        $sort = new Sort("foo", true);
        $this->assertSame("foo", $sort->getKey());
        $this->assertTrue($sort->isAscending());

        $expected = [
            "key" => "foo",
            "isAscending" => true,
        ];
        $this->assertEquals($expected, $sort->toArray());
    }

    public function testDescending()
    {
        $sort = new Sort("foo", false);
        $this->assertFalse($sort->isAscending());
    }
}
