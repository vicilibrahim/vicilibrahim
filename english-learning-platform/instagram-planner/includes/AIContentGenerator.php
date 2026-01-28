<?php
/**
 * AI Content Generator for Instagram
 * Terra+ Instagram Content Management System
 * Uses OpenAI GPT-4 for intelligent content generation
 */

class AIContentGenerator {
    private $api_key;
    private $model;
    private $max_tokens;
    private $api_url = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        $this->api_key = OPENAI_API_KEY;
        $this->model = OPENAI_MODEL;
        $this->max_tokens = OPENAI_MAX_TOKENS;
    }

    /**
     * Generate optimized Instagram caption
     */
    public function generateCaption($topic, $brand_info = [], $options = []) {
        $brand_name = $brand_info['name'] ?? 'Brand';
        $brand_voice = $brand_info['voice'] ?? 'professional and friendly';
        $target_audience = $brand_info['audience'] ?? 'general audience';
        $niche = $brand_info['niche'] ?? 'business';

        $post_type = $options['type'] ?? 'feed';
        $content_category = $options['category'] ?? 'Educational';
        $language = $options['language'] ?? 'tr';

        $prompt = $this->buildCaptionPrompt($topic, $brand_name, $brand_voice, $target_audience, $niche, $post_type, $content_category, $language);

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen profesyonel bir Instagram content creator ve sosyal medya uzmanissin. Instagram icin viral olabilecek, etkileyici ve optimize edilmis icerikler olusturuyorsun. Turkce iceriklerde dogal ve akici bir dil kullan.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.8
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Generate strategic hashtags
     */
    public function generateHashtags($topic, $niche, $options = []) {
        $count = $options['count'] ?? 30;
        $include_branded = $options['include_branded'] ?? false;
        $brand_hashtag = $options['brand_hashtag'] ?? '';

        $prompt = "Instagram icin '$topic' konusunda '$niche' nisindeki bir hesap icin hashtag stratejisi olustur.

KURALLAR:
1. Toplam $count hashtag olustur
2. Kategorilere ayir:
   - HIGH (5-7 adet): 1M+ post sayili populer hashtagler
   - MEDIUM (10-12 adet): 100K-1M post sayili orta seviye hashtagler
   - NICHE (10-15 adet): 100K alti spesifik hashtagler
3. Turkce ve Ingilizce karisik olabilir
4. Yasakli veya spam hashtagler KULLANMA
5. Her hashtag # ile baslamali

FORMAT:
HIGH:
#hashtag1, #hashtag2, ...

MEDIUM:
#hashtag1, #hashtag2, ...

NICHE:
#hashtag1, #hashtag2, ...

" . ($include_branded && $brand_hashtag ? "BRANDED: $brand_hashtag" : "");

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen Instagram hashtag stratejisi uzmanisin. Engagement ve reach artirmak icin optimize edilmis hashtag setleri olusturuyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ]);

        return $this->parseHashtagResponse($response);
    }

    /**
     * Generate Reel hook (first 3 seconds)
     */
    public function generateReelHook($topic, $format = null, $options = []) {
        $niche = $options['niche'] ?? 'general';
        $goal = $options['goal'] ?? 'engagement';

        $prompt = "Instagram Reels icin '$topic' konusunda dikkat cekici bir HOOK (ilk 3 saniye) olustur.

AMAÇ: $goal

HOOK TIPLERI:
1. Soru ile baslama: 'Bunu biliyor muydunuz?'
2. Sok edici istatistik: '%90'iniz bunu yanlis yapiyor'
3. POV formati: 'POV: ...'
4. Unpopular opinion: 'Populer olmayan gorus:'
5. Promise/Vaat: 'Bu 3 ipucu hayatinizi degistirecek'
6. Story baslangic: 'Nasil ... hikayem'

" . ($format ? "FORMAT: $format kullan" : "") . "

CIKTI FORMATI:
HOOK_TEXT: [Ana hook cumle]
OPENING_LINE: [Ilk konusma metni]
VISUAL_SUGGESTION: [Gorsel/hareket onerisi]
EMOTION: [Hedef duygu: merak/sok/gulus/empati]";

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen viral Reels uzmanisan. Izleyiciyi ilk 3 saniyede yakalayan, scroll durduran hooklar olusturuyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 400,
            'temperature' => 0.9
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Generate content calendar for a week
     */
    public function generateContentCalendar($brand_info, $options = []) {
        $niche = $brand_info['niche'] ?? 'business';
        $posts_per_week = $options['posts_per_week'] ?? 5;
        $include_reels = $options['include_reels'] ?? true;
        $include_stories = $options['include_stories'] ?? true;

        $strategy = "
AI ICERIK STRATEJISI:
- %40 Egitici (tips & tricks)
- %30 Urun/Hizmet showcase
- %20 Behind-the-scenes
- %10 User-generated content
";

        $prompt = "'{$brand_info['name']}' markasi icin haftalik Instagram icerik takvimi olustur.

NIS: $niche
MARKA SESI: " . ($brand_info['voice'] ?? 'profesyonel ve samimi') . "
HEDEF KITLE: " . ($brand_info['audience'] ?? 'genel') . "

$strategy

HAFTALIK PLAN ($posts_per_week post):
- Pazartesi, Carsamba, Cuma: Feed post veya Carousel
- Sali, Persembe: " . ($include_reels ? "Reel" : "Feed") . "
- Cumartesi: " . ($include_stories ? "Story serisi" : "Feed") . "
- Pazar: Dinlenme veya hafif icerik

HER GUN ICIN:
1. Icerik tipi (Feed/Carousel/Reel/Story)
2. Kategori (Egitici/Urun/BTS/UGC)
3. Konu basligi
4. Kisa aciklama
5. En iyi paylasim saati
6. Hashtag temasi

JSON FORMATI:
{
  'monday': {'type': 'feed', 'category': 'Educational', 'topic': '...', 'description': '...', 'time': '10:00', 'hashtag_theme': '...'},
  ...
}";

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen sosyal medya stratejisti ve icerik planlamacisisin. Markalara ozel, stratejik ve uygulanabilir icerik takvimleri olusturuyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1500,
            'temperature' => 0.7
        ]);

        return $this->parseCalendarResponse($response);
    }

    /**
     * Optimize existing caption
     */
    public function optimizeCaption($caption, $options = []) {
        $goal = $options['goal'] ?? 'engagement';
        $add_cta = $options['add_cta'] ?? true;
        $add_emojis = $options['add_emojis'] ?? true;
        $max_emojis = $options['max_emojis'] ?? 3;

        $prompt = "Asagidaki Instagram caption'i optimize et:

MEVCUT CAPTION:
$caption

OPTIMIZASYON KURALLARI:
1. Ilk cumle dikkat cekici HOOK olmali
2. " . ($add_emojis ? "Emoji kullan (max $max_emojis adet)" : "Emoji KULLANMA") . "
3. Line break'lerle okunabilirlik sagla
4. " . ($add_cta ? "CTA ekle (yorum yap, kaydet, paylas vb.)" : "") . "
5. Max 2200 karakter
6. Dogal ve samimi bir ton

AMAÇ: $goal (engagement/reach/save/comment)

CIKTI:
OPTIMIZED_CAPTION: [Optimize edilmis caption]
CHANGES_MADE: [Yapilan degisiklikler listesi]
IMPROVEMENT_SCORE: [1-10 arasi tahmini iyilestirme puani]";

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen Instagram copywriting uzmanisin. Captionlari engagement ve reach icin optimize ediyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 800,
            'temperature' => 0.7
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Generate carousel slide descriptions
     */
    public function generateCarouselSlides($topic, $slide_count = 5, $options = []) {
        $style = $options['style'] ?? 'educational';
        $niche = $options['niche'] ?? 'business';

        $prompt = "Instagram Carousel icin '$topic' konusunda $slide_count slide'lik icerik olustur.

STIL: $style
NIS: $niche

HER SLIDE ICIN:
1. Slide basligi (kisa, dikkat cekici)
2. Ana metin (2-3 cumle)
3. Gorsel onerisi
4. Design notu

FORMAT:
SLIDE 1 (COVER):
Title: ...
Text: ...
Visual: ...
Design: ...

SLIDE 2:
...

SON SLIDE (CTA):
Title: ...
Text: ...
CTA: ...

GENEL CAPTION ONERISI:
...";

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen Instagram carousel tasarim ve icerik uzmanisin. Swipe-worthy, bilgilendirici ve gorsel olarak cekici carouseller olusturuyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1200,
            'temperature' => 0.8
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Generate story content with sticker suggestions
     */
    public function generateStoryContent($topic, $story_count = 3, $options = []) {
        $goal = $options['goal'] ?? 'engagement';
        $include_poll = $options['include_poll'] ?? true;

        $prompt = "Instagram Story serisi icin '$topic' konusunda $story_count story'lik icerik olustur.

AMAÇ: $goal

HER STORY ICIN:
1. Gorsel/Video aciklamasi
2. Metin overlay
3. Sticker onerisi (poll/quiz/countdown/question/slider)
4. Sticker icerigi
5. Swipe up/Link (varsa)

STICKER SECENEKLERI:
- POLL: Iki secenekli anket
- QUIZ: Coktan secmeli soru
- COUNTDOWN: Geri sayim
- QUESTION: Soru kutusu
- SLIDER: Emoji slider

FORMAT:
STORY 1:
Visual: ...
Text: ...
Sticker: POLL
Sticker_Content: {question: '...', option1: '...', option2: '...'}

STORY 2:
...

HIGHLIGHT_SUGGESTION: [Highlight ismi onerisi]";

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen Instagram Stories stratejistisin. Interaktif, engaging ve eglenceli story serileri olusturuyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.8
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Suggest trending audio for Reels
     */
    public function suggestTrendingAudio($topic, $mood, $options = []) {
        $genre = $options['genre'] ?? 'any';
        $duration = $options['duration'] ?? '15-30';

        $prompt = "Instagram Reels icin '$topic' konusunda, '$mood' atmosferinde muzik/ses onerisi yap.

TURE: $genre
SURE: $duration saniye

ONERILER (5 adet):
Her biri icin:
1. Sarki/Ses adi
2. Sanatci
3. Neden uygun
4. Trend skoru (1-10)
5. Kullanim onerisi (hangi kisimda)

FORMAT:
1. [Sarki Adi] - [Sanatci]
   Trend: X/10
   Neden: ...
   Kullanim: ...

2. ...";

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen Instagram Reels muzik ve trend uzmanisin. Viral olabilecek, icerikle uyumlu muzik ve ses onerileri yapiyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 600,
            'temperature' => 0.8
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Analyze best posting times based on engagement data
     */
    public function analyzeBestPostingTimes($engagement_data, $options = []) {
        $timezone = $options['timezone'] ?? 'Europe/Istanbul';
        $current_followers = $options['followers'] ?? 1000;

        $data_json = json_encode($engagement_data);

        $prompt = "Asagidaki Instagram engagement verisini analiz et ve en iyi paylasim zamanlarini belirle:

VERI:
$data_json

TIMEZONE: $timezone
TAKIPCI SAYISI: $current_followers

ANALIZ ET:
1. Hafta ici en iyi 3 saat
2. Hafta sonu en iyi 2 saat
3. En iyi gun
4. Kacinilmasi gereken zamanlar
5. Genel strateji onerisi

FORMAT:
WEEKDAY_BEST:
- [Saat]: [Ortalama engagement] - [Neden]
...

WEEKEND_BEST:
- [Saat]: [Ortalama engagement] - [Neden]
...

BEST_DAY: [Gun] - [Neden]

AVOID: [Kacinilmasi gereken zamanlar]

STRATEGY: [Genel oneri]";

        $response = $this->makeRequest([
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen sosyal medya analytics uzmanisin. Veri analizi yaparak optimal posting stratejileri belirliyorsun.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 600,
            'temperature' => 0.5
        ]);

        return $this->parseResponse($response);
    }

    /**
     * Build caption prompt
     */
    private function buildCaptionPrompt($topic, $brand_name, $brand_voice, $target_audience, $niche, $post_type, $content_category, $language) {
        $lang_instruction = $language === 'tr'
            ? 'Turkce yaz. Dogal ve akici bir dil kullan.'
            : 'Write in English. Use natural and fluent language.';

        return "Instagram $post_type icin '$topic' konusunda caption yaz.

MARKA: $brand_name
MARKA SESI: $brand_voice
HEDEF KITLE: $target_audience
NIS: $niche
KATEGORI: $content_category

KURALLAR:
1. Ilk cumle HOOK olmali (dikkat cekici, scroll durduran)
2. Max 3 emoji kullan (abarti YAPMA)
3. Line break'lerle okunabilirlik sagla
4. Sonunda CTA ekle (yorum, kaydet, paylas vb.)
5. Max 2200 karakter
6. $lang_instruction

CIKTI FORMATI:
CAPTION:
[Ana caption buraya]

FIRST_COMMENT:
[Ekstra hashtagler icin ilk yorum]

SUGGESTED_HASHTAGS:
[15-20 hashtag onerisi]";
    }

    /**
     * Parse AI response
     */
    private function parseResponse($response) {
        if (isset($response['error'])) {
            return [
                'success' => false,
                'error' => $response['error']
            ];
        }

        if (isset($response['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'content' => $response['choices'][0]['message']['content'],
                'tokens_used' => $response['usage']['total_tokens'] ?? 0
            ];
        }

        return [
            'success' => false,
            'error' => 'Unexpected response format'
        ];
    }

    /**
     * Parse hashtag response into structured format
     */
    private function parseHashtagResponse($response) {
        $parsed = $this->parseResponse($response);

        if (!$parsed['success']) {
            return $parsed;
        }

        $content = $parsed['content'];
        $hashtags = [
            'high' => [],
            'medium' => [],
            'niche' => [],
            'branded' => []
        ];

        // Extract hashtags using regex
        preg_match_all('/#[\w\u0600-\u06FFА-яа-я]+/u', $content, $matches);

        if (!empty($matches[0])) {
            // Parse by sections
            $sections = preg_split('/\n(HIGH|MEDIUM|NICHE|BRANDED):/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

            $current_category = 'medium';
            foreach ($sections as $section) {
                $section_lower = strtolower(trim($section));
                if (in_array($section_lower, ['high', 'medium', 'niche', 'branded'])) {
                    $current_category = $section_lower;
                } else {
                    preg_match_all('/#[\w\u0600-\u06FFА-яа-я]+/u', $section, $section_matches);
                    if (!empty($section_matches[0])) {
                        $hashtags[$current_category] = array_merge($hashtags[$current_category], $section_matches[0]);
                    }
                }
            }
        }

        $parsed['hashtags'] = $hashtags;
        $parsed['all_hashtags'] = array_merge($hashtags['high'], $hashtags['medium'], $hashtags['niche'], $hashtags['branded']);

        return $parsed;
    }

    /**
     * Parse calendar response into structured format
     */
    private function parseCalendarResponse($response) {
        $parsed = $this->parseResponse($response);

        if (!$parsed['success']) {
            return $parsed;
        }

        // Try to extract JSON from response
        $content = $parsed['content'];
        preg_match('/\{[\s\S]*\}/m', $content, $json_match);

        if (!empty($json_match[0])) {
            $calendar_data = json_decode($json_match[0], true);
            if ($calendar_data) {
                $parsed['calendar'] = $calendar_data;
            }
        }

        return $parsed;
    }

    /**
     * Make request to OpenAI API
     */
    private function makeRequest($data) {
        $ch = curl_init($this->api_url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->api_key
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return ['error' => 'cURL error: ' . $error];
        }

        $result = json_decode($response, true);

        if ($http_code !== 200) {
            return ['error' => $result['error']['message'] ?? 'API error: HTTP ' . $http_code];
        }

        return $result;
    }
}
?>
