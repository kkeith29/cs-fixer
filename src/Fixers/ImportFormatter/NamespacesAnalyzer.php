<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Fixers\ImportFormatter;

use PhpCsFixer\Tokenizer\Tokens;

/**
 * Helps find namespaces
 *
 * Mostly cloned from internal class. Cleaned up anything unnecessary.
 *
 * @see \PhpCsFixer\Tokenizer\Analyzer\NamespacesAnalyzer
 */
class NamespacesAnalyzer
{
    /**
     * @return list<NamespaceAnalysis>
     */
    public static function getDeclarations(Tokens $tokens): array
    {
        $namespaces = [];

        for ($index = 1, $count = \count($tokens); $index < $count; ++$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(\T_NAMESPACE)) {
                continue;
            }

            $declarationEndIndex = $tokens->getNextTokenOfKind($index, [';', '{']);
            $namespace = trim($tokens->generatePartialCode($index + 1, $declarationEndIndex - 1));
            $declarationParts = explode('\\', $namespace);
            $shortName = end($declarationParts);

            if ($tokens[$declarationEndIndex]->equals('{')) {
                $scopeEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $declarationEndIndex);
            } else {
                $scopeEndIndex = $tokens->getNextTokenOfKind($declarationEndIndex, [[\T_NAMESPACE]]);
                if (null === $scopeEndIndex) {
                    $scopeEndIndex = \count($tokens);
                }
                --$scopeEndIndex;
            }

            $namespaces[] = new NamespaceAnalysis(
                $namespace,
                $shortName,
                $index,
                $declarationEndIndex,
                $index,
                $scopeEndIndex
            );

            // Continue the analysis after the end of this namespace to find the next one
            $index = $scopeEndIndex;
        }

        if (0 === \count($namespaces) && $tokens->isTokenKindFound(\T_OPEN_TAG)) {
            $namespaces[] = new NamespaceAnalysis(
                '',
                '',
                $openTagIndex = $tokens[0]->isGivenKind(\T_INLINE_HTML) ? 1 : 0,
                $openTagIndex,
                $openTagIndex,
                \count($tokens) - 1,
            );
        }

        return $namespaces;
    }
}
