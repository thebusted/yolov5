<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo Menu Classification | Freerolls</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
</head>
<body>
<div class="container">
    <h1 class="mt-2">Demo Menu Classification | Freerolls</h1>
    <hr />
    <div>
        <input class="form-control form-control-lg" id="images" type="file" accept="image/*" multiple>
        <div class="form-text">
            Select you image or list of images for upload and classification. Burger, Slider, Wrap or Sandwiches
        </div>
    </div>
    <hr />
    <div id="output" class="mt-2"></div>
</div>
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="//code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function ($) {
		// When images has change
        $('#images').on('change', function () {
            var files = $(this)[0].files;
            var formData = new FormData();
            for (var i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }

			// Add the loading spinner
            $('#output').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

			console.log(files, formData);
            $.ajax({
                url: 'classify.php',
                type: 'post',
                data: formData,
                contentType: false,
                processData: false,
                success: function (response) {
                    $('#output').html(response);
                }
            });

			// Clear the input
            $(this).val('');
        });
    });
</script>
</body>
</html>