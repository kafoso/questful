<?php
namespace Kafoso\Questful\Helper;

class Sqlite3Helper
{
    private $sqlite3;

    public function __construct(\SQLite3 $sqlite3)
    {
        $this->sqlite3 = $sqlite3;
    }

    public function applyAllFunctions()
    {
        $this->applyRegexpFunction();
        $this->applyXorFunction();
    }

    public function applyRegexpFunction()
    {
        $this->sqlite3->createFunction('REGEXP', function($regex, $value){
            $regex = addcslashes($regex, '/');
            return intval(preg_match("/$regex/", $value));
        }, 2);
    }

    public function applyXorFunction()
    {
        $this->sqlite3->createFunction("XOR", function($a, $b){
            return ($a xor $b);
        }, 2);
    }
}
