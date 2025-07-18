<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Services\UseFormatter;

use ArrayIterator;
use PhpCsFixer\Tokenizer\Token;

use function array_map, implode, mb_strlen, str_repeat, strlen;

use const PHP_EOL, T_WHITESPACE;

class RootGroupStatement implements StatementInterface
{
    /**
     * @param \LiquidAwesome\CsFixer\Services\UseFormatter\Name[] $group
     */
    public function __construct(public readonly array $group, public readonly int $priority = 1)
    {}

    public string $sort_string {
        get => implode(', ', array_map(fn(Name $name): string => $name->string, $this->group));
    }

    public function toString(Prefix $prefix, int $max_line_length): string
    {
        $line = $prefix->string;
        $offset = mb_strlen($line);
        $allowed_length = $max_line_length - $offset;
        $length = 0;
        $iterator = new ArrayIterator($this->group);
        $i = 0;
        while ($iterator->valid()) {
            $i++;
            /** @var \LiquidAwesome\CsFixer\Services\UseFormatter\Name $name */
            $name = $iterator->current();
            $data = $name->string;
            $iterator->next();
            if ($iterator->valid()) {
                $data .= ', ';
            }
            $data_length = strlen($data);
            // we check to see if it's the first value since we don't want to have a line break in the first
            // position
            $fits = $i === 1 || $length + $data_length <= $allowed_length;
            if (!$fits) {
                $line .= PHP_EOL . str_repeat(' ', $offset);
                $length = 0;
            }
            $line .= $data;
            $length += $data_length;
        }
        return $line . ';';
    }

    public function toTokens(Prefix $prefix, int $max_line_length): array
    {
        $tokens = $prefix->tokens;
        $offset = $prefix->length;
        $allowed_length = $max_line_length - $offset;
        $length = 0;
        $iterator = new ArrayIterator($this->group);
        $i = 0;
        while ($iterator->valid()) {
            $i++;
            /** @var \LiquidAwesome\CsFixer\Services\UseFormatter\Name $data */
            $data = $iterator->current();
            $data_tokens = $data->tokens;
            $data_length = $data->length;
            $iterator->next();
            if ($iterator->valid()) {
                $data_tokens[] = new Token(',');
                $data_tokens[] = new Token([T_WHITESPACE, ' ']);
                $data_length += 2;
            }
            // we check to see if it's the first value since we don't want to have a line break in the first
            // position
            $fits = $i === 1 || $length + $data_length <= $allowed_length;
            if (!$fits) {
                $tokens[] = new Token([T_WHITESPACE, PHP_EOL . str_repeat(' ', $offset)]);
                $length = 0;
            }
            $tokens = [...$tokens, ...$data_tokens];
            $length += $data_length;
        }
        $tokens[] = new Token(';');
        return $tokens;
    }
}
