<?php

declare(strict_types=1);

namespace YtHub\Tests;

use YtHub\InstallHelper;
use PHPUnit\Framework\TestCase;

final class InstallHelperTest extends TestCase
{
    public function testParseSqlFileSplitsStatements(): void
    {
        $tmp = sys_get_temp_dir() . '/yt_test_' . uniqid('', true) . '.sql';
        file_put_contents($tmp, "-- c\nCREATE TABLE a (id INT);\n\nCREATE TABLE b (id INT);\n");

        $stmts = InstallHelper::parseSqlFile($tmp);
        unlink($tmp);

        $this->assertCount(2, $stmts);
        $this->assertStringContainsString('CREATE TABLE a', $stmts[0]);
        $this->assertStringContainsString('CREATE TABLE b', $stmts[1]);
    }
}
