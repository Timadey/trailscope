<?php

namespace Trail\Tests\Unit;

use Trail\Tests\TestCase;
use Trail\TrailManager;

class TrailManagerTest extends TestCase
{
    public function test_it_records_steps_on_active_trace(): void
    {
        $manager = app(TrailManager::class);

        $trace = $manager->start(['method' => 'POST', 'path' => '/transfer']);
        $manager->step('checking wallet', 5000);

        $this->assertSame($trace, $manager->current());
        $this->assertCount(1, $trace->steps);
        $this->assertSame('checking wallet', $trace->steps[0]['message']);
        $this->assertSame(['value_1' => 5000], $trace->steps[0]['context']);
    }

    public function test_step_without_active_trace_is_ignored(): void
    {
        $manager = app(TrailManager::class);

        $manager->step('outside request');

        $this->assertNull($manager->current());
    }
}
