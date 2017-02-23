<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedLikeFilter;
use Kafoso\Questful\Model\QueryParser\Filter\LikeFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedLikeFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedLikeFilter = new AllowedLikeFilter("foo");
        $this->assertInstanceOf(AllowedLikeFilter::class, $allowedLikeFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableOperators
     */
    public function testConstructorWorksWithAllAvailableOperators($operator)
    {
        $allowedLikeFilter = new AllowedLikeFilter("foo", [$operator]);
        $this->assertSame([$operator], $allowedLikeFilter->getOperators());
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
        $allowedLikeFilter = new AllowedLikeFilter("foo", null, [$constraint]);
        $this->assertSame([$constraint], $allowedLikeFilter->getConstraints());
    }

    public function dataProvider_testConstructorWorksWithAllAvailableConstraints()
    {
        return [
            [new Assert\Blank],
            [new Assert\NotBlank],
            [new Assert\Email],
            [new Assert\Length(['min' => 0, 'max' => 0])],
            [new Assert\Url],
            [new Assert\Regex(['pattern' => '.+'])],
            [new Assert\Ip],
            [new Assert\Uuid],
            [new Assert\EqualTo],
            [new Assert\NotEqualTo],
            [new Assert\IdenticalTo],
            [new Assert\NotIdenticalTo],
            [new Assert\LessThan],
            [new Assert\LessThanOrEqual],
            [new Assert\GreaterThan],
            [new Assert\GreaterThanOrEqual],
            [new Assert\Date],
            [new Assert\DateTime],
            [new Assert\Time],
            [new Assert\Callback],
        ];
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Expected one of ["=","!="]. Found: (string) 7
     */
    public function testConstructorThrowsExceptionWhenOperatorArgumentIsInvalid()
    {
        $allowedLikeFilter = new AllowedLikeFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Constraint \Symfony\Component\Validator\Constraints\IsNull is not available for \Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedLikeFilter
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsAnInvalidConstraint()
    {
        $allowedLikeFilter = new AllowedLikeFilter("foo", null, [new Assert\IsNull]);
    }

    public function testBasicGetters()
    {
        $this->assertSame(LikeFilter::class, AllowedLikeFilter::getFilterClassNamespace());
        $this->assertSame(["=","!="], AllowedLikeFilter::getAvailableOperators());
        $expected = [
            Assert\Blank::class,
            Assert\NotBlank::class,
            Assert\Email::class,
            Assert\Length::class,
            Assert\Url::class,
            Assert\Regex::class,
            Assert\Ip::class,
            Assert\Uuid::class,
            Assert\EqualTo::class,
            Assert\NotEqualTo::class,
            Assert\IdenticalTo::class,
            Assert\NotIdenticalTo::class,
            Assert\LessThan::class,
            Assert\LessThanOrEqual::class,
            Assert\GreaterThan::class,
            Assert\GreaterThanOrEqual::class,
            Assert\Date::class,
            Assert\DateTime::class,
            Assert\Time::class,
            Assert\Callback::class,
        ];
        $this->assertSame($expected, AllowedLikeFilter::getAvailableConstraints());

        $allowedLikeFilter = new AllowedLikeFilter("foo");
        $this->assertSame("foo", $allowedLikeFilter->getKey());
        $this->assertSame(["=","!="], $allowedLikeFilter->getOperators());
        $this->assertNull($allowedLikeFilter->getConstraints());
    }
}
