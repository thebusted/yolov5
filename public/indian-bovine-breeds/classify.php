<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timeout for 1 minute
set_time_limit(60);

define('MOCKUP', false);
if (MOCKUP) {
    // Mockup response
    header('Content-Type: application/json');
    echo '{"task_id":"663a008319951","result":[{"file":"/mnt/volume_sgp1_02/aiml/public/cattle/uploads/663a008319951/muzzle.jpg","payload":[[692,686,984,888,0.8549198508262634,0]]}],"inference":1578.5677433013916}';
    exit(0);
}

// Save the uploaded image to the server
$images = $_FILES['images'];
$uploadDir = __DIR__ . '/uploads/';

// Create the upload directory if it does not exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Create unique name for task
$task = uniqid();

// Create the task directory
$taskDir = $uploadDir . $task . '/';
mkdir($taskDir, 0777, true);

// Move the uploaded images to the task directory
$uploadedImages = [];
foreach ($images['tmp_name'] as $index => $tmpName) {
    $uploadedImages[] = $taskDir . $images['name'][$index];
    move_uploaded_file($tmpName, $taskDir . $images['name'][$index]);
}

// Call internal service to classify the images at localhost:8000 using GET method
$service = 'http://localhost:8000/v8/detect/indian-bovine-breeds/' . $task . '?bucket=' . urlencode($taskDir);

// Initialize cURL
$ch = curl_init($service);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

// Execute cURL
$result = curl_exec($ch);

// Delete the task directory
foreach ($uploadedImages as $uploadedImage) {
    unlink($uploadedImage);
}
rmdir($taskDir);

// Return JSON response
header('Content-Type: application/json');
echo $result;