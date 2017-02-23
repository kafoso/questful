<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedBooleanFilter;
use Kafoso\Questful\Model\QueryParser\Filter\BooleanFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedBooleanFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedBooleanFilter = new AllowedBooleanFilter("foo");
        $this->assertInstanceOf(AllowedBooleanFilter::class, $allowedBooleanFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableOperators
     */
    public function testConstructorWorksWithAllAvailableOperators($operator)
    {
        $allowedBooleanFilter = new AllowedBooleanFilter("foo", [$operator]);
        $this->assertSame([$operator], $allowedBooleanFilter->getOperators());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableOperators()
    {
        return [
            ["="],
        ];
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableConstraints
     */
    public function testConstructorWorksWithAllAvailableConstraints($constraint)
    {
        $allowedBooleanFilter = new AllowedBooleanFilter("foo", null, [$constraint]);
        $this->assertSame([$constraint], $allowedBooleanFilter->getConstraints());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableConstraints()
    {
        return [
            [new Assert\Callback],
            [new Assert\IsFalse],
            [new Assert\IsTrue],
        ];
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Expected one of ["="]. Found: (string) 7
     */
    public function testConstructorThrowsExceptionWhenOperatorArgumentIsInvalid()
    {
        $allowedBooleanFilter = new AllowedBooleanFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Constraint \Symfony\Component\Validator\Constraints\IsNull is not available for \Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedBooleanFilter
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsAnInvalidConstraint()
    {
        $allowedBooleanFilter = new AllowedBooleanFilter("foo", null, [new Assert\IsNull]);
    }

    public function testBasicGetters()
    {
        $this->assertSame(BooleanFilter::class, AllowedBooleanFilter::getFilterClassNamespace());
        $this->assertSame(["="], AllowedBooleanFilter::getAvailableOperators());
        $expected = [
            Assert\Callback::class,
            Assert\IsFalse::class,
            Assert\IsTrue::class,
        ];
        $this->assertSame($expected, AllowedBooleanFilter::getAvailableConstraints());

        $allowedBooleanFilter = new AllowedBooleanFilter("foo");
        $this->assertSame("foo", $allowedBooleanFilter->getKey());
        $this->assertSame(["="], $allowedBooleanFilter->getOperators());
        $this->assertNull($allowedBooleanFilter->getConstraints());
    }
}
