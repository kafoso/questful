<?php
use Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedStringFilter;
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;
use Symfony\Component\Validator\Constraints as Assert;

class AllowedStringFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $allowedStringFilter = new AllowedStringFilter("foo");
        $this->assertInstanceOf(AllowedStringFilter::class, $allowedStringFilter);
    }

    /**
     * @dataProvider dataProvider_testConstructorWorksWithAllAvailableOperators
     */
    public function testConstructorWorksWithAllAvailableOperators($operator)
    {
        $allowedStringFilter = new AllowedStringFilter("foo", [$operator]);
        $this->assertSame([$operator], $allowedStringFilter->getOperators());
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
        $allowedStringFilter = new AllowedStringFilter("foo", null, [$constraint]);
        $this->assertSame([$constraint], $allowedStringFilter->getConstraints());
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
     * @expectedExceptionMessage Invalid operator. Expected one of ["=","!=","<=",">=",">","<"]. Found: (string) 7
     */
    public function testConstructorThrowsExceptionWhenOperatorArgumentIsInvalid()
    {
        $allowedStringFilter = new AllowedStringFilter("foo", ["7"]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Constraint \Symfony\Component\Validator\Constraints\IsNull is not available for \Kafoso\Questful\Model\Mapping\Allowable\Filter\AllowedStringFilter
     */
    public function testConstructorThrowsExceptionWhenConstraintsArgumentContainsAnInvalidConstraint()
    {
        $allowedStringFilter = new AllowedStringFilter("foo", null, [new Assert\IsNull]);
    }

    public function testBasicGetters()
    {
        $this->assertSame(StringFilter::class, AllowedStringFilter::getFilterClassNamespace());
        $this->assertSame(["=","!=","<=",">=",">","<"], AllowedStringFilter::getAvailableOperators());
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
        $this->assertSame($expected, AllowedStringFilter::getAvailableConstraints());

        $allowedStringFilter = new AllowedStringFilter("foo");
        $this->assertSame("foo", $allowedStringFilter->getKey());
        $this->assertSame(["=","!=","<=",">=",">","<"], $allowedStringFilter->getOperators());
        $this->assertNull($allowedStringFilter->getConstraints());
    }
}
