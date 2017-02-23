<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedNullFilter;
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedNullFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedNullFilter = new AllowedNullFilter("foo");
        $this->assertInstanceOf(AllowedNullFilter::class, $allowedNullFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableOperators
     */
    public function testConstructorWorksWithAllAvailableOperators($operator)
    {
        $allowedNullFilter = new AllowedNullFilter("foo", [$operator]);
        $this->assertSame([$operator], $allowedNullFilter->getOperators());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableOperators()
    {
        return [
            ["="],
            ["!="],
        ];
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableConstraints
     */
    public function testConstructorWorksWithAllAvailableConstraints($constraint)
    {
        $allowedNullFilter = new AllowedNullFilter("foo", null, [$constraint]);
        $this->assertSame([$constraint], $allowedNullFilter->getConstraints());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableConstraints()
    {
        return [
            [new Assert\IsNull],
        ];
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Expected one of ["=","!="]. Found: (string) 7
     */
    public function testConstructorThrowsExceptionWhenOperatorArgumentIsInvalid()
    {
        $allowedNullFilter = new AllowedNullFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Constraint \Symfony\Component\Validator\Constraints\IsTrue is not available for \Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedNullFilter
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsAnInvalidConstraint()
    {
        $allowedNullFilter = new AllowedNullFilter("foo", null, [new Assert\IsTrue]);
    }

    public function testBasicGetters()
    {
        $this->assertSame(NullFilter::class, AllowedNullFilter::getFilterClassNamespace());
        $this->assertSame(["=", "!="], AllowedNullFilter::getAvailableOperators());
        $expected = [
            Assert\IsNull::class,
        ];
        $this->assertSame($expected, AllowedNullFilter::getAvailableConstraints());

        $allowedNullFilter = new AllowedNullFilter("foo");
        $this->assertSame("foo", $allowedNullFilter->getKey());
        $this->assertSame(["=", "!="], $allowedNullFilter->getOperators());
        $this->assertNull($allowedNullFilter->getConstraints());
    }
}
