#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Analytics-CSV-Backup (Cron): letzte N Tage → storage/backups/analytics/
 *
 * Umgebung: BACKUP_ANALYTICS_DAYS (Standard 30), BACKUP_RETENTION_FILES (Standard 40)
 */

require_once __DIR__.'/ythub_paths.php';
ythub_require_bootstrap();
$root = ythub_laravel_root();

use YtHub\AnalyticsExportService;
use YtHub\AppLogger;
use YtHub\Db;
$days = max(1, min(366, (int) (getenv('BACKUP_ANALYTICS_DAYS') ?: ($_ENV['BACKUP_ANALYTICS_DAYS'] ?? 30))));
$keep = max(5, min(500, (int) (getenv('BACKUP_RETENTION_FILES') ?: ($_ENV['BACKUP_RETENTION_FILES'] ?? 40))));

$today = (new \DateTimeImmutable('today'))->format('Y-m-d');
$start = (new \DateTimeImmutable($today))->modify('-' . $days . ' days')->format('Y-m-d');

$pdo = Db::pdo();
$svc = new AnalyticsExportService();
$rows = $svc->fetchDailyRows($pdo, $start, $today, null);

$dir = $root . '/storage/backups/analytics';
if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
    fwrite(STDERR, "Verzeichnis nicht anlegbar: {$dir}\n");
    exit(1);
}

$stamp = gmdate('Y-m-d_His');
$base = "analytics_backup_{$stamp}";
$excel = $dir . '/' . $base . '_excel.csv';
$sap = $dir . '/' . $base . '_sap.csv';

file_put_contents($excel, $svc->toCsv(AnalyticsExportService::FORMAT_EXCEL, $rows));
file_put_contents($sap, $svc->toCsv(AnalyticsExportService::FORMAT_SAP, $rows));
@chmod($excel, 0644);
@chmod($sap, 0644);

$log = AppLogger::get();
$log->info('Analytics-Backup geschrieben', [
    'excel' => $excel,
    'sap' => $sap,
    'rows' => count($rows),
    'from' => $start,
    'to' => $today,
]);

$excelFiles = glob($dir . '/analytics_backup_*_excel.csv') ?: [];
usort($excelFiles, static fn ($a, $b) => filemtime($a) <=> filemtime($b));
$toDelete = array_slice($excelFiles, 0, max(0, count($excelFiles) - $keep));
foreach ($toDelete as $f) {
    @unlink($f);
    $sapPair = str_replace('_excel.csv', '_sap.csv', $f);
    @unlink($sapPair);
    $log->notice('Altes Analytics-Backup gelöscht', ['excel' => $f]);
}

echo "OK: {$excel} + SAP-Variante, Zeilen: " . count($rows) . "\n";
exit(0);
