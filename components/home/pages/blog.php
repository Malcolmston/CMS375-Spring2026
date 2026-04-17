<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use jcobhams\NewsApi\NewsApi;

$static_posts = [
    ['category'=>'Heart Health','cat_color'=>'rose','title'=>'5 Habits That Protect Your Heart — Backed by Science','excerpt'=>'Cardiovascular disease remains the leading cause of death worldwide, but research shows that relatively simple lifestyle changes can reduce your risk by up to 80%. Our cardiologist breaks down what actually works.','date'=>'March 18, 2026','url'=>'#'],
    ['category'=>'Telemedicine','cat_color'=>'blue','title'=>'Is Telemedicine Right for Your Next Visit? Here\'s How to Decide','excerpt'=>'Virtual care has come a long way. From cold management to mental health check-ins, we explore which conditions are well-suited for telemedicine — and which still require an in-person exam.','date'=>'March 11, 2026','url'=>'#'],
    ['category'=>'Nutrition','cat_color'=>'emerald','title'=>'What a Registered Dietitian Actually Eats in a Day','excerpt'=>'Forget the fad diets. Our in-house dietitian shares a realistic, evidence-based approach to eating well — without obsessing over every calorie or cutting out entire food groups.','date'=>'March 4, 2026','url'=>'#'],
    ['category'=>'Mental Health','cat_color'=>'violet','title'=>'The Connection Between Sleep and Mental Wellbeing','excerpt'=>'Poor sleep and poor mental health form a vicious cycle that\'s hard to break. Our behavioral health team explains the neuroscience — and shares practical strategies that actually help.','date'=>'February 25, 2026','url'=>'#'],
    ['category'=>'Vaccines','cat_color'=>'amber','title'=>'Adult Vaccines You Might Be Missing — A Complete Guide','excerpt'=>'Many adults assume vaccines are just for children. In reality, several important immunizations are recommended well into adulthood. Here\'s what you need and when to get it.','date'=>'February 17, 2026','url'=>'#'],
    ['category'=>'Preventive Care','cat_color'=>'cyan','title'=>'The Screenings You Should Never Skip After 40','excerpt'=>'Your forties are a pivotal decade for preventive health. From colonoscopies to blood pressure monitoring, we outline the screenings that catch serious conditions early — when they\'re most treatable.','date'=>'February 10, 2026','url'=>'#'],
];

$posts      = $static_posts;
$page_error = null;

$loaded = max(12, (int) ($_GET['loaded'] ?? 12));

$api_key = getenv('NEWS_API_KEY');
if ($api_key) {
    try {
        $newsapi  = @new NewsApi($api_key); // @ suppresses dynamic-property deprecation in PHP 8.2 (vendor issue)
        // getTopHeadLines($q, $sources, $country, $category, $page_size, $page)
        // Fetch extra buffer on top of $loaded to account for filtered-out articles
        $response = $newsapi->getTopHeadLines(null, null, 'us', 'health', min($loaded + 8, 100));

        if (!empty($response->articles)) {
            $articles = array_filter($response->articles, fn($a) =>
                !empty($a->title) &&
                $a->title !== '[Removed]' &&
                !empty($a->description) &&
                $a->description !== '[Removed]' &&
                !empty($a->url)
            );

            $posts = array_slice(array_map(function ($article) {
                return [
                    'category'  => $article->source->name ?? 'Health News',
                    'cat_color' => 'blue',
                    'title'     => $article->title,
                    'excerpt'   => $article->description,
                    'date'      => date('F j, Y', strtotime($article->publishedAt)),
                    'url'       => $article->url,
                ];
            }, array_values($articles)), 0, $loaded);
        }
    } catch (\Throwable $e) {
        $page_error = 'Could not load live articles: ' . $e->getMessage();
    }
} else {
    $page_error = 'NEWS_API_KEY is not set — showing cached articles.';
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200">

    <?php include __DIR__ . '/../nav.php'; ?>

    <?php if ($page_error): ?>
    <div id="error-popup" class="fixed inset-0 z-50 flex items-start justify-center pt-6 px-4 pointer-events-none">
        <div class="pointer-events-auto flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl shadow-lg px-5 py-4 max-w-sm w-full">
            <svg class="mt-0.5 shrink-0 w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <span class="text-sm font-medium flex-1"><?= htmlspecialchars($page_error) ?></span>
            <button onclick="document.getElementById('error-popup').classList.add('hidden')" class="text-red-400 hover:text-red-600 text-lg leading-none">&times;</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hero -->
    <section class="pt-40 pb-16 px-10 text-center max-w-4xl mx-auto">
        <h1 class="text-5xl font-serif font-light text-slate-800 leading-tight mb-6 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            MedHealth Blog
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            Evidence-based health insights, wellness tips, and news from our clinical team — written for real people, not textbooks.
        </p>
    </section>

    <section class="max-w-5xl mx-auto px-6 pb-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($posts as $post): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex flex-col">
                <div class="mb-3">
                    <span class="text-xs font-semibold bg-<?= $post['cat_color'] ?>-50 text-<?= $post['cat_color'] ?>-600 px-3 py-1 rounded-full"><?= $post['category'] ?></span>
                </div>
                <h3 class="font-semibold text-slate-800 text-base leading-snug mb-3 flex-grow"><?= $post['title'] ?></h3>
                <p class="text-slate-600 text-sm leading-relaxed mb-4"><?= $post['excerpt'] ?></p>
                <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-100">
                    <span class="text-xs text-slate-400"><?= $post['date'] ?></span>
                    <a href="<?= htmlspecialchars($post['url']) ?>" target="_blank" rel="noopener" class="text-slate-700 text-sm font-medium hover:text-slate-900 transition-colors">Read more &rarr;</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Load More -->
        <?php if (count($posts) >= $loaded): ?>
        <div class="text-center mt-12">
            <a href="?loaded=<?= $loaded + 12 ?>" class="inline-block bg-slate-800 hover:bg-slate-700 text-white font-semibold rounded-full px-8 py-3 text-sm transition-all">Load More Articles</a>
        </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
