<?php
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;

require_once(__DIR__ . "/../vendor/autoload.php");

$url = "http://www.foo.bar/abc?filter%5B%5D=id%3E1&sort%5B%5D=name";
// Reads as: http://www.foo.bar/abc?filter[]=id>1&sort[]=name

$queryParserFactory = new QueryParserFactory;
$queryParser = $queryParserFactory->createFromUri($url);

try {
    $queryParser->parse();

    $mapping = new Mapping($queryParser);
    $mapping
        ->relate('id', 'u.id')
        ->relate('name', 'u.name')
        ->allow(new Allowable\Filter\AllowedIntegerFilter("id"))
        ->allow(new Allowable\AllowedSort("name"))
        ->validate();
} catch (BadRequestException $e) {
    header("HTTP/1.0 400 Bad Request");
    throw $e;
} catch (\Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    throw $e;
}

header("HTTP/1.0 200 OK");
var_dump($queryParser->toArray()); // Prints metadata about the filterExpression, filters, and sorting
