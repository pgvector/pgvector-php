<?php

namespace Pgvector\Doctrine;

use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

abstract class DistanceNode extends FunctionNode
{
    public AST\Node|string $left;
    public AST\Node|string $right;

    abstract protected function getOp(): string;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->left = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->right = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return sprintf(
            '(%s %s %s)',
            $sqlWalker->walkArithmeticPrimary($this->left),
            $this->getOp(),
            $sqlWalker->walkArithmeticPrimary($this->right)
        );
    }
}
