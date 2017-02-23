<?php
use Kafoso\Questful\Traits\StringNormalizerTrait;

class Example002GeneratingAMysqlParameterizedQueryTest extends \PHPUnit_Framework_TestCase
{
    use StringNormalizerTrait;

    public function testExpectedOutput()
    {
        ob_start();
        @require(TESTS_PATH . "/../examples/002-generating-a-mysql-parameterized-query.php");
        $output = ob_get_contents();
        ob_end_clean();
        $output = trim($output);
        $output = $this->removeTrailingWhitespace($output);
        $output = $this->normalizeLineEndingsToCrlf($output);
        $expected = file_get_contents(TESTS_RESOURCES_PATH . "/examples/unit/Example002GeneratingAMysqlParameterizedQueryTest/testExpectedOutput.resource.txt");
        $expected = trim($expected);
        $expected = $this->removeTrailingWhitespace($expected);
        $expected = $this->normalizeLineEndingsToCrlf($expected);
        $this->assertSame($expected, $output);
    }
}
