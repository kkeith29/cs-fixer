<?php

namespace LiquidAwesome\CsFixer\Services\UseFormatter;

interface StatementInterface
{
    public string $sort_string {get;}

    public int $priority {get;}

    public function toString(Prefix $prefix, int $max_line_length): string;

    /**
     * @return \PhpCsFixer\Tokenizer\Token[]
     */
    public function toTokens(Prefix $prefix, int $max_line_length): array;
}
