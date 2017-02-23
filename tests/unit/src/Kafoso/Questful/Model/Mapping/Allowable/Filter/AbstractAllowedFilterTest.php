<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedStringFilter;
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AbstractAllowedFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedStringFilter = new AllowedStringFilter("foo");
        $this->assertInstanceOf(AllowedStringFilter::class, $allowedStringFilter);
    }

    public function testConstructorWorksWithValidOperatorsArgument()
    {
        $allowedStringFilter = new AllowedStringFilter("foo", ["!="]);
        $this->assertSame(["!="], $allowedStringFilter->getOperators());
    }

    public function testConstructorWorksWithValidConstraintsArgument()
    {
        $allowedStringFilter = new AllowedStringFilter("foo", null, [
            new Assert\GreaterThan(1),
        ]);
        $this->assertCount(1, $allowedStringFilter->getConstraints());
        $this->assertInstanceOf(Assert\GreaterThan::class, $allowedStringFilter->getConstraints()[0]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\InvalidArgumentException
     * @expectedExceptionMessage Expects argument '$key' to be a string. Found: (null) null
     */
    public function testConstructorThrowsExceptionWhenKeyArgumentIsInvalid()
    {
        new AllowedStringFilter(null);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Expects argument '$key' to not be an empty string. Found: (string)
     */
    public function testConstructorThrowsExceptionWhenKeyArgumentIsAnEmptyString()
    {
        new AllowedStringFilter("");
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Expects argument '$operators' to be null or a non-empty array. Found: []
     */
    public function testConstructorThrowsExceptionWhenOperatorsArgumentIsAnEmptyArray()
    {
        new AllowedStringFilter("foo", []);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Expected one of ["=","!=","<=",">=",">","<"]. Found: (string) 7
     */
    public function testConstructorThrowsExceptionWhenOperatorsArgumentIsInvalid()
    {
        new AllowedStringFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Expects argument '$constraint' to contain instances of \Symfony\Component\Validator\Constraint, exclusively. Found (at index 0): (string) bar
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsInvalidArrayValue()
    {
        new AllowedStringFilter("foo", null, ["bar"]);
    }

    public function testBasicGetters()
    {
        $allowedStringFilter = new AllowedStringFilter("foo", ["="]);
        $this->assertSame("foo", $allowedStringFilter->getKey());
        $this->assertSame(["="], $allowedStringFilter->getOperators());
        $this->assertSame(StringFilter::class, $allowedStringFilter->getFilterClassNamespace());
    }
}
