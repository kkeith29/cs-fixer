<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Services\UseFormatter;

use PhpCsFixer\Tokenizer\{CT, Token};

use const T_WHITESPACE;

enum StatementType: string
{
    case ClassLike = 'class';
    case Constant = 'const';
    case Function = 'function';

    public function toPrefixString(): string
    {
        return match ($this) {
            self::ClassLike => '',
            default => "{$this->value} "
        };
    }

    public function toTokens(): array
    {
        return match ($this) {
            self::ClassLike => [],
            self::Constant => [
                new Token([CT::T_CONST_IMPORT, 'const']),
                new Token([T_WHITESPACE, ' '])
            ],
            self::Function => [
                new Token([CT::T_FUNCTION_IMPORT, 'function']),
                new Token([T_WHITESPACE, ' '])
            ],
        };
    }
}
