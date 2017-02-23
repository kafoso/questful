<?php
use Kafoso\Questful\Traits\StringNormalizerTrait;

class Example001BasicFilterAndSortingTest extends \PHPUnit_Framework_TestCase
{
    use StringNormalizerTrait;

    public function testExpectedOutput()
    {
        ob_start();
        @require(TESTS_PATH . "/../examples/001-basic-filter-and-sorting.php");
        $output = ob_get_contents();
        ob_end_clean();
        $output = trim($output);
        $output = $this->removeTrailingWhitespace($output);
        $output = $this->normalizeLineEndingsToCrlf($output);
        $expected = file_get_contents(TESTS_RESOURCES_PATH . "/examples/unit/Example001BasicFilterAndSortingTest/testExpectedOutput.resource.txt");
        $expected = trim($expected);
        $expected = $this->removeTrailingWhitespace($expected);
        $expected = $this->normalizeLineEndingsToCrlf($expected);
        $this->assertSame($expected, $output);
    }
}
