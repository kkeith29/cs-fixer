<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Services\UseFormatter;

use PhpCsFixer\Tokenizer\Token;

class SingleStatement implements StatementInterface
{
    public function __construct(public readonly Name $name, public readonly int $priority = 1)
    {}

    public string $sort_string {
        get => $this->name->string;
    }

    public function toString(Prefix $prefix, int $max_line_length): string
    {
        return "{$prefix->string}{$this->name->string};";
    }

    public function toTokens(Prefix $prefix, int $max_line_length): array
    {
        return [...$prefix->tokens, ...$this->name->tokens, new Token(';')];
    }
}
