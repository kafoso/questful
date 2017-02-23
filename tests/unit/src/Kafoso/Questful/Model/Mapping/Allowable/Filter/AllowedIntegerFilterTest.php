<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedIntegerFilter;
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedIntegerFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedIntegerFilter = new AllowedIntegerFilter("foo");
        $this->assertInstanceOf(AllowedIntegerFilter::class, $allowedIntegerFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableOperators
     */
    public function testConstructorWorksWithAllAvailableOperators($operator)
    {
        $allowedIntegerFilter = new AllowedIntegerFilter("foo", [$operator]);
        $this->assertSame([$operator], $allowedIntegerFilter->getOperators());
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
        $allowedIntegerFilter = new AllowedIntegerFilter("foo", null, [$constraint]);
        $this->assertSame([$constraint], $allowedIntegerFilter->getConstraints());
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
        $allowedIntegerFilter = new AllowedIntegerFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Constraint \Symfony\Component\Validator\Constraints\IsNull is not available for \Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedIntegerFilter
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsAnInvalidConstraint()
    {
        $allowedIntegerFilter = new AllowedIntegerFilter("foo", null, [new Assert\IsNull]);
    }

    public function testBasicGetters()
    {
        $this->assertSame(IntegerFilter::class, AllowedIntegerFilter::getFilterClassNamespace());
        $this->assertSame(["=","!=","<=",">=",">","<"], AllowedIntegerFilter::getAvailableOperators());
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
        $this->assertSame($expected, AllowedIntegerFilter::getAvailableConstraints());

        $allowedIntegerFilter = new AllowedIntegerFilter("foo");
        $this->assertSame("foo", $allowedIntegerFilter->getKey());
        $this->assertSame(["=","!=","<=",">=",">","<"], $allowedIntegerFilter->getOperators());
        $this->assertNull($allowedIntegerFilter->getConstraints());
    }
}
