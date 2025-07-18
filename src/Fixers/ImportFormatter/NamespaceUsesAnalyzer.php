<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Fixers\ImportFormatter;

use PhpCsFixer\Tokenizer\{CT, Tokens};

/**
 * @see \PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer
 */
class NamespaceUsesAnalyzer
{
    protected static function getImportUseIndexes(Tokens $tokens): array
    {
        $uses = [];
        for ($index = 0, $limit = $tokens->count(); $index < $limit; ++$index) {
            if (!$tokens[$index]->isGivenKind(\T_USE)) {
                continue;
            }
            $uses[] = $index;
        }
        return $uses;
    }

    /**
     * @return list<NamespaceUseAnalysis>
     */
    public static function getDeclarations(Tokens $tokens): array
    {
        $uses = [];
        foreach (self::getImportUseIndexes($tokens) as $index) {
            $endIndex = $tokens->getNextTokenOfKind($index, [';', [\T_CLOSE_TAG]]);
            $uses = [...$uses, ...self::parseDeclarations($index, $endIndex, $tokens)];
        }
        return $uses;
    }

    /**
     * @return list<NamespaceUseAnalysis>
     */
    protected static function parseDeclarations(int $startIndex, int $endIndex, Tokens $tokens): array
    {
        $type = self::determineImportType($tokens, $startIndex);
        $potentialMulti = $tokens->getNextTokenOfKind($startIndex, [',', [CT::T_GROUP_IMPORT_BRACE_OPEN]]);
        $multi = null !== $potentialMulti && $potentialMulti < $endIndex;
        $index = $tokens->getNextTokenOfKind($startIndex, [[\T_STRING], [\T_NS_SEPARATOR]]);
        $imports = [];

        while (null !== $index && $index <= $endIndex) {
            $qualifiedName = self::getNearestQualifiedName($tokens, $index);
            $token = $tokens[$qualifiedName['afterIndex']];

            if ($token->isGivenKind(CT::T_GROUP_IMPORT_BRACE_OPEN)) {
                $groupStart = $groupIndex = $qualifiedName['afterIndex'];
                $groupEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_GROUP_IMPORT_BRACE, $groupStart);

                while ($groupIndex < $groupEnd) {
                    $chunkStart = $tokens->getNextMeaningfulToken($groupIndex);

                    // Finish parsing on trailing comma (no more chunks there)
                    if ($tokens[$chunkStart]->isGivenKind(CT::T_GROUP_IMPORT_BRACE_CLOSE)) {
                        break;
                    }

                    $groupQualifiedName = self::getNearestQualifiedName($tokens, $chunkStart);
                    $imports[] = new NamespaceUseAnalysis(
                        $type,
                        $qualifiedName['fullName'].$groupQualifiedName['fullName'], // @phpstan-ignore argument.type
                        $groupQualifiedName['shortName'],
                        $groupQualifiedName['aliased'],
                        true,
                        $startIndex,
                        $endIndex,
                        $chunkStart,
                        $tokens->getPrevMeaningfulToken($groupQualifiedName['afterIndex'])
                    );

                    $groupIndex = $groupQualifiedName['afterIndex'];
                }

                $index = $groupIndex;
            } elseif ($token->equalsAny([',', ';', [\T_CLOSE_TAG]])) {
                $previousToken = $tokens->getPrevMeaningfulToken($qualifiedName['afterIndex']);

                if (!$tokens[$previousToken]->isGivenKind(CT::T_GROUP_IMPORT_BRACE_CLOSE)) {
                    $imports[] = new NamespaceUseAnalysis(
                        $type,
                        $qualifiedName['fullName'],
                        $qualifiedName['shortName'],
                        $qualifiedName['aliased'],
                        $multi,
                        $startIndex,
                        $endIndex,
                        $multi ? $index : null,
                        $multi ? $previousToken : null
                    );
                }

                $index = $qualifiedName['afterIndex'];
            }

            $index = $tokens->getNextMeaningfulToken($index);
        }

        return $imports;
    }

    /**
     * @return NamespaceUseAnalysis::TYPE_*
     */
    protected static function determineImportType(Tokens $tokens, int $startIndex): int
    {
        $potentialType = $tokens[$tokens->getNextMeaningfulToken($startIndex)];

        if ($potentialType->isGivenKind(CT::T_FUNCTION_IMPORT)) {
            return NamespaceUseAnalysis::TYPE_FUNCTION;
        }

        if ($potentialType->isGivenKind(CT::T_CONST_IMPORT)) {
            return NamespaceUseAnalysis::TYPE_CONSTANT;
        }

        return NamespaceUseAnalysis::TYPE_CLASS;
    }

    /**
     * @return array{fullName: class-string, shortName: string, aliased: bool, afterIndex: int}
     */
    protected static function getNearestQualifiedName(Tokens $tokens, int $index): array
    {
        $fullName = $shortName = '';
        $aliased = false;

        while (null !== $index) {
            $token = $tokens[$index];

            if ($token->isGivenKind(\T_STRING)) {
                $shortName = $token->getContent();
                if (!$aliased) {
                    $fullName .= $shortName;
                }
            } elseif ($token->isGivenKind(\T_NS_SEPARATOR)) {
                $fullName .= $token->getContent();
            } elseif ($token->isGivenKind(\T_AS)) {
                $aliased = true;
            } elseif ($token->equalsAny([
                ',',
                ';',
                [CT::T_GROUP_IMPORT_BRACE_OPEN],
                [CT::T_GROUP_IMPORT_BRACE_CLOSE],
                [\T_CLOSE_TAG],
            ])) {
                break;
            }

            $index = $tokens->getNextMeaningfulToken($index);
        }

        /** @var class-string $fqn */
        $fqn = $fullName;

        return [
            'fullName' => $fqn,
            'shortName' => $shortName,
            'aliased' => $aliased,
            'afterIndex' => $index,
        ];
    }
}
