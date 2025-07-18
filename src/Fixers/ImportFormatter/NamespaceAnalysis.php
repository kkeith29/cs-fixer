<?php declare(strict_types=1);

namespace LiquidAwesome\CsFixer\Fixers\ImportFormatter;

readonly class NamespaceAnalysis
{
    /**
     * @param string $full_name Fully qualified namespace name
     * @param string $short_name Short version of namespace
     * @param int $start_index Start index of namespace declaration in analyzed tokens
     * @param int $end_index End index of namespace declaration in analyzed tokens
     * @param int $scope_start_index Start index of the scope of namespace in analyzed tokens
     * @param int $scope_end_index End index of the scope of namespace in analyzed tokens
     */
    public function __construct(
        public string $full_name,
        public string $short_name,
        public int    $start_index,
        public int    $end_index,
        public int    $scope_start_index,
        public int    $scope_end_index
    ) {}
}
