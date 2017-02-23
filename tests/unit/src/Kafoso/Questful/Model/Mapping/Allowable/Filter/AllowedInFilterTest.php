<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedInFilter;
use Kafoso\Questful\Model\QueryParser\Filter\InFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedInFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedInFilter = new AllowedInFilter("foo");
        $this->assertInstanceOf(AllowedInFilter::class, $allowedInFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableOperators
     */
    public function testConstructorWorksWithAllAvailableOperators($operator)
    {
        $allowedInFilter = new AllowedInFilter("foo", [$operator]);
        $this->assertSame([$operator], $allowedInFilter->getOperators());
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
        $allowedInFilter = new AllowedInFilter("foo", null, [$constraint]);
        $this->assertSame([$constraint], $allowedInFilter->getConstraints());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableConstraints()
    {
        return [
            [new Assert\Count(['min' => 0, 'max' => 0])],
        ];
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Expected one of ["=","!="]. Found: (string) 7
     */
    public function testConstructorThrowsExceptionWhenOperatorArgumentIsInvalid()
    {
        $allowedInFilter = new AllowedInFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Constraint \Symfony\Component\Validator\Constraints\IsTrue is not available for \Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedInFilter
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsAnInvalidConstraint()
    {
        $allowedInFilter = new AllowedInFilter("foo", null, [new Assert\IsTrue]);
    }

    public function testBasicGetters()
    {
        $this->assertSame(InFilter::class, AllowedInFilter::getFilterClassNamespace());
        $this->assertSame(["=", "!="], AllowedInFilter::getAvailableOperators());
        $expected = [
            Assert\Count::class,
        ];
        $this->assertSame($expected, AllowedInFilter::getAvailableConstraints());

        $allowedInFilter = new AllowedInFilter("foo");
        $this->assertSame("foo", $allowedInFilter->getKey());
        $this->assertSame(["=", "!="], $allowedInFilter->getOperators());
        $this->assertNull($allowedInFilter->getConstraints());
    }
}
