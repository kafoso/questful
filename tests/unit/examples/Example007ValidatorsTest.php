<?php
class Example007ValidatorsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage 'filter=foo<"2016-01-01"': 1 validation(s) failed. First error: Date is outside confines of 2015-01-01
     */
    public function testExpectedOutcome()
    {
        @require(TESTS_PATH . "/../examples/007-validators.php");
    }
}
