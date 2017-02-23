<?php
namespace Kafoso\Questful\Model\QueryParser\Filter;

use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\UnexpectedValueException;
use Kafoso\Questful\Model\QueryParser;
use PhpParser\ParserFactory;

class InFilter extends AbstractFilter
{
    const MATCH_PATTERN = '/^\[(.*)\](\/([i]))?$/';

    protected $isCaseSensitive = true;
    protected $modifiers = [];

    /**
     * @override
     */
    public function __construct($expression, $key, $value, $operator = null)
    {
        preg_match(self::MATCH_PATTERN, $value, $match);
        if (!$match) {
            throw new UnexpectedValueException(sprintf(
                "Expects value to match \"%s\". Found: %s",
                self::MATCH_PATTERN,
                FormattingHelper::found($value)
            ), QueryParser::EXCEPTION_CODE);
        }
        $innerValue = $match[1];
        try {
            $syntaxTree = $this->parseAndValidateSyntax($innerValue);
        } catch (\PhpParser\Error $e) {
            throw new BadRequestException(sprintf(
                "String syntax in 'filter[]=%s' is invalid: %s",
                $expression,
                $innerValue
            ), QueryParser::EXCEPTION_CODE);
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf(
                "Unexpected error occurred during syntax validation of string 'filter[]=%s' for string: %s",
                $expression,
                $innerValue
            ), QueryParser::EXCEPTION_CODE, $e);
        }
        if (false == ($syntaxTree[0] instanceof \PhpParser\Node\Expr\Array_)) {
            throw new RuntimeException(sprintf(
                "Expected array for 'filter[]=%s'. Found: \\%s",
                $expression,
                get_class($syntaxTree[0])
            ), QueryParser::EXCEPTION_CODE);
        }
        if (!$syntaxTree[0]->items) {
            throw new BadRequestException(sprintf(
                "Array in 'filter[]=%s' is empty",
                $expression
            ), QueryParser::EXCEPTION_CODE);
        }
        $array = $this->syntaxTreeToSupportedArray($syntaxTree[0]);
        parent::__construct($expression, $key, $array, $operator);
        if (isset($match[3]) && $match[3]) {
            $this->modifiers = array_unique(str_split($match[3]));
            $this->isCaseSensitive = (false == in_array("i", $this->modifiers));
        }
    }

    /**
     * @return array
     */
    public function syntaxTreeToSupportedArray(\PhpParser\Node\Expr\Array_ $node)
    {
        $array = [];
        foreach ($node->items as $item) {
            if ($item->value instanceof \PhpParser\Node\Expr\ConstFetch && "null" === $item->value->name->parts[0]) {
                $array[] = null;
            } elseif ($item->value instanceof \PhpParser\Node\Expr\ConstFetch && "true" === $item->value->name->parts[0]) {
                $array[] = true;
            } elseif ($item->value instanceof \PhpParser\Node\Expr\ConstFetch && "false" === $item->value->name->parts[0]) {
                $array[] = false;
            } elseif ($item->value instanceof \PhpParser\Node\Scalar\LNumber) {
                $array[] = $item->value->value;
            } elseif ($item->value instanceof \PhpParser\Node\Scalar\DNumber) {
                $array[] = $item->value->value;
            } elseif ($item->value instanceof \PhpParser\Node\Scalar\String_) {
                $array[] = $item->value->value;
            }
        }
        return $array;
    }

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

    protected function parseAndValidateSyntax($value)
    {
        $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);
        $code = sprintf('<?php [%s];', $value);
        return $parser->parse($code);
    }

    public static function getAvailableOperators()
    {
        return ["=", "!="];
    }

    public static function getIdentifier()
    {
        return "array";
    }

    public static function getValueDataTypeConstraint()
    {
        return "array";
    }
}
