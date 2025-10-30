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
                alert('Upload failed: ' + (data.error || 'Unknown error'));
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
// Tab switching
window.switchMediaTab = function(tabName) {
    // Update tab buttons
    const tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update tab content
    document.getElementById('tab-uploaded').classList.toggle('hidden', tabName !== 'uploaded');
    document.getElementById('tab-external').classList.toggle('hidden', tabName !== 'external');
    document.getElementById('tab-uploaded').classList.toggle('active', tabName === 'uploaded');
    document.getElementById('tab-external').classList.toggle('active', tabName === 'external');
    
    // Clear external URL input when switching away
    if (tabName === 'uploaded') {
        document.getElementById('external-url-input').value = '';
    }
};

// Select external URL
window.selectExternalUrl = function() {
    const urlInput = document.getElementById('external-url-input');
    let url = urlInput.value.trim();
    
    if (!url) {
        alert('Please enter a URL');
        return;
    }
    
    // Validate URL format
    try {
        new URL(url);
    } catch (e) {
        alert('Please enter a valid URL starting with http:// or https://');
        return;
    }
    
    // Convert YouTube URLs to embed format
    url = convertToEmbedUrl(url);
    
    // Determine media type based on currentMediaType
    const type = currentMediaType;
    
    if (type === 'image') {
        document.getElementById('selected-image').value = url;
        document.getElementById('selected-image-webp').value = '';
        
        document.getElementById('selected-image-preview').innerHTML = `
            <img src="${escapeHtml(url)}" alt="External image" onerror="this.src='/assets/images/broken-image.png'">
            <button type="button" class="remove-media" onclick="clearImage()">×</button>
        `;
    } else if (type === 'video') {
        document.getElementById('selected-video').value = url;
        
        // Check if it's a YouTube or Vimeo embed
        if (url.includes('youtube.com/embed/') || url.includes('youtu.be/') || url.includes('vimeo.com/')) {
            document.getElementById('selected-video-preview').innerHTML = `
                <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                    <iframe src="${escapeHtml(url)}" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                            frameborder="0" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                </div>
                <button type="button" class="remove-media" onclick="clearVideo()">×</button>
            `;
        } else {
            document.getElementById('selected-video-preview').innerHTML = `
                <video src="${escapeHtml(url)}" controls style="width: 100%;"></video>
                <button type="button" class="remove-media" onclick="clearVideo()">×</button>
            `;
        }
    } else if (type === 'audio') {
        document.getElementById('selected-audio').value = url;
        document.getElementById('selected-audio-preview').innerHTML = `
            <audio src="${escapeHtml(url)}" controls style="width: 100%;"></audio>
            <button type="button" class="remove-media" onclick="clearAudio()">×</button>
        `;
    }
    
    closeMediaPicker();
    urlInput.value = '';
};

// Convert YouTube/Vimeo URLs to embed format
function convertToEmbedUrl(url) {
    // YouTube watch URL to embed
    if (url.includes('youtube.com/watch')) {
        const urlObj = new URL(url);
        const videoId = urlObj.searchParams.get('v');
        if (videoId) {
            return `https://www.youtube.com/embed/${videoId}`;
        }
    }
    
    // YouTube short URL to embed
    if (url.includes('youtu.be/')) {
        const videoId = url.split('youtu.be/')[1].split('?')[0];
        return `https://www.youtube.com/embed/${videoId}`;
    }
    
    // Vimeo URL to embed
    if (url.includes('vimeo.com/') && !url.includes('/video/')) {
        const videoId = url.split('vimeo.com/')[1].split('?')[0];
        return `https://player.vimeo.com/video/${videoId}`;
    }
    
    return url;
}

// Escape HTML helper for external URLs
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
