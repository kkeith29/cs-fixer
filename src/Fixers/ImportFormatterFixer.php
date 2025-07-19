<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Fixers;

use LiquidAwesome\CsFixer\Fixers\ImportFormatter\{
    NamespaceAnalysis,
    NamespaceUseAnalysis,
    NamespaceUsesAnalyzer,
    NamespacesAnalyzer
};
use LiquidAwesome\CsFixer\Services\UseFormatterService;
use PhpCsFixer\FixerDefinition\{CodeSample, FixerDefinition, FixerDefinitionInterface};
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tokenizer\{Token, Tokens};
use SplFileInfo;

use function array_slice, count, str_repeat;

use const PHP_EOL, T_USE, T_WHITESPACE;

class ImportFormatterFixer implements FixerInterface
{
    public const string NAME = 'LiquidAwesome/import_formatter';

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_USE);
    }

    public function isRisky(): bool
    {
        return false;
    }

    /**
     * @param \LiquidAwesome\CsFixer\Fixers\ImportFormatter\NamespaceUseAnalysis[] $all_use_declarations
     * @throws \Exception
     */
    protected function handleNamespace(Tokens $tokens, NamespaceAnalysis $namespace, array $all_use_declarations): void
    {
        $use_declarations = [];
        foreach ($all_use_declarations as $use_declaration) {
            if (
                $use_declaration->start_index < $namespace->scope_start_index
                || $use_declaration->end_index > $namespace->scope_end_index
            ) {
                continue;
            }
            $use_declarations[] = $use_declaration;
        }
        $service = new UseFormatterService();
        foreach ($use_declarations as $use_declaration) {
            $statement = $use_declaration->full_name;
            $alias = $use_declaration->is_aliased ? $use_declaration->short_name : null;
            match ($use_declaration->type) {
                NamespaceUseAnalysis::TYPE_CLASS => $service->addClassStatement($statement, $alias),
                NamespaceUseAnalysis::TYPE_CONSTANT => $service->addConstantStatement($statement, $alias),
                NamespaceUseAnalysis::TYPE_FUNCTION => $service->addFunctionStatement($statement, $alias),
                default => throw new \Exception("Unhandled type: {$use_declaration->type}")
            };
            $index = $use_declaration->start_index;
            $end_index = $use_declaration->end_index;
            while ($index !== $end_index) {
                $tokens->clearAt($index);
                $index++;
            }
            if (isset($tokens[$index]) && $tokens[$index]->equals(';')) {
                $tokens->clearAt($index);
            }
            ++$index;
            if (isset($tokens[$index]) && $tokens[$index]->isGivenKind(T_WHITESPACE)) {
                $tokens->clearAt($index);
            }
        }

        $index = array_slice($use_declarations, -1)[0]->end_index + 1;
        $service_tokens = $service->getTokens(max_line_length: 120);
        $tokens->insertAt($index, $service_tokens);
        $index += count($service_tokens);
        // standardize whitespace after insert position to 2 line breaks
        while (isset($tokens[$index]) && $tokens[$index]->isWhitespace()) {
            $tokens->clearAt($index);
            $index++;
        }
        if (isset($tokens[$index])) {
            $tokens->insertAt($index, new Token([T_WHITESPACE, str_repeat(PHP_EOL, 2)]));
        }
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        $use_declarations = NamespaceUsesAnalyzer::getDeclarations($tokens);
        if ($use_declarations === []) {
            return;
        }
        foreach (NamespacesAnalyzer::getDeclarations($tokens) as $namespace) {
            $this->handleNamespace($tokens, $namespace, $use_declarations);
        }
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Format import statements using allowed PSR grouping',
            [
                new CodeSample(
                    "<?php\nnamespace A\\B;\nuse A;\nuse B;\n"
                )
            ]
        );
    }

    public function getName(): string
    {
        return static::NAME;
    }

    /**
     * Must run after StatementIndentationFixer, FullyQualifiedStrictTypesFixer, GlobalNamespaceImportFixer, NoUnusedImportsFixer
     *
     */
    public function getPriority(): int
    {
        return -11;
    }

    public function supports(\SplFileInfo $file): bool
    {
        return true;
    }
}
