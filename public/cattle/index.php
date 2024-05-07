<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cattle Muzzle Detection | AIML</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1 class="mt-2">Cattle Muzzle Detection | AIML</h1>
    <hr/>
    <div>
        <input class="form-control form-control-lg" id="images" type="file" accept="image/*" multiple>
        <div class="form-text">
            Select you image or list of images for upload and classification. Burger, Slider, Wrap or Sandwiches
        </div>
    </div>
    <hr/>
    <div id="output" class="mt-2"></div>
</div>
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="//code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
	const CLASS_NAMES = ['muzzle'];
	$(document).ready(function ($) {
		// When images has changed
		$('#images').on('change', function () {
			const FILES = {};
			const files = $(this)[0].files;
			const formData = new FormData();
			for (var i = 0; i < files.length; i++) {
				const file = files[i];
				formData.append('images[]', file);
				FILES[file.name] = files[i];
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
					let html = '<div class="alert alert-success p-2" style="font-size: 0.85em" role="alert">Classification completed in <strong>' + Math.round(response.inference) + '</strong> ms</div>';

					// Iterate result
					const result = Array.from(response.result);

					// Show if empty result
					if (result.length === 0) {
						html += '<div class="alert alert-warning" role="alert">Classification service down</div>';
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

						const img = document.createElement('img');
						img.src = URL.createObjectURL(value);
						img.width = 500;
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
								pre.innerHTML = JSON.stringify(payload, null, 2);
								right.appendChild(pre);

								// Scale factor
								const scale_factor = img.width / img.naturalWidth;

								const ctx = canvas.getContext('2d');
								// Iterate payload
								for (const [, value] of Object.entries(payload)) {
									let [x1, y1, x2, y2, score, class_id] = value;

									// Scale the coordinates
									x1 *= scale_factor;
									y1 *= scale_factor;
									x2 *= scale_factor;
									y2 *= scale_factor;

									const color = '#00ff00';
									ctx.strokeStyle = color;
									ctx.lineWidth = 3;
									ctx.strokeRect(x1, y1, x2 - x1, y2 - y1);
									ctx.font = '16px Arial';
									ctx.fillStyle = color;
									ctx.fillText(CLASS_NAMES[class_id] + ': ' + Number(score).toFixed(2), x1, y1);

									dl_html += '<dt class="col-12 fw-bold">Found "' + CLASS_NAMES[class_id] + '" has confidence is ' + score + '</dt>';
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