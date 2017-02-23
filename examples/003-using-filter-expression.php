<?php
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Bridge\PdoMySql\PdoMySql5_5;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;
use Kafoso\Questful\Model\QueryParser\Filter\IntegerFilter;

require_once(__DIR__ . "/../vendor/autoload.php");

$url = "http://www.foo.bar/abc?filter%5B0%5D=id%3E=10&filter%5B1%5D=id%3C100&filterExpression=1and0";
// Reads as: http://www.foo.bar/abc?filter[0]=id>=10&filter[1]=id<100&filterExpression=1and0

$queryParserFactory = new QueryParserFactory;
$queryParser = $queryParserFactory->createFromUri($url);

try {
    $queryParser->parse();

    $mapping = new Mapping($queryParser);
    $mapping
        ->relate('id', 'u.id')
        ->allow(new Allowable\Filter\AllowedIntegerFilter("id"))
        ->allow(new Allowable\AllowedFilterExpression("1and0"))
        ->validate();

    $pdoMySqlBridge = new PdoMySql5_5($mapping);
    $pdoMySqlBridge->generate();
} catch (BadRequestException $e) {
    header("HTTP/1.0 400 Bad Request");
    throw $e;
} catch (\Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    throw $e;
}

header("HTTP/1.0 200 OK");
var_dump($pdoMySqlBridge->toArray());
var_dump($queryParser->toArray());
