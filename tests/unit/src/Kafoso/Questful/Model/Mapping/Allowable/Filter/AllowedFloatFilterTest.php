<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedFloatFilter;
use Kafoso\Questful\Model\QueryParser\Filter\FloatFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedFloatFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedFloatFilter = new AllowedFloatFilter("foo");
        $this->assertInstanceOf(AllowedFloatFilter::class, $allowedFloatFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableOperators
     */
    public function testConstructorWorksWithAllAvailableOperators($operator)
    {
        $allowedFloatFilter = new AllowedFloatFilter("foo", [$operator]);
        $this->assertSame([$operator], $allowedFloatFilter->getOperators());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableOperators()
    {
        return [
            ["="],
            ["!="],
            ["<="],
            [">="],
            [">"],
            ["<"],
        ];
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableConstraints
     */
    public function testConstructorWorksWithAllAvailableConstraints($constraint)
    {
        $allowedFloatFilter = new AllowedFloatFilter("foo", null, [$constraint]);
        $this->assertSame([$constraint], $allowedFloatFilter->getConstraints());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableConstraints()
    {
        return [
            [new Assert\Range(['min' => 0, 'max' => 1])],
            [new Assert\EqualTo],
            [new Assert\NotEqualTo],
            [new Assert\IdenticalTo],
            [new Assert\NotIdenticalTo],
            [new Assert\LessThan],
            [new Assert\LessThanOrEqual],
            [new Assert\GreaterThan],
            [new Assert\GreaterThanOrEqual],
            [new Assert\Callback],
        ];
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Expected one of ["=","!=","<=",">=",">","<"]. Found: (string) 7
     */
    public function testConstructorThrowsExceptionWhenOperatorArgumentIsInvalid()
    {
        $allowedFloatFilter = new AllowedFloatFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Constraint \Symfony\Component\Validator\Constraints\IsNull is not available for \Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedFloatFilter
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsAnInvalidConstraint()
    {
        $allowedFloatFilter = new AllowedFloatFilter("foo", null, [new Assert\IsNull]);
    }

    public function testBasicGetters()
    {
        $this->assertSame(FloatFilter::class, AllowedFloatFilter::getFilterClassNamespace());
        $this->assertSame(["=","!=","<=",">=",">","<"], AllowedFloatFilter::getAvailableOperators());
        $expected = [
            Assert\Range::class,
            Assert\EqualTo::class,
            Assert\NotEqualTo::class,
            Assert\IdenticalTo::class,
            Assert\NotIdenticalTo::class,
            Assert\LessThan::class,
            Assert\LessThanOrEqual::class,
            Assert\GreaterThan::class,
            Assert\GreaterThanOrEqual::class,
            Assert\Callback::class,
        ];
        $this->assertSame($expected, AllowedFloatFilter::getAvailableConstraints());

        $allowedFloatFilter = new AllowedFloatFilter("foo");
        $this->assertSame("foo", $allowedFloatFilter->getKey());
        $this->assertSame(["=","!=","<=",">=",">","<"], $allowedFloatFilter->getOperators());
        $this->assertNull($allowedFloatFilter->getConstraints());
    }
}
