<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include necessary plugin files
include_once 'functions.inc.php';

$metaFile = '/home/fpp/media/sequences/recording_meta.json';  // Metadata file to store start time

// Check the current recording status
$statusData = capturePlugin_getCaptureStatus();
$isRecording = $statusData['recording'];
$currentFile = $statusData['file'];
$fileSize = $statusData['size'] / 1024;  // Convert bytes to kilobytes

// Read the metadata file to get the start time
$startTime = 0;
if (file_exists($metaFile)) {
    $metaData = json_decode(file_get_contents($metaFile), true);
    $startTime = isset($metaData['startTime']) ? $metaData['startTime'] : 0;
}

// Calculate duration based on the stored start time
$duration = ($isRecording && $startTime > 0) ? (time() - $startTime) : 0;

// Convert duration to hours, minutes, and seconds
$hours = floor($duration / 3600);
$minutes = floor(($duration % 3600) / 60);
$seconds = $duration % 60;

// Ensure $currentFile is not null before processing
$currentFile = $currentFile ?? '';

echo json_encode([
    'isRecording' => $isRecording,
    'duration' => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
    'fileSize' => number_format($fileSize, 2),
    'file' => htmlspecialchars($currentFile)
]);
?>
