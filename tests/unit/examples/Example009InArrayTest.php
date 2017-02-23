<?php
use Kafoso\Questful\Traits\StringNormalizerTrait;

class Example009InArrayTest extends \PHPUnit_Framework_TestCase
{
    use StringNormalizerTrait;

    public function testExpectedOutput()
    {
        ob_start();
        $argv = [
            TESTS_PATH . "/../examples/009-in-array.php",
            '?filter[]=name=["fOO", "bAr"]/i',
        ];
        @require($argv[0]);
        $output = ob_get_contents();
        ob_end_clean();
        $output = trim($output);
        $output = $this->removeTrailingWhitespace($output);
        $output = $this->normalizeLineEndingsToCrlf($output);
        $expected = file_get_contents(TESTS_RESOURCES_PATH . "/examples/unit/Example009InArrayTest/testExpectedOutput.resource.txt");
        $expected = trim($expected);
        $expected = $this->removeTrailingWhitespace($expected);
        $expected = $this->normalizeLineEndingsToCrlf($expected);
        $this->assertSame($expected, $output);
    }

    public function testPrintsErrorMessageWhen2ndArgumentIsMissing()
    {
        ob_start();
        $argv = [
            TESTS_PATH . "/../examples/009-in-array.php",
        ];
        @require($argv[0]);
        $output = ob_get_contents();
        ob_end_clean();
        $output = trim($output);
        $expected = "Please provide a URL as the 2nd argument. E.g.: php 009-in-array.php '?filter[]=name=[\"fOO\", \"bAr\"]/i'";
        $this->assertSame($expected, $output);
    }
}
