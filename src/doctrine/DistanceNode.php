<?php

namespace Pgvector\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

abstract class DistanceNode extends FunctionNode
{
    public $left;
    public $right;

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
            $this->left->dispatch($sqlWalker),
            $this->getOp(),
            $this->right->dispatch($sqlWalker),
        );
    }
}
