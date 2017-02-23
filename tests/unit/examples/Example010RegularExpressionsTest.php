<?php
use Kafoso\Questful\Traits\StringNormalizerTrait;

class Example010RegularExpressionsTest extends \PHPUnit_Framework_TestCase
{
    use StringNormalizerTrait;

    public function testExpectedOutput()
    {
        ob_start();
        $argv = [
            TESTS_PATH . "/../examples/010-regular-expressions.php",
            '?filter[]=timeCreated=/01-\d{2}$/',
        ];
        @require($argv[0]);
        $output = ob_get_contents();
        ob_end_clean();
        $output = trim($output);
        $output = $this->removeTrailingWhitespace($output);
        $output = $this->normalizeLineEndingsToCrlf($output);
        $expected = file_get_contents(TESTS_RESOURCES_PATH . "/examples/unit/Example010RegularExpressionsTest/testExpectedOutput.resource.txt");
        $expected = trim($expected);
        $expected = $this->removeTrailingWhitespace($expected);
        $expected = $this->normalizeLineEndingsToCrlf($expected);
        $this->assertSame($expected, $output);
    }

    public function testPrintsErrorMessageWhen2ndArgumentIsMissing()
    {
        ob_start();
        $argv = [
            TESTS_PATH . "/../examples/010-regular-expressions.php",
        ];
        @require($argv[0]);
        $output = ob_get_contents();
        ob_end_clean();
        $output = trim($output);
        $expected = "Please provide a URL as the 2nd argument. E.g.: php 010-regular-expressions.php '?filter[]=timeCreated=/01-\d{2}$/'";
        $this->assertSame($expected, $output);
    }
}
