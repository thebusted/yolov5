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
$service = 'http://localhost:8000/predict/' . $task;

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
//echo '{"task_id":"66389475b9136","result":[{"file":"/mnt/volume_sgp1_02/aiml/public/freerolls/uploads/66389475b9136/483035184095297657.jpg","payload":[[257.0293273925781,85.07670593261719,479.4988098144531,340.343994140625,0.8276516199111938,2],[53.465003967285156,42.57720184326172,271.3375549316406,255.58447265625,0.7433387041091919,2]]}],"inference":3776.2367725372314}';