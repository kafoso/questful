<?php
use Kafoso\Questful\Model\QueryParser\FilterExpression\Lexer;
use PhpParser\ParserFactory;

class LexerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWorks()
    {
        $lexer = new Lexer("0");
        $this->assertSame("0", $lexer->getExpressionOriginal());
    }

    public function testParseAndBasicGettersWork()
    {
        $lexer = new Lexer("0");
        $lexer->parse();
        $this->assertSame("0", $lexer->getExpressionOriginal());
        $this->assertSame(["0"], $lexer->getTokensOriginal());
        $this->assertSame("0", $lexer->getExpressionNormalized());
        $this->assertSame(["0"], $lexer->getTokensNormalized());
    }

    /**
     * @dataProvider dataProvider_testAVarietyOfValidExpressions
     */
    public function testAVarietyOfValidExpressions($expectedExpressionNormalized, $expressionOriginal)
    {
        $lexer = new Lexer($expressionOriginal);
        $lexer->parse();
        $this->assertSame($expectedExpressionNormalized, $lexer->getExpressionNormalized());
    }

    public function dataProvider_testAVarietyOfValidExpressions()
    {
        return [
            ["0and1", "0and1"],
            ["(0and1)or2", "0and1or2"],
            ["0or(1and2)", "0or1and2"],
            ["(0and1)or(2xor3)", "0and1or2xor3"],
            ["((0and1)and2)and3", "0and1and2and3"],
            ["((0or1)or2)or3", "0or1or2or3"],
            ["((0xor1)xor2)xor3", "0xor1xor2xor3"],
            ["0and(1or2)", "0and(1or2)"],
            ["0and(1or(2xor3))", "0and(1or(2xor3))"],
            ["0or(1and(2or3))", "0or1and(2or3)"],
            ["(0or(1and2))or3", "0or(1and2)or3"],
            ["(0and1)or((2and3)xor(4and5))", "0and1or2and3xor4and5"],
        ];
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage Malformed expression. Ended unexpectedly: 0and
     */
    public function testParseThrowsExceptionWhenExpressionEndsUnexpectedly()
    {
        $lexer = new Lexer("0and");
        $lexer->parse();
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage Malformed expression. Unexpected token '?' at position 4 in expression: 0and?
     */
    public function testParseThrowsExceptionWhenExpressionContainsAnInvalidCharacter()
    {
        $lexer = new Lexer("0and?");
        $lexer->parse();
    }

    /**
     * @expectedException Kafoso\Questful\Exception\BadRequestException
     * @expectedExceptionMessage Malformed expression. Mismatching number of starting and ending parentheses. 1 and 0, respectively, in: (0and1
     */
    public function testParseThrowsExceptionWhenExpressionHasUnmatchedParenthesis()
    {
        $lexer = new Lexer("(0and1");
        $lexer->parse();
    }

    public function testProduceTokensFromExpressionWorks()
    {
        $lexer = new Lexer("");
        $found = $lexer->produceTokensFromExpression("0or1");
        $this->assertSame(["0", "or", "1"], $found);
    }

    public function testSyntaxTreeToExpressionNormalizedRecursionWorks()
    {
        $lexer = new Lexer("");
        $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);
        $code = '<?php (0 and 1);';
        $syntaxTree = $parser->parse($code);
        $found = $lexer->syntaxTreeToExpressionNormalizedRecursion($syntaxTree[0]);
        $this->assertSame("0and1", $found);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\RuntimeException
     * @expectedExceptionMessage Unsupported logical operator node: (object) \PhpParser\Node\Expr\BinaryOp\BooleanAnd
     */
    public function testSyntaxTreeToExpressionNormalizedRecursionThrowsExceptionWhenUnsupportedLogicalOperatorIsEncountered()
    {
        $lexer = new Lexer("");
        $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);
        $code = '<?php (0 && 1);';
        $syntaxTree = $parser->parse($code);
        $lexer->syntaxTreeToExpressionNormalizedRecursion($syntaxTree[0]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\RuntimeException
     * @expectedExceptionMessage Unsupported node on the left-hand side: \PhpParser\Node\Expr\Assign
     */
    public function testSyntaxTreeToExpressionNormalizedRecursionThrowsExceptionWhenUnsupportedLeftHandNodeIsEncountered()
    {
        $lexer = new Lexer("");
        $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);
        $code = '<?php ($a = 0 and 1);';
        $syntaxTree = $parser->parse($code);
        $lexer->syntaxTreeToExpressionNormalizedRecursion($syntaxTree[0]);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\RuntimeException
     * @expectedExceptionMessage Unsupported node on the right-hand side: \PhpParser\Node\Expr\Assign
     */
    public function testSyntaxTreeToExpressionNormalizedRecursionThrowsExceptionWhenUnsupportedRightHandNodeIsEncountered()
    {
        $lexer = new Lexer("");
        $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);
        $code = '<?php (0 and ($a = 1));';
        $syntaxTree = $parser->parse($code);
        $lexer->syntaxTreeToExpressionNormalizedRecursion($syntaxTree[0]);
    }

    public function testTokensToSyntaxTreeAndSyntaxTreeToExpressionNormalizedRecursionWorks()
    {
        $lexer = new Lexer("");
        $syntaxTree = $lexer->tokensToSyntaxTree(["0", "and", "1", "or", "2"]);
        $found = $lexer->syntaxTreeToExpressionNormalizedRecursion($syntaxTree);
        $this->assertSame("(0and1)or2", $found);
    }

    /**
     * @expectedException Kafoso\Questful\Exception\RuntimeException
     * @expectedExceptionMessage Failed to build syntax tree; PHP syntax is invalid: <?php (0 and 1 or ;);
     */
    public function testTokensToExpressionNormalizedThrowsExceptionWhenTokensProduceInvalidPhpSyntax()
    {
        $lexer = new Lexer("");
        $lexer->tokensToSyntaxTree(["0", "and", "1", "or", ";"]);
    }
}
