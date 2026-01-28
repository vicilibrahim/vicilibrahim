/**
 * Instagram Planner JavaScript
 * Terra+ Instagram Content Management System
 */

// Global state
let selectedHashtags = [];
let carouselItems = [];
let currentPostType = 'feed';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initializeMobileMenu();
    initializeFormHandlers();
    initializeMediaUploads();
    initializeHashtagInput();
    initializeScheduleOptions();
    initializeCaptionCounter();
    initializeFilters();
});

/**
 * Mobile Menu Toggle
 */
function initializeMobileMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.querySelector('.ig-sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        // Close sidebar on outside click
        document.addEventListener('click', function(e) {
            if (sidebar.classList.contains('active') &&
                !sidebar.contains(e.target) &&
                e.target !== menuToggle) {
                sidebar.classList.remove('active');
            }
        });
    }
}

/**
 * Form Handlers
 */
function initializeFormHandlers() {
    const form = document.getElementById('createPostForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }

    // Add highlight checkbox handler
    const highlightCheckbox = document.querySelector('input[name="add_to_highlight"]');
    const highlightNameInput = document.getElementById('highlightName');
    if (highlightCheckbox && highlightNameInput) {
        highlightCheckbox.addEventListener('change', function() {
            highlightNameInput.style.display = this.checked ? 'block' : 'none';
        });
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    // Show loading state
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
    submitBtn.disabled = true;

    try {
        // Update hidden hashtags field
        document.getElementById('hashtagsHidden').value = selectedHashtags.join(',');

        const formData = new FormData(form);

        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showNotification('success', result.message || 'Icerik kaydedildi');
            setTimeout(() => {
                window.location.href = 'posts.php';
            }, 1500);
        } else {
            throw new Error(result.error || 'Bir hata olustu');
        }
    } catch (error) {
        showNotification('error', error.message);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

/**
 * Media Uploads
 */
function initializeMediaUploads() {
    // Feed upload
    const feedInput = document.getElementById('feedMediaInput');
    if (feedInput) {
        feedInput.addEventListener('change', function(e) {
            handleFeedUpload(e.target.files[0]);
        });
    }

    // Carousel upload
    const carouselInput = document.getElementById('carouselMediaInput');
    if (carouselInput) {
        carouselInput.addEventListener('change', function(e) {
            handleCarouselUpload(e.target.files);
        });
    }

    // Story upload
    const storyInput = document.getElementById('storyMediaInput');
    if (storyInput) {
        storyInput.addEventListener('change', function(e) {
            handleStoryUpload(e.target.files[0]);
        });
    }

    // Reel upload
    const reelInput = document.getElementById('reelMediaInput');
    if (reelInput) {
        reelInput.addEventListener('change', function(e) {
            handleReelUpload(e.target.files[0]);
        });
    }

    // Drag and drop
    initializeDragDrop();
}

function handleFeedUpload(file) {
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const placeholder = document.querySelector('#feedUpload .ig-upload-placeholder');
        const preview = document.getElementById('feedPreviewContainer');
        const previewImage = document.getElementById('feedPreviewImage');
        const mockContainer = document.getElementById('mockMediaContainer');

        placeholder.style.display = 'none';
        preview.style.display = 'block';
        previewImage.src = e.target.result;

        // Update phone mock preview
        if (mockContainer) {
            mockContainer.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        }
    };
    reader.readAsDataURL(file);
}

function handleCarouselUpload(files) {
    const container = document.getElementById('carouselItems');
    const addBtn = container.querySelector('.ig-carousel-add');

    Array.from(files).forEach((file, index) => {
        if (carouselItems.length >= 10) {
            showNotification('warning', 'Maximum 10 gorsel ekleyebilirsiniz');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const item = document.createElement('div');
            item.className = 'ig-carousel-item';
            item.innerHTML = `
                <img src="${e.target.result}" alt="Slide ${carouselItems.length + 1}">
                <button type="button" class="ig-carousel-remove" onclick="removeCarouselItem(${carouselItems.length})">
                    <i class="fas fa-times"></i>
                </button>
                <span class="ig-carousel-index">${carouselItems.length + 1}</span>
            `;

            container.insertBefore(item, addBtn);
            carouselItems.push({ file, dataUrl: e.target.result });
        };
        reader.readAsDataURL(file);
    });
}

function removeCarouselItem(index) {
    carouselItems.splice(index, 1);
    renderCarouselItems();
}

function renderCarouselItems() {
    const container = document.getElementById('carouselItems');
    const addBtn = container.querySelector('.ig-carousel-add');

    // Remove existing items
    container.querySelectorAll('.ig-carousel-item').forEach(item => item.remove());

    // Re-render
    carouselItems.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'ig-carousel-item';
        div.innerHTML = `
            <img src="${item.dataUrl}" alt="Slide ${index + 1}">
            <button type="button" class="ig-carousel-remove" onclick="removeCarouselItem(${index})">
                <i class="fas fa-times"></i>
            </button>
            <span class="ig-carousel-index">${index + 1}</span>
        `;
        container.insertBefore(div, addBtn);
    });
}

function handleStoryUpload(file) {
    if (!file) return;

    const placeholder = document.querySelector('#storyUpload .ig-upload-placeholder');
    const preview = document.getElementById('storyPreviewContainer');
    const previewImage = document.getElementById('storyPreviewImage');
    const previewVideo = document.getElementById('storyPreviewVideo');

    if (file.type.startsWith('video/')) {
        previewImage.style.display = 'none';
        previewVideo.style.display = 'block';
        previewVideo.src = URL.createObjectURL(file);
    } else {
        previewVideo.style.display = 'none';
        previewImage.style.display = 'block';

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    placeholder.style.display = 'none';
    preview.style.display = 'block';
}

function handleReelUpload(file) {
    if (!file) return;

    if (!file.type.startsWith('video/')) {
        showNotification('error', 'Reel icin video dosyasi secin');
        return;
    }

    const placeholder = document.querySelector('#reelUpload .ig-upload-placeholder');
    const preview = document.getElementById('reelPreviewContainer');
    const previewVideo = document.getElementById('reelPreviewVideo');

    previewVideo.src = URL.createObjectURL(file);
    placeholder.style.display = 'none';
    preview.style.display = 'block';
}

function removeMedia(type) {
    const placeholder = document.querySelector(`#${type}Upload .ig-upload-placeholder`);
    const preview = document.getElementById(`${type}PreviewContainer`);
    const input = document.getElementById(`${type}MediaInput`);

    placeholder.style.display = 'flex';
    preview.style.display = 'none';
    input.value = '';

    // Reset mock preview
    const mockContainer = document.getElementById('mockMediaContainer');
    if (mockContainer) {
        mockContainer.innerHTML = '<i class="fas fa-image"></i>';
    }
}

function initializeDragDrop() {
    const uploadAreas = document.querySelectorAll('.ig-upload-area');

    uploadAreas.forEach(area => {
        area.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        area.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });

        area.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            const input = this.querySelector('input[type="file"]');

            if (input && files.length > 0) {
                // Create new FileList
                const dt = new DataTransfer();
                Array.from(files).forEach(file => dt.items.add(file));
                input.files = dt.files;

                // Trigger change event
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}

/**
 * Post Type Switcher
 */
function switchPostType(type) {
    currentPostType = type;

    // Update buttons
    document.querySelectorAll('.ig-type-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.type === type) {
            btn.classList.add('active');
        }
    });

    // Update hidden field
    document.getElementById('postType').value = type;

    // Show/hide upload areas
    document.querySelectorAll('.ig-upload-area').forEach(area => {
        area.style.display = 'none';
    });
    document.getElementById(`${type}Upload`).style.display = 'block';

    // Show/hide type specific options
    document.querySelectorAll('.ig-reel-options, .ig-story-options').forEach(el => {
        el.style.display = 'none';
    });

    if (type === 'reel') {
        document.querySelector('.ig-reel-options').style.display = 'block';
    } else if (type === 'story') {
        document.querySelector('.ig-story-options').style.display = 'block';
    }

    // Update submit button text
    const submitBtnText = document.getElementById('submitBtnText');
    if (submitBtnText) {
        submitBtnText.textContent = type === 'story' ? 'Story Paylas' : 'Paylas';
    }
}

/**
 * Hashtag Input
 */
function initializeHashtagInput() {
    const input = document.getElementById('hashtagInput');
    if (!input) return;

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            const value = this.value.trim();
            if (value) {
                addHashtag(value);
                this.value = '';
            }
        }
    });

    input.addEventListener('blur', function() {
        const value = this.value.trim();
        if (value) {
            addHashtag(value);
            this.value = '';
        }
    });
}

function addHashtag(tag) {
    // Normalize hashtag
    tag = tag.replace(/^#/, '').trim();
    if (!tag) return;

    const hashtag = '#' + tag.toLowerCase();

    // Check limit
    if (selectedHashtags.length >= 30) {
        showNotification('warning', 'Maximum 30 hashtag ekleyebilirsiniz');
        return;
    }

    // Check duplicate
    if (selectedHashtags.includes(hashtag)) {
        showNotification('warning', 'Bu hashtag zaten ekli');
        return;
    }

    selectedHashtags.push(hashtag);
    renderHashtags();
    updateHashtagCount();
}

function removeHashtag(index) {
    selectedHashtags.splice(index, 1);
    renderHashtags();
    updateHashtagCount();
}

function renderHashtags() {
    const container = document.getElementById('selectedHashtags');
    if (!container) return;

    container.innerHTML = selectedHashtags.map((tag, index) => `
        <span class="ig-hashtag-tag">
            ${tag}
            <span class="remove" onclick="removeHashtag(${index})"><i class="fas fa-times"></i></span>
        </span>
    `).join('');
}

function updateHashtagCount() {
    const countEl = document.getElementById('hashtagCount');
    if (countEl) {
        countEl.textContent = selectedHashtags.length;
    }
}

/**
 * Schedule Options
 */
function initializeScheduleOptions() {
    const radios = document.querySelectorAll('input[name="schedule_type"]');
    const datetimeSection = document.getElementById('scheduleDatetime');

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (datetimeSection) {
                datetimeSection.style.display = this.value === 'schedule' ? 'block' : 'none';
            }

            // Update submit button
            const submitBtnText = document.getElementById('submitBtnText');
            if (submitBtnText) {
                if (this.value === 'draft') {
                    submitBtnText.textContent = 'Taslak Kaydet';
                } else if (this.value === 'schedule') {
                    submitBtnText.textContent = 'Zamanla';
                } else {
                    submitBtnText.textContent = 'Simdi Paylas';
                }
            }
        });
    });

    // Set minimum datetime to now
    const datetimeInput = document.getElementById('scheduledAt');
    if (datetimeInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        datetimeInput.min = now.toISOString().slice(0, 16);
    }
}

/**
 * Caption Counter
 */
function initializeCaptionCounter() {
    const captionInput = document.getElementById('captionInput');
    const captionCount = document.getElementById('captionCount');
    const mockCaptionText = document.getElementById('mockCaptionText');

    if (captionInput && captionCount) {
        captionInput.addEventListener('input', function() {
            captionCount.textContent = this.value.length;

            // Update mock preview
            if (mockCaptionText) {
                mockCaptionText.textContent = this.value || 'Caption burada gorunecek...';
            }
        });
    }
}

/**
 * AI Content Generation
 */
async function generateAICaption() {
    const modal = document.getElementById('aiCaptionModal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

async function generateCaption() {
    const topic = document.getElementById('aiCaptionTopic').value;
    const tone = document.getElementById('aiCaptionTone').value;
    const length = document.getElementById('aiCaptionLength').value;

    if (!topic) {
        showNotification('error', 'Konu giriniz');
        return;
    }

    const resultDiv = document.getElementById('aiCaptionResult');
    const outputDiv = document.getElementById('aiCaptionOutput');

    resultDiv.style.display = 'none';
    showNotification('info', 'AI caption olusturuyor...');

    try {
        const response = await fetch('api/ai-generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'generate_caption',
                topic: topic,
                tone: tone,
                length: length,
                post_type: currentPostType
            })
        });

        const result = await response.json();

        if (result.success) {
            outputDiv.innerHTML = `<pre>${result.content}</pre>`;
            resultDiv.style.display = 'block';
        } else {
            throw new Error(result.error || 'AI hatasi');
        }
    } catch (error) {
        showNotification('error', error.message);
    }
}

function useAICaption() {
    const output = document.getElementById('aiCaptionOutput');
    const captionInput = document.getElementById('captionInput');

    // Extract caption from output (assuming CAPTION: format)
    const content = output.textContent;
    const captionMatch = content.match(/CAPTION:\s*([\s\S]*?)(?:FIRST_COMMENT:|SUGGESTED_HASHTAGS:|$)/);

    if (captionMatch && captionMatch[1]) {
        captionInput.value = captionMatch[1].trim();
        captionInput.dispatchEvent(new Event('input'));
    } else {
        captionInput.value = content.trim();
        captionInput.dispatchEvent(new Event('input'));
    }

    closeModal('aiCaptionModal');
    showNotification('success', 'Caption eklendi');
}

async function optimizeCaption() {
    const captionInput = document.getElementById('captionInput');
    const caption = captionInput.value;

    if (!caption) {
        showNotification('error', 'Optimize edilecek caption giriniz');
        return;
    }

    showNotification('info', 'Caption optimize ediliyor...');

    try {
        const response = await fetch('api/ai-generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'optimize_caption',
                caption: caption
            })
        });

        const result = await response.json();

        if (result.success) {
            const optimizedMatch = result.content.match(/OPTIMIZED_CAPTION:\s*([\s\S]*?)(?:CHANGES_MADE:|$)/);
            if (optimizedMatch && optimizedMatch[1]) {
                captionInput.value = optimizedMatch[1].trim();
                captionInput.dispatchEvent(new Event('input'));
                showNotification('success', 'Caption optimize edildi');
            }
        } else {
            throw new Error(result.error || 'Optimizasyon hatasi');
        }
    } catch (error) {
        showNotification('error', error.message);
    }
}

async function generateAIHashtags() {
    const captionInput = document.getElementById('captionInput');
    const caption = captionInput?.value || '';

    if (!caption) {
        showNotification('warning', 'Hashtag onerisi icin caption yazin');
        return;
    }

    showNotification('info', 'Hashtagler olusturuluyor...');

    try {
        const response = await fetch('api/ai-generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'generate_hashtags',
                topic: caption.substring(0, 200)
            })
        });

        const result = await response.json();

        if (result.success && result.all_hashtags) {
            // Add hashtags (max 30)
            const newHashtags = result.all_hashtags.slice(0, 30 - selectedHashtags.length);
            newHashtags.forEach(tag => {
                if (!selectedHashtags.includes(tag)) {
                    selectedHashtags.push(tag);
                }
            });
            renderHashtags();
            updateHashtagCount();
            showNotification('success', `${newHashtags.length} hashtag eklendi`);
        } else {
            throw new Error(result.error || 'Hashtag olusturulamadi');
        }
    } catch (error) {
        showNotification('error', error.message);
    }
}

async function generateReelHook() {
    const captionInput = document.getElementById('captionInput');
    const hookInput = document.getElementById('hookTextInput');
    const topic = captionInput?.value || '';

    if (!topic) {
        showNotification('warning', 'Hook icin konu/caption yazin');
        return;
    }

    showNotification('info', 'Reel hook olusturuluyor...');

    try {
        const response = await fetch('api/ai-generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'generate_reel_hook',
                topic: topic.substring(0, 200)
            })
        });

        const result = await response.json();

        if (result.success) {
            const hookMatch = result.content.match(/HOOK_TEXT:\s*(.*?)(?:\n|$)/);
            if (hookMatch && hookMatch[1]) {
                hookInput.value = hookMatch[1].trim();
                showNotification('success', 'Hook eklendi');
            }
        } else {
            throw new Error(result.error || 'Hook olusturulamadi');
        }
    } catch (error) {
        showNotification('error', error.message);
    }
}

/**
 * AI Calendar Generator
 */
async function generateAICalendar() {
    const postsPerWeek = document.getElementById('aiPostsPerWeek').value;
    const focusTopics = document.getElementById('aiFocusTopics').value;
    const weeksCount = document.getElementById('aiWeeksCount').value;

    showNotification('info', 'Takvim olusturuluyor...');

    try {
        const response = await fetch('api/ai-generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'generate_calendar',
                posts_per_week: parseInt(postsPerWeek),
                weeks: parseInt(weeksCount),
                focus_topics: focusTopics,
                include_reels: document.querySelector('input[name="include_reels"]')?.checked,
                include_stories: document.querySelector('input[name="include_stories"]')?.checked
            })
        });

        const result = await response.json();

        if (result.success) {
            const previewDiv = document.getElementById('aiCalendarPreview');
            const resultDiv = document.getElementById('aiCalendarResult');

            previewDiv.innerHTML = formatCalendarPreview(result.calendar || result.content);
            resultDiv.style.display = 'block';
            showNotification('success', 'Takvim olusturuldu');
        } else {
            throw new Error(result.error || 'Takvim olusturulamadi');
        }
    } catch (error) {
        showNotification('error', error.message);
    }
}

function formatCalendarPreview(calendar) {
    if (typeof calendar === 'string') {
        return `<pre>${calendar}</pre>`;
    }

    let html = '<div class="ig-calendar-ai-preview">';
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const dayNames = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar'];

    days.forEach((day, index) => {
        if (calendar[day]) {
            const post = calendar[day];
            html += `
                <div class="ig-ai-day">
                    <strong>${dayNames[index]}</strong>
                    <span class="ig-ai-type">${post.type}</span>
                    <span class="ig-ai-category">${post.category}</span>
                    <p>${post.topic || ''}</p>
                    <small>${post.time}</small>
                </div>
            `;
        }
    });

    html += '</div>';
    return html;
}

async function applyAICalendar() {
    // This would create the posts in the database
    showNotification('info', 'Takvim uygulanacak...');
    closeModal('aiCalendarModal');
    // Redirect to calendar with the generated posts
    window.location.href = 'calendar.php';
}

/**
 * Filter Functionality
 */
function initializeFilters() {
    // Type filters
    document.querySelectorAll('.ig-filter-btn[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ig-filter-btn[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterByType(this.dataset.filter);
        });
    });

    // Status filters
    document.querySelectorAll('.ig-filter-btn[data-status]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ig-filter-btn[data-status]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterByStatus(this.dataset.status);
        });
    });
}

function filterByType(type) {
    document.querySelectorAll('.ig-calendar-post').forEach(post => {
        if (type === 'all' || post.dataset.type === type) {
            post.style.display = '';
        } else {
            post.style.display = 'none';
        }
    });
}

function filterByStatus(status) {
    document.querySelectorAll('.ig-calendar-post').forEach(post => {
        if (status === 'all' || post.dataset.status === status) {
            post.style.display = '';
        } else {
            post.style.display = 'none';
        }
    });
}

/**
 * Post Detail Modal
 */
function renderPostDetail(post) {
    const container = document.getElementById('postDetailContent');

    const thumbnail = post.media && post.media.length > 0 ? post.media[0].media_url : 'assets/images/placeholder.jpg';

    container.innerHTML = `
        <div class="ig-post-detail">
            <div class="ig-post-detail-media">
                <img src="${thumbnail}" alt="Post">
                ${post.media && post.media.length > 1 ? `<span class="ig-carousel-indicator">${post.media.length} gorsel</span>` : ''}
            </div>
            <div class="ig-post-detail-info">
                <div class="ig-post-detail-header">
                    <span class="ig-post-type-badge">
                        ${getPostTypeIcon(post.post_type)} ${post.post_type}
                    </span>
                    <span class="ig-post-status status-${post.status}">
                        ${getStatusText(post.status)}
                    </span>
                </div>
                <p class="ig-post-caption">${post.caption || 'Captionsiz'}</p>
                ${post.first_comment ? `<p class="ig-first-comment"><small>Ilk yorum: ${post.first_comment}</small></p>` : ''}
                ${post.hashtags && post.hashtags.length > 0 ? `
                    <div class="ig-post-hashtags">
                        ${post.hashtags.map(h => `<span class="ig-hashtag-tag">${h.hashtag}</span>`).join('')}
                    </div>
                ` : ''}
                <div class="ig-post-detail-meta">
                    <span><i class="fas fa-calendar"></i> ${formatDate(post.scheduled_at || post.created_at)}</span>
                    ${post.location_name ? `<span><i class="fas fa-map-marker-alt"></i> ${post.location_name}</span>` : ''}
                </div>
                ${post.status === 'published' ? `
                    <div class="ig-post-detail-stats">
                        <div class="ig-stat"><i class="fas fa-heart"></i> ${post.likes || 0}</div>
                        <div class="ig-stat"><i class="fas fa-comment"></i> ${post.comments || 0}</div>
                        <div class="ig-stat"><i class="fas fa-bookmark"></i> ${post.saves || 0}</div>
                        <div class="ig-stat"><i class="fas fa-eye"></i> ${post.reach || 0}</div>
                    </div>
                ` : ''}
                <div class="ig-post-detail-actions">
                    <a href="edit-post.php?id=${post.id}" class="ig-btn ig-btn-outline">
                        <i class="fas fa-edit"></i> Duzenle
                    </a>
                    ${post.status === 'draft' || post.status === 'scheduled' ? `
                        <button class="ig-btn ig-btn-danger" onclick="deletePost(${post.id})">
                            <i class="fas fa-trash"></i> Sil
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

function getPostTypeIcon(type) {
    const icons = {
        feed: '<i class="fas fa-image"></i>',
        carousel: '<i class="fas fa-images"></i>',
        story: '<i class="fas fa-circle-dot"></i>',
        reel: '<i class="fas fa-film"></i>'
    };
    return icons[type] || icons.feed;
}

function getStatusText(status) {
    const texts = {
        draft: '<i class="fas fa-edit"></i> Taslak',
        scheduled: '<i class="fas fa-clock"></i> Planli',
        published: '<i class="fas fa-check"></i> Yayinda',
        failed: '<i class="fas fa-exclamation-triangle"></i> Hata'
    };
    return texts[status] || status;
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('tr-TR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

async function deletePost(postId) {
    if (!confirm('Bu icerigi silmek istediginize emin misiniz?')) {
        return;
    }

    try {
        const response = await fetch('api/delete-post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('success', 'Icerik silindi');
            closeModal('postDetailModal');
            location.reload();
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        showNotification('error', error.message);
    }
}

/**
 * Draft Save
 */
function saveDraft() {
    const scheduleRadios = document.querySelectorAll('input[name="schedule_type"]');
    scheduleRadios.forEach(radio => {
        if (radio.value === 'draft') {
            radio.checked = true;
        }
    });

    document.getElementById('createPostForm').dispatchEvent(new Event('submit'));
}

/**
 * Modal Functions
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modals on outside click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('ig-modal')) {
        e.target.style.display = 'none';
    }
});

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.ig-modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

/**
 * Notifications
 */
function showNotification(type, message) {
    // Remove existing notifications
    document.querySelectorAll('.ig-notification').forEach(n => n.remove());

    const notification = document.createElement('div');
    notification.className = `ig-notification ig-notification-${type}`;

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    notification.innerHTML = `
        <i class="fas ${icons[type]}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;

    // Add styles inline if not in CSS
    notification.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        padding: 16px 24px;
        border-radius: 10px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .ig-notification button {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        margin-left: 12px;
        opacity: 0.7;
    }
    .ig-notification button:hover { opacity: 1; }
`;
document.head.appendChild(style);
