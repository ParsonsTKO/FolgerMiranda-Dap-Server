<?php declare(strict_types=1);

namespace IIIFBundle\Doctrine\Query;

use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class RemoteSystemEquals extends FunctionNode
{
    /**
     * @var AggregateExpression
     */
    public $field = null;

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->field = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return sprintf(
            $this->getSQlString(),
            $this->field->dispatch($sqlWalker)
        );
    }

    /**
     * @return string
     */
    protected function getSQlString() : string
    {
        return "%s->'remoteUniqueID'->>'remoteSystem'";
    }
}