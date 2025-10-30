// Main JavaScript for sucky.life
(function() {
    'use strict';

    // State management
    let isPlaying = false;
    let isMuted = false;
    let tearInterval = null;
    let activeTears = [];
    let mouseX = 0;
    let mouseY = 0;

    // DOM elements
    const screechBtn = document.getElementById('screech-btn');
    const audioControls = document.getElementById('audio-controls');
    const muteBtn = document.getElementById('mute-btn');
    const stopBtn = document.getElementById('stop-btn');
    const screechAudio = document.getElementById('screech-audio');
    const tearsContainer = document.getElementById('tears-container');
    const modal = document.getElementById('egg-modal');
    const modalBackdrop = modal.querySelector('.modal-backdrop');
    const modalClose = modal.querySelector('.modal-close');
    const modalContent = document.getElementById('modal-content');

    // Track mouse position
    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    // Screech button handler
    screechBtn.addEventListener('click', () => {
        if (!isPlaying) {
            screechAudio.play().then(() => {
                isPlaying = true;
                isMuted = false;
                audioControls.classList.remove('hidden');
                startTears();
            }).catch(err => {
                console.error('Failed to play audio:', err);
            });
        }
    });

    // Mute button handler
    muteBtn.addEventListener('click', () => {
        isMuted = !isMuted;
        screechAudio.muted = isMuted;
        muteBtn.querySelector('.mute-icon').textContent = isMuted ? '🔇' : '🔊';
        
        if (isMuted) {
            stopTears();
        } else {
            startTears();
        }
    });

    // Stop button handler
    stopBtn.addEventListener('click', () => {
        screechAudio.pause();
        screechAudio.currentTime = 0;
        isPlaying = false;
        isMuted = false;
        audioControls.classList.add('hidden');
        muteBtn.querySelector('.mute-icon').textContent = '🔊';
        stopTears();
    });

    // Tear creation and animation
    function createTear() {
        const tear = document.createElement('div');
        tear.className = 'tear';
        
        // Random horizontal position
        const startX = Math.random() * window.innerWidth;
        tear.style.left = startX + 'px';
        tear.style.top = '-20px';
        
        // Random duration between 2-4 seconds
        const duration = 2 + Math.random() * 2;
        tear.style.animationDuration = duration + 's';
        
        tearsContainer.appendChild(tear);
        activeTears.push({
            element: tear,
            x: startX,
            y: -20,
            vx: 0,
            vy: 100 / duration // pixels per second
        });

        // Remove after animation
        setTimeout(() => {
            if (tear.parentNode) {
                tear.remove();
            }
            activeTears = activeTears.filter(t => t.element !== tear);
        }, duration * 1000);
    }

    function startTears() {
        if (tearInterval) return;
        
        // Create tears every 150ms
        tearInterval = setInterval(() => {
            if (isPlaying && !isMuted) {
                createTear();
            }
        }, 150);

        // Update tear positions with mouse repulsion
        requestAnimationFrame(updateTears);
    }

    function stopTears() {
        if (tearInterval) {
            clearInterval(tearInterval);
            tearInterval = null;
        }
        
        // Clean up existing tears
        activeTears.forEach(tear => {
            if (tear.element.parentNode) {
                tear.element.remove();
            }
        });
        activeTears = [];
    }

    function updateTears() {
        if (!isPlaying || isMuted) return;

        const repelRadius = 100;
        const repelForce = 50;

        activeTears.forEach(tear => {
            const rect = tear.element.getBoundingClientRect();
            tear.x = rect.left + rect.width / 2;
            tear.y = rect.top + rect.height / 2;

            // Calculate distance from mouse
            const dx = tear.x - mouseX;
            const dy = tear.y - mouseY;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < repelRadius && distance > 0) {
                // Apply repulsion force
                const force = (1 - distance / repelRadius) * repelForce;
                const angle = Math.atan2(dy, dx);
                
                const offsetX = Math.cos(angle) * force;
                const offsetY = Math.sin(angle) * force;
                
                tear.element.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
            } else {
                tear.element.style.transform = '';
            }
        });

        requestAnimationFrame(updateTears);
    }

    // Easter egg hotspots
    const hotspots = document.querySelectorAll('.egg-hotspot');
    
    hotspots.forEach(hotspot => {
        // Click handler
        hotspot.addEventListener('click', (e) => {
            e.preventDefault();
            const slug = hotspot.dataset.slug;
            openEggModal(slug);
        });

        // Keyboard handler
        hotspot.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const slug = hotspot.dataset.slug;
                openEggModal(slug);
            }
        });
    });

    // Modal functions
    function openEggModal(slug) {
        const egg = window.eggsData.find(e => e.slug === slug);
        if (!egg) return;

        let html = '';
        
        if (egg.title) {
            html += `<h2 class="modal-title">${escapeHtml(egg.title)}</h2>`;
        }
        
        if (egg.caption) {
            html += `<p class="modal-caption">${escapeHtml(egg.caption)}</p>`;
        }
        
        if (egg.image || egg.video) {
            html += '<div class="modal-media">';

            if (egg.image) {
                const imgSrc = egg.image_webp || egg.image;
                const altText = egg.alt || egg.title || 'Easter egg image';
                html += `<img src="${escapeHtml(imgSrc)}" alt="${escapeHtml(altText)}" loading="lazy">`;
            } else if (egg.video) {
                // Check if it's a YouTube or Vimeo embed
                if (egg.video.includes('youtube.com/embed/') || egg.video.includes('youtu.be/') || egg.video.includes('vimeo.com/')) {
                    html += `<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                        <iframe src="${escapeHtml(egg.video)}"
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    </div>`;
                } else {
                    html += `<video controls${egg.video_poster ? ' poster="' + escapeHtml(egg.video_poster) + '"' : ''}>
                        <source src="${escapeHtml(egg.video)}" type="video/mp4">
                        Your browser does not support video.
                    </video>`;
                }
            }

            html += '</div>';
        }
        
        if (egg.audio) {
            html += `<audio controls class="modal-audio">
                <source src="${escapeHtml(egg.audio)}" type="audio/mpeg">
                Your browser does not support audio.
            </audio>`;
        }
        
        if (egg.body) {
            html += `<div class="modal-body">${egg.body}</div>`;
        }

        modalContent.innerHTML = html;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modalClose.focus();
    }

    function closeEggModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');

        // Stop any audio playing in modal
        const modalAudio = modal.querySelector('audio');
        if (modalAudio) {
            modalAudio.pause();
        }

        const modalVideo = modal.querySelector('video');
        if (modalVideo) {
            modalVideo.pause();
        }

        // Stop YouTube/Vimeo embeds by reloading the iframe
        const modalIframe = modal.querySelector('iframe');
        if (modalIframe) {
            modalIframe.src = modalIframe.src;
        }
    }

    // Modal close handlers
    modalBackdrop.addEventListener('click', closeEggModal);
    modalClose.addEventListener('click', closeEggModal);
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeEggModal();
        }
    });

    // Utility function
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Prevent text selection during interactions
    document.addEventListener('selectstart', (e) => {
        if (e.target.classList.contains('egg-hotspot') || 
            e.target.closest('.egg-hotspot')) {
            e.preventDefault();
        }
    });
})();
