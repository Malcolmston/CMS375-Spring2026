<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Team | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200">

    <?php include __DIR__ . '/../nav.php'; ?>

    <!-- Hero -->
    <section class="pt-40 pb-16 px-10 text-center max-w-4xl mx-auto">
        <h1 class="text-5xl font-serif font-light text-slate-800 leading-tight mb-6 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            Our Team
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            MedHealth is led by a diverse group of clinicians, technologists, and operators united by a single purpose: better health for everyone.
        </p>
    </section>

    <!-- Leadership Grid -->
    <section class="max-w-5xl mx-auto px-6 pb-20">
        <h2 class="text-3xl font-serif font-light text-slate-800 mb-10 text-center">Leadership Team</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <!-- CEO -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex gap-6 items-start">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-slate-300 to-slate-400 flex-shrink-0 flex items-center justify-center text-white text-2xl font-serif font-light">EV</div>
                <div>
                    <h3 class="text-xl font-semibold text-slate-800">Dr. Eleanor Voss</h3>
                    <p class="text-sm text-slate-500 font-medium mb-3">Chief Executive Officer &amp; Co-Founder</p>
                    <p class="text-slate-600 text-sm leading-relaxed">A board-certified internist with 25 years of clinical experience, Dr. Voss founded MedHealth after recognizing a systemic gap between excellent medical knowledge and patient-centered delivery. She holds an MD from Johns Hopkins and an MBA from Wharton.</p>
                </div>
            </div>

            <!-- CMO -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex gap-6 items-start">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-300 to-blue-400 flex-shrink-0 flex items-center justify-center text-white text-2xl font-serif font-light">MR</div>
                <div>
                    <h3 class="text-xl font-semibold text-slate-800">Dr. Marcus Reid</h3>
                    <p class="text-sm text-slate-500 font-medium mb-3">Chief Medical Officer</p>
                    <p class="text-slate-600 text-sm leading-relaxed">Dr. Reid oversees clinical quality and patient safety across all MedHealth facilities. A former department chief at UCSF Medical Center, he is a leading voice in value-based care and holds fellowships in both cardiology and health policy.</p>
                </div>
            </div>

            <!-- CTO -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex gap-6 items-start">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-300 to-emerald-400 flex-shrink-0 flex items-center justify-center text-white text-2xl font-serif font-light">SP</div>
                <div>
                    <h3 class="text-xl font-semibold text-slate-800">Saanvi Patel</h3>
                    <p class="text-sm text-slate-500 font-medium mb-3">Chief Technology Officer</p>
                    <p class="text-slate-600 text-sm leading-relaxed">Saanvi leads MedHealth's engineering and data science teams, building the platform that powers our telemedicine and patient portal products. Previously VP of Engineering at a leading health-tech startup, she holds a BS in Computer Science from MIT.</p>
                </div>
            </div>

            <!-- COO -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 flex gap-6 items-start">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-amber-300 to-amber-400 flex-shrink-0 flex items-center justify-center text-white text-2xl font-serif font-light">JK</div>
                <div>
                    <h3 class="text-xl font-semibold text-slate-800">James Kowalski</h3>
                    <p class="text-sm text-slate-500 font-medium mb-3">Chief Operating Officer</p>
                    <p class="text-slate-600 text-sm leading-relaxed">James manages day-to-day operations, clinic expansion, and supply chain for MedHealth's 30 locations. With a background in hospital administration and a Master's in Healthcare Management from Georgetown, he has overseen operations serving millions of patients.</p>
                </div>
            </div>
        </div>

        <!-- Board Section -->
        <h2 class="text-3xl font-serif font-light text-slate-800 mt-16 mb-8 text-center">Board of Directors</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-rose-200 to-rose-300 mx-auto mb-4 flex items-center justify-center text-slate-700 font-semibold">LH</div>
                <h3 class="font-semibold text-slate-800">Linda Hartley</h3>
                <p class="text-sm text-slate-500 mt-1">Board Chair, Former Secretary of Health</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-violet-200 to-violet-300 mx-auto mb-4 flex items-center justify-center text-slate-700 font-semibold">TN</div>
                <h3 class="font-semibold text-slate-800">Thomas Nguyen</h3>
                <p class="text-sm text-slate-500 mt-1">Partner, HealthBridge Ventures</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-cyan-200 to-cyan-300 mx-auto mb-4 flex items-center justify-center text-slate-700 font-semibold">AC</div>
                <h3 class="font-semibold text-slate-800">Dr. Amara Chibuike</h3>
                <p class="text-sm text-slate-500 mt-1">Professor of Public Health, Harvard</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../../footer.php'; ?>
</body>
</html>
