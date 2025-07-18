<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Services\UseFormatter;

use PhpCsFixer\Tokenizer\{CT, Token};

use function array_map, array_reduce, count, implode, mb_strlen, strcmp, usort;

use const PHP_EOL, T_NS_SEPARATOR, T_WHITESPACE;

class NamespacedGroupStatement implements StatementInterface
{
    /**
     * @var \LiquidAwesome\CsFixer\Services\UseFormatter\Name[]
     */
    public readonly array $group;

    /**
     * @param \LiquidAwesome\CsFixer\Services\UseFormatter\Name[] $group
     */
    public function __construct(public readonly Name $namespace, array $group, public readonly int $priority = 2)
    {
        usort($group, fn(Name $a, Name $b): int => strcmp($a->string, $b->string));
        $this->group = $group;
    }

    public string $sort_string {
        get => $this->namespace->string;
    }

    public function toString(Prefix $prefix, int $max_line_length): string
    {
        $names = array_map(fn(Name $name): string => $name->string, $this->group);
        $prefix = "{$prefix->string}{$this->namespace->string}\\{";
        $length = mb_strlen($prefix);
        $length += array_reduce($names, fn(int $length, string $name): int => $length + mb_strlen($name), 0);
        $length += (count($names) - 1) * 2; // separators between names
        $length += 2; // end };
        if ($length > $max_line_length) {
            return $prefix . PHP_EOL . '    ' . implode(',' . PHP_EOL . '    ', $names) . PHP_EOL . '};';
        }
        return $prefix . implode(', ', $names) . '};';
    }

    public function toTokens(Prefix $prefix, int $max_line_length): array
    {
        $length = $prefix->length + mb_strlen($this->namespace->string) + 2; // the additional 2 is the \{ after than namespace
        $length += array_reduce($this->group, fn(int $length, Name $name): int => $length + mb_strlen($name->string), 0);
        $length += (count($this->group) - 1) * 2; // separators between names
        $length += 2; // end };
        $tokens = [
            ...$prefix->tokens,
            ...$this->namespace->tokens,
            new Token([T_NS_SEPARATOR, '\\']),
            new Token([CT::T_GROUP_IMPORT_BRACE_OPEN, '{'])
        ];
        $c = 1;
        if ($length > $max_line_length) {
            foreach ($this->group as $name) {
                if ($c !== 1) {
                    $tokens[] = new Token(',');
                }
                $tokens[] = new Token([T_WHITESPACE, PHP_EOL . '    ']);
                $tokens = [...$tokens, ...$name->tokens];
                $c++;
            }
            $tokens[] = new Token([T_WHITESPACE, PHP_EOL]);
            $tokens[] = new Token([CT::T_GROUP_IMPORT_BRACE_CLOSE, '}']);
            $tokens[] = new Token(';');
            return $tokens;
        }
        foreach ($this->group as $name) {
            if ($c !== 1) {
                $tokens[] = new Token(',');
                $tokens[] = new Token([T_WHITESPACE, ' ']);
            }
            $tokens = [...$tokens, ...$name->tokens];
            $c++;
        }
        $tokens[] = new Token([CT::T_GROUP_IMPORT_BRACE_CLOSE, '}']);
        $tokens[] = new Token(';');
        return $tokens;
    }
}
