<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Specialists | MedHealth</title>
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
            Specialist Care
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            When you need expert care beyond primary care, MedHealth connects you to world-class specialists — all within our coordinated care network.
        </p>
    </section>

    <section class="max-w-5xl mx-auto px-6 pb-20">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 mb-12">
            <p class="text-slate-600 leading-relaxed">
                Our specialist network includes over 120 board-certified physicians across a wide range of specialties. Because they work within the MedHealth system, your primary care doctor and specialist share your records seamlessly — no more faxing paperwork or repeating your history at every visit. Referrals are coordinated by your care team, and wait times average just 5 business days.
            </p>
        </div>

        <h2 class="text-3xl font-serif font-light text-slate-800 mb-8 text-center">Our Specialties</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center mb-4">

                    <i class="fa-slab fa-regular fa-heart text-rose-400 fa-lg"></i>

                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-2">Cardiology</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Diagnosis and management of heart disease, arrhythmias, heart failure, and coronary artery disease. Includes echocardiography and stress testing.</p>
                <a href="/contact" class="inline-block mt-4 text-slate-700 text-sm font-medium hover:text-slate-900 transition-colors">Request referral &rarr;</a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="w-12 h-12 bg-violet-50 rounded-xl flex items-center justify-center mb-4">

                    <i class="fa-duotone fa-solid fa-lightbulb-cfl-on fa-lg" style="--fa-primary-color: oklch(70.2% 0.183 293.541); --fa-primary-opacity: 0.4; --fa-secondary-color: oklch(70.2% 0.183 293.541); --fa-secondary-opacity: 1;"></i>

                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-2">Neurology</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Expert care for migraines, epilepsy, multiple sclerosis, Parkinson's disease, stroke recovery, and peripheral neuropathy.</p>
                <a href="/contact" class="inline-block mt-4 text-slate-700 text-sm font-medium hover:text-slate-900 transition-colors">Request referral &rarr;</a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fa-regular fa-user text-blue-400"></i>

                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-2">Orthopedics</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Non-surgical and surgical management of joint, bone, and muscle conditions including sports injuries, arthritis, and fractures.</p>
                <a href="/contact" class="inline-block mt-4 text-slate-700 text-sm font-medium hover:text-slate-900 transition-colors">Request referral &rarr;</a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center mb-4">

                    <i class="fa-slab fa-regular fa-triangle-exclamation text-amber-400 fa-lg"></i>

                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-2">Oncology</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Comprehensive cancer care including diagnosis, medical oncology, coordination with radiation and surgical teams, and survivorship support.</p>
                <a href="/contact" class="inline-block mt-4 text-slate-700 text-sm font-medium hover:text-slate-900 transition-colors">Request referral &rarr;</a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center mb-4">

                    <i class="fa-light fa-swatchbook text-emerald-400 fa-lg"></i>

                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-2">Dermatology</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Medical and cosmetic dermatology including skin cancer screening, eczema, psoriasis, acne management, and dermatologic surgery.</p>
                <a href="/contact" class="inline-block mt-4 text-slate-700 text-sm font-medium hover:text-slate-900 transition-colors">Request referral &rarr;</a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="w-12 h-12 bg-cyan-50 rounded-xl flex items-center justify-center mb-4">
                    <i class="fa-kit fa-beaker-slosh text-cyan-400 fa-lg"></i>

                </div>
                <h3 class="font-semibold text-slate-800 text-lg mb-2">Gastroenterology</h3>
                <p class="text-slate-600 text-sm leading-relaxed">Evaluation and treatment of digestive conditions including IBS, GERD, inflammatory bowel disease, and colonoscopy screening.</p>
                <a href="/contact" class="inline-block mt-4 text-slate-700 text-sm font-medium hover:text-slate-900 transition-colors">Request referral &rarr;</a>
            </div>

        </div>

        <div class="mt-12 bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
            <h3 class="text-xl font-semibold text-slate-800 mb-3">Need a referral?</h3>
            <p class="text-slate-600 text-sm mb-6 max-w-xl mx-auto">Ask your MedHealth primary care physician for a referral, or contact our care coordination team directly and we'll match you with the right specialist.</p>
            <a href="/contact" class="inline-block bg-slate-800 hover:bg-slate-700 text-white font-semibold rounded-full px-8 py-3 text-sm transition-all">Contact Care Coordination</a>
        </div>
    </section>

</body>
</html>
