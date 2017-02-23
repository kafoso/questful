<?php
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

require_once(__DIR__ . "/../vendor/autoload.php");

$url = "http://www.foo.bar/abc?filter%5B%5D=foo<\"2016-01-01\"";

$queryParserFactory = new QueryParserFactory;
$queryParser = $queryParserFactory->createFromUri($url);

try {
    $queryParser->parse();

    $dateConfineMax = "2015-01-01";

    $mapping = new Mapping($queryParser);
    $mapping
        ->relate('foo', 'foo')
        ->allow(new Allowable\Filter\AllowedStringFilter("foo", ["<"], [
            new Assert\NotBlank(),
            new Assert\Regex([
                'pattern' => '/^\d{4}-\d{2}-\d{2}$/',
            ]),
            new Assert\Callback(function($value, ExecutionContextInterface $executionContext) use($dateConfineMax){
                if ($value > $dateConfineMax) {
                    $executionContext->addViolation("Date is outside confines of %dateConfineMax%", [
                        "%dateConfineMax%" => $dateConfineMax,
                    ]);
                }
            }),
        ]))
        ->validate();
} catch (BadRequestException $e) {
    header("HTTP/1.0 400 Bad Request");
    throw $e;
} catch (\Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    throw $e;
}
