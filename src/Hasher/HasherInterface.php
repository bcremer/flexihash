<?php

declare(strict_types=1);

namespace Flexihash\Hasher;

/**
 * Hashes given values into a sortable fixed size address space.
 *
 * @author Paul Annesley
 * @license http://www.opensource.org/licenses/mit-license.php
 */
interface HasherInterface
{
    /**
     * Hashes the given string into a 32bit address space.
     *
     * The data must have 0xFFFFFFFF possible values, and be sortable by
     * PHP sort functions using SORT_REGULAR.
     *
     * @return int A sortable format with 0xFFFFFFFF possible values
     */
    public function hash(string $string): int;
}
