<!DOCTYPE html>
<html>
<head>
    <title>Image Cropper & Watermark</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .upload-area { border: 2px dashed #ccc; padding: 20px; text-align: center; margin-bottom: 20px; }
        .controls { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .control-group { display: flex; flex-direction: column; gap: 5px; }
        .control-group label { font-weight: bold; }
        .control-group input, .control-group select { padding: 5px; }
        #image-container { max-width: 100%; margin-bottom: 20px; }
        #preview-image { max-width: 100%; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Image Cropper & Watermark Tool</h1>
        
        <div class="upload-area">
            <input type="file" id="image-input" accept="image/*">
            <p>Select an image to crop</p>
        </div>

        <div id="image-container" class="hidden">
            <img id="preview-image" src="">
        </div>

        <div class="controls">
            <div class="control-group">
                <label>Crop Width:</label>
                <input type="number" id="crop-width" value="300">
            </div>
            <div class="control-group">
                <label>Crop Height:</label>
                <input type="number" id="crop-height" value="300">
            </div>
            <div class="control-group">
                <label>
                    <input type="checkbox" id="maintain-aspect"> Maintain Aspect Ratio
                </label>
            </div>
            <div class="control-group">
                <label>Watermark:</label>
                <input type="file" id="watermark-input" accept="image/*">
            </div>
            <div class="control-group">
                <label>Watermark Size:</label>
                <input type="number" id="watermark-size" value="100" min="50" max="500">
            </div>
            <div class="control-group">
                <label>Watermark Position:</label>
                <select id="watermark-position">
                    <option value="top-left">Top Left</option>
                    <option value="top-right">Top Right</option>
                    <option value="bottom-left">Bottom Left</option>
                    <option value="bottom-right" selected>Bottom Right</option>
                    <option value="center">Center</option>
                </select>
            </div>
        </div>

        <button id="crop-btn" class="btn hidden">Crop & Process Image</button>
        
        <div id="preview-section" class="hidden" style="margin: 20px 0;">
            <h3>Preview:</h3>
            <img id="preview-result" style="max-width: 400px; border: 1px solid #ccc;">
            <br><br>
            <a id="download-btn" class="btn btn-success" download>Download Image</a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
        let cropper;
        const imageInput = document.getElementById('image-input');
        const previewImage = document.getElementById('preview-image');
        const imageContainer = document.getElementById('image-container');
        const cropBtn = document.getElementById('crop-btn');
        const downloadBtn = document.getElementById('download-btn');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    imageContainer.classList.remove('hidden');
                    cropBtn.classList.remove('hidden');
                    
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    cropper = new Cropper(previewImage, {
                        aspectRatio: NaN,
                        viewMode: 1,
                        autoCropArea: 0.8,
                        responsive: true,
                        crop: function(event) {
                            if (!isUpdating && !document.getElementById('crop-width').matches(':focus') && !document.getElementById('crop-height').matches(':focus')) {
                                document.getElementById('crop-width').value = Math.round(event.detail.width);
                                document.getElementById('crop-height').value = Math.round(event.detail.height);
                                aspectRatio = event.detail.width / event.detail.height;
                            }
                        }
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Update cropper when width/height inputs change
        let aspectRatio = 1;
        let isUpdating = false;
        
        document.getElementById('crop-width').addEventListener('input', function() {
            if (isUpdating) return;
            if (document.getElementById('maintain-aspect').checked) {
                isUpdating = true;
                const width = parseInt(this.value) || 300;
                document.getElementById('crop-height').value = Math.round(width / aspectRatio);
                isUpdating = false;
            }
            updateCropBox();
        });
        
        document.getElementById('crop-height').addEventListener('input', function() {
            if (isUpdating) return;
            if (document.getElementById('maintain-aspect').checked) {
                isUpdating = true;
                const height = parseInt(this.value) || 300;
                document.getElementById('crop-width').value = Math.round(height * aspectRatio);
                isUpdating = false;
            }
            updateCropBox();
        });

        function updateCropBox() {
            if (!cropper) return;
            
            const width = parseInt(document.getElementById('crop-width').value) || 300;
            const height = parseInt(document.getElementById('crop-height').value) || 300;
            
            const cropBoxData = cropper.getCropBoxData();
            cropper.setCropBoxData({
                left: cropBoxData.left,
                top: cropBoxData.top,
                width: width,
                height: height
            });
        }

        cropBtn.addEventListener('click', function() {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas();
            const cropData = cropper.getData();
            
            const formData = new FormData();
            formData.append('image', imageInput.files[0]);
            formData.append('x', Math.round(cropData.x));
            formData.append('y', Math.round(cropData.y));
            formData.append('width', Math.round(cropData.width));
            formData.append('height', Math.round(cropData.height));
            
            const watermarkFile = document.getElementById('watermark-input').files[0];
            if (watermarkFile) {
                formData.append('watermark', watermarkFile);
                formData.append('watermark_size', document.getElementById('watermark-size').value);
                formData.append('watermark_position', document.getElementById('watermark-position').value);
            }

            fetch('/crop', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('preview-result').src = data.download_url;
                    document.getElementById('download-btn').href = data.download_url;
                    document.getElementById('preview-section').classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error processing image');
            });
        });
    </script>
</body>
</html>