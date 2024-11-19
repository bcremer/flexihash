<?php

declare(strict_types=1);

namespace Flexihash\Tests;

use Flexihash\Flexihash;
use Flexihash\Hasher\Crc32Hasher;
use Flexihash\Hasher\Md5Hasher;
use PHPUnit\Framework\TestCase;

use function abs;
use function array_keys;
use function array_sum;
use function array_values;
use function count;
use function crc32;
use function floor;
use function max;
use function microtime;
use function min;
use function range;
use function round;
use function sort;
use function sprintf;

/**
 * Benchmarks, not really tests.
 *
 * @group benchmark
 * @author Paul Annesley
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class BenchmarkTest extends TestCase
{
    private int $targets = 10;
    private int $lookups = 1000;

    public function dump(string $message): void
    {
        echo $message . "\n";
    }

    public function testAddTargetWithNonConsistentHash(): void
    {
        $results1 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results1[$i] = $this->basicHash(sprintf('t%s', $i), 10);
        }

        $results2 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results2[$i] = $this->basicHash(sprintf('t%s', $i), 11);
        }

        $differences = 0;
        foreach (range(1, $this->lookups) as $i) {
            if ($results1[$i] === $results2[$i]) {
                continue;
            }

            ++$differences;
        }

        $percent = round($differences / $this->lookups * 100);

        $this->dump(sprintf('NonConsistentHash: %s%% of lookups changed after adding a target to the existing %d', $percent, $this->targets));
    }

    public function testRemoveTargetWithNonConsistentHash(): void
    {
        $results1 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results1[$i] = $this->basicHash(sprintf('t%s', $i), 10);
        }

        $results2 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results2[$i] = $this->basicHash(sprintf('t%s', $i), 9);
        }

        $differences = 0;
        foreach (range(1, $this->lookups) as $i) {
            if ($results1[$i] === $results2[$i]) {
                continue;
            }

            ++$differences;
        }

        $percent = round($differences / $this->lookups * 100);

        $this->dump(sprintf('NonConsistentHash: %s%% of lookups changed after removing 1 of %d targets', $percent, $this->targets));
    }

    public function testHopeAddingTargetDoesNotChangeMuchWithCrc32Hasher(): void
    {
        $hashSpace = new Flexihash(
            new Crc32Hasher(),
        );
        foreach (range(1, $this->targets) as $i) {
            $hashSpace->addTarget(sprintf('target%s', $i));
        }

        $results1 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results1[$i] = $hashSpace->lookup(sprintf('t%s', $i));
        }

        $hashSpace->addTarget('target-new');

        $results2 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results2[$i] = $hashSpace->lookup(sprintf('t%s', $i));
        }

        $differences = 0;
        foreach (range(1, $this->lookups) as $i) {
            if ($results1[$i] === $results2[$i]) {
                continue;
            }

            ++$differences;
        }

        $percent = round($differences / $this->lookups * 100);

        $this->dump(sprintf('ConsistentHash: %s%% of lookups changed after adding a target to the existing %d', $percent, $this->targets));
    }

    public function testHopeRemovingTargetDoesNotChangeMuchWithCrc32Hasher(): void
    {
        $hashSpace = new Flexihash(
            new Crc32Hasher(),
        );
        foreach (range(1, $this->targets) as $i) {
            $hashSpace->addTarget(sprintf('target%s', $i));
        }

        $results1 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results1[$i] = $hashSpace->lookup(sprintf('t%s', $i));
        }

        $hashSpace->removeTarget('target1');

        $results2 = [];
        foreach (range(1, $this->lookups) as $i) {
            $results2[$i] = $hashSpace->lookup(sprintf('t%s', $i));
        }

        $differences = 0;
        foreach (range(1, $this->lookups) as $i) {
            if ($results1[$i] === $results2[$i]) {
                continue;
            }

            ++$differences;
        }

        $percent = round($differences / $this->lookups * 100);

        $this->dump(sprintf('ConsistentHash: %s%% of lookups changed after removing 1 of %d targets', $percent, $this->targets));
    }

    public function testHashDistributionWithCrc32Hasher(): void
    {
        $hashSpace = new Flexihash(
            new Crc32Hasher(),
        );

        foreach (range(1, $this->targets) as $i) {
            $hashSpace->addTarget(sprintf('target%s', $i));
        }

        $results = [];
        foreach (range(1, $this->lookups) as $i) {
            $results[$i] = $hashSpace->lookup(sprintf('t%s', $i));
        }

        $distribution = [];
        foreach ($hashSpace->getAllTargets() as $target) {
            $distribution[$target] = count(array_keys($results, $target));
        }

        $this->dump(sprintf(
            'Distribution of %d lookups per target (min/max/median/avg): %d/%d/%d/%d',
            $this->lookups / $this->targets,
            min($distribution),
            max($distribution),
            round($this->median($distribution)),
            round(array_sum($distribution) / count($distribution)),
        ));
    }

    public function testHasherSpeed(): void
    {
        $hashCount = 100000;

        $md5Hasher   = new Md5Hasher();
        $crc32Hasher = new Crc32Hasher();

        $start = microtime(true);
        for ($i = 0; $i < $hashCount; ++$i) {
            $md5Hasher->hash(sprintf('test%s', $i));
        }

        $timeMd5 = microtime(true) - $start;

        $start = microtime(true);
        for ($i = 0; $i < $hashCount; ++$i) {
            $crc32Hasher->hash(sprintf('test%s', $i));
        }

        $timeCrc32 = microtime(true) - $start;

        $this->dump(sprintf(
            'Hashers timed over %d hashes (MD5 / CRC32): %f / %f',
            $hashCount,
            $timeMd5,
            $timeCrc32,
        ));
    }

    private function basicHash(string $value, int $targets): int
    {
        return abs(crc32($value) % $targets);
    }

    /**
     * @param array<numeric> $values
     *
     * @return numeric
     */
    private function median(array $values): int
    {
        $values = array_values($values);
        sort($values);

        $count       = count($values);
        $middleFloor = floor($count / 2);

        if ($count % 2 === 1) {
            return $values[$middleFloor];
        }

        return (int) (($values[$middleFloor] + $values[$middleFloor + 1]) / 2);
    }
}
