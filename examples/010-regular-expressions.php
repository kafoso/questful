<?php
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Helper\Sqlite3Helper;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;
use Kafoso\Questful\Model\Bridge\PdoSqlite\PdoSqlite3;

require_once(__DIR__ . "/../vendor/autoload.php");

/**
 * Requires the sqlite3 extension for PHP.
 *
 * For details, see: 008-sqlite3.php
 *
 * Usage: php 010-regular-expressions.php <URL>
 * Where <URL> is any URL containing a query (i.e. content after "?").
 *
 * Sample URLs:
 *  - "?filter[]=id=/^1$/"
 *  - "?filter[]=name=%2F%5Ef%5Cw%2B%24%2Fi"
 *  - "?filter[]=timeCreated%3D%2F201(%3F%3D6)%2F"
 */

if (false == class_exists('SQLite3')) {
    echo "Class 'SQLite3' not found." . PHP_EOL;
    exit(1);
}

if (false == isset($argv[1])) {
    echo "Please provide a URL as the 2nd argument. E.g.: php 010-regular-expressions.php '?filter[]=timeCreated=/01-\d{2}$/'" . PHP_EOL;
    return false;
}

$databaseFilePathAbsolute = __DIR__ . "/resources/__generated/010-regular-expressions.db";

if (file_exists($databaseFilePathAbsolute)) {
    unlink($databaseFilePathAbsolute);
}

$db = new SQLite3($databaseFilePathAbsolute);
$db->query("CREATE TABLE User (id INT(11), name VARCHAR(255), timeCreated DATETIME DEFAULT CURRENT_TIMESTAMP);");
$db->query("INSERT INTO User VALUES (1, \"Foo\", \"2015-01-01\")");
$db->query("INSERT INTO User VALUES (2, \"Bar\", \"2016-01-02\")");
$db->query("INSERT INTO User VALUES (3, \"Baz\", \"2016-02-01\")");

$sqlite3Helper = new Sqlite3Helper($db);
$sqlite3Helper->applyAllFunctions();

$url = $argv[1];

$queryParserFactory = new QueryParserFactory;
$queryParser = $queryParserFactory->createFromUri($url);

try {
    $queryParser->parse();

    $mapping = new Mapping($queryParser);
    $mapping
        ->relate('id', 'u.id')
        ->relate('timeCreated', 'u.timeCreated')
        ->relate('name', 'u.name')
        ->allow(new Allowable\Filter\AllowedRegexpFilter("id"))
        ->allow(new Allowable\Filter\AllowedRegexpFilter("timeCreated"))
        ->allow(new Allowable\Filter\AllowedRegexpFilter("name"))
        ->validate();

    $pdoSqlite = new PdoSqlite3($mapping);
    $pdoSqlite->generate();

    $sql = "SELECT u.* FROM User u";
    if ($pdoSqlite->getWhere()) {
        $sql .= " WHERE {$pdoSqlite->getWhere()}";
    }
    if ($pdoSqlite->getOrderBy()) {
        $sql .= " ORDER BY {$pdoSqlite->getOrderBy()}";
    }
    $stmt = $db->prepare($sql);
    foreach ($pdoSqlite->getParameters() as $k => $v) {
        $stmt->bindParam(":{$k}", $v);
    }
    $result = $stmt->execute();
    $users = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $row;
    }
    echo json_encode($users, JSON_PRETTY_PRINT);
} catch (BadRequestException $e) {
    header("HTTP/1.0 400 Bad Request");
    throw $e;
    /**
     * Will throw \Kafoso\Questful\Exception\BadRequestException with message:
     * 1 filter(s) is/are not allowed. These are: ...
     */
} catch (\Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    throw $e;
} finally {
    $db->close();
    if (file_exists($databaseFilePathAbsolute)) {
        unlink($databaseFilePathAbsolute);
    }
}
