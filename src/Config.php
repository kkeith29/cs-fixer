<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer;

use LiquidAwesome\CsFixer\Fixers\ImportFormatterFixer;
use PedroTroller\CS\Fixer\CodingStyle\LineBreakBetweenMethodArgumentsFixer;

class Config extends \PhpCsFixer\Config
{
    public function __construct(string $name = 'default')
    {
        parent::__construct($name);
        $this->registerCustomFixers([
            new ImportFormatterFixer(),
            new LineBreakBetweenMethodArgumentsFixer()
        ]);
        $this->setRules([
            '@PER-CS' => true,
            'ordered_imports' => false,
            'blank_line_between_import_groups' => false,
            'blank_line_after_opening_tag' => false,
            'linebreak_after_opening_tag' => false,
            'trailing_comma_in_multiline' => false,
            'single_import_per_statement' => false,
            'fully_qualified_strict_types' => [
                'import_symbols' => true,
                'phpdoc_tags' => []
            ],
            ImportFormatterFixer::NAME => true,
            'PedroTroller/line_break_between_method_arguments' => [
                'max-args' => false,
                'max-length' => 120,
                'automatic-argument-merge' => false,
                'inline-attributes' => false
            ],
            'no_unused_imports' => true,
            'no_extra_blank_lines' => [
                'tokens' => [
                    'attribute', 'break', 'case', 'comma', 'continue', 'curly_brace_block', 'default', 'extra',
                    'parenthesis_brace_block', 'return', 'square_brace_block', 'switch', 'throw'
                ]
            ]
        ]);
    }
}
