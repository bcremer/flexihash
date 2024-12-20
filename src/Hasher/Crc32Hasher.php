<?php

declare(strict_types=1);

namespace Flexihash\Hasher;

use function crc32;

/**
 * Uses CRC32 to hash a value into a signed 32bit int address space.
 * Under 32bit PHP this (safely) overflows into negatives ints.
 *
 * @author Paul Annesley
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class Crc32Hasher implements HasherInterface
{
    public function hash(string $string): int
    {
        return crc32($string);
    }
}
