<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Fixers\ImportFormatter;

use LogicException;

/**
 * @see \PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis
 */
readonly class NamespaceUseAnalysis
{
    public const int TYPE_CLASS = 1; // "classy" could be class, interface or trait
    public const int TYPE_FUNCTION = 2;
    public const int TYPE_CONSTANT = 3;

    /**
     * @param self::TYPE_* $type The type of import: class, function or constant.
     * @param class-string $full_name The fully qualified use namespace.
     * @param string $short_name The short version of use namespace or the alias name in case of aliased use statements.
     * @param bool $is_aliased Is the use statement being aliased?
     * @param bool $is_in_multi Is the use statement part of multi-use (`use A, B, C;`, `use A\{B, C};`)?
     * @param int $start_index The start index of the namespace declaration in the analyzed Tokens.
     * @param int $end_index The end index of the namespace declaration in the analyzed Tokens.
     * @param int|null $chunk_start_index The start index of the single import in the multi-use statement.
     * @param int|null $chunk_end_index The end index of the single import in the multi-use statement.
     */
    public function __construct(
        public int    $type,
        public string $full_name,
        public string $short_name,
        public bool   $is_aliased,
        public bool   $is_in_multi,
        public int    $start_index,
        public int    $end_index,
        public ?int   $chunk_start_index = null,
        public ?int   $chunk_end_index = null
    ) {
        if (true === $is_in_multi && (null === $chunk_start_index || null === $chunk_end_index)) {
            throw new LogicException('Chunk start and end index must be set when the import is part of a multi-use statement.');
        }
    }
}
