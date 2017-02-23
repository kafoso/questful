<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedRegexpFilter;
use Kafoso\Questful\Model\QueryParser\Filter\RegexpFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedRegexpFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedRegexpFilter = new AllowedRegexpFilter("foo");
        $this->assertInstanceOf(AllowedRegexpFilter::class, $allowedRegexpFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableOperators
     */
    public function testConstructorWorksWithAllAvailableOperators($operator)
    {
        $allowedRegexpFilter = new AllowedRegexpFilter("foo", [$operator]);
        $this->assertSame([$operator], $allowedRegexpFilter->getOperators());
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
        $allowedRegexpFilter = new AllowedRegexpFilter("foo", null, [$constraint]);
        $this->assertSame([$constraint], $allowedRegexpFilter->getConstraints());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableConstraints()
    {
        return [
            [new Assert\EqualTo],
            [new Assert\NotEqualTo],
            [new Assert\IdenticalTo],
            [new Assert\NotIdenticalTo],
            [new Assert\LessThan],
            [new Assert\LessThanOrEqual],
            [new Assert\GreaterThan],
            [new Assert\GreaterThanOrEqual],
            [new Assert\Length(['min' => 0, 'max' => 0])],
            [new Assert\Regex(['pattern' => '.+'])],
            [new Assert\Callback],
        ];
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Expected one of ["=","!="]. Found: (string) 7
     */
    public function testConstructorThrowsExceptionWhenOperatorArgumentIsInvalid()
    {
        $allowedRegexpFilter = new AllowedRegexpFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Constraint \Symfony\Component\Validator\Constraints\IsNull is not available for \Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedRegexpFilter
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsAnInvalidConstraint()
    {
        $allowedRegexpFilter = new AllowedRegexpFilter("foo", null, [new Assert\IsNull]);
    }

    public function testBasicGetters()
    {
        $this->assertSame(RegexpFilter::class, AllowedRegexpFilter::getFilterClassNamespace());
        $this->assertSame(["=","!="], AllowedRegexpFilter::getAvailableOperators());
        $expected = [
            Assert\EqualTo::class,
            Assert\NotEqualTo::class,
            Assert\IdenticalTo::class,
            Assert\NotIdenticalTo::class,
            Assert\LessThan::class,
            Assert\LessThanOrEqual::class,
            Assert\GreaterThan::class,
            Assert\GreaterThanOrEqual::class,
            Assert\Length::class,
            Assert\Regex::class,
            Assert\Callback::class,
        ];
        $this->assertSame($expected, AllowedRegexpFilter::getAvailableConstraints());

        $allowedRegexpFilter = new AllowedRegexpFilter("foo");
        $this->assertSame("foo", $allowedRegexpFilter->getKey());
        $this->assertSame(["=","!="], $allowedRegexpFilter->getOperators());
        $this->assertNull($allowedRegexpFilter->getConstraints());
    }
}
