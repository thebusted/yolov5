<?php
use Kreait\Firebase\Factory;

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Store debug log
ini_set('error_log', __DIR__ . '/error.log');

require_once __DIR__ . '/../vendor/autoload.php';

if (isset($_SESSION['expiresIn']) && time() > $_SESSION['expiresIn']) {
    $factory = (new Factory)->withServiceAccount(__DIR__ . '/aiml-cattle-insurance-firebase-adminsdk-et09n-60c76cf2a5.json');

    $auth = $factory->createAuth();

    $refresh = $auth->signInWithRefreshToken($_SESSION['refreshToken']);

    $customToken = $auth->createCustomToken($data['localId'] ?? '');

    $_SESSION['idToken'] = $refresh->idToken();
    $_SESSION['refreshToken'] = $refresh->refreshToken();
    $_SESSION['expiresIn'] = time() + 3600;
    $_SESSION['customToken'] = $customToken->toString();
}