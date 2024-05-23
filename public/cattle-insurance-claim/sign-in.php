<?php
use Kreait\Firebase\Auth\SignIn\FailedToSignIn;
use Kreait\Firebase\Factory;

require_once __DIR__ . '/_inc_/config.php';

$factory = (new Factory)->withServiceAccount(__DIR__ . '/_inc_/aiml-cattle-insurance-firebase-adminsdk-et09n-60c76cf2a5.json');

$auth = $factory->createAuth();

try {
    $signInResult = $auth->signInWithEmailAndPassword($_POST['em'] ?? '', $_POST['pw'] ?? '');
    $data = $signInResult->data();

    $customToken = $auth->createCustomToken($data['localId'] ?? '');

    $_SESSION['idToken'] = $data['idToken'];
    $_SESSION['refreshToken'] = $data['refreshToken'];
    $_SESSION['customToken'] = $customToken->toString();
    $_SESSION['expiresIn'] = time() + intval($data['expiresIn']);
} catch (FailedToSignIn $e) {
    $_SESSION['error'] = $e->getMessage();
} finally {
    header('Location: ./');
    exit;
}