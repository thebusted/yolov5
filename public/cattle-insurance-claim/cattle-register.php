<?php
use Kreait\Firebase\Auth\SignIn\FailedToSignIn;
use Kreait\Firebase\Factory;

require_once __DIR__ . '/_inc_/config.php';

$factory = (new Factory)->withServiceAccount(__DIR__ . '/_inc_/aiml-cattle-insurance-firebase-adminsdk-et09n-60c76cf2a5.json');

$database = $factory->withDatabaseUri('https://aiml-cattle-insurance-default-rtdb.asia-southeast1.firebasedatabase.app')->createDatabase();

if (isset($_SESSION['cattle_register'])) {
    $register = $_SESSION['cattle_register'];

    $taskDir = $register['taskDir'];
    if (is_dir($taskDir)) {
        $uid = $register['uid'];
        $muzzle = $register['file'];
        $task = $register['task'];
        $ext = $register['ext'];

        // Create unique name for task
        $cid = uniqid();

        // Initialize cURL
        $service_register = 'http://localhost:8000/register/' . $uid . '/' . $task . '?file=' . urlencode($muzzle) . '&cid=' . $cid;
        error_log("Service register: " . $service_register . "\n");

        $ch = curl_init($service_register);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        // Execute cURL
        $register = curl_exec($ch);
        $register = json_decode($register, true);

        error_log("Register: " . print_r($register, true) . "\n");

        // Close curl
        curl_close($ch);

        $_SESSION['message'] = 'Cattle registered successfully and has ID "' . $cid . '"';

        $database->getReference('users/' . $uid . '/cattle/' . $cid)->set([
            'task' => $task,
            'ext' => $ext
        ]);

        // Create cattle directory
        $cattleDir = __DIR__ . '/cattle/' . $uid . '/' . $cid . '/';
        if (!is_dir($cattleDir)) {
            mkdir($cattleDir, 0755, true);
        }

        // Delete all files in the task directory
        $files = glob($taskDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                // If file equals origin.$ext and muzzle.$ext then move it to cattle directory
                $filename = basename($file);
                if ($filename === 'original.' . $ext || $filename === 'muzzle.' . $ext) {
                    rename($file, $cattleDir . $filename);
                } else {
                    unlink($file);
                }
            }
        }

        // Delete the task directory
        rmdir($taskDir);
    }

    unset($_SESSION['cattle_register']);
}

header('Location: ./');