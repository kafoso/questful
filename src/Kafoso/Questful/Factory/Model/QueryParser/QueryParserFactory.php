<?php
namespace Kafoso\Questful\Factory\Model\QueryParser;

use Kafoso\Questful\Model\QueryParser;

class QueryParserFactory
{
    /**
     * @param $uri string
     * @return object \Kafoso\Questful\Model\QueryParser
     */
    public function createFromUri($uri)
    {
        $query = parse_url($uri, PHP_URL_QUERY);
        $queryArray = [];
        if ($query) {
            parse_str($query, $queryArray);
        }
        return new QueryParser($queryArray);
    }
}
