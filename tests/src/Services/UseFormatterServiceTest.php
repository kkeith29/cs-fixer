<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Tests\Services;

use PHPUnit\Framework\Attributes\{DataProvider, Test};
use InvalidArgumentException;
use LiquidAwesome\CsFixer\Services\{UseFormatterService, UseFormatter\StatementType};
use PHPUnit\Framework\TestCase;

use const PHP_INT_MAX;

class UseFormatterServiceTest extends TestCase
{
    protected function newService(): UseFormatterService
    {
        return new UseFormatterService();
    }

    #[Test]
    public function format_throws_exception_with_no_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->newService()->addStatementsFromCodeBlock('');
    }

    #[Test]
    public function format_throws_exception_with_no_semicolon_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->newService()->addStatementsFromCodeBlock('');
    }

    public static function codeProvider(): array
    {
        return [
            [ // root level items group together
                <<<PHP
                use A;
                use B;
                use C;
                use D;
                PHP,
                <<<PHP
                use A, B, C, D;
                PHP
            ],
            [ // root level items group together in proper order
                <<<PHP
                use D;
                use B;
                use C;
                use A;
                PHP,
                <<<PHP
                use A, B, C, D;
                PHP
            ],
            [ // groups classes by common prefix (A\) using bracket syntax
                <<<PHP
                use A\A;
                use A\B;
                use A\C;
                PHP,
                <<<PHP
                use A\{A, B, C};
                PHP
            ],
            [ // groups classes by common prefix (A\) in alphabetical order
                <<<PHP
                use A\B;
                use A\A;
                use A\C;
                PHP,
                <<<PHP
                use A\{A, B, C};
                PHP
            ],
            [ // groups nested classes by common prefix (A\)
                <<<PHP
                use A\B\C;
                use A\C\D;
                use K;
                use X\Y;
                PHP,
                <<<PHP
                use A\{B\C, C\D};
                use K, X\Y;
                PHP
            ],
            [ // grouping does not go beyond 2 levels for PER Coding Style 2.0 compliance
                <<<PHP
                use A\C\D\E;
                use A\B\C;
                use X\Y;
                PHP,
                <<<PHP
                use A\B\C;
                use A\C\D\E;
                use X\Y;
                PHP
            ],
            [ // groups deeply nested classes in alphabetical order
                <<<PHP
                use X\Y\Z\A;
                use X\Y\Z\B;
                use A\B\C\E;
                use A\B\C\D;
                PHP,
                <<<PHP
                use A\B\C\{D, E};
                use X\Y\Z\{A, B};
                PHP
            ],
            [ // nested group includes name which matches namespace prefix of other classes
                <<<PHP
                use A\B;
                use A\B\C;
                PHP,
                <<<PHP
                use A\{B, B\C};
                PHP
            ],
            [ // fully qualified single item comes before groupings with a shared prefix
                <<<PHP
                use A\B\C\D;
                use A\B\C\E;
                use A\B\C;
                PHP,
                <<<PHP
                use A\B\C;
                use A\B\C\{D, E};
                PHP
            ],
            [ // two or more classes with the same prefix group together instead of merging with other item
                <<<'PHP'
                use A\B\C\D;
                use A\B\C\E;
                use A\B\C;
                PHP,
                <<<PHP
                use A\B\C;
                use A\B\C\{D, E};
                PHP
            ],
            [ // ensure longer name is processed first and not rolled up into any other groups
                <<<'PHP'
                use A\B\C\D;
                use A\B\C\E;
                use A\B\C\F\G;
                use A\B\C\F\G\H\J;
                use A\B\C;
                PHP,
                <<<'PHP'
                use A\B\C;
                use A\B\C\{D, E};
                use A\B\C\F\G;
                use A\B\C\F\G\H\J;
                PHP
            ],
            [ // item alias passes through
                <<<'PHP'
                use A as B;
                PHP,
                <<<'PHP'
                use A as B;
                PHP
            ],
            [ // alias within brackets is correctly read
                <<<'PHP'
                use A\{B as C, D};
                PHP,
                <<<'PHP'
                use A\{B as C, D};
                PHP
            ],
            [ // properly formatted input returns same output (bracket parsing works)
                <<<'PHP'
                use A\B\C;
                use A\B\C\{D, E};
                use J\K\{L, M\N as O};
                use O\P\Q\R\S\T\U\V as W;
                use X, Y as Z, Z\A;
                PHP,
                <<<'PHP'
                use A\B\C;
                use A\B\C\{D, E};
                use J\K\{L, M\N as O};
                use O\P\Q\R\S\T\U\V as W;
                use X, Y as Z, Z\A;
                PHP
            ],
            [ // function and const types are parsed and returned in proper order
                <<<'PHP'
                use const FIVE as SIX;
                use A\B\C;
                use A\B\C\{D, E};
                use J\K\{L, M\N as O};
                use O\P\Q\R\S\T\U\V as W;
                use X, Y as Z, Z\A;

                use function array_pop;
                use const H as I, J, K\L;
                use const ONE, TWO;
                use const A\B\C\THREE;

                use function array_merge, array_chunk, array_column, array_diff,
                 array_key, array_count_values;
                use function array_shift;
                PHP,
                <<<'PHP'
                use A\B\C;
                use A\B\C\{D, E};
                use J\K\{L, M\N as O};
                use O\P\Q\R\S\T\U\V as W;
                use X, Y as Z, Z\A;

                use function array_chunk, array_column, array_count_values, array_diff, array_key, array_merge, array_pop, array_shift;

                use const A\B\C\THREE;
                use const FIVE as SIX, H as I, J, K\L, ONE, TWO;
                PHP
            ],
            [ // test longer lists of items wraps according to max line length
                <<<'PHP'
                use function array_chunk, array_column, array_count_values, array_diff, array_key, array_merge, array_pop, array_shift;
                PHP,
                <<<'PHP'
                use function array_chunk, array_column, 
                             array_count_values, array_diff, 
                             array_key, array_merge, array_pop, 
                             array_shift;
                PHP,
                50
            ],
            [ // test behavior when line length exceeded the declared max line length
                <<<'PHP'
                use One\Two\Three\Four\Five\Size\Seven\Eight\Nine\Ten\Eleven\Twelve;
                use One\Two\Three\Four\Five\Size\Seven\Eight\Nine\Ten\Eleven\Thirteen as Fourteen;

                use function stupid_really_really_long_extra_super_long_function_name as a_long_alias;
                PHP,
                <<<'PHP'
                use One\Two\Three\Four\Five\Size\Seven\Eight\Nine\Ten\Eleven\{
                    Thirteen as Fourteen,
                    Twelve
                };

                use function stupid_really_really_long_extra_super_long_function_name as a_long_alias;
                PHP,
                25
            ]
        ];
    }

    #[Test]
    #[DataProvider('codeProvider')]
    public function getCode_returns_expected_output(
        string $input,
        string $expected,
        int    $max_line_length = PHP_INT_MAX,
        int    $min_sibling_group_count = 2,
        int    $max_group_depth = 2,
        array  $type_order = [StatementType::ClassLike, StatementType::Function, StatementType::Constant]
    ): void {
        $service = $this->newService();
        $service->addStatementsFromCodeBlock($input);
        $actual = $service->getCode($type_order, $max_line_length, $min_sibling_group_count, $max_group_depth);
        self::assertEquals($expected, $actual);
    }

    #[Test]
    #[DataProvider('codeProvider')]
    public function getTokens_returns_expected_output(
        string $input,
        string $expected,
        int    $max_line_length = PHP_INT_MAX,
        int    $min_sibling_group_count = 2,
        int    $max_group_depth = 2,
        array  $type_order = [StatementType::ClassLike, StatementType::Function, StatementType::Constant]
    ): void {
        $service = $this->newService();
        $service->addStatementsFromCodeBlock($input);
        $tokens = $service->getTokens($type_order, $max_line_length, $min_sibling_group_count, $max_group_depth);
        $code = '';
        foreach ($tokens as $token) {
            $code .= $token->getContent();
        }
        self::assertEquals($expected, $code);
    }
}
