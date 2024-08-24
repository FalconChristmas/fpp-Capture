<?php
// Include necessary plugin files
include_once 'functions.inc.php';

$metaFile = '/home/fpp/media/sequences/recording_meta.json';  // Metadata file to store start time
$fileName = isset($_POST['fileName']) ? $_POST['fileName'] : 'capture_' . date('Ymd_His') . '.fseq';
$message = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start'])) {
        if (substr($fileName, -5) !== '.fseq') {
            $fileName .= '.fseq';
        }
        if (capturePlugin_startCapture($fileName)) {
            // Store the start time in the metadata file
            file_put_contents($metaFile, json_encode(['startTime' => time()]));
            $message = "Recording started. File: $fileName";
        } else {
            $message = "Failed to start recording.";
        }
    } elseif (isset($_POST['stop'])) {
        if (capturePlugin_stopCapture()) {
            $message = "Recording stopped.";
            // Remove the metadata file when recording stops
            if (file_exists($metaFile)) {
                unlink($metaFile);
            }
        } else {
            $message = "Failed to stop recording.";
        }
    }

    // Refresh the status after any actions
    $statusData = capturePlugin_getCaptureStatus();
    $isRecording = $statusData['recording'];
    $currentFile = $statusData['file'];
    $fileSize = $statusData['size'] / 1024;  // Convert bytes to kilobytes

    // Recalculate duration based on the stored start time
    if (file_exists($metaFile)) {
        $metaData = json_decode(file_get_contents($metaFile), true);
        $startTime = isset($metaData['startTime']) ? $metaData['startTime'] : 0;
    }
    $duration = ($isRecording && $startTime > 0) ? (time() - $startTime) : 0;

    // Convert duration to hours, minutes, and seconds
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>FPP Capture Plugin</title>
</head>
<body>
<h2>FPP Capture Plugin</h2>
<p><?php echo $message; ?></p>
<p>Current Status: <strong><?php echo $isRecording ? 'Recording' : 'Not Recording'; ?></strong></p>

<?php if ($isRecording): ?>
    <p>Recording Duration: <?php echo sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds); ?></p>
    <p>Recording File: <?php echo htmlspecialchars($currentFile); ?></p>
    <p>File Size: <?php echo number_format($fileSize, 2); ?> KB</p>
<?php endif; ?>

<form method="POST">
    <?php if (!$isRecording): // Hide the file name entry box if recording is active ?>
        <label for="fileName">File Name:</label>
        <input type="text" id="fileName" name="fileName" value="<?php echo htmlspecialchars($fileName); ?>">
        <br><br>
    <?php endif; ?>
    <button type="submit" name="<?php echo $isRecording ? 'stop' : 'start'; ?>">
        <?php echo $isRecording ? 'Stop Recording' : 'Start Recording'; ?>
    </button>
</form>
</body>
</html>
