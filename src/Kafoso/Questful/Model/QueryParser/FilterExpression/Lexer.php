<?php
namespace Kafoso\Questful\Model\QueryParser\FilterExpression;

use Kafoso\Questful\Exception\BadRequestException;
use Kafoso\Questful\Exception\FormattingHelper;
use Kafoso\Questful\Exception\InvalidArgumentException;
use Kafoso\Questful\Exception\RuntimeException;
use Kafoso\Questful\Model\QueryParser;
use PhpParser\ParserFactory;

/**
 * Tokenizes and normalizes a logical expression, e.g. "0and1or2".
 */
class Lexer
{
    protected $expressionOriginal;

    protected $expressionNormalized = null;
    protected $syntaxTree = null;
    protected $tokensNormalized = null;
    protected $tokensOriginal = null;

    /**
     * @param $expression string
     * @throws \Kafoso\Questful\Exception\InvalidArgumentException
     */
    public function __construct($expressionOriginal)
    {
        if (false == is_string($expressionOriginal)) {
            throw new InvalidArgumentException(sprintf(
                "Expects argument '%s' to be a string. Found: %s",
                '$expressionOriginal',
                FormattingHelper::found($expressionOriginal)
            ));
        }
        $this->expressionOriginal = $expressionOriginal;
    }

    /**
     * @return object $this
     */
    public function parse()
    {
        $this->expressionNormalized = null;
        $this->syntaxTree = null;
        $this->tokensOriginal = null;
        $this->tokensNormalized = null;
        if ("" !== trim($this->expressionOriginal)) {
            $this->tokensOriginal = $this->produceTokensFromExpression($this->expressionOriginal);
            $this->syntaxTree = $this->tokensToSyntaxTree($this->tokensOriginal);
            $this->expressionNormalized = $this->syntaxTreeToExpressionNormalizedRecursion($this->syntaxTree);
            $this->tokensNormalized = $this->produceTokensFromExpression($this->expressionNormalized);
        }
        return $this;
    }

    /**
     * @param $expression string
     * @throws \Kafoso\Questful\Exception\BadRequestException
     * @throws \Kafoso\Questful\Exception\RuntimeException
     * @return array (string[])
     */
    public function produceTokensFromExpression($expression)
    {
        $tokens = [];
        $position = 0;
        $expected = ['\(', '\d+'];
        $parenthesesStartEncountered = 0;
        $parenthesesEndEncountered = 0;
        $canEnd = true;
        while (strlen($expression)) {
            $regex = sprintf('/^(%s)/', implode('|', $expected));
            if (preg_match($regex, $expression, $match)) {
                if ('(' == $match[1]) {
                    $parenthesesStartEncountered++;
                    $expression = strval(substr($expression, 1));
                    $position++;
                    $expected = ['\(', '\d+'];
                    $canEnd = false;
                } elseif (')' == $match[1]) {
                    $parenthesesEndEncountered++;
                    $expression = strval(substr($expression, 1));
                    $position++;
                    $expected = ['and', 'x?or'];
                    if ($parenthesesEndEncountered < $parenthesesStartEncountered) {
                        $expected[] = '\)';
                    }
                    $canEnd = ($parenthesesEndEncountered == $parenthesesStartEncountered);
                } elseif (is_numeric($match[1])) {
                    $expression = strval(substr($expression, strlen($match[1])));
                    $position += strlen($match[1]);
                    $expected = ['and', 'x?or'];
                    if ($parenthesesEndEncountered < $parenthesesStartEncountered) {
                        $expected[] = '\)';
                    }
                    $canEnd = ($parenthesesEndEncountered == $parenthesesStartEncountered);
                } elseif (in_array($match[1], ['and', 'or', 'xor'])) {
                    $expression = strval(substr($expression, strlen($match[1])));
                    $position += strlen($match[1]);
                    $expected = ['\(', '\d+'];
                    $canEnd = false;
                } else {
                    throw new RuntimeException(sprintf(
                        "Uncovered case for '%s' in expression: %s",
                        $match[1],
                        $this->expressionOriginal
                    ));
                }
                $tokens[] = $match[1];
            } else {
                throw new BadRequestException(sprintf(
                    "Malformed expression. Unexpected token '%s' at position %d in expression: %s",
                    substr($expression, 0, 1),
                    $position,
                    $this->expressionOriginal
                ));
            }
        }
        if (false == $canEnd) {
            if ($parenthesesStartEncountered != $parenthesesEndEncountered) {
                throw new BadRequestException(sprintf(
                    "Malformed expression. Mismatching number of starting and ending parentheses. %d and %d, respectively, in: %s",
                    $parenthesesStartEncountered,
                    $parenthesesEndEncountered,
                    $this->expressionOriginal
                ));
            } else {
                throw new BadRequestException(sprintf(
                    "Malformed expression. Ended unexpectedly: %s",
                    $this->expressionOriginal
                ));
            }
        }
        return $tokens;
    }

    /**
     * Recursion.
     * @throws \Kafoso\Questful\Exception\RuntimeException
     * @return string
     */
    public function syntaxTreeToExpressionNormalizedRecursion(\PhpParser\Node $node)
    {
        $str = "";
        if ($node instanceof \PhpParser\Node\Expr\BinaryOp) {
            $operator = null;
            if ($node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalAnd) {
                $operator = "and";
            } elseif ($node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalOr) {
                $operator = "or";
            } elseif ($node instanceof \PhpParser\Node\Expr\BinaryOp\LogicalXor) {
                $operator = "xor";
            }
            if (null === $operator) {
                throw new RuntimeException(sprintf(
                    "Unsupported logical operator node: %s",
                    FormattingHelper::found($node)
                ));
            }
            if ($node->left instanceof \PhpParser\Node\Scalar\LNumber) {
                $str .= $node->left->value;
            } elseif ($node->left instanceof \PhpParser\Node\Expr\BinaryOp) {
                $str .= sprintf("(%s)", $this->syntaxTreeToExpressionNormalizedRecursion($node->left));
            } else {
                throw new RuntimeException(sprintf(
                    "Unsupported node on the left-hand side: \\%s",
                    get_class($node->left)
                ));
            }
            $str .= $operator;
            if ($node->right instanceof \PhpParser\Node\Scalar\LNumber) {
                $str .= $node->right->value;
            } elseif ($node->right instanceof \PhpParser\Node\Expr\BinaryOp) {
                $str .= sprintf("(%s)", $this->syntaxTreeToExpressionNormalizedRecursion($node->right));
            } else {
                throw new RuntimeException(sprintf(
                    "Unsupported node on the right-hand side: \\%s",
                    get_class($node->right)
                ));
            }
        } elseif ($node instanceof \PhpParser\Node\Scalar\LNumber) {
            $str .= $node->value;
        }
        return $str;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            "expressionNormalized" => $this->getExpressionNormalized(),
            "expressionOriginal" => $this->getExpressionOriginal(),
            "tokensNormalized" => $this->getTokensNormalized(),
            "tokensOriginal" => $this->getTokensOriginal(),
        ];
    }

    /**
     * @param $tokens array (string[])
     * @throws \Kafoso\Questful\Exception\RuntimeException
     * @return ?string
     */
    public function tokensToSyntaxTree(array $tokens)
    {
        $code = null;
        try {
            $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);
            $code = sprintf('<?php (%s);', implode(" ", $tokens));
            $stmts = $parser->parse($code);
            if (!$stmts) {
                return null;
            }
            return $stmts[0];
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf(
                "Failed to build syntax tree; PHP syntax is invalid: %s",
                $code
            ), 0, $e);
        }
        return null;
    }

    /**
     * @return ?string
     */
    public function getExpressionNormalized()
    {
        return $this->expressionNormalized;
    }

    /**
     * @return string
     */
    public function getExpressionOriginal()
    {
        return $this->expressionOriginal;
    }

    /**
     * @return ?object \PhpParser\Node
     */
    public function getSyntaxTree()
    {
        return $this->syntaxTree;
    }

    /**
     * @return ?array (string[])
     */
    public function getTokensNormalized()
    {
        return $this->tokensNormalized;
    }

    /**
     * @return ?array (string[])
     */
    public function getTokensOriginal()
    {
        return $this->tokensOriginal;
    }
}
