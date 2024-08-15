<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timeout for 1 minute
set_time_limit(60);

$detect = [];

$url = $_GET['url'] ?? '';

$COLORS = [
    '#ff0000',
    '#ba0b0b',
    '#00ff00',
    '#0000ff',
    '#b10236',
    '#00994c',
    '#006633',
    '#00994c',
    '#006633',
    '#00994c',
    '#006633',
    '#cc0000',
    '#990000',
    '#cc0000',
    '#990000',
    '#cc0000',
    '#ff9900',
    '#F08080',
];

$COLORS_RGB = [
    [255, 0, 0],
    [186, 11, 11],
    [0, 255, 0],
    [0, 0, 255],
    [177, 2, 54],
    [0, 153, 76],
    [0, 102, 51],
    [0, 153, 76],
    [0, 102, 51],
    [0, 153, 76],
    [0, 102, 51],
    [204, 0, 0],
    [153, 0, 0],
    [204, 0, 0],
    [153, 0, 0],
    [204, 0, 0],
    [255, 153, 0],
    [240,128,128]
];

if (!empty($url)) {
    $uploadDir = __DIR__ . '/uploads/';

    // Create the upload directory if it does not exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Save the url to file in folder downloads
    $img = file_get_contents($url);
    $url = strtok($url, '?');
    $file_name = basename($url);

    // Create unique name for task
    $task = md5($url);

    // Create the task directory
    $taskDir = $uploadDir . $task . '/';
    if (!file_exists($taskDir)) {
        mkdir($taskDir, 0777, true);
    }

    // Move the uploaded images to the task directory
    $uploadedImages = [$taskDir . $file_name];
    file_put_contents($taskDir . $file_name, $img);

    // Call internal service to classify the images at localhost:8000 using GET method
    $service = 'http://localhost:8000/v8/detect/stain3/' . $task . '?bucket=' . urlencode($taskDir);

    // Initialize cURL
    $ch = curl_init($service);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // Execute cURL
    $response = curl_exec($ch);

    $detect = json_decode($response, true);8
    if (json_last_error() === JSON_ERROR_NONE) {
        foreach ($detect['result'] as $result) {
            foreach ($result['payload'] as $key => $value) {
                $x1 = $value[0];
                $y1 = $value[1];
                $x2 = $value[2];
                $y2 = $value[3];
                $score = $value[4];
                $class_id = $value[5];
                $img_width = $value[6];
                $img_height = $value[7];
                $class_name = $value[8];

                // Get image mime
                $mime = mime_content_type($result['file']);

                // Create image by mime
                switch ($mime) {
                    case 'image/jpeg':
                        $image = imagecreatefromjpeg($result['file']);
                        break;
                    case 'image/png':
                        $image = imagecreatefrompng($result['file']);
                        break;
                    case 'image/gif':
                        $image = imagecreatefromgif($result['file']);
                        break;
                    default:
                        $image = null;
                        break;
                }

                if (is_null($image)) {
                    continue;
                }

                // Draw bounding box
                $color = imagecolorallocate($image, $COLORS_RGB[$class_id][0], $COLORS_RGB[$class_id][1], $COLORS_RGB[$class_id][2]);

                $thickness = 3;
                for ($i = 0; $i < $thickness; $i++) {
                    imagerectangle($image, $x1 + $i, $y1 + $i, $x2 - $i, $y2 - $i, $color);
                }

                // Draw text
                $text = $class_name . ': ' . number_format($score, 4);

                // Get the text box size
                $text_box = imagettfbbox(10, 0, __DIR__ . '/Arial.ttf', $text);
                $text_width = $text_box[2] - $text_box[0];

                // Calculate the position of the text (centered)
                $text_x = $x1 + ($x2 - $x1) / 2 - $text_width / 2;
                $text_y = $y1 - 5;

                // Add text to image
                imagettftext($image, 14, 0, $text_x, $text_y, $color, __DIR__ . '/Arial.ttf', $text);

                // Save the image base on mime
                switch ($mime) {
                    case 'image/jpeg':
                        imagejpeg($image, $result['file'], 100);
                        break;
                    case 'image/png':
                        imagepng($image, $result['file']);
                        break;
                    case 'image/gif':
                        imagegif($image, $result['file']);
                        break;
                }

                // Free up memory
                imagedestroy($image);
            }
        }
    } else {
        $detect = [];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Linen Stain Detect | AIML</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1 class="mt-2">Linen Stain Detect | AIML</h1>
    <hr/>
    <div id="output" class="mt-2">
        <?php if (!empty($detect)): ?>
            <div id="output" class="mt-2">
                <?php if (empty($detect['result'])): ?>
                    <div class="alert alert-warning" role="alert">No stain detected. inference <strong><?php echo $detect['inference'] ?></strong> ms</div>
                <?php else: ?>
                    <div class="alert alert-success p-2" style="font-size: 0.85em" role="alert">Detect completed. inference <strong><?php echo $detect['inference'] ?></strong> ms</div>
                <?php endif; ?>
                <div id="viewer">
                    <?php foreach ($detect['result'] as $result): ?>
                        <div class="card rounded-0 shadow-sm mb-3">
                            <div class="card-body d-flex gap-2 flex-column">
                                <div class="d-flex align-items-center justify-content-center">
                                    <img class="shadow img-fluid" src="<?php echo str_replace('/mnt/volume_sgp1_02/aiml/public', '', $result['file']) ?>">
                                </div>
                                <div class="flex-fill mt-3">
                                    <dl class="row">
                                        <?php foreach ($result['payload'] as $payload): ?>
                                            <dt class="col-12">Found <strong style="color: <?php echo $COLORS[$payload[5]] ?>">"<?php echo $payload[8] ?>"</strong> has confidence
                                                is <?php echo $payload[4] ?></dt>
                                        <?php endforeach; ?>
                                    </dl>
                                    <pre class="border p-2"><?php echo json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>