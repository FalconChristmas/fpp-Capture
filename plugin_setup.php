<?php
// Include the necessary FPP libraries
include_once '/opt/fpp/www/common.php';
include_once 'functions.inc.php';

// Initialize variables
$status = getCaptureStatus(); // Function to check if recording is active
$startTime = isset($_SESSION['startTime']) ? $_SESSION['startTime'] : 0;
$duration = $status ? time() - $startTime : 0;  // Calculate duration

$fileName = isset($_POST['fileName']) ? $_POST['fileName'] : 'capture_' . date('Ymd_His') . '.fseq';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start'])) {
        // Ensure the file name ends with .fseq
        if (substr($fileName, -5) !== '.fseq') {
            $fileName .= '.fseq';
        }
        if (startCapture($fileName)) {
            $_SESSION['startTime'] = time();  // Store the start time in session
            $message = "Recording started. File: $fileName";
        } else {
            $message = "Failed to start recording.";
        }
    } elseif (isset($_POST['stop'])) {
        if (stopCapture()) {
            $message = "Recording stopped.";
            unset($_SESSION['startTime']);  // Clear the start time
        } else {
            $message = "Failed to stop recording.";
        }
    }
    // Refresh the status after action
    $status = getCaptureStatus();
    $duration = $status ? time() - $_SESSION['startTime'] : 0;
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
<p>Current Status: <strong><?php echo $status ? 'Recording' : 'Not Recording'; ?></strong></p>
<?php if ($status): ?>
    <p>Recording Duration: <?php echo gmdate("H:i:s", $duration); ?></p>
<?php endif; ?>

<form method="POST">
    <label for="fileName">File Name:</label>
    <input type="text" id="fileName" name="fileName" value="<?php echo htmlspecialchars($fileName); ?>">
    
    <br><br>
    <?php if ($status): ?>
        <button type="submit" name="stop">Stop Recording</button>
    <?php else: ?>
        <button type="submit" name="start">Start Recording</button>
    <?php endif; ?>
</form>
</body>
</html>
