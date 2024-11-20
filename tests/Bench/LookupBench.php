<?php

declare(strict_types=1);

namespace Flexihash\Tests\Bench;

use Flexihash\Flexihash;
use Flexihash\Hasher\Md5Hasher;
use Generator;
use PhpBench\Attributes\ParamProviders;

use function bin2hex;
use function random_bytes;
use function range;

final class LookupBench
{
    private Flexihash $hasher;

    /** @var list<string> */
    private array $radomKeys = [];

    public function __construct()
    {
        $this->hasher = new Flexihash(new Md5Hasher(), 64);
        foreach (range(1, 10) as $i) {
            $this->hasher->addTarget('target_' . $i, 1);
        }

        // pre-generate random lookup keys outside of measurement function
        foreach (range(1, 80000) as $i) {
            $this->radomKeys[] = bin2hex(random_bytes(12));
        }
    }

    /** @param array{count: int} $params */
    #[ParamProviders(['provideLookupCount'])]
    public function benchLookup(array $params): void
    {
        foreach (range(0, $params['count'] - 1) as $i) {
            $this->hasher->lookup($this->radomKeys[$i]);
        }
    }

    public function provideLookupCount(): Generator
    {
        yield '10000 lookups' => ['count' => 10000];
        yield '20000 lookups' => ['count' => 20000];
        yield '40000 lookups' => ['count' => 40000];
        yield '80000 lookups' => ['count' => 80000];
    }
}
