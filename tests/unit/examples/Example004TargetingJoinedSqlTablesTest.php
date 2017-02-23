<?php
use Kafoso\Questful\Traits\StringNormalizerTrait;

class Example004TargetingJoinedSqlTablesTest extends \PHPUnit_Framework_TestCase
{
    use StringNormalizerTrait;

    public function testExpectedOutput()
    {
        ob_start();
        @require(TESTS_PATH . "/../examples/004-targeting-joined-sql-tables.php");
        $output = ob_get_contents();
        ob_end_clean();
        $output = trim($output);
        $output = $this->removeTrailingWhitespace($output);
        $output = $this->normalizeLineEndingsToCrlf($output);
        $expected = file_get_contents(TESTS_RESOURCES_PATH . "/examples/unit/Example004TargetingJoinedSqlTablesTest/testExpectedOutput.resource.txt");
        $expected = trim($expected);
        $expected = $this->removeTrailingWhitespace($expected);
        $expected = $this->normalizeLineEndingsToCrlf($expected);
        $this->assertSame($expected, $output);
    }
}
