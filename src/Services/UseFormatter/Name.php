<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Services\UseFormatter;

use PhpCsFixer\Tokenizer\Token;

use function count, explode;

use const T_AS, T_NS_SEPARATOR, T_STRING, T_WHITESPACE;

class Name
{
    public static function fromItem(Item $item): static
    {
        return new static($item->getFullyQualifiedName(false), $item->alias);
    }

    public function __construct(public readonly string $name, public readonly ?string $alias = null)
    {}

    /**
     * @var Token[]
     */
    public array $tokens {
        get {
            if (!isset($this->tokens)) {
                $this->tokens = [];
                $parts = explode('\\', $this->name);
                $count = count($parts);
                $c = 1;
                foreach ($parts as $part) {
                    $this->tokens[] = new Token([T_STRING, $part]);
                    if ($c !== $count) {
                        $this->tokens[] = new Token([T_NS_SEPARATOR, '\\']);
                    } elseif ($this->alias !== null) {
                        $this->tokens[] = new Token([T_WHITESPACE, ' ']);
                        $this->tokens[] = new Token([T_AS, 'as']);
                        $this->tokens[] = new Token([T_WHITESPACE, ' ']);
                        $this->tokens[] = new Token([T_STRING, $this->alias]);
                    }
                    $c++;
                }
            }
            return $this->tokens;
        }
    }

    public function toString(bool $with_alias = true): string
    {
        $name = $this->name;
        if ($with_alias && $this->alias !== null) {
            $name .= " as {$this->alias}";
        }
        return $name;
    }

    public string $string {
        get => $this->string ??= $this->toString();
    }

    public int $length {
        get => $this->length ??= mb_strlen($this->string);
    }
}
