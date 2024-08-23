<?php

function sendRestCommand($command, $args = []) {
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

function startCapture($fileName) {
    // Send the FSEQ Capture Start command with the file name as an argument
    return sendRestCommand("FSEQ Capture Start", [$fileName]);
}

function stopCapture() {
    // Send the FSEQ Capture Stop command (no arguments required)
    return sendRestCommand("FSEQ Capture Stop");
}

function getCaptureStatus() {
    // Check status using an appropriate API endpoint if available
    // Placeholder: Here you would call the appropriate endpoint to get the actual recording status
    // For example, if FPP exposes a status endpoint like /api/status
    $url = "http://localhost/api/status";  // Adjust the URL to the actual status endpoint

    // Initialize cURL session
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the request and get the response
    $response = curl_exec($ch);

    // Close the cURL session
    curl_close($ch);

    // Parse the response and determine if recording is active
    $data = json_decode($response, true);
    
    // Adjust this based on the actual structure of the response from the status endpoint
    return isset($data['recording']) && $data['recording'] === true;
}
?>
