<?php
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Bridge\PdoMySql\PdoMySql5_5;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;

require_once(__DIR__ . "/../vendor/autoload.php");

$url = "http://www.foo.bar/abc?filter%5B%5D=timeCreated%3E%3D%222016-01-01%22&filter%5B%5D=c.id=2";
// Reads as: http://www.foo.bar/abc?filter[]=timeCreated>=2016-01-01&filter[]=c.id=2

$queryParserFactory = new QueryParserFactory;
$queryParser = $queryParserFactory->createFromUri($url);

try {
    $queryParser->parse();

    $mapping = new Mapping($queryParser);
    $mapping
        ->relate('timeCreated', 'u.timeCreated')
        ->relate('c.id', 'c.id')
        ->allow(new Allowable\Filter\AllowedStringFilter("timeCreated"))
        ->allow(new Allowable\Filter\AllowedIntegerFilter("c.id"))
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

$sql = "
    SELECT u.*
    FROM User u
    JOIN Company c ON (u.company_id = c.id)
    {$pdoMySqlBridge->getWhere()};";
echo "Sample SQL Query: " . PHP_EOL;
echo "$sql" . PHP_EOL . PHP_EOL;
echo "Parameters: " . print_r($pdoMySqlBridge->getParameters(), true) . PHP_EOL;
