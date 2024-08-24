<?php

// Function to send REST commands to the FPP API
function capturePlugin_sendRestCommand($command, $args = []) {
    $url = "http://localhost/api/command";  // Update with the correct FPP API URL if different

    // Create the JSON payload
    $data = array(
        "command" => $command,
        "args" => $args
    );
    $jsonData = json_encode($data);

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData))
    );

    // Execute the request and get the response
    $response = curl_exec($ch);

    // Get the HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close the cURL session
    curl_close($ch);

    // Return success if the status code is 200 (OK)
    return $httpCode === 200;
}

// Function to start the FSEQ capture recording
function capturePlugin_startCapture($fileName) {
    // Send the FSEQ Capture Start command with the file name as an argument
    return capturePlugin_sendRestCommand("FSEQ Capture Start", [$fileName]);
}

// Function to stop the FSEQ capture recording
function capturePlugin_stopCapture() {
    // Send the FSEQ Capture Stop command (no arguments required)
    return capturePlugin_sendRestCommand("FSEQ Capture Stop");
}

// Function to check if recording is active by monitoring the .fseq.capture file
function capturePlugin_getCaptureStatus() {
    $directory = "/home/fpp/media/sequences";
    $filePattern = "*.fseq.capture";

    // Find any file that ends with .fseq.capture
    $files = glob($directory . "/" . $filePattern);

    if (!empty($files)) {
        $file = $files[0];  // Assuming only one capture file at a time
        $fileSize = filesize($file);

        // Return the file's existence and its size for monitoring
        return [
            'recording' => true,
            'file' => $file,
            'size' => $fileSize
        ];
    }

    return [
        'recording' => false,
        'file' => null,
        'size' => 0
    ];
}
?>
