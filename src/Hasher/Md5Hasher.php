<?php

declare(strict_types=1);

namespace Flexihash\Hasher;

use function hexdec;
use function md5;
use function substr;

/**
 * Uses MD5 to hash a value into a 32bit int.
 *
 * @author Paul Annesley
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class Md5Hasher implements HasherInterface
{
    /**
     * {@inheritDoc}
     *
     * 8 hexits = 32bit, which also allows us to forego having to check whether
     * it's over PHP_INT_MAX.
     *
     * The substring is converted to an int since hex strings sometimes get
     * treated as ints if all digits are ints and this results in unexpected
     * sorting order.
     */
    public function hash(string $string): int
    {
        return (int) hexdec(substr(md5($string), 0, 8));
    }
}
