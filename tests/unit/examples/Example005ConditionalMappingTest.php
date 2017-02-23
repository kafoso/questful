<?php
class Example005ConditionalMappingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage 1 filter(s) is/are not allowed. These are: id=1
     */
    public function testExpectedOutcome()
    {
        @require(TESTS_PATH . "/../examples/005-conditional-mapping.php");
    }
}
