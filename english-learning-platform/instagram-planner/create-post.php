<?php
$page_title = 'Yeni Icerik Olustur';
require_once 'includes/header.php';

if (!$current_account) {
    header('Location: connect.php');
    exit;
}

$post_type = isset($_GET['type']) ? $_GET['type'] : 'feed';
$content_categories = ig_get_content_categories();
$viral_formats = ig_get_viral_formats($current_account['niche']);
$trending_audio = ig_get_trending_audio(10);

// Get existing hashtags for suggestions
$suggested_hashtags = ig_get_hashtags_by_category(null, $current_account['niche'], 30);
?>

<div class="ig-create-post">
    <!-- Post Type Selector -->
    <div class="ig-post-type-selector">
        <button class="ig-type-btn <?php echo $post_type === 'feed' ? 'active' : ''; ?>" data-type="feed" onclick="switchPostType('feed')">
            <i class="fas fa-image"></i>
            <span>Feed Post</span>
        </button>
        <button class="ig-type-btn <?php echo $post_type === 'carousel' ? 'active' : ''; ?>" data-type="carousel" onclick="switchPostType('carousel')">
            <i class="fas fa-images"></i>
            <span>Carousel</span>
        </button>
        <button class="ig-type-btn <?php echo $post_type === 'story' ? 'active' : ''; ?>" data-type="story" onclick="switchPostType('story')">
            <i class="fas fa-circle-dot"></i>
            <span>Story</span>
        </button>
        <button class="ig-type-btn <?php echo $post_type === 'reel' ? 'active' : ''; ?>" data-type="reel" onclick="switchPostType('reel')">
            <i class="fas fa-film"></i>
            <span>Reel</span>
        </button>
    </div>

    <form id="createPostForm" action="api/save-post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="post_type" id="postType" value="<?php echo htmlspecialchars($post_type); ?>">
        <input type="hidden" name="account_id" value="<?php echo $current_account['id']; ?>">

        <div class="ig-create-layout">
            <!-- Left: Media Upload & Preview -->
            <div class="ig-create-media">
                <div class="ig-media-preview" id="mediaPreview">
                    <!-- Feed Post Upload -->
                    <div class="ig-upload-area" id="feedUpload" style="<?php echo $post_type !== 'feed' ? 'display:none;' : ''; ?>">
                        <div class="ig-upload-placeholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Fotograf yukle veya surukle birak</p>
                            <span>1:1 veya 4:5 oran onerilir</span>
                            <input type="file" name="media[]" id="feedMediaInput" accept="image/*" class="ig-file-input">
                            <button type="button" class="ig-btn ig-btn-outline" onclick="document.getElementById('feedMediaInput').click()">
                                Dosya Sec
                            </button>
                        </div>
                        <div class="ig-preview-container" id="feedPreviewContainer" style="display:none;">
                            <img id="feedPreviewImage" src="" alt="Preview">
                            <button type="button" class="ig-remove-media" onclick="removeMedia('feed')">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Carousel Upload -->
                    <div class="ig-upload-area ig-carousel-upload" id="carouselUpload" style="<?php echo $post_type !== 'carousel' ? 'display:none;' : ''; ?>">
                        <div class="ig-carousel-items" id="carouselItems">
                            <div class="ig-carousel-add" onclick="document.getElementById('carouselMediaInput').click()">
                                <i class="fas fa-plus"></i>
                                <span>Ekle</span>
                            </div>
                        </div>
                        <input type="file" name="media[]" id="carouselMediaInput" accept="image/*,video/*" multiple class="ig-file-input">
                        <p class="ig-carousel-info">
                            <i class="fas fa-info-circle"></i>
                            Maximum 10 gorsel/video ekleyebilirsiniz
                        </p>
                    </div>

                    <!-- Story Upload -->
                    <div class="ig-upload-area ig-story-upload" id="storyUpload" style="<?php echo $post_type !== 'story' ? 'display:none;' : ''; ?>">
                        <div class="ig-story-frame">
                            <div class="ig-upload-placeholder">
                                <i class="fas fa-circle-dot"></i>
                                <p>Story icin gorsel/video yukle</p>
                                <span>9:16 oran (1080x1920px)</span>
                                <input type="file" name="media[]" id="storyMediaInput" accept="image/*,video/*" class="ig-file-input">
                                <button type="button" class="ig-btn ig-btn-outline" onclick="document.getElementById('storyMediaInput').click()">
                                    Dosya Sec
                                </button>
                            </div>
                            <div class="ig-preview-container" id="storyPreviewContainer" style="display:none;">
                                <img id="storyPreviewImage" src="" alt="Preview">
                                <video id="storyPreviewVideo" src="" style="display:none;"></video>
                                <button type="button" class="ig-remove-media" onclick="removeMedia('story')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Reel Upload -->
                    <div class="ig-upload-area ig-reel-upload" id="reelUpload" style="<?php echo $post_type !== 'reel' ? 'display:none;' : ''; ?>">
                        <div class="ig-reel-frame">
                            <div class="ig-upload-placeholder">
                                <i class="fas fa-film"></i>
                                <p>Reel videosu yukle</p>
                                <span>9:16 oran, max 90 saniye</span>
                                <input type="file" name="media[]" id="reelMediaInput" accept="video/*" class="ig-file-input">
                                <button type="button" class="ig-btn ig-btn-outline" onclick="document.getElementById('reelMediaInput').click()">
                                    Video Sec
                                </button>
                            </div>
                            <div class="ig-preview-container" id="reelPreviewContainer" style="display:none;">
                                <video id="reelPreviewVideo" src="" controls></video>
                                <button type="button" class="ig-remove-media" onclick="removeMedia('reel')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Instagram Preview Mock -->
                <div class="ig-device-preview">
                    <div class="ig-phone-frame">
                        <div class="ig-phone-header">
                            <span class="ig-phone-time">09:41</span>
                            <span class="ig-phone-notch"></span>
                            <span class="ig-phone-icons">
                                <i class="fas fa-signal"></i>
                                <i class="fas fa-wifi"></i>
                                <i class="fas fa-battery-full"></i>
                            </span>
                        </div>
                        <div class="ig-phone-content">
                            <div class="ig-post-preview-mock">
                                <div class="ig-post-header-mock">
                                    <img src="<?php echo htmlspecialchars($current_account['profile_picture'] ?? 'assets/images/default-profile.png'); ?>" alt="Profile">
                                    <span><?php echo htmlspecialchars($current_account['username']); ?></span>
                                    <i class="fas fa-ellipsis-h"></i>
                                </div>
                                <div class="ig-post-media-mock" id="mockMediaContainer">
                                    <i class="fas fa-image"></i>
                                </div>
                                <div class="ig-post-actions-mock">
                                    <div class="ig-actions-left">
                                        <i class="far fa-heart"></i>
                                        <i class="far fa-comment"></i>
                                        <i class="far fa-paper-plane"></i>
                                    </div>
                                    <i class="far fa-bookmark"></i>
                                </div>
                                <div class="ig-post-caption-mock" id="mockCaption">
                                    <strong><?php echo htmlspecialchars($current_account['username']); ?></strong>
                                    <span id="mockCaptionText">Caption burada gorunecek...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Content Details -->
            <div class="ig-create-details">
                <!-- Caption -->
                <div class="ig-form-section">
                    <label class="ig-form-label">
                        <i class="fas fa-pen"></i> Caption
                        <span class="ig-char-count"><span id="captionCount">0</span>/2200</span>
                    </label>
                    <textarea name="caption" id="captionInput" class="ig-textarea" placeholder="Cazip bir caption yazin..." maxlength="2200"></textarea>
                    <div class="ig-ai-actions">
                        <button type="button" class="ig-btn ig-btn-ai" onclick="generateAICaption()">
                            <i class="fas fa-robot"></i> AI ile Yaz
                        </button>
                        <button type="button" class="ig-btn ig-btn-outline" onclick="optimizeCaption()">
                            <i class="fas fa-wand-magic-sparkles"></i> Optimize Et
                        </button>
                    </div>
                </div>

                <!-- Content Category -->
                <div class="ig-form-section">
                    <label class="ig-form-label">
                        <i class="fas fa-tag"></i> Icerik Kategorisi
                    </label>
                    <div class="ig-category-selector">
                        <?php foreach ($content_categories as $cat): ?>
                        <label class="ig-category-option">
                            <input type="radio" name="content_category_id" value="<?php echo $cat['id']; ?>">
                            <span class="ig-category-badge" style="background-color: <?php echo $cat['color']; ?>20; border-color: <?php echo $cat['color']; ?>; color: <?php echo $cat['color']; ?>">
                                <i class="fas <?php echo $cat['icon']; ?>"></i>
                                <?php echo htmlspecialchars($cat['name_tr']); ?>
                                <span class="ig-category-percent"><?php echo $cat['percentage']; ?>%</span>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Hashtags -->
                <div class="ig-form-section">
                    <label class="ig-form-label">
                        <i class="fas fa-hashtag"></i> Hashtag'ler
                        <span class="ig-char-count"><span id="hashtagCount">0</span>/30</span>
                    </label>
                    <div class="ig-hashtag-input-container">
                        <div class="ig-hashtag-tags" id="selectedHashtags"></div>
                        <input type="text" id="hashtagInput" class="ig-input" placeholder="Hashtag yazin ve Enter'a basin">
                        <input type="hidden" name="hashtags" id="hashtagsHidden">
                    </div>
                    <div class="ig-ai-actions">
                        <button type="button" class="ig-btn ig-btn-ai" onclick="generateAIHashtags()">
                            <i class="fas fa-robot"></i> AI Onerisi
                        </button>
                    </div>
                    <div class="ig-hashtag-suggestions" id="hashtagSuggestions">
                        <h5>Onerilen:</h5>
                        <div class="ig-suggestion-list">
                            <?php foreach (array_slice($suggested_hashtags, 0, 10) as $tag): ?>
                            <span class="ig-suggestion-tag" onclick="addHashtag('<?php echo htmlspecialchars($tag['hashtag']); ?>')">
                                <?php echo htmlspecialchars($tag['hashtag']); ?>
                                <small><?php echo ig_format_number($tag['media_count']); ?></small>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- First Comment -->
                <div class="ig-form-section">
                    <label class="ig-form-label">
                        <i class="fas fa-comment"></i> Ilk Yorum (Ekstra Hashtag)
                    </label>
                    <textarea name="first_comment" id="firstCommentInput" class="ig-textarea ig-textarea-sm" placeholder="Ekstra hashtagleri buraya ekleyin..."></textarea>
                </div>

                <!-- Location -->
                <div class="ig-form-section">
                    <label class="ig-form-label">
                        <i class="fas fa-map-marker-alt"></i> Konum
                    </label>
                    <div class="ig-location-search">
                        <input type="text" id="locationSearch" class="ig-input" placeholder="Konum ara...">
                        <input type="hidden" name="location_id" id="locationId">
                        <input type="hidden" name="location_name" id="locationName">
                        <div class="ig-location-results" id="locationResults" style="display:none;"></div>
                    </div>
                </div>

                <!-- Reel Specific Options -->
                <div class="ig-form-section ig-reel-options" style="<?php echo $post_type !== 'reel' ? 'display:none;' : ''; ?>">
                    <label class="ig-form-label">
                        <i class="fas fa-music"></i> Muzik/Ses
                    </label>
                    <div class="ig-trending-audio">
                        <h5>Trend Muzikler:</h5>
                        <div class="ig-audio-list">
                            <?php foreach ($trending_audio as $audio): ?>
                            <label class="ig-audio-option">
                                <input type="radio" name="audio_id" value="<?php echo $audio['audio_id']; ?>">
                                <span class="ig-audio-info">
                                    <i class="fas fa-music"></i>
                                    <?php echo htmlspecialchars($audio['audio_name']); ?>
                                    <small><?php echo htmlspecialchars($audio['artist'] ?? ''); ?></small>
                                </span>
                                <span class="ig-trend-score"><?php echo $audio['trend_score']; ?>/10</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <label class="ig-form-label">
                        <i class="fas fa-bolt"></i> Hook (Ilk 3 Saniye)
                    </label>
                    <textarea name="hook_text" id="hookTextInput" class="ig-textarea ig-textarea-sm" placeholder="Dikkat cekici acilis cumleniz..."></textarea>
                    <button type="button" class="ig-btn ig-btn-ai" onclick="generateReelHook()">
                        <i class="fas fa-robot"></i> AI Hook Onerisi
                    </button>

                    <label class="ig-form-label">
                        <i class="fas fa-fire"></i> Viral Format
                    </label>
                    <div class="ig-format-selector">
                        <?php foreach ($viral_formats as $format): ?>
                        <label class="ig-format-option">
                            <input type="radio" name="format_type" value="<?php echo $format['id']; ?>">
                            <span class="ig-format-info">
                                <strong><?php echo htmlspecialchars($format['name']); ?></strong>
                                <small><?php echo htmlspecialchars($format['description']); ?></small>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Story Specific Options -->
                <div class="ig-form-section ig-story-options" style="<?php echo $post_type !== 'story' ? 'display:none;' : ''; ?>">
                    <label class="ig-form-label">
                        <i class="fas fa-sticky-note"></i> Sticker
                    </label>
                    <div class="ig-sticker-selector">
                        <label class="ig-sticker-option">
                            <input type="radio" name="sticker_type" value="poll">
                            <span><i class="fas fa-chart-bar"></i> Anket</span>
                        </label>
                        <label class="ig-sticker-option">
                            <input type="radio" name="sticker_type" value="quiz">
                            <span><i class="fas fa-question-circle"></i> Quiz</span>
                        </label>
                        <label class="ig-sticker-option">
                            <input type="radio" name="sticker_type" value="countdown">
                            <span><i class="fas fa-hourglass-half"></i> Geri Sayim</span>
                        </label>
                        <label class="ig-sticker-option">
                            <input type="radio" name="sticker_type" value="question">
                            <span><i class="fas fa-comment-dots"></i> Soru</span>
                        </label>
                        <label class="ig-sticker-option">
                            <input type="radio" name="sticker_type" value="slider">
                            <span><i class="fas fa-sliders-h"></i> Slider</span>
                        </label>
                    </div>

                    <div class="ig-sticker-config" id="stickerConfig" style="display:none;"></div>

                    <label class="ig-checkbox-label">
                        <input type="checkbox" name="add_to_highlight" value="1">
                        <span><i class="fas fa-star"></i> Highlight'a Ekle</span>
                    </label>
                    <input type="text" name="highlight_name" id="highlightName" class="ig-input" placeholder="Highlight adi" style="display:none;">
                </div>

                <!-- Scheduling -->
                <div class="ig-form-section">
                    <label class="ig-form-label">
                        <i class="fas fa-clock"></i> Zamanlama
                    </label>
                    <div class="ig-schedule-options">
                        <label class="ig-schedule-option">
                            <input type="radio" name="schedule_type" value="now" checked>
                            <span><i class="fas fa-paper-plane"></i> Simdi Paylas</span>
                        </label>
                        <label class="ig-schedule-option">
                            <input type="radio" name="schedule_type" value="schedule">
                            <span><i class="fas fa-calendar-alt"></i> Zamanla</span>
                        </label>
                        <label class="ig-schedule-option">
                            <input type="radio" name="schedule_type" value="draft">
                            <span><i class="fas fa-file-alt"></i> Taslak Kaydet</span>
                        </label>
                    </div>
                    <div class="ig-schedule-datetime" id="scheduleDatetime" style="display:none;">
                        <input type="datetime-local" name="scheduled_at" id="scheduledAt" class="ig-input">
                        <div class="ig-best-times-hint">
                            <i class="fas fa-lightbulb"></i>
                            En iyi zamanlar: 10:00, 14:00, 18:00
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="ig-form-actions">
                    <button type="button" class="ig-btn ig-btn-outline" onclick="saveDraft()">
                        <i class="fas fa-save"></i> Taslak Kaydet
                    </button>
                    <button type="submit" class="ig-btn ig-btn-primary ig-btn-lg">
                        <i class="fas fa-paper-plane"></i>
                        <span id="submitBtnText">Paylas</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- AI Caption Generator Modal -->
<div class="ig-modal" id="aiCaptionModal" style="display:none;">
    <div class="ig-modal-content">
        <div class="ig-modal-header">
            <h3><i class="fas fa-robot"></i> AI Caption Olusturucu</h3>
            <button type="button" class="ig-modal-close" onclick="closeModal('aiCaptionModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ig-modal-body">
            <div class="ig-form-group">
                <label>Konu/Tema</label>
                <input type="text" id="aiCaptionTopic" class="ig-input" placeholder="Ne hakkinda yazalim?">
            </div>
            <div class="ig-form-group">
                <label>Ton</label>
                <select id="aiCaptionTone" class="ig-select">
                    <option value="professional">Profesyonel</option>
                    <option value="friendly">Samimi</option>
                    <option value="humorous">Eglenceli</option>
                    <option value="inspirational">Ilham Verici</option>
                </select>
            </div>
            <div class="ig-form-group">
                <label>Uzunluk</label>
                <select id="aiCaptionLength" class="ig-select">
                    <option value="short">Kisa (1-2 cumle)</option>
                    <option value="medium" selected>Orta (3-5 cumle)</option>
                    <option value="long">Uzun (paragraf)</option>
                </select>
            </div>
            <button type="button" class="ig-btn ig-btn-primary ig-btn-block" onclick="generateCaption()">
                <i class="fas fa-wand-magic-sparkles"></i> Olustur
            </button>
            <div class="ig-ai-result" id="aiCaptionResult" style="display:none;">
                <label>Onerilen Caption:</label>
                <div class="ig-ai-output" id="aiCaptionOutput"></div>
                <button type="button" class="ig-btn ig-btn-outline" onclick="useAICaption()">
                    <i class="fas fa-check"></i> Kullan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const postType = '<?php echo $post_type; ?>';
const accountId = <?php echo $current_account['id']; ?>;
</script>

<?php require_once 'includes/footer.php'; ?>
