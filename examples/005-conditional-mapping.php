<?php
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Model\Bridge\PdoMySql\PdoMySql5_5;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;

require_once(__DIR__ . "/../vendor/autoload.php");

$url = "http://www.foo.bar/abc?filter%5B%5D=id=1";
// Reads as: http://www.foo.bar/abc?filter[]=id=1

$queryParserFactory = new QueryParserFactory;
$queryParser = $queryParserFactory->createFromUri($url);

try {
    $queryParser->parse();

    $mapping = new Mapping($queryParser);
    $mapping->relate('id', 'u.id');

    if (isset($_SESSION['user']) && $_SESSION['user']->getId() > 0) {
        // Remember to start session (e.g. run session_start()) when applying this in your own code
        $mapping->allow(new Allowable\Filter\AllowedIntegerFilter("id"));
    }

    $mapping->validate();

    $pdoMySqlBridge = new PdoMySql5_5($mapping);
    $pdoMySqlBridge->generate();
} catch (BadRequestException $e) {
    header("HTTP/1.0 400 Bad Request");
    throw $e;
} catch (\Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    throw $e;
}
