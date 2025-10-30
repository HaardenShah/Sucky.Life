// Admin editor JavaScript
(function() {
    'use strict';

    const mediaPickerModal = document.getElementById('media-picker-modal');
    const mediaPickerGrid = document.getElementById('media-picker-grid');
    const mediaPickerTitle = document.getElementById('media-picker-title');
    const uploadZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('file-input');

    // Open media picker
    window.openMediaPicker = function(type) {
        currentMediaType = type;
        
        let mediaList = [];
        let title = 'Select Media';
        
        if (type === 'image') {
            mediaList = allImages;
            title = 'Select Image';
        } else if (type === 'video') {
            mediaList = allVideos;
            title = 'Select Video';
        } else if (type === 'audio') {
            mediaList = allAudio;
            title = 'Select Audio';
        }
        
        mediaPickerTitle.textContent = title;
        renderMediaGrid(mediaList, type);
        mediaPickerModal.classList.remove('hidden');
    };

    // Close media picker
    window.closeMediaPicker = function() {
        mediaPickerModal.classList.add('hidden');
        currentMediaType = null;
    };

    // Render media grid
    function renderMediaGrid(mediaList, type) {
        if (mediaList.length === 0) {
            mediaPickerGrid.innerHTML = '<p style="text-align: center; color: #86868b; padding: 2rem;">No media files uploaded yet. Use the upload zone below to add files.</p>';
            return;
        }

        mediaPickerGrid.innerHTML = '';
        
        mediaList.forEach(media => {
            const item = document.createElement('div');
            item.className = 'media-item';
            
            if (type === 'image') {
                item.innerHTML = `
                    <img src="${media.path}" alt="${media.filename}" loading="lazy">
                    <div class="media-item-name">${media.filename}</div>
                `;
            } else if (type === 'video') {
                item.innerHTML = `
                    <video src="${media.path}" preload="metadata"></video>
                    <div class="media-item-name">${media.filename}</div>
                `;
            } else if (type === 'audio') {
                item.innerHTML = `
                    <div class="media-item-audio">🎵</div>
                    <div class="media-item-name">${media.filename}</div>
                `;
            }
            
            item.addEventListener('click', () => selectMedia(media, type));
            mediaPickerGrid.appendChild(item);
        });
    }

    // Select media
    function selectMedia(media, type) {
        if (type === 'image') {
            document.getElementById('selected-image').value = media.path;
            
            // Find WebP version if available
            const webpPath = media.path.replace(/\.(jpg|jpeg|png)$/i, '.webp');
            const webpExists = allImages.some(img => img.path === webpPath);
            
            if (webpExists) {
                document.getElementById('selected-image-webp').value = webpPath;
            }
            
            document.getElementById('selected-image-preview').innerHTML = `
                <img src="${media.path}" alt="Selected">
                <button type="button" class="remove-media" onclick="clearImage()">×</button>
            `;
        } else if (type === 'video') {
            document.getElementById('selected-video').value = media.path;
            document.getElementById('selected-video-preview').innerHTML = `
                <video src="${media.path}" controls style="width: 100%;"></video>
                <button type="button" class="remove-media" onclick="clearVideo()">×</button>
            `;
        } else if (type === 'audio') {
            document.getElementById('selected-audio').value = media.path;
            document.getElementById('selected-audio-preview').innerHTML = `
                <audio src="${media.path}" controls style="width: 100%;"></audio>
                <button type="button" class="remove-media" onclick="clearAudio()">×</button>
            `;
        }
        
        closeMediaPicker();
    }

    // Clear media functions
    window.clearImage = function() {
        document.getElementById('selected-image').value = '';
        document.getElementById('selected-image-webp').value = '';
        document.getElementById('selected-image-preview').innerHTML = '<div class="no-media">No image selected</div>';
    };

    window.clearVideo = function() {
        document.getElementById('selected-video').value = '';
        document.getElementById('selected-video-preview').innerHTML = '<div class="no-media">No video selected</div>';
    };

    window.clearAudio = function() {
        document.getElementById('selected-audio').value = '';
        document.getElementById('selected-audio-preview').innerHTML = '<div class="no-media">No audio selected</div>';
    };

    // Upload zone
    uploadZone.addEventListener('click', () => {
        fileInput.click();
    });

    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = '#667eea';
        uploadZone.style.background = 'rgba(102, 126, 234, 0.05)';
    });

    uploadZone.addEventListener('dragleave', () => {
        uploadZone.style.borderColor = '#d2d2d7';
        uploadZone.style.background = '';
    });

    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = '#d2d2d7';
        uploadZone.style.background = '';
        
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    fileInput.addEventListener('change', (e) => {
        const files = e.target.files;
        handleFiles(files);
    });

    // Handle file uploads
    async function handleFiles(files) {
        for (let i = 0; i < files.length; i++) {
            await uploadFile(files[i]);
        }
        
        // Reload page to show new uploads
        location.reload();
    }

    async function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('csrf_token', csrfToken);

        try {
            const response = await fetch('/admin/api.php', {
                method: 'POST',
                body: formData
            });

            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                alert('Upload failed: Server returned HTML instead of JSON. Check browser console for details.');
                return;
            }

            const data = await response.json();

            if (!data.success) {
                let errorMsg = 'Upload failed: ' + (data.error || 'Unknown error');
                if (data.debug) {
                    errorMsg += '\n\nDebug Info:\n' + JSON.stringify(data.debug, null, 2);
                }
                alert(errorMsg);
                console.error('Upload error details:', data);
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert('Upload error: ' + error.message);
        }
    }

    // Close modal on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !mediaPickerModal.classList.contains('hidden')) {
            closeMediaPicker();
        }
    });
})();