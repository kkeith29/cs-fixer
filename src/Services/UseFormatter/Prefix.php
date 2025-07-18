<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Services\UseFormatter;

use PhpCsFixer\Tokenizer\Token;

use function mb_strlen;

use const T_USE, T_WHITESPACE;

class Prefix
{
    public function __construct(public readonly StatementType $type)
    {}

    public string $string {
        get => $this->string ??= "use {$this->type->toPrefixString()}";
    }

    /**
     * @var Token[]
     */
    public array $tokens {
        get => $this->tokens ??= [
            new Token([T_USE, 'use']),
            new Token([T_WHITESPACE, ' ']),
            ...$this->type->toTokens()
        ];
    }

    public int $length {
        get => $this->length ??= mb_strlen($this->string);
    }
}
