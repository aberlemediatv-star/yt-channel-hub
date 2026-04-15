<?php

declare(strict_types=1);

namespace YtHub\Tests;

use YtHub\StaffModule;
use PHPUnit\Framework\TestCase;

final class StaffModuleTest extends TestCase
{
    public function testMergeDefaults(): void
    {
        $m = StaffModule::mergeDefaults(['upload' => false, 'edit_video' => true]);
        $this->assertFalse($m['upload']);
        $this->assertTrue($m['edit_video']);
        $this->assertFalse($m['view_revenue']);
    }
}
