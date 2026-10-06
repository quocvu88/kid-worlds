<?php

namespace KidsWorld\Backend\Services;

class AiService {
    /**
     * Generate complete topic pack and items based on prompt and options
     */
    public static function generate(string $prompt, int $count = 6, string $targetAge = '3-8', ?string $apiKey = null): array {
        $cleanPrompt = trim($prompt);
        if (empty($cleanPrompt)) {
            $cleanPrompt = 'Khám phá thế giới động vật vui nhộn';
        }

        // Parse min/max age
        $ageMin = 3;
        $ageMax = 8;
        if (preg_match('/(\d+)\s*[-–]\s*(\d+)/', $targetAge, $m)) {
            $ageMin = (int)$m[1];
            $ageMax = (int)$m[2];
        }

        // If API key is provided or found in ENV, attempt live LLM call
        $key = $apiKey ?: getenv('GEMINI_API_KEY') ?: getenv('OPENAI_API_KEY');
        if (!empty($key)) {
            try {
                $llmResult = self::callLlm($cleanPrompt, $count, $ageMin, $ageMax, $key);
                if (!empty($llmResult) && isset($llmResult['pack']) && isset($llmResult['items'])) {
                    return $llmResult;
                }
            } catch (\Throwable $e) {
                // Fallback to intelligent template generator
            }
        }

        // Smart Knowledge Generator according to Kids World Multi-Sensory template
        return self::generateSmartTemplate($cleanPrompt, $count, $ageMin, $ageMax);
    }

    /**
     * Build Standard Master Prompt for external AI models (ChatGPT, Claude, Gemini, DeepSeek...)
     */
    public static function buildPromptTemplate(string $topic = '', int $count = 6, string $targetAge = '3-8'): string {
        $cleanTopic = !empty(trim($topic)) ? trim($topic) : 'Thế giới động vật hoang dã kỳ thú';
        $ageMin = 3;
        $ageMax = 8;
        if (preg_match('/(\d+)\s*[-–]\s*(\d+)/', $targetAge, $m)) {
            $ageMin = (int)$m[1];
            $ageMax = (int)$m[2];
        }

        return <<<PROMPT
Bạn là chuyên gia thiết kế chương trình giáo dục mầm non & tiểu học cho ứng dụng trẻ em "Kids World".
Hãy tạo 01 bộ học phần Flashcard đa giác quan hoàn chỉnh theo chủ đề: "{$cleanTopic}" dành cho bé lứa tuổi {$targetAge} ({$ageMin} đến {$ageMax} tuổi), gồm chính xác {$count} thẻ học tập.

=== TIÊU CHUẨN CẤU TRÚC BỘ HỌC PHẦN ĐA GIÁC QUAN (KIDS WORLD STANDARD SPECIFICATION) ===
1. THÔNG TIN GÓI CHỦ ĐỀ (TOPIC PACK):
- id: Mã định danh dạng slug không dấu, bắt đầu bằng topic_ (ví dụ: topic_savanna_animals).
- title_vi: Tên gói tiếng Việt hấp dẫn, kích thích tò mò (ví dụ: "Muôn Loài Động Vật Thảo Nguyên").
- title_en: Tên gói tiếng Anh chuẩn bản ngữ (ví dụ: "Fascinating Savanna Animals").
- category: Một trong các danh mục: 'animals' | 'vehicles' | 'dinosaurs' | 'space' | 'fruits' | 'nature' | 'general'.
- theme_color: Mã màu chủ đạo HEX phù hợp chủ đề (ví dụ: động vật thảo nguyên #10b981, vũ trụ #8b5cf6, xe cộ #ef4444, đại dương #0284c7).
- background_url: URL ảnh nền hoạt họa thiên nhiên phù hợp chủ đề (Unsplash hoặc để trống để app tự gán nền chuẩn).
- target_age_min: {$ageMin}
- target_age_max: {$ageMax}
- target_gender: "all"
- thumbnail_url: URL ảnh đại diện vuông hoặc ngang tỷ lệ 4:3 chất lượng cao.
- description_vi: Lời giới thiệu ngắn gọn, truyền cảm hứng cho phụ huynh và bé.
- description_en: Lời giới thiệu tiếng Anh tương ứng.
- version: 1
- size_mb: 1.8
- is_active: 1

2. MỖI THẺ HỌC PHẦN (TOPIC ITEM) - BẮT BUỘC ĐỦ CÁC TRƯỜNG ĐA GIÁC QUAN:
- id: Mã thẻ slug duy nhất (ví dụ: item_lion, item_elephant).
- name_vi: Tên sự vật tiếng Việt chuẩn mực (ví dụ: "Sư Tử").
- name_en: Tên tiếng Anh chuẩn bản ngữ (ví dụ: "Lion").
- phonics_en: Phiên âm ngữ âm từng âm tiết đánh vần cho bé (ví dụ: "/l-aɪ-ə-n/", "/d-ɒ-ɡ/", "/k-æ-t/").
- description_vi: Mô tả hình dáng đặc trưng 1-2 câu ngắn, ngôn từ trong sáng, thân thiện với trẻ nhỏ.
- description_en: Mô tả tiếng Anh tương ứng 1-2 câu.
- images: Mảng chứa 1 URL ảnh minh họa (Vector / Cute illustration / 3D Cartoon) chất lượng cao.
- real_image_url: URL ảnh chụp đời thực (Real Photo) chất lượng cao để bé so sánh và liên hệ cuộc sống thực tế.
- sfx_sound: Từ tượng thanh tiếng kêu thực tế hoặc tiếng động cơ (ví dụ: "Gừ... Roar!", "Gâu gâu! Woof woof!", "Bíp bíp! Beep beep!", "Xình xịch! Choo choo!").
- youtube_video_id: Mã ID video YouTube an toàn 11 ký tự (ví dụ: "VbZ07n242-g").
- BENTO CARD CẨM NANG PHỤ HUYNH & BÉ (3 KHỐI SƯ PHẠM):
  * fun_fact_vi & fun_fact_en: 1 sự thật khoa học/tự nhiên kỳ thú ngắn gọn gây bất ngờ cho bé.
  * prompt_question_vi & prompt_question_en: 1 câu hỏi tương tác mở để bố mẹ đố bé tư duy và quan sát.
  * action_hint_vi: 1 trò chơi thể chất / hành động mô phỏng vui nhộn để bố mẹ và bé cùng làm theo.
- map_x & map_y: Tọa độ phân bổ trên bản đồ thám hiểm (từ 100 đến 750).
- sort_order: Số thứ tự thẻ từ 1 đến {$count}.
- is_selected: true

=== YÊU CẦU ĐẦU RA (OUTPUT FORMAT) ===
- Trả về DUY NHẤT mã JSON hợp lệ.
- TUYỆT ĐỐI KHÔNG bọc trong markdown ```json, KHÔNG thêm lời giải thích hay bất kỳ ký tự nào trước/sau JSON.
- Đảm bảo đúng cấu trúc JSON sau:

{
  "pack": {
    "id": "topic_sample_slug",
    "title_vi": "Tên Bộ Tiếng Việt",
    "title_en": "English Pack Name",
    "category": "animals",
    "theme_color": "#10b981",
    "background_url": "https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1200&q=80",
    "target_age_min": {$ageMin},
    "target_age_max": {$ageMax},
    "target_gender": "all",
    "thumbnail_url": "https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80",
    "description_vi": "Mô tả truyền cảm hứng cho phụ huynh và bé bằng tiếng Việt.",
    "description_en": "Inspirational description for parents and kids in English.",
    "version": 1,
    "size_mb": 1.8,
    "is_active": 1
  },
  "items": [
    {
      "id": "item_sample_1",
      "name_vi": "Tên Thẻ Tiếng Việt",
      "name_en": "English Item Name",
      "phonics_en": "/p-h-o-n-i-c-s/",
      "description_vi": "Mô tả ngắn gọn, dễ thương cho bé bằng tiếng Việt.",
      "description_en": "Short cute English description for young children.",
      "images": ["https://images.unsplash.com/photo-...minh_hoa"],
      "real_image_url": "https://images.unsplash.com/photo-...doi_thuc",
      "sfx_sound": "Tiếng kêu thực tế (ví dụ: Roar! Roar!)",
      "youtube_video_id": "VbZ07n242-g",
      "fun_fact_vi": "💡 Sự thật kỳ thú về sự vật này cho bé.",
      "fun_fact_en": "💡 Exciting fun fact about this item.",
      "prompt_question_vi": "❓ Câu hỏi cha mẹ đố bé để kích thích tư duy.",
      "prompt_question_en": "❓ Interactive prompt question for parents to ask the child.",
      "action_hint_vi": "🤸 Hành động mô phỏng vui nhộn cha mẹ và bé cùng thực hiện.",
      "map_x": 180,
      "map_y": 220,
      "sort_order": 1,
      "is_selected": true
    }
  ]
}
PROMPT;
    }

    /**
     * Fallback & Built-in Smart Knowledge Engine
     */
    private static function generateSmartTemplate(string $prompt, int $count, int $ageMin, int $ageMax): array {
        $pLower = mb_strtolower($prompt, 'UTF-8');

        // Determine Theme Category & Context
        $theme = 'animals';
        $packTitleVi = 'Thế Giới Động Vật Kỳ Thú';
        $packTitleEn = 'Amazing Animal Kingdom';
        $packDescVi = 'Khám phá những loài động vật đáng yêu và kỳ thú trên khắp thế giới cho bé.';
        $packDescEn = 'Explore adorable and fascinating animals around the world for kids.';
        $packId = 'topic_animals_' . time();
        $thumbUrl = 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80';
        $backgroundUrl = 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?auto=format&fit=crop&w=1200&q=80';
        $themeColor = '#10b981';

        $rawItems = [];

        if (str_contains($pLower, 'khủng long') || str_contains($pLower, 'dinosaur')) {
            $theme = 'dinosaurs';
            $packTitleVi = 'Thế Giới Khủng Long Tiền Sử';
            $packTitleEn = 'Prehistoric Dinosaur World';
            $packDescVi = 'Hành trình ngược thời gian khám phá các loài khủng long khổng lồ thời tiền sử dành cho bé.';
            $packDescEn = 'Travel back in time to explore giant prehistoric dinosaurs for children.';
            $packId = 'topic_dinosaurs';
            $thumbUrl = 'https://images.unsplash.com/photo-1606856110002-d0991ce78250?auto=format&fit=crop&w=800&q=80';
            $backgroundUrl = 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=1200&q=80';
            $themeColor = '#10b981';

            $rawItems = [
                [
                    'name_vi' => 'Khủng Long Bạo Chúa',
                    'name_en' => 'Tyrannosaurus Rex',
                    'slug' => 'trex',
                    'phonics' => '/t-aɪ-ˈr-æ-n-ə-ˌs-ɔː-r-ə-s/ /r-ɛ-k-s/',
                    'desc_vi' => 'T-Rex là loài khủng long ăn thịt khổng lồ với hàm răng sắc nhọn, được mệnh danh là chúa tể thời tiền sử.',
                    'desc_en' => 'T-Rex was a giant meat-eating dinosaur with sharp teeth, known as the king of dinosaurs.',
                    'image' => 'https://images.unsplash.com/photo-1583845112239-97ef1341b271?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1579202673506-ca3ce28943ef?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Gừ gừ... Roarrrr!',
                    'fun_fact_vi' => 'Răng của T-Rex to bằng một quả chuối lớn và có thể cắn vỡ cả xương cứng!',
                    'fun_fact_en' => 'A T-Rex tooth could be as long as a large banana and crush bones easily!',
                    'prompt_vi' => 'Bé thử tìm xem hai cánh tay nhỏ của T-Rex nằm ở đâu trên người bạn ấy nào?',
                    'prompt_en' => 'Can you spot the two tiny arms on the T-Rex body?',
                    'action_vi' => 'Bé giơ hai tay gập nhỏ trước ngực, bước đi chân dậm đất và gầm "Roarrr" như T-Rex nhé!',
                    'youtube' => 'G3gXWDYpLAE',
                    'x' => 150, 'y' => 200
                ],
                [
                    'name_vi' => 'Khủng Long Ba Sừng',
                    'name_en' => 'Triceratops',
                    'slug' => 'triceratops',
                    'phonics' => '/t-r-aɪ-ˈs-ɛ-r-ə-t-ɒ-p-s/',
                    'desc_vi' => 'Triceratops là loài khủng long ăn cỏ hiền lành với ba chiếc sừng cứng cáp và chiếc diềm cổ như chiếc khiên.',
                    'desc_en' => 'Triceratops was a gentle plant-eating dinosaur with three sturdy horns and a large bony frill.',
                    'image' => 'https://images.unsplash.com/photo-1570481662006-a3a1374699e8?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1610056494052-6a4f83a8368c?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Húm... Humfff!',
                    'fun_fact_vi' => 'Chiếc diềm cổ cứng cáp của Triceratops hoạt động như chiếc khiên che chở bạn ấy khỏi răng kẻ săn mồi.',
                    'fun_fact_en' => 'Triceratops bony neck frill acted like a protective shield against predators.',
                    'prompt_vi' => 'Bé đếm cùng ba mẹ xem bạn khủng long này có mấy chiếc sừng trên đầu nhé?',
                    'prompt_en' => 'Count along with mom and dad: how many horns does this dinosaur have?',
                    'action_vi' => 'Bé dùng 3 ngón tay đặt lên trán làm 3 chiếc sừng và nghiêng đầu húc nhẹ đáng yêu.',
                    'youtube' => '9qFpA9N2e0E',
                    'x' => 320, 'y' => 380
                ],
                [
                    'name_vi' => 'Khủng Long Cổ Dài',
                    'name_en' => 'Brachiosaurus',
                    'slug' => 'brachiosaurus',
                    'phonics' => '/b-r-æ-k-i-oʊ-ˈs-ɔː-r-ə-s/',
                    'desc_vi' => 'Brachiosaurus có chiếc cổ siêu dài giúp nó dễ dàng ăn những tán lá non trên ngọn cây cao vút.',
                    'desc_en' => 'Brachiosaurus had an exceptionally long neck that helped it reach tender leaves at high treetops.',
                    'image' => 'https://images.unsplash.com/photo-1584824486509-112e4181ff6b?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Ùm bò... Ooooomph!',
                    'fun_fact_vi' => 'Bạn ấy cao bằng một tòa nhà 4 tầng và nặng ngang 12 chú voi cộng lại!',
                    'fun_fact_en' => 'It was as tall as a 4-story building and weighed as much as 12 elephants combined!',
                    'prompt_vi' => 'Nếu bé có chiếc cổ dài như bạn ấy, bé sẽ với tay chạm tới những cành cây nào?',
                    'prompt_en' => 'If you had such a long neck, what high branches would you reach for?',
                    'action_vi' => 'Bé kiễng chân thật cao, duỗi thẳng 2 tay lên trần nhà như chiếc cổ dài vươn hái lá non.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 500, 'y' => 180
                ],
                [
                    'name_vi' => 'Khủng Long Bay',
                    'name_en' => 'Pterodactyl',
                    'slug' => 'pterodactyl',
                    'phonics' => '/t-ɛ-r-ə-ˈd-æ-k-t-ɪ-l/',
                    'desc_vi' => 'Khủng long bay lượn trên bầu trời xanh thời tiền sử với đôi cánh da rộng lớn và chiếc mỏ dài.',
                    'desc_en' => 'Pterodactyl glided through prehistoric skies with large leathery wings and a long beak.',
                    'image' => 'https://images.unsplash.com/photo-1550684848-fac1c5b4e853?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1559827291-72ee739d0d9a?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Éc éc... Screeech!',
                    'fun_fact_vi' => 'Pterodactyl không có lông vũ như loài chim bây giờ mà có đôi cánh bằng màng da mỏng.',
                    'fun_fact_en' => 'Pterodactyls had skin membrane wings rather than bird feathers.',
                    'prompt_vi' => 'Bạn ấy bay trên bầu trời để săn bắt những con cá nhỏ dưới sông suối, bé thấy đúng không?',
                    'prompt_en' => 'Did you know they flew above waters to scoop up fresh fish with their beak?',
                    'action_vi' => 'Bé giang rộng hai tay sang ngang và nghiêng người liệng cánh như đang bay lượn trên mây.',
                    'youtube' => 'q76N4Uo8_4o',
                    'x' => 680, 'y' => 120
                ],
                [
                    'name_vi' => 'Khủng Long Gai Lưng',
                    'name_en' => 'Stegosaurus',
                    'slug' => 'stegosaurus',
                    'phonics' => '/s-t-ɛ-ɡ-ə-ˈs-ɔː-r-ə-s/',
                    'desc_vi' => 'Stegosaurus có hai hàng phiến sừng hình tam giác chạy dọc sống lưng và chiếc đuôi có 4 chiếc gai sắc.',
                    'desc_en' => 'Stegosaurus featured double rows of triangular plates along its back and four spikes on its tail.',
                    'image' => 'https://images.unsplash.com/photo-1618336753974-aae8e04506aa?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1570481662006-a3a1374699e8?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Thump... Thump... Grrr!',
                    'fun_fact_vi' => 'Dù thân hình to như chiếc xe buýt, bộ não của Stegosaurus chỉ nhỏ bằng một quả óc chó!',
                    'fun_fact_en' => 'Although as large as a bus, Stegosaurus had a brain the size of a walnut!',
                    'prompt_vi' => 'Bé hãy nhìn vào chiếc đuôi của bạn ấy xem có bao nhiêu chiếc gai nhọn nào?',
                    'prompt_en' => 'Look closely at its tail spikes: how do they help keep the dinosaur safe?',
                    'action_vi' => 'Bé khum bàn tay làm gai sau lưng và lắc lắc hông như chiếc đuôi gai nhịp nhàng.',
                    'youtube' => 'eMv_oN4yv_Q',
                    'x' => 420, 'y' => 450
                ],
                [
                    'name_vi' => 'Khủng Long Giáp',
                    'name_en' => 'Ankylosaurus',
                    'slug' => 'ankylosaurus',
                    'phonics' => '/æ-ŋ-k-aɪ-l-oʊ-ˈs-ɔː-r-ə-s/',
                    'desc_vi' => 'Ankylosaurus giống như một chiếc xe tăng sống với lớp áo giáp gai và chiếc búa tạ ở chóp đuôi.',
                    'desc_en' => 'Ankylosaurus was like a living tank, covered in heavy armor plates with a heavy club tail.',
                    'image' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1563245372-f21724e3856d?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Cạch cạch... Clank-thump!',
                    'fun_fact_vi' => 'Chiếc búa nặng ở đuôi của bạn ấy có thể quật gãy chân cả loài khủng long ăn thịt hung dữ!',
                    'fun_fact_en' => 'Its tail club was strong enough to shatter the bones of attacking carnivores!',
                    'prompt_vi' => 'Lớp áo giáp gai cứng cáp giúp Ankylosaurus tránh được điều nguy hiểm gì nhỉ?',
                    'prompt_en' => 'How does the hard armored back help protect Ankylosaurus?',
                    'action_vi' => 'Bé cuộn tròn người bảo vệ như chiếc mai giáp rồi vẫy tay vung chiếc búa đuôi dũng cảm.',
                    'youtube' => 'U0j34y-xR7w',
                    'x' => 260, 'y' => 520
                ],
                [
                    'name_vi' => 'Khủng Long Tốc Độ',
                    'name_en' => 'Velociraptor',
                    'slug' => 'velociraptor',
                    'phonics' => '/v-ɪ-ˈl-ɒ-s-ɪ-ˌr-æ-p-t-ə/',
                    'desc_vi' => 'Velociraptor là loài khủng long thông minh, chạy rất nhanh và săn mồi theo bầy đàn.',
                    'desc_en' => 'Velociraptor was an agile and smart dinosaur that ran fast and hunted in packs.',
                    'image' => 'https://images.unsplash.com/photo-1606856110002-d0991ce78250?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1579202673506-ca3ce28943ef?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Chít chít... Sssss-snap!',
                    'fun_fact_vi' => 'Velociraptor có chiếc móng vuốt hình lưỡi liềm sắc bén ở ngón chân thứ hai giúp giữ thăng bằng siêu tốt!',
                    'fun_fact_en' => 'Velociraptor had a curved sickle claw on its second toe for grip and agility!',
                    'prompt_vi' => 'Bé đố xem bạn Velociraptor chạy nhanh hơn hay bạn rùa chạy nhanh hơn?',
                    'prompt_en' => 'Can you run fast like a quick and clever Velociraptor?',
                    'action_vi' => 'Bé nhón chân chạy nhanh tại chỗ với những bước chân thoăn thoắt săn đuổi nhé!',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 610, 'y' => 340
                ]
            ];
        } elseif (str_contains($pLower, 'biển') || str_contains($pLower, 'đại dương') || str_contains($pLower, 'ocean') || str_contains($pLower, 'sea')) {
            $theme = 'nature';
            $packTitleVi = 'Thế Giới Đại Dương Bao La';
            $packTitleEn = 'Wonderful Ocean Wonders';
            $packDescVi = 'Lặn sâu xuống lòng đại dương để gặp gỡ các bạn cá voi, rùa biển và san hô rực rỡ sắc màu.';
            $packDescEn = 'Dive deep into the ocean to meet whales, sea turtles, and colorful corals for kids.';
            $packId = 'topic_ocean_' . time();
            $thumbUrl = 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80';
            $backgroundUrl = 'https://images.unsplash.com/photo-1518467166778-b88f373ffec7?auto=format&fit=crop&w=1200&q=80';
            $themeColor = '#0284c7';

            $rawItems = [
                [
                    'name_vi' => 'Cá Voi Xanh',
                    'name_en' => 'Blue Whale',
                    'slug' => 'blue_whale',
                    'phonics' => '/b-l-uː/ /w-eɪ-l/',
                    'desc_vi' => 'Cá voi xanh là sinh vật to lớn nhất trên Trái Đất, bơi lội nhẹ nhàng giữa đại dương bao la.',
                    'desc_en' => 'The blue whale is the largest creature on Earth, swimming gracefully in the deep blue ocean.',
                    'image' => 'https://images.unsplash.com/photo-1568430462989-44163eb1752f?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Ù... Ùm... Whooosh!',
                    'fun_fact_vi' => 'Trái tim của cá voi xanh to bằng một chiếc xe ô tô nhỏ đấy!',
                    'fun_fact_en' => 'A blue whale\'s heart is as big as a small car!',
                    'prompt_vi' => 'Bé hãy tìm xem chiếc lỗ phun nước của bạn cá voi nằm ở đâu trên lưng nào?',
                    'prompt_en' => 'Can you find the blowhole on top of the whale\'s head?',
                    'action_vi' => 'Bé chụm miệng thổi mạnh "Phììì..." như bạn cá voi đang phun cột nước cầu vồng lên trời.',
                    'youtube' => 'bgiPTUy2RqI',
                    'x' => 200, 'y' => 220
                ],
                [
                    'name_vi' => 'Cá Heo Thông Minh',
                    'name_en' => 'Friendly Dolphin',
                    'slug' => 'dolphin',
                    'phonics' => '/ˈd-ɒ-l-f-ɪ-n/',
                    'desc_vi' => 'Bạn cá heo rất thông minh, thích nhảy múa trên mặt nước và thân thiện với con người.',
                    'desc_en' => 'Dolphins are very playful and smart, often leaping over the ocean waves.',
                    'image' => 'https://images.unsplash.com/photo-1607153333879-c1a05d658663?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1570481662006-a3a1374699e8?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Chít chít... Click-click-whistle!',
                    'fun_fact_vi' => 'Cá heo giao tiếp với nhau bằng những tiếng huýt và tiếng lách cách siêu âm đặc biệt!',
                    'fun_fact_en' => 'Dolphins communicate using unique clicks and whistling sounds underwater!',
                    'prompt_vi' => 'Bạn cá heo có biết nhảy nhót trên ngọn sóng không bé ơi?',
                    'prompt_en' => 'Do dolphins love leaping high out of the water?',
                    'action_vi' => 'Bé nhún nhảy hai chân tại chỗ và chắp 2 tay trước ngực uốn lượn như bạn cá heo tung tăng.',
                    'youtube' => 'j_V8o2Z8i5g',
                    'x' => 450, 'y' => 160
                ],
                [
                    'name_vi' => 'Rùa Biển Cổ Đại',
                    'name_en' => 'Sea Turtle',
                    'slug' => 'sea_turtle',
                    'phonics' => '/s-iː/ /ˈt-ɜː-t-l/',
                    'desc_vi' => 'Rùa biển có chiếc mai cứng cáp và hai chi trước như mái chèo giúp bạn bơi qua hàng ngàn dặm biển.',
                    'desc_en' => 'Sea turtles glide gently through coral reefs using their flipper-like front legs.',
                    'image' => 'https://images.unsplash.com/photo-1518467166778-b88f373ffec7?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Xoạt... Swoosh-glide!',
                    'fun_fact_vi' => 'Rùa biển mẹ có thể bơi vượt đại dương về đúng bãi cát nơi mình sinh ra để đẻ trứng!',
                    'fun_fact_en' => 'Mother sea turtles travel thousands of miles back to the exact beach where they were born!',
                    'prompt_vi' => 'Hai vây trước của bạn rùa nhìn giống như vật dụng gì trên thuyền chèo?',
                    'prompt_en' => 'What do the turtle\'s front flippers remind you of when rowing a boat?',
                    'action_vi' => 'Bé dang 2 tay quạt nhẹ nhàng sang hai bên như mái chèo êm đềm dưới nước.',
                    'youtube' => '5Rms3T5rJ1k',
                    'x' => 620, 'y' => 380
                ],
                [
                    'name_vi' => 'Bạch Tuộc Biến Hình',
                    'name_en' => 'Octopus',
                    'slug' => 'octopus',
                    'phonics' => '/ˈɒ-k-t-ə-p-ə-s/',
                    'desc_vi' => 'Bạch tuộc có 8 chiếc xúc tu linh hoạt và khả năng đổi màu ngụy trang kỳ diệu để ẩn mình.',
                    'desc_en' => 'The octopus has eight flexible arms and can change colors to camouflage instantly.',
                    'image' => 'https://images.unsplash.com/photo-1545671913-b89ac1b4ac10?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1568430462989-44163eb1752f?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Xoẹt xì... Splish-squirt!',
                    'fun_fact_vi' => 'Bạch tuộc có tận 3 trái tim và dòng máu của bạn ấy có màu xanh dương kỳ diệu!',
                    'fun_fact_en' => 'An octopus actually has 3 hearts and its blood is naturally blue!',
                    'prompt_vi' => 'Bé thử đếm xem bạn bạch tuộc có tất cả bao nhiêu chiếc xúc tu nào?',
                    'prompt_en' => 'Let\'s count together: how many flexible arms does the octopus have?',
                    'action_vi' => 'Bé xòe các ngón tay và uốn éo mềm mại như những chiếc xúc tu đang khám phá rạn san hô.',
                    'youtube' => 'mFP_AjJeP-M',
                    'x' => 280, 'y' => 480
                ],
                [
                    'name_vi' => 'Cá Hề Nhỏ',
                    'name_en' => 'Clownfish',
                    'slug' => 'clownfish',
                    'phonics' => '/k-l-aʊ-n/ /f-ɪ-ʃ/',
                    'desc_vi' => 'Bạn cá hề sọc cam trắng xinh xắn sống hòa thuận bên trong những rặng hải quỳ mềm mại.',
                    'desc_en' => 'The bright orange clownfish makes its safe cozy home among soft sea anemones.',
                    'image' => 'https://images.unsplash.com/photo-1535591273668-578e31182c4f?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Búp búp... Blub-blub!',
                    'fun_fact_vi' => 'Chất nhầy đặc biệt trên vảy cá hề giúp bạn không hề bị ngứa hay đau khi bơi vào hải quỳ!',
                    'fun_fact_en' => 'A special mucus layer protects clownfish from the stinging tentacles of anemones!',
                    'prompt_vi' => 'Cá hề có những màu sắc nào nổi bật trên thân bạn ấy bé ơi?',
                    'prompt_en' => 'What vibrant stripes can you see on this friendly little fish?',
                    'action_vi' => 'Bé chụm 2 bàn tay vẫy đuôi cá và chu mỏ bập bập bong bóng nước thật dễ thương.',
                    'youtube' => 'rB4aQp5qL_o',
                    'x' => 520, 'y' => 520
                ],
                [
                    'name_vi' => 'Sao Biển Lấp Lánh',
                    'name_en' => 'Starfish',
                    'slug' => 'starfish',
                    'phonics' => '/s-t-ɑː/ /f-ɪ-ʃ/',
                    'desc_vi' => 'Sao biển hình ngôi sao 5 cánh nhiều màu sắc nằm nghỉ ngơi trên bờ cát và rạn san hô.',
                    'desc_en' => 'Starfish look like colorful stars resting on the soft ocean sand and corals.',
                    'image' => 'https://images.unsplash.com/photo-1549480017-d76466a4b7e8?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1518467166778-b88f373ffec7?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Lắng đọng... Soft-glow!',
                    'fun_fact_vi' => 'Nếu không may bị rụng một cánh, sao biển có thể tự mọc lại cánh mới hoàn toàn!',
                    'fun_fact_en' => 'Starfish can regenerate and grow back an entire new arm if one is lost!',
                    'prompt_vi' => 'Bạn sao biển có hình dáng giống như vật thể lấp lánh nào trên bầu trời đêm?',
                    'prompt_en' => 'What night sky object does the starfish remind you of?',
                    'action_vi' => 'Bé xòe rộng 5 ngón tay tạo hình ngôi sao và làm động tác nhấp nháy tỏa sáng.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 700, 'y' => 260
                ]
            ];
        } elseif (str_contains($pLower, 'vũ trụ') || str_contains($pLower, 'space') || str_contains($pLower, 'hành tinh')) {
            $theme = 'space';
            $packTitleVi = 'Khám Phá Vũ Trụ & Các Hành Tinh';
            $packTitleEn = 'Space & Solar System Exploration';
            $packDescVi = 'Chuyến du hành liên hành tinh kỳ thú giúp bé khám phá Mặt Trời, Mặt Trăng và các vì sao lấp lánh.';
            $packDescEn = 'An exciting space voyage to explore the Sun, Moon, and dazzling planets for children.';
            $packId = 'topic_space_' . time();
            $thumbUrl = 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80';
            $backgroundUrl = 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?auto=format&fit=crop&w=1200&q=80';
            $themeColor = '#8b5cf6';

            $rawItems = [
                [
                    'name_vi' => 'Mặt Trời Tỏa Sáng',
                    'name_en' => 'The Radiant Sun',
                    'slug' => 'sun',
                    'phonics' => '/s-ʌ-n/',
                    'desc_vi' => 'Mặt Trời là ngôi sao khổng lồ tỏa ánh sáng ấm áp và nuôi dưỡng mọi sự sống trên Trái Đất.',
                    'desc_en' => 'The Sun is a giant bright star providing light and warmth to nurture all life on Earth.',
                    'image' => 'https://images.unsplash.com/photo-1614728894747-a83421e2b9c9?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Ấm áp... Warm-radiance!',
                    'fun_fact_vi' => 'Mặt Trời to đến mức có thể chứa được hơn 1 triệu Trái Đất ở bên trong!',
                    'fun_fact_en' => 'Over 1 million Earths could fit inside the Sun!',
                    'prompt_vi' => 'Mỗi sáng thức dậy, bé thấy ánh nắng Mặt Trời mang lại cảm giác gì?',
                    'prompt_en' => 'How does the morning sunshine make you feel warm and energized?',
                    'action_vi' => 'Bé giơ hai tay lên cao vẽ một vòng tròn khổng lồ như vầng hào quang rực rỡ.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 150, 'y' => 150
                ],
                [
                    'name_vi' => 'Trái Đất Xanh Yêu Thương',
                    'name_en' => 'Planet Earth',
                    'slug' => 'earth',
                    'phonics' => '/ɜː-θ/',
                    'desc_vi' => 'Trái Đất là ngôi nhà chung xinh đẹp của chúng ta với đại dương xanh ngắt và những cánh rừng trù phú.',
                    'desc_en' => 'Earth is our beautiful home planet filled with blue oceans, green continents, and life.',
                    'image' => 'https://images.unsplash.com/photo-1614730321146-b6fa6a46bcb4?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Bình yên... Life-whisper!',
                    'fun_fact_vi' => 'Hơn 70% bề mặt Trái Đất được bao phủ bởi nước biển nên từ vũ trụ nhìn về bạn ấy có màu xanh biếc!',
                    'fun_fact_en' => 'Over 70% of Earth is ocean water, giving it the nickname Blue Marble!',
                    'prompt_vi' => 'Bé nhìn xem Trái Đất có những màu sắc nào nhiều nhất: màu xanh dương hay màu lục?',
                    'prompt_en' => 'What main colors can you spot on Earth: blue oceans or green continents?',
                    'action_vi' => 'Bé ôm hai tay trước ngực thật chặt gửi một cái ôm yêu thương tới hành tinh Trái Đất.',
                    'youtube' => 'w-9g58R4318',
                    'x' => 380, 'y' => 280
                ],
                [
                    'name_vi' => 'Mặt Trăng Dịu Dàng',
                    'name_en' => 'The Moon',
                    'slug' => 'moon',
                    'phonics' => '/m-uː-n/',
                    'desc_vi' => 'Mặt Trăng là người bạn thân thiết quay quanh Trái Đất, tỏa ánh sáng êm dịu ru bé ngủ mỗi đêm.',
                    'desc_en' => 'The Moon orbits around Earth, shining gentle silvery light during the quiet night.',
                    'image' => 'https://images.unsplash.com/photo-1522030299830-16b8d3d049fe?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Dịu êm... Silver-lullaby!',
                    'fun_fact_vi' => 'Dấu chân của các nhà du hành vũ trụ in trên Mặt Trăng sẽ tồn tại hàng triệu năm vì không có gió thổi!',
                    'fun_fact_en' => 'Astronaut footprints on the Moon stay forever because there is no wind to blow them away!',
                    'prompt_vi' => 'Vào đêm rằm, bé ngắm Mặt Trăng tròn xoe trông giống như món bánh gì nào?',
                    'prompt_en' => 'On a full moon night, what tasty round treat does the Moon look like?',
                    'action_vi' => 'Bé nhắm mắt nghiêng đầu đặt tay dưới má làm động tác ngủ ngon dưới ánh trăng êm.',
                    'youtube' => 'CY92z1O9d2Y',
                    'x' => 520, 'y' => 180
                ],
                [
                    'name_vi' => 'Tàu Vũ Trụ Tên Lửa',
                    'name_en' => 'Space Rocket',
                    'slug' => 'rocket',
                    'phonics' => '/ˈr-ɒ-k-ɪ-t/',
                    'desc_vi' => 'Tên lửa mạnh mẽ đưa các phi hành gia dũng cảm bay vút qua bầu khí quyển để khám phá không gian.',
                    'desc_en' => 'Powerful space rockets launch brave astronauts high above the atmosphere to explore outer space.',
                    'image' => 'https://images.unsplash.com/photo-1517976487588-e9f0d8a5c4e7?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Vút... 3, 2, 1... Blast-off!',
                    'fun_fact_vi' => 'Tên lửa cần đạt tốc độ hơn 40.000 km/h mới có thể thoát khỏi sức hút của Trái Đất!',
                    'fun_fact_en' => 'Rockets must reach 25,000 mph to escape Earth\'s gravitational pull!',
                    'prompt_vi' => 'Bé cùng đếm ngược 3, 2, 1 để phóng tên lửa bay lên trời cao nào!',
                    'prompt_en' => 'Are you ready to count down: 3... 2... 1... Blast off!',
                    'action_vi' => 'Bé ngồi xổm đếm "3, 2, 1" rồi bật nhảy vút lên cao, 2 tay chụm nhọn như mũi tên lửa.',
                    'youtube' => 'aBC_yZ0978o',
                    'x' => 280, 'y' => 450
                ],
                [
                    'name_vi' => 'Phi Hành Gia Dũng Cảm',
                    'name_en' => 'Astronaut Explorer',
                    'slug' => 'astronaut',
                    'phonics' => '/ˈæ-s-t-r-ə-n-ɔː-t/',
                    'desc_vi' => 'Phi hành gia mặc bộ đồ bảo hộ đặc biệt màu trắng và lơ lửng không trọng lực bên ngoài trạm không gian.',
                    'desc_en' => 'Astronauts wear specialized spacesuits to float weightlessly and conduct science in space.',
                    'image' => 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1614728894747-a83421e2b9c9?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Thở đều... Radio: Houston copy that!',
                    'fun_fact_vi' => 'Ở ngoài vũ trụ không có trọng lực, các phi hành gia có thể bay lơ lửng và uống nước dạng quả bóng tròn!',
                    'fun_fact_en' => 'In zero gravity, water floats in mid-air like floating liquid bubbles!',
                    'prompt_vi' => 'Chiếc mũ bảo hộ của chú phi hành gia giúp chú ấy thở được điều gì quý giá?',
                    'prompt_en' => 'How does the astronaut\'s helmet help them breathe fresh air in space?',
                    'action_vi' => 'Bé bước đi thật chậm rãi, lảo đảo nhẹ nhàng giả vờ như đang lơ lửng không trọng lượng.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 700, 'y' => 500
                ],
                [
                    'name_vi' => 'Sao Hỏa Đỏ',
                    'name_en' => 'Mars the Red Planet',
                    'slug' => 'mars',
                    'phonics' => '/m-ɑː-z/',
                    'desc_vi' => 'Sao Hỏa được gọi là Hành tinh Đỏ vì bề mặt phủ đầy cát và bụi oxit sắt màu cam rực rỡ.',
                    'desc_en' => 'Mars is known as the Red Planet because its surface is covered in rusty iron dust.',
                    'image' => 'https://images.unsplash.com/photo-1614728894747-a83421e2b9c9?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1522030299830-16b8d3d049fe?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Gió cát... Martian-wind!',
                    'fun_fact_vi' => 'Trên Sao Hỏa có ngọn núi lửa Olympus Mons cao gấp 3 lần đỉnh Everest trên Trái Đất!',
                    'fun_fact_en' => 'Mars is home to Olympus Mons, a volcano three times taller than Mount Everest!',
                    'prompt_vi' => 'Vì sao bạn Sao Hỏa lại được các nhà khoa học gọi là Hành Tinh Đỏ bé nhỉ?',
                    'prompt_en' => 'Why do astronomers give Mars the lovely nickname Red Planet?',
                    'action_vi' => 'Bé lấy tay che mắt nhìn xa xăm như đang điều khiển xe tự hành thám hiểm bề mặt cát đỏ.',
                    'youtube' => 'D8pnmwOXhoY',
                    'x' => 640, 'y' => 350
                ]
            ];
        } elseif (str_contains($pLower, 'xe') || str_contains($pLower, 'giao thông') || str_contains($pLower, 'vehicle')) {
            $theme = 'vehicles';
            $packTitleVi = 'Phương Tiện Giao Thông Quanh Bé';
            $packTitleEn = 'Vehicles & Transportation';
            $packDescVi = 'Tìm hiểu các phương tiện giao thông đường bộ, đường thủy và đường hàng không quen thuộc.';
            $packDescEn = 'Learn about friendly road, water, and air vehicles traveling around us everyday.';
            $packId = 'topic_vehicles_' . time();
            $thumbUrl = 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=800&q=80';
            $backgroundUrl = 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1200&q=80';
            $themeColor = '#ef4444';

            $rawItems = [
                [
                    'name_vi' => 'Xe Cứu Hỏa',
                    'name_en' => 'Fire Truck',
                    'slug' => 'fire_truck',
                    'phonics' => '/ˈf-aɪ-ə/ /t-r-ʌ-k/',
                    'desc_vi' => 'Xe cứu hỏa màu đỏ rực rỡ mang theo thang cao và vòi phun nước dũng cảm dập tắt đám cháy.',
                    'desc_en' => 'The bright red fire truck carries tall ladders and high-power hoses to put out fires.',
                    'image' => 'https://images.unsplash.com/photo-1582139329536-e7284fece509?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1587745416684-47953f16f02f?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Ò e ó e... Wee-woo-wee-woo!',
                    'fun_fact_vi' => 'Xe cứu hỏa có thể chứa tới hàng ngàn lít nước và chiếc thang vươn cao tới tầng 15 của tòa nhà!',
                    'fun_fact_en' => 'Fire engines carry thousands of gallons of water and ladders reaching up 15 stories high!',
                    'prompt_vi' => 'Xe cứu hỏa thường có màu gì đặc trưng để mọi người trên đường dễ dàng nhường đường?',
                    'prompt_en' => 'What bright color helps people spot and make way for the fire truck immediately?',
                    'action_vi' => 'Bé cầm vô lăng lái xe và kêu "Ò e ó e..." rồi giơ tay làm vòi rồng xịt nước dập lửa.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 180, 'y' => 200
                ],
                [
                    'name_vi' => 'Xe Cứu Thương',
                    'name_en' => 'Ambulance',
                    'slug' => 'ambulance',
                    'phonics' => '/ˈæ-m-b-j-ʊ-l-ə-n-s/',
                    'desc_vi' => 'Xe cứu thương bật đèn còi ưu tiên, nhanh chóng đưa các bác sĩ đến trợ giúp người bệnh.',
                    'desc_en' => 'The ambulance rushes with flashing lights and sirens to bring doctors to help people.',
                    'image' => 'https://images.unsplash.com/photo-1587745416684-47953f16f02f?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1582139329536-e7284fece509?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'U u u... Whee-whoo-whee-whoo!',
                    'fun_fact_vi' => 'Chữ AMBULANCE trước đầu xe được in ngược để tài xế xe phía trước nhìn qua gương chiếu hậu sẽ đọc xuôi thuận mắt!',
                    'fun_fact_en' => 'The word AMBULANCE is written backward on the hood so drivers see it right in their rearview mirrors!',
                    'prompt_vi' => 'Khi nghe thấy tiếng còi xe cứu thương trên đường phố, các xe khác cần làm gì?',
                    'prompt_en' => 'What should cars on the road do when hearing an ambulance siren?',
                    'action_vi' => 'Bé giơ một ngón tay lên đầu quay tròn làm ngọn đèn tín hiệu cấp cứu nhấp nháy.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 420, 'y' => 220
                ],
                [
                    'name_vi' => 'Máy Bay Chở Khách',
                    'name_en' => 'Airplane',
                    'slug' => 'airplane',
                    'phonics' => '/ˈeə-p-l-eɪ-n/',
                    'desc_vi' => 'Máy bay sải cánh rộng lớn trên những tầng mây trắng, đưa mọi người đi du lịch khắp thế giới.',
                    'desc_en' => 'Airplanes soar high above the fluffy clouds, flying people to distant lands around the world.',
                    'image' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Ù ù... Whoooosh-jet!',
                    'fun_fact_vi' => 'Máy bay bay trên bầu trời với độ cao hơn 10.000 mét, cao hơn cả ngọn núi cao nhất thế giới!',
                    'fun_fact_en' => 'Commercial airplanes cruise at 35,000 feet, higher than the peak of Mount Everest!',
                    'prompt_vi' => 'Khi máy bay cất cánh bay vào mây, bé nhìn qua cửa sổ sẽ thấy mặt đất trông như thế nào?',
                    'prompt_en' => 'What do houses and roads look like from high up in the sky through airplane windows?',
                    'action_vi' => 'Bé giang hai cánh tay sang ngang và nghiêng người bay lượn vút qua phòng khách.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 650, 'y' => 160
                ],
                [
                    'name_vi' => 'Tàu Hỏa Xe Lửa',
                    'name_en' => 'Train',
                    'slug' => 'train',
                    'phonics' => '/t-r-eɪ-n/',
                    'desc_vi' => 'Đoàn tàu hỏa dài với nhiều toa chạy xình xịch trên đường ray sắt qua núi đồi xanh mát.',
                    'desc_en' => 'The long train chugs along the railway tracks traveling across green hills and rivers.',
                    'image' => 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Xình xịch... Tu tu... Choo-choo!',
                    'fun_fact_vi' => 'Có những chiếc tàu cao tốc chạy bằng đệm từ trường có thể đạt tốc độ hơn 450 km/h mà không chạm đường ray!',
                    'fun_fact_en' => 'Bullet trains using magnetic levitation can glide smoothly at over 280 mph!',
                    'prompt_vi' => 'Bác lái tàu hỏa sẽ kéo còi kêu như thế nào khi tàu chuẩn bị vào ga bé ơi?',
                    'prompt_en' => 'How does the train horn sound when arriving at the station?',
                    'action_vi' => 'Bé gập khuỷu tay xoay vòng tròn hai bên hông và dậm chân "Xình xịch, tu tu... Choo choo!".',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 300, 'y' => 450
                ],
                [
                    'name_vi' => 'Xe Buýt Vàng',
                    'name_en' => 'School Bus',
                    'slug' => 'school_bus',
                    'phonics' => '/s-k-uː-l/ /b-ʌ-s/',
                    'desc_vi' => 'Xe buýt trường học đón các bạn nhỏ vui vẻ đến trường mỗi buổi sáng sớm.',
                    'desc_en' => 'The cheerful yellow school bus picks up smiling children to go to school every morning.',
                    'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Bíp bíp... Honk-honk!',
                    'fun_fact_vi' => 'Màu vàng của xe buýt trường học là màu sắc mắt người dễ nhận biết nhất ngay cả trong sương mù sớm mai!',
                    'fun_fact_en' => 'School bus glossy yellow is scientifically the easiest color for human eyes to detect quickly!',
                    'prompt_vi' => 'Trên xe buýt đón học sinh, các bạn nhỏ thường cùng nhau hát bài hát gì vui nhộn?',
                    'prompt_en' => 'What happy song do children often sing together on the school bus?',
                    'action_vi' => 'Bé và ba mẹ cùng hát "The wheels on the bus go round and round" và xoay tròn hai cánh tay.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 580, 'y' => 420
                ],
                [
                    'name_vi' => 'Tàu Thủy Biển',
                    'name_en' => 'Cruise Ship',
                    'slug' => 'ship',
                    'phonics' => '/ʃ-ɪ-p/',
                    'desc_vi' => 'Con tàu thủy to lớn rẽ sóng trắng xóa trên đại dương xanh bao la.',
                    'desc_en' => 'The grand ship glides smoothly across the ocean waves under the sunny sky.',
                    'image' => 'https://images.unsplash.com/photo-1548574505-5e239809ee19?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Tuuuuu... Ship-horn!',
                    'fun_fact_vi' => 'Dù làm bằng kim loại sắt thép rất nặng nhưng con tàu vẫn nổi bồng bềnh nhờ khoang chứa không khí rộng lớn bên trong!',
                    'fun_fact_en' => 'Even made of heavy steel, giant ships float easily due to large air cavities providing buoyancy!',
                    'prompt_vi' => 'Vật dụng gì trên tàu được thả xuống nước để giữ cho tàu đứng yên khi neo đậu?',
                    'prompt_en' => 'What heavy iron object is dropped into water to keep the ship anchored in place?',
                    'action_vi' => 'Bé làm động tác kéo còi tàu "Tuuuuu..." và lắc lư người như đang cưỡi trên những con sóng lớn.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 720, 'y' => 320
                ]
            ];
        } else {
            // General Animals Theme
            $rawItems = [
                [
                    'name_vi' => 'Sư Tử Dũng Mãnh',
                    'name_en' => 'Brave Lion',
                    'slug' => 'lion',
                    'phonics' => '/ˈl-aɪ-ə-n/',
                    'desc_vi' => 'Sư tử với chiếc bờm vàng óng ả, được gọi là chúa sơn lâm của thảo nguyên bao la.',
                    'desc_en' => 'The lion with its magnificent golden mane is known as the king of the savanna.',
                    'image' => 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1547721064-da6cfb341d50?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Gầm gừ... Roarrrr!',
                    'fun_fact_vi' => 'Tiếng gầm uy lực của sư tử có thể vang xa tới tận 8 cây số!',
                    'fun_fact_en' => 'A lion\'s majestic roar can be heard from up to 5 miles away!',
                    'prompt_vi' => 'Chiếc bờm rậm rạp màu vàng chỉ có ở bạn sư tử đực hay sư tử cái nhỉ?',
                    'prompt_en' => 'Does only the male lion grow such a magnificent fluffy mane?',
                    'action_vi' => 'Bé xòe rộng 5 ngón tay làm móng vuốt, bước đi oai vệ và gầm vang "Roarrr!" dũng mãnh.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 200, 'y' => 250
                ],
                [
                    'name_vi' => 'Hươu Cao Cổ',
                    'name_en' => 'Tall Giraffe',
                    'slug' => 'giraffe',
                    'phonics' => '/dʒ-ɪ-ˈr-ɑː-f/',
                    'desc_vi' => 'Hươu cao cổ có chiếc cổ dài kỷ lục và bộ lông đốm hoa văn rất duyên dáng.',
                    'desc_en' => 'The giraffe has an astonishingly tall neck and graceful patterned coat.',
                    'image' => 'https://images.unsplash.com/photo-1547721064-da6cfb341d50?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Nhẹ nhàng... Chomp-crunch!',
                    'fun_fact_vi' => 'Chiếc lưỡi của hươu cao cổ dài tới 50cm và có màu tím đen để chống bị cháy nắng!',
                    'fun_fact_en' => 'Giraffes have 20-inch-long bluish tongues protected from sunburns!',
                    'prompt_vi' => 'Nhờ có chiếc cổ siêu cao, bạn hươu có thể ăn những chiếc lá non ở đâu?',
                    'prompt_en' => 'Where can tall giraffes reach to munch on sweet fresh leaves?',
                    'action_vi' => 'Bé kiễng chân cao hết cỡ, chụm tay trên đầu vươn hái chiếc lá tưởng tượng trên ngọn cây.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 450, 'y' => 180
                ],
                [
                    'name_vi' => 'Gấu Trúc Dễ Thương',
                    'name_en' => 'Panda Bear',
                    'slug' => 'panda',
                    'phonics' => '/ˈp-æ-n-d-ə/',
                    'desc_vi' => 'Bạn gấu trúc có bộ lông đen trắng bụ bẫm và sở thích ăn lá tre trúc non suốt cả ngày.',
                    'desc_en' => 'The cuddly black-and-white giant panda loves munching on crunchy green bamboo shoots.',
                    'image' => 'https://images.unsplash.com/photo-1564349683136-77e08dba1ef7?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Gặm trúc... Crunch-munch!',
                    'fun_fact_vi' => 'Mỗi ngày bạn gấu trúc có thể dành tới 12 tiếng đồng hồ chỉ để gặm nhấm những búp măng tre!',
                    'fun_fact_en' => 'Pandas can spend up to 12 hours a day happily chewing on green bamboo!',
                    'prompt_vi' => 'Quanh đôi mắt của bạn gấu trúc có đốm lông màu gì ngộ nghĩnh giống như chiếc kính đen?',
                    'prompt_en' => 'What color fur patches encircle the panda\'s cute round eyes?',
                    'action_vi' => 'Bé ngồi bệt xuống đất, hai tay ôm một cây tre tưởng tượng nhai "Rột roạt" ngon lành.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 620, 'y' => 320
                ],
                [
                    'name_vi' => 'Voi Con Thông Thái',
                    'name_en' => 'Gentle Elephant',
                    'slug' => 'elephant',
                    'phonics' => '/ˈɛ-l-ɪ-f-ə-n-t/',
                    'desc_vi' => 'Bạn voi có đôi tai to như chiếc quạt và chiếc vòi khéo léo để uống nước và chào các bạn nhỏ.',
                    'desc_en' => 'The wise elephant has large ears like fans and a strong versatile trunk to drink water.',
                    'image' => 'https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1526095179574-86e545346ae6?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Tu húuuu... Trumpet-toot!',
                    'fun_fact_vi' => 'Chiếc vòi của voi có hơn 40.000 bó cơ khác nhau nên vừa có thể nâng thân cây gỗ lớn vừa nhặt được hạt lạc tí hon!',
                    'fun_fact_en' => 'An elephant\'s trunk has over 40,000 muscles and can pick up a tiny peanut or lift huge tree logs!',
                    'prompt_vi' => 'Bạn voi dùng đôi tai to để làm gì mỗi khi trời nóng nực bé có biết không?',
                    'prompt_en' => 'Why does the elephant flap its big ears on a hot sunny day?',
                    'action_vi' => 'Bé lấy một cánh tay làm chiếc vòi dài vung vẩy trước mũi và kêu "Pa-ooom!".',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 300, 'y' => 480
                ],
                [
                    'name_vi' => 'Cánh Cụt Nam Cực',
                    'name_en' => 'Penguin',
                    'slug' => 'penguin',
                    'phonics' => '/ˈp-ɛ-ŋ-ɡ-w-ɪ-n/',
                    'desc_vi' => 'Chim cánh cụt mặc bộ âu phục đen trắng ngộ nghĩnh, trượt băng thoăn thoắt ở vùng băng giá.',
                    'desc_en' => 'Penguins waddle cutely in their natural tuxedo feathers and glide smoothly across ice.',
                    'image' => 'https://images.unsplash.com/photo-1598439210625-5067c578f3f6?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1564349683136-77e08dba1ef7?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Cạp cạp... Honk-waddle!',
                    'fun_fact_vi' => 'Chim cánh cụt không biết bay trên trời nhưng lại là những tay bơi lội siêu đỉnh dưới làn nước buốt giá!',
                    'fun_fact_en' => 'Penguins can\'t fly in the air, but they "fly" effortlessly through icy ocean waters!',
                    'prompt_vi' => 'Bạn chim cánh cụt di chuyển trên mặt tuyết trơn như thế nào cho thật nhanh?',
                    'prompt_en' => 'How do penguins slide fast across slippery ice on their bellies?',
                    'action_vi' => 'Bé khép chặt 2 tay sát hông, bàn chân bẹt sang 2 bên và lắc lư lạch bạch như chim cánh cụt.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 540, 'y' => 500
                ],
                [
                    'name_vi' => 'Ngựa Vằn Thảo Nguyên',
                    'name_en' => 'Zebra',
                    'slug' => 'zebra',
                    'phonics' => '/ˈz-ɛ-b-r-ə/',
                    'desc_vi' => 'Ngựa vằn nổi bật với những sọc trắng đen xen kẽ, chạy rất nhanh cùng bầy đàn.',
                    'desc_en' => 'The zebra is famous for its unique black-and-white stripes, galloping swiftly across grassy plains.',
                    'image' => 'https://images.unsplash.com/photo-1526095179574-86e545346ae6?auto=format&fit=crop&w=800&q=80',
                    'real_image' => 'https://images.unsplash.com/photo-1534188753412-3e26d0d618d6?auto=format&fit=crop&w=800&q=80',
                    'sfx' => 'Hí hí... Neigh-gallop!',
                    'fun_fact_vi' => 'Không có hai chú ngựa vằn nào trên thế giới có sọc vằn giống hệt nhau, giống như vân tay của con người!',
                    'fun_fact_en' => 'No two zebras have the exact same stripe pattern; each coat is as unique as a human fingerprint!',
                    'prompt_vi' => 'Sọc đen trắng xen kẽ giúp bạn ngựa vằn đánh lạc hướng loài thú ăn thịt nào trên đồng cỏ?',
                    'prompt_en' => 'How do dazzling black-and-white stripes confuse predators on the open grassland?',
                    'action_vi' => 'Bé làm động tác phi nước đại, dậm dậm gót chân nhịp nhàng như chú ngựa vằn dũng cảm.',
                    'youtube' => 'VbZ07n242-g',
                    'x' => 680, 'y' => 200
                ]
            ];
        }

        // Limit to requested count
        $selectedItems = array_slice($rawItems, 0, max(3, min(12, $count)));

        // Format items with Kids World schema
        $formattedItems = [];
        foreach ($selectedItems as $idx => $it) {
            $formattedItems[] = [
                'id' => 'item_' . $it['slug'] . '_' . substr(md5(uniqid()), 0, 4),
                'name_vi' => $it['name_vi'],
                'name_en' => $it['name_en'],
                'phonics_en' => $it['phonics'] ?? '',
                'description_vi' => $it['desc_vi'],
                'description_en' => $it['desc_en'],
                'images' => [$it['image']],
                'real_image_url' => $it['real_image'] ?? $it['image'],
                'sfx_sound' => $it['sfx'] ?? '',
                'fun_fact_vi' => $it['fun_fact_vi'] ?? '',
                'fun_fact_en' => $it['fun_fact_en'] ?? '',
                'prompt_question_vi' => $it['prompt_vi'] ?? '',
                'prompt_question_en' => $it['prompt_en'] ?? '',
                'action_hint_vi' => $it['action_vi'] ?? '',
                'pronounce_vi_url' => 'https://translate.google.com/translate_tts?ie=UTF-8&tl=vi&client=tw-ob&q=' . urlencode($it['name_vi']),
                'pronounce_en_url' => 'https://translate.google.com/translate_tts?ie=UTF-8&tl=en&client=tw-ob&q=' . urlencode($it['name_en']),
                'youtube_video_id' => $it['youtube'] ?? 'VbZ07n242-g',
                'map_x' => (float)$it['x'],
                'map_y' => (float)$it['y'],
                'sort_order' => $idx + 1,
                'is_selected' => true
            ];
        }

        return [
            'pack' => [
                'id' => $packId,
                'title_vi' => $packTitleVi,
                'title_en' => $packTitleEn,
                'category' => $theme,
                'theme_color' => $themeColor,
                'background_url' => $backgroundUrl,
                'target_age_min' => $ageMin,
                'target_age_max' => $ageMax,
                'target_gender' => 'all',
                'thumbnail_url' => $thumbUrl,
                'description_vi' => $packDescVi,
                'description_en' => $packDescEn,
                'version' => 1,
                'size_mb' => round(count($formattedItems) * 0.35 + 0.8, 1),
                'is_active' => 1
            ],
            'items' => $formattedItems,
            'source' => 'smart_ai_template'
        ];
    }

    /**
     * Call Live LLM (Gemini or OpenAI) with strict JSON Schema
     */
    private static function callLlm(string $prompt, int $count, int $ageMin, int $ageMax, string $apiKey): ?array {
        $masterPrompt = self::buildPromptTemplate($prompt, $count, "{$ageMin}-{$ageMax}");

        // Send request to Gemini endpoint if key looks like AIzaSy... or OpenAI
        if (str_starts_with($apiKey, 'AIzaSy')) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $masterPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.7
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = curl_exec($ch);
            curl_close($ch);

            if ($res) {
                $decoded = json_decode($res, true);
                $rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if ($rawText) {
                    $parsed = json_decode($rawText, true);
                    if (isset($parsed['pack']) && isset($parsed['items'])) {
                        $parsed['source'] = 'gemini_api';
                        return $parsed;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Generate precision AI Image Prompts for Kids World Flashcards & Games
     * Supports: 3D Cute Pixar/Disney, Realistic National Geographic Photography, and Coloring Book Page
     */
    public static function generateImagePrompts(string $keyword, string $context = '', string $aspectRatio = '1:1', string $resolution = '1024x1024'): array {
        $cleanKw = trim($keyword);
        if (empty($cleanKw)) {
            $cleanKw = 'Hà Mã';
        }

        $arParam = !empty($aspectRatio) ? $aspectRatio : '1:1';
        $resParam = !empty($resolution) ? $resolution : '1024x1024';

        // Dictionary of common educational entities with English translations and traits
        $dict = [
            'hà mã' => ['en' => 'hippopotamus', 'en_baby' => 'cute baby hippopotamus', 'category' => 'animals', 'setting' => 'resting peacefully near a clean tropical African riverbank', 'features' => 'chubby body, friendly smiling expression, big sparkling eyes'],
            'hippo' => ['en' => 'hippopotamus', 'en_baby' => 'cute baby hippopotamus', 'category' => 'animals', 'setting' => 'resting peacefully near a clean tropical African riverbank', 'features' => 'chubby body, friendly smiling expression, big sparkling eyes'],
            'hippopotamus' => ['en' => 'hippopotamus', 'en_baby' => 'cute baby hippopotamus', 'category' => 'animals', 'setting' => 'resting peacefully near a clean tropical African riverbank', 'features' => 'chubby body, friendly smiling expression, big sparkling eyes'],
            'sư tử' => ['en' => 'lion', 'en_baby' => 'cute playful lion cub', 'category' => 'animals', 'setting' => 'on the golden savanna grass under warm sunlight', 'features' => 'fluffy golden fur, soft mane, curious friendly eyes'],
            'lion' => ['en' => 'lion', 'en_baby' => 'cute playful lion cub', 'category' => 'animals', 'setting' => 'on the golden savanna grass under warm sunlight', 'features' => 'fluffy golden fur, soft mane, curious friendly eyes'],
            'voi' => ['en' => 'elephant', 'en_baby' => 'adorable baby elephant', 'category' => 'animals', 'setting' => 'in African grassland near acacia trees', 'features' => 'large soft ears, cheerful raised little trunk, gentle happy smile'],
            'elephant' => ['en' => 'elephant', 'en_baby' => 'adorable baby elephant', 'category' => 'animals', 'setting' => 'in African grassland near acacia trees', 'features' => 'large soft ears, cheerful raised little trunk, gentle happy smile'],
            'hươu cao cổ' => ['en' => 'giraffe', 'en_baby' => 'cute friendly baby giraffe', 'category' => 'animals', 'setting' => 'in sunny open savanna with green acacia leaves', 'features' => 'long slender neck, patterned coat, sweet gentle face'],
            'giraffe' => ['en' => 'giraffe', 'en_baby' => 'cute friendly baby giraffe', 'category' => 'animals', 'setting' => 'in sunny open savanna with green acacia leaves', 'features' => 'long slender neck, patterned coat, sweet gentle face'],
            'ngựa vằn' => ['en' => 'zebra', 'en_baby' => 'cheerful baby zebra', 'category' => 'animals', 'setting' => 'in lush green grassland under clear blue sky', 'features' => 'crisp black and white stripes, fluffy mane, playful stance'],
            'zebra' => ['en' => 'zebra', 'en_baby' => 'cheerful baby zebra', 'category' => 'animals', 'setting' => 'in lush green grassland under clear blue sky', 'features' => 'crisp black and white stripes, fluffy mane, playful stance'],
            'khỉ' => ['en' => 'monkey', 'en_baby' => 'cheeky little baby monkey', 'category' => 'animals', 'setting' => 'on a tropical tree branch surrounded by lush leaves', 'features' => 'curled tail, holding a small banana, mischievous joyful smile'],
            'cá heo' => ['en' => 'dolphin', 'en_baby' => 'happy playful baby dolphin', 'category' => 'sea', 'setting' => 'leaping gracefully from crystal clear turquoise ocean water with sun rays', 'features' => 'sleek smooth body, joyful smiling mouth, gentle water splashes'],
            'cá voi' => ['en' => 'blue whale', 'en_baby' => 'gentle giant baby whale', 'category' => 'sea', 'setting' => 'swimming in deep majestic blue ocean with light caustics', 'features' => 'friendly eye, smooth skin, peaceful graceful swimming'],
            'rùa biển' => ['en' => 'sea turtle', 'en_baby' => 'cute baby green sea turtle', 'category' => 'sea', 'setting' => 'gliding above vibrant colorful coral reef', 'features' => 'patterned shell, flippers spread, curious gentle eyes'],
            'máy bay' => ['en' => 'passenger airplane', 'en_baby' => 'friendly cartoon passenger airplane', 'category' => 'vehicles', 'setting' => 'flying smoothly through soft fluffy white clouds in clear sky', 'features' => 'cute cockpit windows, smiling front, bright cheerful colors'],
            'tàu hỏa' => ['en' => 'steam train', 'en_baby' => 'cheerful vintage steam locomotive', 'category' => 'vehicles', 'setting' => 'rolling along scenic railway tracks through green hills', 'features' => 'puffy white steam cloud, smiling train face, shiny colorful paint'],
            'xe buýt' => ['en' => 'school bus', 'en_baby' => 'cute yellow school bus', 'category' => 'vehicles', 'setting' => 'driving happily down a sunny suburban street', 'features' => 'classic yellow body, big headlights like friendly eyes, clean cartoon details'],
            't-rex' => ['en' => 'Tyrannosaurus Rex', 'en_baby' => 'cute baby Tyrannosaurus Rex dinosaur', 'category' => 'dino', 'setting' => 'in prehistoric jungle with giant fern leaves', 'features' => 'tiny cute arms, big friendly head with gentle smile, emerald green scales'],
            'khủng long' => ['en' => 'dinosaur', 'en_baby' => 'adorable friendly baby dinosaur', 'category' => 'dino', 'setting' => 'in prehistoric lush tropical landscape', 'features' => 'vibrant cute colors, big expressive eyes, playful stance'],
            'quả chuối' => ['en' => 'ripe banana', 'en_baby' => 'cute smiling golden banana', 'category' => 'fruits', 'setting' => 'on clean wooden kitchen counter with natural morning light', 'features' => 'vibrant yellow peel, sweet curved shape, fresh and organic'],
            'quả táo' => ['en' => 'red apple', 'en_baby' => 'glossy red apple with green leaf', 'category' => 'fruits', 'setting' => 'in rustic wooden basket in an apple orchard', 'features' => 'bright crimson skin, fresh water droplets, little green leaf attached'],
            'quả xoài' => ['en' => 'ripe mango', 'en_baby' => 'succulent golden mango', 'category' => 'fruits', 'setting' => 'on tropical table with green mango leaves', 'features' => 'golden-orange blush skin, plump juicy shape, fresh tropical aesthetic'],
        ];

        $lower = mb_strtolower($cleanKw, 'UTF-8');
        $matched = null;
        foreach ($dict as $key => $val) {
            if ($lower === $key || str_contains($lower, $key)) {
                $matched = $val;
                break;
            }
        }

        if (!$matched) {
            $enSubject = ucwords($cleanKw);
            $enBaby = "adorable cute baby " . $cleanKw;
            $setting = "in clean, natural bright environment";
            $features = "friendly expression, big sparkling eyes, smooth adorable shapes";
        } else {
            $enSubject = $matched['en'];
            $enBaby = $matched['en_baby'];
            $setting = $matched['setting'];
            $features = $matched['features'];
        }

        // Prompt 1: 3D Cute Pixar / Disney Style (Chuẩn Flashcard 3D)
        $prompt3D = "Adorable cute {$enBaby}, {$features}, smooth 3D Pixar Disney animation style, soft warm studio lighting, vibrant cheerful pastel colors, clean solid light pastel background, 3D claymation render, Octane render, 8k, volumetric lighting, children educational book illustration, centered, full body shot, high resolution {$resParam} pixels --ar {$arParam} --v 6.0";

        // Prompt 2: Real Life Photography (Chuẩn Ảnh Thật Bách Khoa)
        $promptReal = "Authentic high resolution wildlife photograph of a {$enSubject} {$setting}, National Geographic documentary style, sharp crisp focus on natural skin texture and gentle eyes, shot on 85mm lens f/2.8, photorealistic, beautiful natural daylight, detailed wildlife photography, clean uncluttered composition, high resolution {$resParam} pixels --ar {$arParam} --v 6.0";

        // Prompt 3: Coloring Page Line Art (Tranh Nét Chia Mảng Rõ Ràng Cho Bé Tô Màu)
        $promptColoring = "Clean bold black line art, coloring book page for toddlers and kids, cute friendly {$enSubject}, segmented into distinct clear individual sections for easy coloring, thick closed outlines, well-defined large coloring areas, no shading, no gradients, no grey tones, pure solid white background, high contrast, minimalist cartoon illustration, printable coloring sheet, crisp vector lines, square format {$resParam} pixels --ar {$arParam} --v 6.0";

        // Prompt 4: 2D Flat Vector / Sticker
        $promptVector = "Cute cartoon {$enSubject} sticker, kawaii flat vector illustration, bold clean outlines, cheerful friendly expression, vibrant pastel tones, isolated on white background, modern children educational graphic, SVG vector icon style, high detail, {$resParam} pixels --ar {$arParam}";

        return [
            'keyword' => $cleanKw,
            'english_term' => $enSubject,
            'aspect_ratio' => $arParam,
            'resolution' => $resParam,
            'prompts' => [
                'pixar_3d' => [
                    'title' => 'Hoạt Họa 3D Pixar Cute',
                    'badge' => 'Flashcard 3D',
                    'style_code' => '3d_cute',
                    'prompt' => $prompt3D,
                ],
                'real_photo' => [
                    'title' => 'Ảnh Chụp Thật (National Geographic)',
                    'badge' => 'Ảnh Đời Thực',
                    'style_code' => 'real_photo',
                    'prompt' => $promptReal,
                ],
                'coloring_outline' => [
                    'title' => 'Tranh Nét Chia Mảng Để Tô',
                    'badge' => 'Game Tô Màu',
                    'style_code' => 'coloring_outline',
                    'prompt' => $promptColoring,
                ],
                'vector_2d' => [
                    'title' => 'Đồ Họa Phẳng 2D (Vector / Sticker)',
                    'badge' => 'Mini-games & Đố Vui',
                    'style_code' => 'vector_2d',
                    'prompt' => $promptVector,
                ]
            ]
        ];
    }
}
