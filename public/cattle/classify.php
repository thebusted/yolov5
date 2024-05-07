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

// Call internal service to classify the images at localhost:8000 using GET method
$service = 'http://localhost:8000/cattle/' . $task;

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

//// Mockup response
//header('Content-Type: application/json');
//echo '{"task_id":"6638a55cf12f2","result":[{"file":"/mnt/volume_sgp1_02/aiml/public/freerolls/uploads/6638a55cf12f2/483035184095297657.jpg","payload":[[593,196,1107,785,0.8276516199111938,2],[123,98,626,590,0.7433387041091919,2]]}],"inference":1159.2860221862793}';