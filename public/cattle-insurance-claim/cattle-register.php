<?php
require_once __DIR__ . '/_inc_/config.php';

if (isset($_SESSION['cattle_register'])) {
    $register = $_SESSION['cattle_register'];

    if (is_dir($register['taskDir'])) {
        $uid = $register['uid'];
        $muzzle = $register['file'];
        $task = $register['task'];

        // Initialize cURL
        $service_register = 'http://localhost:8000/register/' . $uid . '/' . $task . '?file=' . urlencode($muzzle);
        error_log("Service register: " . $service_register . "\n");

        $ch = curl_init($service_register);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        // Execute cURL
        $register = curl_exec($ch);
        $register = json_decode($register, true);

        // Close curl
        curl_close($ch);

//        // Delete all files and folder in task directory
//        $taskDir = $register['taskDir'];
//        $files = glob($taskDir . '*', GLOB_MARK);
//        foreach ($files as $file) {
//            if (is_dir($file)) {
//                $files = glob($file . '*', GLOB_MARK);
//                foreach ($files as $f) {
//                    unlink($f);
//                }
//                rmdir($file);
//            } else {
//                unlink($file);
//            }
//        }
    }

    unset($_SESSION['cattle_register']);
}

header('Location: ./');