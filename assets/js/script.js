/* ==============================
   GLOBAL UTILITIES
================================ */

// konfirmasi beli game
function confirmBuy(gameTitle) {
    return confirm("Apakah kamu yakin ingin membeli:\n\n" + gameTitle + " ?");
}

// konfirmasi hapus (admin)
function confirmDelete(msg = "Yakin ingin menghapus data ini?") {
    return confirm(msg);
}

/* ==============================
   UI INTERACTIONS
================================ */
document.addEventListener("DOMContentLoaded", () => {

    /* Hover animation (fallback JS) */
    document.querySelectorAll(".game-card").forEach(card => {
        card.addEventListener("mouseenter", () => {
            card.style.transform = "scale(1.05)";
        });

        card.addEventListener("mouseleave", () => {
            card.style.transform = "scale(1)";
        });
    });

    /* Highlight owned game */
    document.querySelectorAll(".owned").forEach(badge => {
        const card = badge.closest(".game-card");
        if (card) {
            card.style.border = "2px solid #16a34a";
        }
    });

    /* Auto hide alert */
    document.querySelectorAll(".alert").forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = "0";
        }, 3000);
    });

});

/* ==============================
   NAVBAR (optional future)
================================ */
function toggleMenu() {
    const nav = document.querySelector(".nav-links");
    if (nav) nav.classList.toggle("show");
}

// Game Form Functionality
class GameForm {
    constructor() {
        this.form = document.getElementById('gameForm');
        this.imageUrlInput = document.querySelector('input[name="image_url"]');
        this.imagePreview = document.getElementById('imagePreview');
        this.init();
    }

    init() {
        if (this.form) {
            this.bindEvents();
            this.updateImagePreview(this.imageUrlInput?.value.trim());
        }
    }

    bindEvents() {
        // Real-time image preview
        if (this.imageUrlInput && this.imagePreview) {
            this.imageUrlInput.addEventListener('input', () => {
                this.updateImagePreview(this.imageUrlInput.value.trim());
            });
        }

        // Form validation
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.validateForm(e));
        }

        // Genre suggestions
        this.initGenreSuggestions();

        // Price formatting
        this.initPriceFormatting();

        // Status badge selection
        this.initStatusBadges();
    }

    updateImagePreview(url) {
        if (!this.imagePreview) return;

        if (url && this.isValidUrl(url)) {
            this.imagePreview.innerHTML = `
                <img src="${url}" 
                     alt="Game preview" 
                     onerror="this.onerror=null; this.src=''; this.alt='Image failed to load'">
            `;
        } else if (url && !this.isValidUrl(url)) {
            this.imagePreview.innerHTML = `
                <div class="no-image" style="color: #ef4444;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>Invalid URL</div>
                </div>
            `;
        } else {
            this.imagePreview.innerHTML = `
                <div class="no-image">
                    <i class="fas fa-image"></i>
                    <div>No image preview</div>
                </div>
            `;
        }
    }

    validateForm(e) {
        let isValid = true;

        // Clear previous errors
        document.querySelectorAll('.input-wrapper').forEach(el => {
            el.classList.remove('error');
        });

        // Validate required fields
        const requiredFields = this.form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.closest('.input-wrapper').classList.add('error');
                isValid = false;
            }
        });

        // Validate price
        const priceField = this.form.querySelector('input[name="price"]');
        if (priceField) {
            const priceValue = parseFloat(priceField.value);
            if (isNaN(priceValue) || priceValue < 0) {
                priceField.closest('.input-wrapper').classList.add('error');
                isValid = false;
            }
        }

        // Validate URL format if provided
        if (this.imageUrlInput && this.imageUrlInput.value.trim() && !this.isValidUrl(this.imageUrlInput.value)) {
            this.imageUrlInput.closest('.input-wrapper').classList.add('error');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            this.showNotification('Please fill in all required fields correctly.', 'error');
        } else {
            // Show loading state
            const submitBtn = this.form.querySelector('.btn-submit');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;

                // Re-enable after 3 seconds if form doesn't submit
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 3000);
            }
        }
    }

    initGenreSuggestions() {
        const genreInput = document.querySelector('input[name="genre"]');
        if (!genreInput) return;

        const genreSuggestions = [
            'Action', 'Adventure', 'RPG', 'Strategy', 'Simulation',
            'Sports', 'Racing', 'Puzzle', 'Horror', 'FPS',
            'Indie', 'Casual', 'Arcade', 'Platformer', 'Fighting',
            'Shooter', 'Survival', 'Battle Royale', 'MMO', 'MOBA'
        ];

        genreInput.addEventListener('focus', function () {
            if (!this.hasAttribute('list')) {
                const datalist = document.createElement('datalist');
                datalist.id = 'genreSuggestions';

                genreSuggestions.forEach(genre => {
                    const option = document.createElement('option');
                    option.value = genre;
                    datalist.appendChild(option);
                });

                document.body.appendChild(datalist);
                this.setAttribute('list', 'genreSuggestions');
            }
        });
    }

    initPriceFormatting() {
        const priceInput = document.querySelector('input[name="price"]');
        if (!priceInput) return;

        priceInput.addEventListener('blur', function () {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = Math.round(value);
                // Format with thousand separators for display
                this.value = new Intl.NumberFormat('id-ID').format(this.value);
            }
        });

        priceInput.addEventListener('focus', function () {
            // Remove formatting when focused
            this.value = this.value.replace(/[^\d]/g, '');
        });
    }

    initStatusBadges() {
        const statusBadges = document.querySelectorAll('.status-badge');
        statusBadges.forEach(badge => {
            badge.addEventListener('click', function () {
                // Remove active class from all badges
                statusBadges.forEach(b => b.classList.remove('active'));

                // Add active class to clicked badge
                this.classList.add('active');

                // Check the radio input
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            });
        });
    }

    isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' :
                type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        // Add to DOM
        document.body.appendChild(notification);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => notification.remove(), 300);
        }, 5000);

        // Add close button functionality
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.style.cssText = `
            background: none;
            border: none;
            color: inherit;
            cursor: pointer;
            margin-left: auto;
            font-size: 14px;
            opacity: 0.7;
            transition: opacity 0.2s;
        `;
        closeBtn.addEventListener('click', () => {
            notification.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => notification.remove(), 300);
        });
        closeBtn.addEventListener('mouseenter', () => {
            closeBtn.style.opacity = '1';
        });
        closeBtn.addEventListener('mouseleave', () => {
            closeBtn.style.opacity = '0.7';
        });

        notification.appendChild(closeBtn);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    // Initialize game form if exists
    if (document.getElementById('gameForm')) {
        window.gameForm = new GameForm();
    }

    // Auto-populate URL parameters for edit forms
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('msg')) {
        const msg = urlParams.get('msg');
        const messages = {
            'added': 'Game added successfully!',
            'updated': 'Game updated successfully!',
            'deleted': 'Game deleted successfully!',
            'error': 'An error occurred. Please try again.'
        };

        if (messages[msg]) {
            const type = msg === 'error' ? 'error' : 'success';
            if (window.gameForm) {
                window.gameForm.showNotification(messages[msg], type);
            }

            // Remove message from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }
});

// Function untuk preview screenshots baru
function previewScreenshots(input) {
    const previewContainer = document.getElementById('screenshotsPreview');
    const previewGrid = document.getElementById('previewGrid');
    const countSpan = document.getElementById('screenshotsCount');

    // Clear previous previews
    previewGrid.innerHTML = '';

    if (input.files && input.files.length > 0) {
        const files = Array.from(input.files);

        // Limit to 5 files
        if (files.length > 5) {
            alert('Maximum 5 screenshots allowed. Only the first 5 will be uploaded.');
            files.length = 5;
            input.files = new DataTransfer().files; // Reset files
        }

        countSpan.textContent = files.length + ' file(s) selected';
        countSpan.style.color = '#66c0f4';

        files.forEach((file, index) => {
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                return;
            }

            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                alert(`File "${file.name}" is not a valid image type.`);
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'screenshot-preview-item';
                previewItem.style.position = 'relative';
                previewItem.style.background = '#2a2f3a';
                previewItem.style.borderRadius = '8px';
                previewItem.style.padding = '10px';
                previewItem.style.border = '1px solid #3d4452';

                previewItem.innerHTML = `
                    <div style="position: relative;">
                        <img src="${e.target.result}" 
                             alt="Preview ${index + 1}"
                             style="width: 100%; height: 100px; object-fit: cover; border-radius: 5px;">
                        <div style="position: absolute; top: 5px; right: 5px; background: rgba(239, 68, 68, 0.9); 
                                    color: white; width: 25px; height: 25px; border-radius: 50%;
                                    display: flex; align-items: center; justify-content: center;
                                    cursor: pointer; font-size: 14px; font-weight: bold;" 
                             onclick="removeScreenshotPreview(this, ${index})">
                            ×
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <input type="text" 
                               name="new_screenshot_captions[]" 
                               placeholder="Caption (optional)"
                               style="width: 100%; padding: 8px; background: #1b2838; border: 1px solid #3d4452; 
                                      border-radius: 4px; color: #c7d5e0; font-size: 13px;">
                        <input type="hidden" name="new_screenshot_order[]" value="${index + 1}">
                    </div>
                `;
                previewGrid.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });

        previewContainer.style.display = 'block';
    } else {
        countSpan.textContent = 'No files chosen';
        countSpan.style.color = '#8f98a0';
        previewContainer.style.display = 'none';
    }
}

// Function untuk remove screenshot preview
function removeScreenshotPreview(button, index) {
    const input = document.getElementById('screenshotsInput');
    const dt = new DataTransfer();
    const files = Array.from(input.files);

    // Remove file from files array
    files.splice(index, 1);

    // Update input files
    files.forEach(file => dt.items.add(file));
    input.files = dt.files;

    // Remove preview
    button.closest('.screenshot-preview-item').remove();

    // Update count
    const countSpan = document.getElementById('screenshotsCount');
    const remainingPreviews = document.querySelectorAll('.screenshot-preview-item').length;

    if (remainingPreviews === 0) {
        countSpan.textContent = 'No files chosen';
        document.getElementById('screenshotsPreview').style.display = 'none';
    } else {
        countSpan.textContent = remainingPreviews + ' file(s) selected';
    }

    // Re-index all previews
    document.querySelectorAll('.screenshot-preview-item').forEach((item, newIndex) => {
        const removeBtn = item.querySelector('div[onclick^="removeScreenshotPreview"]');
        if (removeBtn) {
            removeBtn.setAttribute('onclick', `removeScreenshotPreview(this, ${newIndex})`);
        }
    });
}

// Delete checkbox functionality
document.querySelectorAll('input[name^="delete_screenshots"]').forEach(checkbox => {
    checkbox.addEventListener('change', function () {
        const item = this.closest('div[style*="background: #2a2f3a"]');
        if (this.checked) {
            item.style.opacity = '0.6';
            item.style.borderColor = '#ef4444';
        } else {
            item.style.opacity = '1';
            item.style.borderColor = '#3d4452';
        }
    });
});

