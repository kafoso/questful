<?php
use Kafoso\Questful\Model\Mapping\Allowable\AllowedSort;

class AllowedSortTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $AllowedSort = new AllowedSort("foo");
        $this->assertInstanceOf(AllowedSort::class, $AllowedSort);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expects argument '$key' to be a string. Found: (null) null
     */
    public function testConstructorThrowsExceptionWhenKeyArgumentIsInvalid()
    {
        new AllowedSort(null);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expects argument '$key' to not be an empty string. Found: (string)
     */
    public function testConstructorThrowsExceptionWhenKeyArgumentIsAnEmptyString()
    {
        new AllowedSort("");
    }

    public function testBasicGetters()
    {
        $AllowedSort = new AllowedSort("foo");
        $this->assertSame("foo", $AllowedSort->getKey());
    }
}
