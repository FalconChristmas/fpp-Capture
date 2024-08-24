<?php
// Enable error reporting for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Assume session is already started by Falcon Player

// Include necessary plugin files
include_once 'functions.inc.php';

$fileName = isset($_POST['fileName']) ? $_POST['fileName'] : 'capture_' . date('Ymd_His') . '.fseq';
$message = '';

// Check the current recording status
$statusData = capturePlugin_getCaptureStatus();
$isRecording = $statusData['recording'];
$currentFile = $statusData['file'];
$fileSize = $statusData['size'] / 1024;  // Convert bytes to kilobytes
$startTime = isset($_SESSION['startTime']) ? $_SESSION['startTime'] : 0;

// Debugging output: Log session and recording status
error_log("Session Start Time: " . $startTime);
error_log("Recording Status: " . ($isRecording ? 'Recording' : 'Not Recording'));

// Calculate duration only if start time is valid
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
            $_SESSION['startTime'] = time();  // Set start time
            $message = "Recording started. File: $fileName";
            error_log("Recording started, Start Time Set: " . $_SESSION['startTime']);
        } else {
            $message = "Failed to start recording.";
            error_log("Failed to start recording.");
        }
    } elseif (isset($_POST['stop'])) {
        if (capturePlugin_stopCapture()) {
            $message = "Recording stopped.";
            unset($_SESSION['startTime']);  // Clear start time
            error_log("Recording stopped, Start Time Cleared.");
        } else {
            $message = "Failed to stop recording.";
            error_log("Failed to stop recording.");
        }
    }

    // Refresh the status after any actions
    $statusData = capturePlugin_getCaptureStatus();
    $isRecording = $statusData['recording'];
    $currentFile = $statusData['file'];
    $fileSize = $statusData['size'] / 1024;  // Convert bytes to kilobytes

    // Recalculate duration
    $startTime = isset($_SESSION['startTime']) ? $_SESSION['startTime'] : 0;
    $duration = ($isRecording && $startTime > 0) ? (time() - $startTime) : 0;

    // Convert duration to hours, minutes, and seconds
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    $seconds = $duration % 60;

    // Debugging output: Log recalculated duration
    error_log("Recalculated Duration: " . $duration . " seconds");
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
