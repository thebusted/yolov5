<?php
require_once __DIR__ . '/../../_inc_/config.php';

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

$uid = $_POST['uid'] ?? '';

// Save the uploaded image to the server
$cattle = $_FILES['cattle'];
$uploadDir = __DIR__ . '/../../uploads/';

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
foreach ($cattle['tmp_name'] as $index => $tmpName) {
    $uploadedImages[] = $taskDir . $cattle['name'][$index];
    move_uploaded_file($tmpName, $taskDir . $cattle['name'][$index]);
}

// Call internal service to classify the images at localhost:8000 using GET method
$service = 'http://localhost:8000/detect/cattle-muzzle/' . $task . '?bucket=' . urlencode($taskDir);

$auto_delete = true;

// Initialize cURL
$ch = curl_init($service);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

// Execute cURL
$result = curl_exec($ch);

// Close curl
curl_close($ch);

$result = json_decode($result, true);
if (json_last_error() === JSON_ERROR_NONE) {
    if (!empty($result)) {
        $muzzle = $result['result'][0];

        // Crop image and save it
        // Example payload: [415, 1730, 1988, 2791, 0.8867025971412659, 0]
        // Description: [xmin, ymin, xmax, ymax, confidence, class]

        // Check image type and create image by type
        $image = null;
        $imagePath = $muzzle['file'];
        $imageType = exif_imagetype($imagePath);
        $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $ext = 'jpg';
                $image = imagecreatefromjpeg($muzzle['file']);

                // Read the EXIF metadata
                $exif = exif_read_data($imagePath);

                // Check if the orientation data exists
                if (!empty($exif['Orientation'])) {
                    // Rotate the image based on the orientation
                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }
                }
                break;
            case IMAGETYPE_PNG:
                $ext = 'png';
                $image = imagecreatefrompng($muzzle['file']);
                break;
            case IMAGETYPE_GIF:
                $ext = 'gif';
                $image = imagecreatefromgif($muzzle['file']);
                break;
            default:
                break;
        }

        // Crop image
        if ($image) {
            $boundary = $muzzle['payload'][0];
            $croppedImage = imagecrop($image, [
                'x' => $boundary[0],
                'y' => $boundary[1],
                'width' => $boundary[2] - $boundary[0],
                'height' => $boundary[3] - $boundary[1]
            ]);

            // Save cropped image
            if ($croppedImage) {
                $croppedImageFile = $taskDir . 'muzzle.' . $ext;
                imagejpeg($croppedImage, $croppedImageFile);
            }

            $originalImageFile = $taskDir . 'original.' . $ext;
            imagejpeg($image, $originalImageFile);

            imagedestroy($image);
            imagedestroy($croppedImage);

            // Initialize cURL
            $service_identify = 'http://localhost:8000/identify/' . $uid . '/' . $task . '?file=' . urlencode($croppedImageFile);
            error_log("Service Identify: " . $service_identify . "\n");

            $ch = curl_init($service_identify);

            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            // Execute cURL
            $identify = curl_exec($ch);
            $identify = json_decode($identify, true);

            // Close curl
            curl_close($ch);

            error_log("Identify: " . print_r($identify, true) . "\n");

            $result['identify'] = $identify['result'];

            $_SESSION['cattle_register'] = [
                'uid' => $uid,
                'task' => $task,
                'ext' => $ext,
                'taskDir' => $taskDir,
                'file' => $croppedImageFile
            ];
        }
    }
}

if ($auto_delete) {
//    // Delete the task directory
//    foreach ($uploadedImages as $uploadedImage) {
//        unlink($uploadedImage);
//    }
//    rmdir($taskDir);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);