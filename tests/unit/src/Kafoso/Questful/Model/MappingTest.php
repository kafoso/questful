<?php
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;
use Kafoso\Questful\Model\QueryParser;
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;
use Kafoso\Questful\Model\QueryParser\Filter\NullFilter;
use Kafoso\Questful\Model\QueryParser\Filter\StringFilter;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MappingTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $queryParser = $this
            ->getMockBuilder(QueryParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mapping = new Mapping($queryParser);
        $this->assertInstanceOf(Mapping::class, $mapping);
    }

    public function testBasicGetters()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3E"bar"&sort%5B%5D=foo');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $this->assertInstanceOf(Mapping::class, $mapping);
        $this->assertSame([], $mapping->getAllowedFilters());
        $this->assertSame([], $mapping->getAllowedSorts());
        $this->assertInstanceOf(QueryParser::class, $mapping->getQueryParser());
    }

    public function testAllowWorks()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $allowedIntegerFilter = new Allowable\Filter\AllowedIntegerFilter("foo");
        $mapping
            ->relate("foo", "t.foo")
            ->allow($allowedIntegerFilter);
        $this->assertCount(1, $mapping->getAllowedFilters());
        $this->assertSame($allowedIntegerFilter, $mapping->getAllowedFilters()[0]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\UnexpectedValueException
     * @expectedExceptionMessage Invalid operator. Expected one of ["=","!=","<=",">=",">","<"]. Found: (string) 7
     */
    public function testAllowThrowsExceptionWhenInvalidOperatorIsProvided()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo", ["7"]));
    }

    public function testValidateWorks()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3D1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate('foo', 'foo')
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo"))
            ->validate();
        $this->assertCount(1, $mapping->getAllowedFilters());
    }

    public function testValidateWorksWithRestrictedOperators()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3D1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate('foo', 'foo')
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo", ["="]))
            ->validate();
        $this->assertCount(1, $mapping->getAllowedFilters());
    }

    public function testValidateWorksWithASingleValidator()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $allowedIntegerFilter = new Allowable\Filter\AllowedIntegerFilter("foo", null, [
            new Assert\GreaterThan(0),
        ]);
        $mapping
            ->relate("foo", "t.foo")
            ->allow($allowedIntegerFilter)
            ->validate();
        $this->assertCount(1, $mapping->getAllowedFilters());
    }

    public function testValidateWorksWithMultipleValidators()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $allowedIntegerFilter = new Allowable\Filter\AllowedIntegerFilter("foo", null, [
            new Assert\GreaterThan(0),
            new Assert\LessThan(2),
        ]);
        $mapping
            ->relate("foo", "t.foo")
            ->allow($allowedIntegerFilter)
            ->validate();
        $this->assertCount(1, $mapping->getAllowedFilters());
    }

    public function testValidateWorksWhenTwoDifferentFilterTypesTargetTheSameKey()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=1&filter%5B%5D=foo!=null');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo"))
            ->allow(new Allowable\Filter\AllowedNullFilter("foo"))
            ->validate();
        $this->assertCount(2, $mapping->getAllowedFilters());
    }

    public function testValidateWorksWithAVarietyOfFilterTypesAndOperatorsAndValidators()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=1&filter%5B%5D=foo!=null&filter%5B%5D=foo<"2016-01-01"');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $dateConfineMax = "2017-01-01";
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo", ["="], [
                new Assert\GreaterThan(0),
                new Assert\LessThan(2),
            ]))
            ->allow(new Allowable\Filter\AllowedNullFilter("foo", ["!="], [
                new Assert\IsNull(),
            ]))
            ->allow(new Allowable\Filter\AllowedStringFilter("foo", ["<"], [
                new Assert\NotBlank(),
                new Assert\Regex([
                    'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
                ]),
            ]))
            ->validate();
        $this->assertCount(3, $mapping->getAllowedFilters());
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    1 filter(s) is/are not allowed. These are: foo>"bar"
     */
    public function testValidateThrowsExceptionWhenAFilterIsUnmapped()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3E"bar"');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping->validate();
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    1 filter(s) is/are not allowed. These are: foo>"bar"
     */
    public function testValidateThrowsExceptionExceptionWhenRequestDoesNotHaveAMappedMatch()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo%3E"bar"');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("id", "t.id")
            ->allow(new Allowable\Filter\AllowedIntegerFilter("id"))
            ->validate();
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage 'filter=foo="bar"': Disallowed operator "="; allowed operators are: ["!="]
     */
    public function testValidateThrowsExceptionWhenOperatorIsNotAvailable()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo="bar"');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedStringFilter("foo", ["!="]))
            ->validate();
    }

    /**
     * @expectedException   Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage    1 filter(s) is/are not allowed. These are: foo=1
     */
    public function testValidateThrowsExceptionWhenAFilterTypeIsNotMapped()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo="bar"&filter%5B%5D=foo=1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedStringFilter("foo"))
            ->validate();
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage 'filter=foo=1': 1 validation(s) failed. Error 1/1: This value should be greater than 1.
     */
    public function testValidateThrowsExceptionWhenAFilterConstraintIsNotMet()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri('?filter%5B%5D=foo=1');
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedIntegerFilter("foo", null, [new Assert\GreaterThan(1)]))
            ->validate();
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage  'filter=t.foo=[1,2,3]': 1 validation(s) failed. Error 1/1: This collection should contain 2 elements or less.
     */
    public function testValidateThrowsExceptionWhenAllowedInFilterContainsTooManyElements()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = new QueryParser([
            "filter" => [
                "t.foo=[1,2,3]"
            ]
        ]);
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate("t.foo", "t.foo")
            ->allow(new Allowable\Filter\AllowedInFilter("t.foo", null, [
                new Assert\Count(['max' => 2]),
            ]))
            ->validate();
    }

    /**
     * @dataProvider dataProvider_testValidateThrowsExceptionWhenAFiltersSubConstraintIsNotMet
     */
    public function testValidateThrowsExceptionWhenAFiltersSubConstraintIsNotMet($expectedExceptionMessage, $innerArrayValue, $dataType, array $constraints)
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = new QueryParser([
            "filter" => [
                "t.foo=[{$innerArrayValue}]"
            ]
        ]);
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $allowedFilter = new Allowable\Filter\AllowedInFilter("t.foo");
        $allowedFilter->setSubConstraintsForDatatype($dataType, $constraints);
        try {
            $mapping
                ->relate("t.foo", "t.foo")
                ->allow($allowedFilter)
                ->validate();
        } catch (\Kafoso\Questful\Exception\BadRequestException $e) {
            $this->assertSame($expectedExceptionMessage, $e->getMessage());
            return;
        }
        $this->fail();
    }

    public function dataProvider_testValidateThrowsExceptionWhenAFiltersSubConstraintIsNotMet()
    {
        return [
            [
                "'filter=t.foo=[null]': 1 validation(s) failed for value at index 0. Error 1/1: This value should not be null.",
                "null",
                "null",
                [
                    new Assert\NotNull(),
                ],
            ],
            [
                "'filter=t.foo=[true]': 1 validation(s) failed for value at index 0. Error 1/1: This value should be false.",
                "true",
                "boolean",
                [
                    new Assert\IsFalse(),
                ],
            ],
            [
                "'filter=t.foo=[3.14]': 1 validation(s) failed for value at index 0. Error 1/1: This value should be greater than 4.",
                "3.14",
                "double",
                [
                    new Assert\GreaterThan(4),
                ],
            ],
            [
                "'filter=t.foo=[42]': 1 validation(s) failed for value at index 0. Error 1/1: This value should be less than 42.",
                "42",
                "integer",
                [
                    new Assert\LessThan(42),
                ],
            ],
            [
                "'filter=t.foo=[\"bar\"]': 1 validation(s) failed for value at index 0. Error 1/1: This value is too long. It should have 2 characters or less.",
                '"bar"',
                "string",
                [
                    new Assert\Length(['max' => 2]),
                ],
            ],
        ];
    }
}
