<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timeout for 1 minute
set_time_limit(60);

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

// Sleep for 5 seconds to simulate the classification process
sleep(10);

// Call internal service to classify the images at localhost:8000 using GET method
$result = file_get_contents('http://localhost:8000/predict/' . $task);

// Delete the task directory
foreach ($uploadedImages as $uploadedImage) {
    unlink($uploadedImage);
}
rmdir($taskDir);

// Return JSON response
header('Content-Type: application/json');
echo $result;