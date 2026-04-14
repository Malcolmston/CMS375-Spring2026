<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telemedicine | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200">

    <?php include __DIR__ . '/../nav.php'; ?>

    <!-- Hero -->
    <section class="pt-40 pb-16 px-10 text-center max-w-4xl mx-auto">
        <h1 class="text-5xl font-serif font-light text-slate-800 leading-tight mb-6 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            Telemedicine
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            See a MedHealth provider from the comfort of your home. Quality care, zero commute.
        </p>
        <a href="/contact" class="inline-block mt-8 bg-slate-800 hover:bg-slate-700 text-white font-semibold rounded-full px-8 py-3 text-sm transition-all animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
            Start a Visit
        </a>
    </section>

    <section class="max-w-5xl mx-auto px-6 pb-20">

        <!-- How It Works -->
        <h2 class="text-3xl font-serif font-light text-slate-800 mb-10 text-center">How It Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-bold">1</div>
                <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fa-graphite fa-thin fa-calendar text-blue-500 fa-lg"></i>

                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-3">Book</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Schedule a same-day or next-day video visit through our patient portal or mobile app. Choose your provider or let us match you.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-bold">2</div>
                <div class="w-14 h-14 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fa-light fa-video text-emerald-500 fa-lg"></i>
                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-3">Connect</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Join your secure video visit — no downloads required. Your provider reviews your history before the call so you can get straight to the point.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center relative">
                <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-bold">3</div>
                <div class="w-14 h-14 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-5">
                    <i class="fa-sharp fa-light fa-circle-check text-amber-500 fa-lg"></i>
                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-3">Get Care</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Receive your diagnosis, treatment plan, and prescriptions sent directly to your pharmacy. Visit notes are in your portal within the hour.</p>
            </div>
        </div>

        <!-- Benefits -->
        <h2 class="text-3xl font-serif font-light text-slate-800 mb-8 text-center">Why Choose Telemedicine?</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
            <?php
            $benefits = [
                ['title' => 'No Waiting Rooms', 'desc' => 'Skip the commute and the wait. Most telemedicine visits connect you to a provider within minutes.'],
                ['title' => 'Available 7 Days a Week', 'desc' => 'Our telemedicine team is available evenings and weekends — when your regular office might be closed.'],
                ['title' => 'Covered by Most Insurers', 'desc' => 'Telemedicine visits are covered by most major insurance plans at the same cost as in-person visits.'],
                ['title' => 'Your Records Stay Synced', 'desc' => 'Everything from your telemedicine visit is stored in your MedHealth record, visible to your whole care team.'],
            ];
            foreach ($benefits as $b): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 flex gap-4">
                <div class="w-2 flex-shrink-0 bg-slate-300 rounded-full"></div>
                <div>
                    <h3 class="font-semibold text-slate-800 mb-1"><?= $b['title'] ?></h3>
                    <p class="text-slate-600 text-sm leading-relaxed"><?= $b['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Compatible Devices -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
            <h2 class="text-2xl font-serif font-light text-slate-800 mb-4">Compatible Devices</h2>
            <p class="text-slate-600 text-sm mb-6">Our telemedicine platform works on any modern device — no special software required.</p>
            <div class="flex flex-wrap justify-center gap-6">
                <div class="flex items-center gap-2 text-slate-700 font-medium text-sm">
                    <i class="fa-light fa-desktop text-slate-500 fa-lg"></i>
                    Desktop / Laptop
                </div>
                <div class="flex items-center gap-2 text-slate-700 font-medium text-sm">
                    <i class="fa-light fa-mobile text-slate-500 fa-lg"></i>
                    Smartphone (iOS &amp; Android)
                </div>
                <div class="flex items-center gap-2 text-slate-700 font-medium text-sm">
                    <i class="fa-light fa-tablet text-slate-500 fa-lg"></i>
                    Tablet
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../../footer.php'; ?>
</body>
</html>
