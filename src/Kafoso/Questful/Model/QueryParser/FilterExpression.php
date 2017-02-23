<?php
namespace Kafoso\Questful\Model\QueryParser;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Model\QueryParser;
use Kafoso\Questful\Model\QueryParser\FilterExpression\Lexer;

class FilterExpression implements FilterExpressionInterface
{
    protected $expression;
    protected $normalized;
    protected $lexer;

    /**
     * @param $expression string
     */
    public function __construct($expression)
    {
        $this->expression = $expression;
        $this->lexer = new Lexer($this->getExpression());
        $this->lexer->parse();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'expressionNormalized' => $this->getLexer()->getExpressionNormalized(),
            'expressionOriginal' => $this->getLexer()->getExpressionOriginal(),
            'indexes' => $this->getIndexes(),
        ];
    }

    /**
     * @return array (integer[])
     */
    public function getIndexes()
    {
        $indexes = [];
        foreach ($this->getLexer()->getTokensNormalized() as $token) {
            if (preg_match("/^\d+$/", $token)) {
                $indexes[] = intval($token);
            }
        }
        $indexes = array_unique($indexes);
        sort($indexes);
        return $indexes;
    }

    /**
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * @return object \Kafoso\Questful\Model\QueryParser\FilterExpression\Lexer
     */
    public function getLexer()
    {
        return $this->lexer;
    }
}
