<?php

declare(strict_types=1);

namespace Flexihash\Tests;

use Flexihash\Exception;
use Flexihash\Flexihash;
use Flexihash\Tests\Hasher\MockHasher;
use PHPUnit\Framework\TestCase;

use function count;
use function in_array;
use function range;
use function sprintf;

/**
 * @author Paul Annesley
 * @license http://www.opensource.org/licenses/mit-license.php
 */
class FlexihashTest extends TestCase
{
    public function testGetAllTargetsEmpty(): void
    {
        $hashSpace = new Flexihash();
        $this->assertEquals($hashSpace->getAllTargets(), []);
    }

    public function testAddTargetThrowsExceptionOnDuplicateTarget(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('t-a');
        $this->expectException(Exception::class);
        $hashSpace->addTarget('t-a');
    }

    public function testAddTargetAndGetAllTargets(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('t-a');
        $hashSpace->addTarget('t-b');
          $hashSpace->addTarget('t-c');

        $this->assertEquals($hashSpace->getAllTargets(), ['t-a', 't-b', 't-c']);
    }

    public function testAddTargetsAndGetAllTargets(): void
    {
        $targets = ['t-a', 't-b', 't-c'];

        $hashSpace = new Flexihash();
        $hashSpace->addTargets($targets);
        $this->assertEquals($hashSpace->getAllTargets(), $targets);
    }

    public function testRemoveTarget(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('t-a');
        $hashSpace->addTarget('t-b');
        $hashSpace->addTarget('t-c');
        $hashSpace->removeTarget('t-b');
        $this->assertEquals($hashSpace->getAllTargets(), ['t-a', 't-c']);
    }

    public function testRemoveTargetFailsOnMissingTarget(): void
    {
        $hashSpace = new Flexihash();
        $this->expectException(Exception::class);
        $hashSpace->removeTarget('not-there');
    }

    public function testHashSpaceRepeatableLookups(): void
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget(sprintf('target%s', $i));
        }

        $this->assertEquals($hashSpace->lookup('t1'), $hashSpace->lookup('t1'));
        $this->assertEquals($hashSpace->lookup('t2'), $hashSpace->lookup('t2'));
    }

    public function testHashSpaceLookupListEmpty(): void
    {
        $hashSpace = new Flexihash();
        $this->assertEmpty($hashSpace->lookupList('t1', 2));
    }

    public function testHashSpaceLookupListNoTargets(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No targets exist');
        $hashSpace = new Flexihash();
        $hashSpace->lookup('t1');
    }

    public function testHashSpaceLookupsAreValidTargets(): void
    {
        $targets = [];
        foreach (range(1, 10) as $i) {
            $targets[] = sprintf('target%s', $i);
        }

        $hashSpace = new Flexihash();
        $hashSpace->addTargets($targets);

        foreach (range(1, 10) as $i) {
            $this->assertTrue(
                in_array($hashSpace->lookup(sprintf('r%s', $i)), $targets),
                'target must be in list of targets',
            );
        }
    }

    public function testHashSpaceConsistentLookupsAfterAddingAndRemoving(): void
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget(sprintf('target%s', $i));
        }

        $results1 = [];
        foreach (range(1, 100) as $i) {
            $results1[] = $hashSpace->lookup(sprintf('t%s', $i));
        }

        $hashSpace->addTarget('new-target');
        $hashSpace->removeTarget('new-target');
        $hashSpace->addTarget('new-target');
        $hashSpace->removeTarget('new-target');

        $results2 = [];
        foreach (range(1, 100) as $i) {
            $results2[] = $hashSpace->lookup(sprintf('t%s', $i));
        }

        // This is probably optimistic, as adding/removing a target may
        // clobber existing targets and is not expected to restore them.
        $this->assertEquals($results1, $results2);
    }

    public function testHashSpaceConsistentLookupsWithNewInstance(): void
    {
        $hashSpace1 = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace1->addTarget(sprintf('target%s', $i));
        }

        $results1 = [];
        foreach (range(1, 100) as $i) {
            $results1[] = $hashSpace1->lookup(sprintf('t%s', $i));
        }

        $hashSpace2 = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace2->addTarget(sprintf('target%s', $i));
        }

        $results2 = [];
        foreach (range(1, 100) as $i) {
            $results2[] = $hashSpace2->lookup(sprintf('t%s', $i));
        }

        $this->assertEquals($results1, $results2);
    }

    public function testGetMultipleTargets(): void
    {
        $hashSpace = new Flexihash();
        foreach (range(1, 10) as $i) {
            $hashSpace->addTarget(sprintf('target%s', $i));
        }

        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertIsArray($targets);
        $this->assertEquals(count($targets), 2);
        $this->assertNotEquals($targets[0], $targets[1]);
    }

    public function testGetMultipleTargetsWithOnlyOneTarget(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('single-target');

        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertIsArray($targets);
        $this->assertEquals(count($targets), 1);
        $this->assertEquals($targets[0], 'single-target');
    }

    public function testGetMoreTargetsThanExist(): void
    {
        $hashSpace = new Flexihash();
        $hashSpace->addTarget('target1');
        $hashSpace->addTarget('target2');

        $targets = $hashSpace->lookupList('resource', 4);

        $this->assertIsArray($targets);
        $this->assertEquals(count($targets), 2);
        $this->assertNotEquals($targets[0], $targets[1]);
    }

    public function testGetMultipleTargetsNeedingToLoopToStart(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace  = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $hashSpace->addTarget('t1');

        $mockHasher->setHashValue(20);
        $hashSpace->addTarget('t2');

        $mockHasher->setHashValue(30);
        $hashSpace->addTarget('t3');

        $mockHasher->setHashValue(40);
        $hashSpace->addTarget('t4');

        $mockHasher->setHashValue(50);
        $hashSpace->addTarget('t5');

        $mockHasher->setHashValue(35);
        $targets = $hashSpace->lookupList('resource', 4);

        $this->assertEquals($targets, ['t4', 't5', 't1', 't2']);
    }

    public function testGetMultipleTargetsWithoutGettingAnyBeforeLoopToStart(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace  = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $hashSpace->addTarget('t1');

        $mockHasher->setHashValue(20);
        $hashSpace->addTarget('t2');

        $mockHasher->setHashValue(30);
        $hashSpace->addTarget('t3');

        $mockHasher->setHashValue(100);
        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertEquals($targets, ['t1', 't2']);
    }

    public function testGetMultipleTargetsWithoutNeedingToLoopToStart(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace  = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $hashSpace->addTarget('t1');

        $mockHasher->setHashValue(20);
        $hashSpace->addTarget('t2');

        $mockHasher->setHashValue(30);
        $hashSpace->addTarget('t3');

        $mockHasher->setHashValue(15);
        $targets = $hashSpace->lookupList('resource', 2);

        $this->assertEquals($targets, ['t2', 't3']);
    }

    public function testFallbackPrecedenceWhenServerRemoved(): void
    {
        $mockHasher = new MockHasher();
        $hashSpace  = new Flexihash($mockHasher, 1);

        $mockHasher->setHashValue(10);
        $hashSpace->addTarget('t1');

        $mockHasher->setHashValue(20);
        $hashSpace->addTarget('t2');

        $mockHasher->setHashValue(30);
        $hashSpace->addTarget('t3');

        $mockHasher->setHashValue(15);

        $this->assertEquals($hashSpace->lookup('resource'), 't2');
        $this->assertEquals(
            $hashSpace->lookupList('resource', 3),
            ['t2', 't3', 't1'],
        );

        $hashSpace->removeTarget('t2');

        $this->assertEquals($hashSpace->lookup('resource'), 't3');
        $this->assertEquals(
            $hashSpace->lookupList('resource', 3),
            ['t3', 't1'],
        );

        $hashSpace->removeTarget('t3');

        $this->assertEquals($hashSpace->lookup('resource'), 't1');
        $this->assertEquals(
            $hashSpace->lookupList('resource', 3),
            ['t1'],
        );
    }
}
