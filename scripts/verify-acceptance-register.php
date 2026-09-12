<?php

declare(strict_types=1);

// Rehla Acceptance Register Verifier
// Ensures every requirement in docs/requirements/rehla-phase-1-acceptance.csv has verified evidence or valid deferral reason.

$csvPath = __DIR__.'/../docs/requirements/rehla-phase-1-acceptance.csv';
if (! file_exists($csvPath)) {
    fwrite(STDERR, "Error: Acceptance register CSV not found at {$csvPath}\n");
    exit(1);
}

$handle = fopen($csvPath, 'r');
if (! $handle) {
    fwrite(STDERR, "Error: Unable to open CSV at {$csvPath}\n");
    exit(1);
}

$header = fgetcsv($handle);
if (! $header) {
    fwrite(STDERR, "Error: Empty CSV header\n");
    exit(1);
}

$headerMap = array_flip($header);
$requiredColumns = ['acceptance_id', 'source_requirement', 'status', 'test_file', 'test_name', 'evidence', 'deferred_reason'];
foreach ($requiredColumns as $col) {
    if (! isset($headerMap[$col])) {
        fwrite(STDERR, "Error: Missing required column '{$col}' in CSV\n");
        exit(1);
    }
}

$errors = [];
$stats = [
    'total' => 0,
    'verified' => 0,
    'passed' => 0,
    'deferred' => 0,
    'planned' => 0,
    'failed' => 0,
];

$rowNum = 1;
while (($row = fgetcsv($handle)) !== false) {
    $rowNum++;
    $stats['total']++;
    $id = trim($row[$headerMap['acceptance_id']] ?? '');
    $status = trim($row[$headerMap['status']] ?? '');
    $testFile = trim($row[$headerMap['test_file']] ?? '');
    $testName = trim($row[$headerMap['test_name']] ?? '');
    $evidence = trim($row[$headerMap['evidence']] ?? '');
    $deferredReason = trim($row[$headerMap['deferred_reason']] ?? '');

    if (isset($stats[$status])) {
        $stats[$status]++;
    }

    if ($status === 'verified') {
        if ($evidence === '') {
            $errors[] = "Row {$rowNum} [{$id}]: status is 'verified' but 'evidence' is empty.";
        }
        if ($testFile === '') {
            $errors[] = "Row {$rowNum} [{$id}]: status is 'verified' but 'test_file' is empty.";
        }
        if ($testName === '') {
            $errors[] = "Row {$rowNum} [{$id}]: status is 'verified' but 'test_name' is empty.";
        }
    } elseif ($status === 'deferred') {
        if ($deferredReason === '') {
            $errors[] = "Row {$rowNum} [{$id}]: status is 'deferred' but 'deferred_reason' is empty.";
        }
    } elseif ($status === 'passed') {
        $errors[] = "Row {$rowNum} [{$id}]: status is 'passed' but not yet verified with release evidence command.";
    } elseif (in_array($status, ['planned', 'red', 'green', 'failed'], true)) {
        $errors[] = "Row {$rowNum} [{$id}]: requirement is in state '{$status}' and is not release-ready.";
    } else {
        $errors[] = "Row {$rowNum} [{$id}]: unknown status '{$status}'.";
    }
}
fclose($handle);

echo "=== Rehla Phase 1 Acceptance Register Summary ===\n";
echo "Total Rows: {$stats['total']}\n";
echo "Verified:   {$stats['verified']}\n";
echo "Deferred:   {$stats['deferred']}\n";
echo "Passed:     {$stats['passed']}\n";
echo "Planned:    {$stats['planned']}\n";

if (! empty($errors)) {
    fwrite(STDERR, "\nFAILED: Acceptance register contains ".count($errors)." unverified or invalid rows:\n");
    foreach (array_slice($errors, 0, 15) as $err) {
        fwrite(STDERR, "  - {$err}\n");
    }
    if (count($errors) > 15) {
        fwrite(STDERR, '  ... and '.(count($errors) - 15)." more errors.\n");
    }
    exit(1);
}

echo "\nSUCCESS: All {$stats['total']} requirements in the acceptance register are fully verified or properly deferred.\n";
exit(0);
