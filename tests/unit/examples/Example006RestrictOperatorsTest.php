<?php
class Example006RestrictOperatorsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage 'filter=id<1': Disallowed operator "<"; allowed operators are: ["="]
     */
    public function testExpectedOutcome()
    {
        @require(TESTS_PATH . "/../examples/006-restrict-operators.php");
    }
}
