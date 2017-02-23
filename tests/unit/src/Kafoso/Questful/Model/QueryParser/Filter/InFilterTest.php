<?php
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;

class InFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $InFilter = new InFilter('foo=["bar"]', "foo", "[\"bar\"]");
        $this->assertInstanceOf(InFilter::class, $InFilter);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessageRegExp    /Expects value to match "\/(.+?)\/"\. Found: \(string\) bar/
     */
    public function testConstructorThrowsExceptionWhenValueIsInvalid()
    {
        new InFilter('foo=bar', "foo", "bar");
    }
}
