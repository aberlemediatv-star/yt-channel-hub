<?php

declare(strict_types=1);

namespace YtHub\Tests;

use PHPUnit\Framework\TestCase;
use YtHub\AnalyticsExportService;

final class AnalyticsExportServiceFormatTest extends TestCase
{
    public function testFilenameSuffixMapsFormats(): void
    {
        $this->assertSame('excel', AnalyticsExportService::filenameSuffix(AnalyticsExportService::FORMAT_EXCEL));
        $this->assertSame('sap', AnalyticsExportService::filenameSuffix(AnalyticsExportService::FORMAT_SAP));
        $this->assertSame('json', AnalyticsExportService::filenameSuffix(AnalyticsExportService::FORMAT_JSON));
    }

    public function testJsonFormatConstant(): void
    {
        $this->assertSame('json', AnalyticsExportService::FORMAT_JSON);
    }

    public function testToCsvProducesUtf8BomForExcel(): void
    {
        $svc = new AnalyticsExportService;
        $rows = [];
        $csv = $svc->toCsv(AnalyticsExportService::FORMAT_EXCEL, $rows);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('channel_id', $csv);
    }
}
