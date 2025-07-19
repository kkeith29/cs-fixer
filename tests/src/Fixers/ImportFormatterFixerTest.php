<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Tests\Fixers;

use LiquidAwesome\CsFixer\Fixers\ImportFormatterFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\Attributes\{DataProvider, Test};
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class ImportFormatterFixerTest extends TestCase
{
    public static function codeProvider(): array
    {
        return [
            [ // use statements in global namespace
                <<<'PHP'
                <?php
                use Attribute;
                use Exception;
                PHP,
                <<<'PHP'
                <?php
                use Attribute, Exception;
                PHP
            ],
            [ // use statements under namespace
                <<<'PHP'
                <?php declare(strict_types=1);
                
                namespace Test\Namespace;

                use Attribute;
                use Exception;
                PHP,
                <<<'PHP'
                <?php declare(strict_types=1);
                
                namespace Test\Namespace;

                use Attribute, Exception;
                PHP
            ],
            [ // different types of use statements, spread out, return in proper order
                <<<'PHP'
                <?php declare(strict_types=1);
                
                namespace Test\Namespace;

                use function array_map;
                use Attribute;
                use Exception;
                use const SORT_ASC;


                use function array_diff;

                class Example {}
                PHP,
                <<<'PHP'
                <?php declare(strict_types=1);
                
                namespace Test\Namespace;

                use Attribute, Exception;

                use function array_diff, array_map;

                use const SORT_ASC;

                class Example {}
                PHP
            ],
        ];
    }

    #[Test]
    #[DataProvider('codeProvider')]
    public function fix_returns_expected_output(string $input, string $expected): void
    {
        Tokens::clearCache();
        $tokens = Tokens::fromCode($input);
        $fixer = new ImportFormatterFixer();
        $fixer->fix(new SplFileInfo(__FILE__), $tokens);

        self::assertEquals($expected, $tokens->generateCode());
    }
}
