<?php
use Kreait\Firebase\Factory;

require_once __DIR__ . '/_inc_/config.php';

date_default_timezone_set('Asia/Bangkok');

$debug = [
    'id_token' => $_SESSION['idToken'],
    'refresh_token' => $_SESSION['refreshToken'],
    'customToken' => $_SESSION['customToken'],
    'expires_in' => $_SESSION['expiresIn'],
    'expire_date_time' => date('Y-m-d H:i:s', $_SESSION['expiresIn']),
    'current_date_time' => date('Y-m-d H:i:s'),
];

//$_SESSION['expiresIn'] -= 3600;

//if (isset($_SESSION['expiresIn']) && time() > $_SESSION['expiresIn']) {
//    $debug['message'] = 'Token expired';
//    $factory = (new Factory)->withServiceAccount(__DIR__ . '/_inc_/aiml-cattle-insurance-firebase-adminsdk-et09n-60c76cf2a5.json');
//
//    $auth = $factory->createAuth();
//
//    $refresh = $auth->signInWithRefreshToken($_SESSION['refreshToken']);
//
//    $customToken = $auth->createCustomToken($data['localId'] ?? '');
//
//    $_SESSION['idToken'] = $refresh->idToken();
//    $_SESSION['refreshToken'] = $refresh->refreshToken();
//    $_SESSION['expiresIn'] = time() + 3600;
//    $_SESSION['customToken'] = $customToken->toString();
//}

echo json_encode($debug);