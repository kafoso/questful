<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\RuntimeException;
use PhpParser\ParserFactory;

abstract class AbstractStringFilter extends AbstractFilter
{
    protected $isCaseSensitive = true;
    protected $modifiers = [];

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['extra']['isCaseSensitive'] = $this->isCaseSensitive();
        $array['extra']['modifiers'] = $this->getModifiers();
        ksort($array);
        return $array;
    }

    /**
     * @return array (string[])
     */
    public function getModifiers()
    {
        return $this->modifiers;
    }

    /**
     * @return boolean
     */
    public function isCaseSensitive()
    {
        return $this->isCaseSensitive;
    }

    protected function validateSyntax($value)
    {
        $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);
        $code = sprintf('<?php "%s";', $value);
        $syntaxTree = $parser->parse($code);
        return $syntaxTree;
    }

    public static function getAvailableOperators()
    {
        return ["=", "!=", "<=", ">=", ">", "<"];
    }

    public static function getValueDataTypeConstraint()
    {
        return "string";
    }
}
