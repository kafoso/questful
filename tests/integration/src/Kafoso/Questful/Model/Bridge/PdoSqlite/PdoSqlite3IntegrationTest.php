l<?php
use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Factory\Model\QueryParser\QueryParserFactory;
use Kafoso\Questful\Helper\Sqlite3Helper;
use Kafoso\Questful\Model\Mapping;
use Kafoso\Questful\Model\Mapping\Allowable;
use Kafoso\Questful\Model\Bridge\PdoSqlite\PdoSqlite3;

class PdoSqliteIntegrationTest extends \PHPUnit_Framework_TestCase
{
    private $dbFilePath;
    private $db;

    protected function setUp()
    {
        $this->dbFilePath = TESTS_RESOURCES_PATH . "/__generated/PdoSqliteIntegrationTest.db";
        if (file_exists($this->dbFilePath)) {
            unlink($this->dbFilePath);
        }
        $this->db = new SQLite3($this->dbFilePath);
        $this->db->query("CREATE TABLE User (id INT(11), name VARCHAR(255), timeCreated DATETIME DEFAULT CURRENT_TIMESTAMP);");
        $this->db->query("INSERT INTO User VALUES (1, \"Foo\", \"2015-01-01\")");
        $this->db->query("INSERT INTO User VALUES (2, \"Bar\", \"2016-01-01\")");
        $this->db->query("INSERT INTO User VALUES (3, \"Baz\", \"2016-02-02\")");
        $this->db->query("CREATE TABLE UserGroup (id INT(11), name VARCHAR(255));");
        $this->db->query("INSERT INTO UserGroup VALUES (1, \"Admin\")");
        $this->db->query("INSERT INTO UserGroup VALUES (2, \"Guest\")");
        $this->db->query("CREATE TABLE User_to_UserGroup (user_id INT(11), userGroup_id INT(11));");
        $this->db->query("INSERT INTO User_to_UserGroup VALUES (1, 1)");
        $this->db->query("INSERT INTO User_to_UserGroup VALUES (3, 2)");

        $sqlite3Helper = new Sqlite3Helper($this->db);
        $sqlite3Helper->applyAllFunctions();
    }

    protected function tearDown()
    {
        if ($this->db) {
            $this->db->close();
        }
        if (file_exists($this->dbFilePath)) {
            unlink($this->dbFilePath);
        }
    }

    public function testSimpleIdQuery()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri("?filter[]=id=1&sort[]=name");
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate('id', 'u.id')
            ->relate('name', 'u.name')
            ->allow(new Allowable\Filter\AllowedIntegerFilter("id"))
            ->allow(new Allowable\AllowedSort("name"))
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
        $expectedSql = "SELECT u.* FROM User u WHERE (u.id = :filter_0) ORDER BY u.name ASC";
        $this->assertSame($expectedSql, $sql);
        $stmt = $this->db->prepare($sql);
        $parameters = $pdoSqlite->getParameters();
        foreach ($parameters as $k => $v) {
            $stmt->bindParam(":{$k}", $parameters[$k]);
        }
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        $expected = [
            [
                'id' => 1,
                'name' => "Foo",
                'timeCreated' => "2015-01-01",
            ]
        ];
        $this->assertSame($expected, $users);
    }

    public function testJoin()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri("?filter[]=ug.id>0");
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate('ug.id', 'ug.id')
            ->allow(new Allowable\Filter\AllowedIntegerFilter("ug.id"))
            ->validate();
        $pdoSqlite = new PdoSqlite3($mapping);
        $pdoSqlite->generate();
        $sql =
            "SELECT u.*
            FROM User u
            JOIN User_to_UserGroup u2ug ON (u.id = u2ug.user_id)
            JOIN UserGroup ug ON(u2ug.userGroup_id = ug.id)";
        if ($pdoSqlite->getWhere()) {
            $sql .= " WHERE {$pdoSqlite->getWhere()}";
        }
        if ($pdoSqlite->getOrderBy()) {
            $sql .= " ORDER BY {$pdoSqlite->getOrderBy()}";
        }
        $expectedSql =
            "SELECT u.*
            FROM User u
            JOIN User_to_UserGroup u2ug ON (u.id = u2ug.user_id)
            JOIN UserGroup ug ON(u2ug.userGroup_id = ug.id) WHERE (ug.id > :filter_0)";
        $this->assertSame($expectedSql, $sql);
        $stmt = $this->db->prepare($sql);
        $parameters = $pdoSqlite->getParameters();
        foreach ($parameters as $k => $v) {
            $stmt->bindParam(":{$k}", $parameters[$k]);
        }
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        $expected = [
            [
                'id' => 1,
                'name' => "Foo",
                'timeCreated' => "2015-01-01",
            ],
            [
                'id' => 3,
                'name' => "Baz",
                'timeCreated' => "2016-02-02",
            ],
        ];
        $this->assertSame($expected, $users);
    }

    public function testFilterExpression()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri("?filter[]=id=1&filter[]=timeCreated=\"2016-01-01\"&filterExpression=0or1");
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate('id', 'u.id')
            ->relate('timeCreated', 'u.timeCreated')
            ->allow(new Allowable\Filter\AllowedIntegerFilter("id"))
            ->allow(new Allowable\Filter\AllowedStringFilter("timeCreated"))
            ->allow(new Allowable\AllowedFilterExpression("*"))
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
        $expectedSql = "SELECT u.* FROM User u WHERE (u.id = :filter_0 OR u.timeCreated = :filter_1)";
        $this->assertSame($expectedSql, $sql);
        $stmt = $this->db->prepare($sql);
        $parameters = $pdoSqlite->getParameters();
        foreach ($parameters as $k => $v) {
            $stmt->bindParam(":{$k}", $parameters[$k]);
        }
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        $expected = [
            [
                'id' => 1,
                'name' => "Foo",
                'timeCreated' => "2015-01-01",
            ],
            [
                'id' => 2,
                'name' => "Bar",
                'timeCreated' => "2016-01-01",
            ],
        ];
        $this->assertSame($expected, $users);
    }

    public function testFilterExpressionWithSimpleXor()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri("?filter[]=id!=3&filter[]=timeCreated=\"2015-01-01\"&filterExpression=0xor1");
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate('id', 'u.id')
            ->relate('timeCreated', 'u.timeCreated')
            ->allow(new Allowable\Filter\AllowedIntegerFilter("id"))
            ->allow(new Allowable\Filter\AllowedStringFilter("timeCreated"))
            ->allow(new Allowable\AllowedFilterExpression("*"))
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
        $expectedSql = "SELECT u.* FROM User u WHERE (XOR(u.id != :filter_0, u.timeCreated = :filter_1))";
        $this->assertSame($expectedSql, $sql);
        $stmt = $this->db->prepare($sql);
        $parameters = $pdoSqlite->getParameters();
        foreach ($parameters as $k => $v) {
            $stmt->bindParam(":{$k}", $parameters[$k]);
        }
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        $expected = [
            [
                'id' => 2,
                'name' => "Bar",
                'timeCreated' => "2016-01-01",
            ],
        ];
        $this->assertSame($expected, $users);
    }

    public function testInFilter()
    {
        $queryParserFactory = new QueryParserFactory;
        $queryParser = $queryParserFactory->createFromUri("?filter[]=id=[2,3]");
        $queryParser->parse();
        $mapping = new Mapping($queryParser);
        $mapping
            ->relate('id', 'u.id')
            ->relate('timeCreated', 'u.timeCreated')
            ->allow(new Allowable\Filter\AllowedInFilter("id"))
            ->allow(new Allowable\AllowedFilterExpression("*"))
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
        $expectedSql = "SELECT u.* FROM User u WHERE (u.id IN (:filter_0_0, :filter_0_1))";
        $this->assertSame($expectedSql, $sql);
        $stmt = $this->db->prepare($sql);
        $parameters = $pdoSqlite->getParameters();
        foreach ($parameters as $k => $v) {
            $stmt->bindParam(":{$k}", $parameters[$k]);
        }
        $result = $stmt->execute();
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        $expected = [
            [
                'id' => 2,
                'name' => "Bar",
                'timeCreated' => "2016-01-01",
            ],
            [
                'id' => 3,
                'name' => "Baz",
                'timeCreated' => "2016-02-02",
            ],
        ];
        $this->assertSame($expected, $users);
    }
}
