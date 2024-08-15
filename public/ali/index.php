<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timeout for 1 minute
set_time_limit(60);

$detect = [];

$url = $_GET['url'] ?? '';

$COLORS = [
    '#00ff00',
    '#0000ff',
];

$COLORS_RGB = [
    [0, 255, 0],
    [0, 0, 255],
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
    $service = 'http://localhost:8000/v8/detect/ali/' . $task . '?bucket=' . urlencode($taskDir);

    // Initialize cURL
    $ch = curl_init($service);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // Execute cURL
    $response = curl_exec($ch);

    $detect = json_decode($response, true);
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
    <title>ALI | AIML</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1 class="mt-2">ALI | AIML</h1>
    <?php if (empty($detect)): ?>
        <hr/>
        <div>
            <input class="form-control form-control-lg" id="images" type="file" accept="image/*" multiple>
            <div class="form-text">
                Select you image or list of images for upload and detect.
            </div>
        </div>
    <?php endif; ?>
    <hr/>
    <div id="output" class="mt-2">
        <?php if (!empty($detect)): ?>
            <div id="output" class="mt-2">
                <?php if (empty($detect['result'])): ?>
                    <div class="alert alert-warning" role="alert">No logo / text detected. inference <strong><?php echo $detect['inference'] ?></strong> ms</div>
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
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="//code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
	const CLASS_NAMES = [
		'Logo',
		'Text'
	];
	// Class color based on disease, healthy, or normal
	const CLASS_COLORS = [
		'#00ff00',
		'#0000ff',
	];
	$(document).ready(function ($) {
		// When images has changed
		$('#images').on('change', function () {
			const FILES = {};
			const files = $(this)[0].files;
			const formData = new FormData();
			for (var i = 0; i < files.length; i++) {
				const file = files[i];
				formData.append('images[]', file);
				FILES[file.name] = file;
				console.log('File:', file)
			}

			// Add the loading spinner
			$('#output').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
			$.ajax({
				url: 'classify.php',
				type: 'post',
				data: formData,
				contentType: false,
				processData: false,
				success: function (response) {
					// Process time html
					let html = '<div class="alert alert-success p-2" style="font-size: 0.85em" role="alert">Detect Diseases completed in <strong>' + Math.round(response.inference) + '</strong> ms</div>';

					// Iterate result
					const result = Array.from(response.result);

					// Show if empty result
					if (result.length === 0) {
						html += '<div class="alert alert-warning" role="alert">the diseases not found</div>';
					}

					html += '<div id="viewer"></div>';

					$('#output').html(html);

					const viewer = $('#viewer').get(0);
					// FILES
					for (const [, value] of Object.entries(FILES)) {
						const card = document.createElement('div');
						card.className = 'card rounded-0 shadow-sm mb-3';
						viewer.appendChild(card);

						const body = document.createElement('div');
						body.className = 'card-body d-flex gap-2';
						card.appendChild(body);

						const left = document.createElement('div');
						left.className = 'position-relative';
						body.appendChild(left);

						const center = document.createElement('div');
						body.appendChild(center);

						const img = document.createElement('img');
						img.src = URL.createObjectURL(value);
						img.width = 200;
						left.appendChild(img);

						// Create canvas
						const canvas = document.createElement('canvas');

						// Make the canvas as overlay
						canvas.style.position = 'absolute';
						canvas.style.top = '0';
						canvas.style.left = '0';
						canvas.style.zIndex = '1';
						canvas.style.pointerEvents = 'none';
						left.appendChild(canvas);

						const right = document.createElement('div');
						right.className = 'flex-fill';
						body.appendChild(right);

						img.onload = function () {
							// Get the original image size
							canvas.width = img.width;
							canvas.height = img.height;

							console.log('Value:', value.name)
							const result = Array.from(response.result).find(r => {
								const file_name = r.file.toString().split('/').pop();
								return file_name === value.name;
							});
							if (result) {
								const payload = Array.from(result.payload);

								// Create Description list alignment
								const dl = document.createElement('dl');
								dl.className = 'row';
								let dl_html = '';
								// right.appendChild(dl);

								// Create <pre> element
								const pre = document.createElement('pre');
								pre.className = 'border p-2';
								pre.innerHTML = JSON.stringify(payload, null, 4);
								right.appendChild(pre);

								// Scale factor
								const scale_factor = img.width / img.naturalWidth;

								const ctx = canvas.getContext('2d');
								// Iterate payload
								for (const [, value] of Object.entries(payload)) {
									let [x1, y1, x2, y2, score, class_id] = value;

									// Create canvas and add to the center
									const focusCanvas = document.createElement('canvas');
									focusCanvas.width = x2 - x1;
									focusCanvas.height = y2 - y1;
									focusCanvas.style.maxWidth = 200 + 'px';
									focusCanvas.getContext('2d').drawImage(img, x1, y1, x2 - x1, y2 - y1, 0, 0, x2 - x1, y2 - y1);
									center.appendChild(focusCanvas);

									// Scale the coordinates
									x1 *= scale_factor;
									y1 *= scale_factor;
									x2 *= scale_factor;
									y2 *= scale_factor;

									const color = CLASS_COLORS[class_id] || '#000000';
									ctx.strokeStyle = color;
									ctx.lineWidth = 3;
									ctx.strokeRect(x1, y1, x2 - x1, y2 - y1);
									ctx.font = '16px Arial';
									ctx.fillStyle = color;
									ctx.fillText(CLASS_NAMES[class_id] + ': ' + Number(score).toFixed(2), x1, y1);

									dl_html += '<dt class="col-12">Found <strong style="color: ' + CLASS_COLORS[class_id] + '">"' + CLASS_NAMES[class_id] + '"</strong> has confidence is ' + score + '</dt>';
								}

								dl.innerHTML = dl_html;

								right.appendChild(dl);

								console.log('Payload:', payload)
							} else {
								const alert = document.createElement('div');
								alert.className = 'alert alert-warning';
								alert.textContent = 'No classification result';
								right.appendChild(alert);
							}
						};
					}
				}
			});

			// Clear the input
			$(this).val('');
		});
	});
</script>
</body>
</html>