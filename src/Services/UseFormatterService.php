<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Services;

use ArrayIterator;
use LiquidAwesome\CsFixer\Services\UseFormatter\{
    ItemContainer,
    Prefix,
    RootGroupStatement,
    SingleStatement,
    StatementInterface,
    StatementType
};
use PhpCsFixer\Tokenizer\Token;
use WeakMap;

use function count, explode, iterator_to_array, str_repeat, strcmp, substr_count, usort;

use const PHP_EOL, PHP_INT_MAX, T_WHITESPACE;

class UseFormatterService
{
    /**
     * @var \WeakMap<StatementType, ItemContainer>|null
     */
    protected ?WeakMap $containers = null;

    /**
     * Add statement parsed from input to an item container
     */
    protected function addStatement(ItemContainer $container, string $statement, ?string $alias): void
    {
        $parts = explode('\\', $statement);
        $count = count($parts);
        $last = $count - 1;
        for ($i = 0; $i < $count; $i++) {
            $part = $parts[$i];
            if ($i === $last) {
                $item = $container->findOrCreate($part);
                $item->use = true;
                $item->alias = $part !== $alias ? $alias : null;
                break;
            }
            $container = $container->findOrCreate($part)->children;
        }
    }

    protected function addStatementByType(StatementType $type, string $statement, ?string $alias): void
    {
        $this->containers ??= new WeakMap();
        $container = $this->containers[$type] ??= new ItemContainer();
        $this->addStatement($container, $statement, $alias);
    }

    public function addClassStatement(string $statement, ?string $alias): void
    {
        $this->addStatementByType(StatementType::ClassLike, $statement, $alias);
    }

    public function addFunctionStatement(string $statement, ?string $alias): void
    {
        $this->addStatementByType(StatementType::Function, $statement, $alias);
    }

    public function addConstantStatement(string $statement, ?string $alias): void
    {
        $this->addStatementByType(StatementType::Constant, $statement, $alias);
    }

    /**
     * Gets grouped items from container and sorts them by their name or priority
     *
     * Items are sorted by their fully qualified name (if available) or their namespace. If two items like a FQN and
     * namespace, we then sort by priority to ensure FQN items will show before namespace groups.
     *
     * @return array<int, \LiquidAwesome\CsFixer\Services\UseFormatter\StatementInterface>
     * @throws \Exception
     */
    protected function getOrderedItems(ItemContainer $container, int $min_sibling_group_count, int $max_group_depth): array
    {
        $items = iterator_to_array($container->getGroupedItems($min_sibling_group_count, $max_group_depth), false);
        // sort items by FQN or namespace, then by priority. if FQN and namespace match, we sort by individual
        // priority so FQN will show before namespace group
        usort($items, function (StatementInterface $a, StatementInterface $b): int {
            $result = strcmp($a->sort_string, $b->sort_string);
            if ($result === 0) {
                $result = $a->priority <=> $b->priority;
            }
            return $result;
        });
        return $items;
    }

    /**
     * Group single statement items into root statement group for display
     *
     * If single statement items are mixed between bracketed groups, we combine them where possible while keeping alphabetical
     * sorting.
     *
     * @param StatementInterface[] $statements
     * @return StatementInterface[]
     */
    protected function groupSingleStatements(array $statements): array
    {
        // group single classes into non bracketed use statements
        $items = [];
        $group = [];
        $iterator = new ArrayIterator($statements);
        while ($iterator->valid()) {
            /** @var StatementInterface $item */
            $item = $iterator->current();
            $iterator->next();
            $is_long = $is_single = false;
            if ($item instanceof SingleStatement) {
                $is_single = true;
                $is_long = substr_count($item->name->string, '\\') > 1;
            }
            if ($is_single && !$is_long) {
                $group[] = $item->name;
            }
            if ((!$is_single || $is_long || !$iterator->valid()) && count($group) > 0) {
                $items[] = new RootGroupStatement($group);
                $group = [];
            }
            if (!$is_single || $is_long) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Create properly formatted token list for a container
     *
     * Gets all renderable items ordered alphabetically and sorted according to their type (Single Fully Qualified Name,
     * Comma Separated Group of Fully Qualified Names, and Bracketed Namespace Grouping). Tokens are generated for each
     * type and combined with a new line.
     *
     * @throws \Exception
     */
    protected function getTokensForStatementType(
        StatementType $type,
        int $max_line_length,
        int $min_sibling_group_count,
        int $max_group_depth
    ): array {
        if (!isset($this->containers[$type])) {
            return [];
        }
        $items = $this->getOrderedItems($this->containers[$type], $min_sibling_group_count, $max_group_depth);
        $items = $this->groupSingleStatements($items);
        $prefix = new Prefix($type);
        $tokens = [];
        $c = 1;
        foreach ($items as $item) {
            if ($c !== 1) {
                $tokens[] = new Token([T_WHITESPACE, PHP_EOL]);
            }
            $tokens = [...$tokens, ...$item->toTokens($prefix, $max_line_length)];
            $c++;
        }
        return $tokens;
    }

    /**
     * @param StatementType[] $type_order
     *
     * @return Token[]
     * @throws \Exception
     */
    public function getTokens(
        array $type_order = [StatementType::ClassLike, StatementType::Function, StatementType::Constant],
        int $max_line_length = PHP_INT_MAX,
        int $min_sibling_group_count = 2,
        int $max_group_depth = 2
    ): array {
        $tokens = [];
        $i = 1;
        foreach ($type_order as $type) {
            $statement_tokens = $this->getTokensForStatementType($type, $max_line_length, $min_sibling_group_count, $max_group_depth);
            if ($statement_tokens === []) {
                continue;
            }
            if ($i !== 1) {
                $tokens[] = new Token([T_WHITESPACE, str_repeat(PHP_EOL, 2)]);
            }
            $tokens = [...$tokens, ...$statement_tokens];
            $i++;
        }
        return $tokens;
    }
}
