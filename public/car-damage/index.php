<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Car Damage Detection | AIML</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1 class="mt-2">Car Damage Detection | AIML</h1>
    <hr/>
    <div>
        <input class="form-control form-control-lg" id="images" type="file" accept="image/*" multiple>
        <div class="form-text">
            Select you image or list of images for upload.
        </div>
    </div>
    <hr/>
    <div id="output" class="mt-2"></div>
</div>
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="//code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
	const CLASS_NAMES = [
		'Minor',
		'Moderate',
		'Severe',
	];
	// Class color based on disease, healthy, or normal
	const CLASS_COLORS = [
		'#FFFF00',
		// Orange
		'#FFA500',
		'#FF0000',
	];

	// Format the number on thousands
	const formatNumber = (number) => {
		return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
	};

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
					let html = '<div class="alert alert-success p-2" style="font-size: 0.85em" role="alert">Detect completed in <strong>' + formatNumber(Math.round(response.inference)) + '</strong> ms</div>';

					// Iterate result
					const result = Array.from(response.result);

					// Show if empty result
					if (result.length === 0) {
						html += '<div class="alert alert-warning" role="alert">the muzzle not found</div>';
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
						body.className = 'card-body d-flex flex-column gap-2';
						card.appendChild(body);

						// Create div for store buttons
						const buttons = document.createElement('div');
						buttons.className = 'd-flex justify-content-between mb-2';
						body.appendChild(buttons);

						const left = document.createElement('div');
						left.className = 'position-relative';
						body.appendChild(left);

						const center = document.createElement('div');
						body.appendChild(center);

						// Create button for toggle hide and show canvas
						const toggleButton = document.createElement('button');
						toggleButton.className = 'btn btn-primary btn-sm';
						toggleButton.textContent = 'Toggle Mask';
						toggleButton.onclick = function () {
							const canvas = left.querySelector('canvas');
							canvas.hidden = !canvas.hidden;
							const img = left.querySelector('img');
							img.style.opacity = img.style.opacity === '0' ? '1' : '0';
						};
						buttons.appendChild(toggleButton);

						// Download button, to download the image including the mask
						const downloadButton = document.createElement('button');
						downloadButton.className = 'btn btn-success btn-sm';
						downloadButton.textContent = 'Download';
						downloadButton.onclick = function () {
							const canvas = left.querySelector('canvas');
							const a = document.createElement('a');
							a.href = canvas.toDataURL('image/png');
							a.download = value.name;
                            a.click();
						};
						buttons.appendChild(downloadButton);

						const img = document.createElement('img');
						img.src = URL.createObjectURL(value);
						img.classList.add('img-fluid');
						left.appendChild(img);

						// Create canvas
						const canvas = document.createElement('canvas');

						// Make the canvas as overlay
						canvas.style.position = 'absolute';
						canvas.style.top = '0';
						canvas.style.left = '0';
						canvas.style.zIndex = '1';
						canvas.style.maxWidth = '100%';
						canvas.style.pointerEvents = 'none';
						left.appendChild(canvas);

						const right = document.createElement('div');
						right.className = 'flex-fill';
						body.appendChild(right);

						img.onload = function () {
							// Get the original image size
							canvas.width = img.width;
							canvas.height = img.height;

							// Make img opacity 0
							img.style.opacity = '0';

							console.log('Value:', value.name)
							const result = Array.from(response.result).find(r => {
								const base_name = value.name.split('.').slice(0, -1).join('.');
								return r.file === base_name;
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
								pre.hidden = true;
								pre.innerHTML = JSON.stringify(payload, null, 4);
								right.appendChild(pre);

								// Scale factor
								const scale_factor = img.width / img.naturalWidth;

								const ctx = canvas.getContext('2d');

								// Draw the image to the canvas with resized
								ctx.drawImage(img, 0, 0, img.width, img.height);

								// Iterate payload
								for (const [, value] of Object.entries(payload)) {
									let [x1, y1, x2, y2, points, score, class_id] = value;

									// points = Array.from(points).map(([x, y]) => [x * scale_factor, y * scale_factor]
									points = Array.from(points).shift();

									// // Create canvas and add to the center
									// const focusCanvas = document.createElement('canvas');
									// focusCanvas.width = x2 - x1;
									// focusCanvas.height = y2 - y1;
									// focusCanvas.style.maxWidth = 200 + 'px';
									// focusCanvas.getContext('2d').drawImage(img, x1, y1, x2 - x1, y2 - y1, 0, 0, x2 - x1, y2 - y1);
									// center.appendChild(focusCanvas);

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

									// Draw the points as mask and fill with color opacity 0.6
									if (points.length > 0) {
										ctx.fillStyle = color;
										ctx.globalAlpha = 0.6;
										ctx.beginPath();
										ctx.moveTo(points[0][0] * scale_factor, points[0][1] * scale_factor);
										for (let i = 1; i < points.length; i++) {
											ctx.lineTo(points[i][0] * scale_factor, points[i][1] * scale_factor);
										}
										ctx.closePath();
										ctx.fill();
									}

									dl_html += '<dt class="col-12">Found <strong style="color: ' + CLASS_COLORS[class_id] + '">"' + CLASS_NAMES[class_id] + '"</strong> has confidence is ' + score + '</dt>';
								}

								dl.innerHTML = dl_html;

								right.appendChild(dl);

								console.log('Payload:', payload)
							} else {
								const alert = document.createElement('div');
								alert.className = 'alert alert-warning';
								alert.textContent = 'No result';
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