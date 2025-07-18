<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Services\UseFormatter;

use Exception;

class Item
{
    protected static int $last_id = 0;

    public readonly int $id;

    public bool $grouped = false;

    /**
     * Item constructor
     *
     * Set unique id for item
     */
    public function __construct(
        public readonly string $name,
        public bool $use = false,
        public ?string $alias = null
    ) {
        $this->id = self::$last_id++;
    }

    public ItemContainer $container {
        get => $this->container ?? throw new Exception('No parent defined on Item');
    }

    public string $name_with_alias {
        get => $this->name . ($this->alias !== null ? " as $this->alias" : '');
    }

    /**
     * Get namespace of item
     *
     * Loops through parents of item to build namespace string. If $until item is provided, the namespace generation
     * will stop when that $until item is hit in the parent list.
     *
     * @throws \Exception
     */
    public function getNamespace(?Item $until = null): string
    {
        if (($parent = $this->container->parent) === null || $parent === $until) {
            return '';
        }
        if (($namespace = $parent->getNamespace($until)) !== '') {
            $namespace .= '\\';
        }
        return $namespace . $parent->name;
    }

    public string $namespace {
        get => $this->getNamespace();
    }

    /**
     * Get fully qualified name (FQN) using namespace and name
     *
     * @throws \Exception
     */
    public function getFullyQualifiedName(bool $with_alias = true, ?Item $until = null): string
    {
        if (($namespace = $this->getNamespace($until)) !== '') {
            $namespace .= '\\';
        }
        return $namespace . ($with_alias ? $this->name_with_alias : $this->name);
    }

    public string $fully_qualified_name {
        get => $this->getFullyQualifiedName();
    }

    public bool $has_children {
        get => isset($this->children) && !$this->children->is_empty;
    }

    /**
     * Get children item container (or create if it doesn't exist)
     */
    public ItemContainer $children {
        get => $this->children ??= new ItemContainer($this);
    }

    /**
     * Get depth of item from the root container
     */
    public int $depth {
        get => $this->depth ??= $this->container->depth + 1;
    }

    /**
     * Determines if item is the last in its branch (has no children)
     */
    public bool $is_last {
        get => !$this->has_children;
    }

    /**
     * Get and cache the number of levels this item is from the end of it's branch
     */
    public int $count_to_last {
        get => $this->count_to_last ??= $this->has_children ? $this->children->count_to_last : 0;
    }

    /**
     * Get parent at specified depth
     *
     * Traverses up the tree to find parent at $depth and returns it. If no parent is found, then null is returned.
     *
     * @throws \Exception
     */
    public function getParent(int $depth): ?Item
    {
        $item = $this;
        while ($depth > 0 && $item !== null) {
            $item = $item->container->parent;
            $depth--;
        }
        return $item;
    }
}
