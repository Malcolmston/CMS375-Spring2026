<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Services | MedHealth</title>
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
            Lab Services
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            Accurate results, fast turnaround, and seamless integration with your MedHealth care team. Everything from routine panels to advanced diagnostics.
        </p>
    </section>

    <section class="max-w-5xl mx-auto px-6 pb-20">

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 mb-12">
            <p class="text-slate-600 leading-relaxed">
                MedHealth operates CLIA-certified laboratories at all major clinic locations, with partnerships with national reference labs for specialized testing. Your results are available in your patient portal within the guaranteed turnaround window, and your care team is automatically notified of any critical values — so nothing slips through.
            </p>
        </div>

        <!-- Test Types -->
        <h2 class="text-3xl font-serif font-light text-slate-800 mb-8 text-center">Types of Tests</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-kit fa-beaker-slosh text-rose-400 fa-lg"></i>

                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-lg mb-1">Blood Panels</h3>
                        <p class="text-slate-600 text-sm leading-relaxed mb-3">Complete metabolic panel, CBC, lipid panel, HbA1c, thyroid function, vitamin levels, and more. Most results available within 24 hours.</p>
                        <span class="text-xs font-medium bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full">Turnaround: 24–48 hrs</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-slab fa-regular fa-image text-blue-400 fa-lg"></i>

                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-lg mb-1">Imaging</h3>
                        <p class="text-slate-600 text-sm leading-relaxed mb-3">X-ray, ultrasound, and MRI available at select locations. CT and PET scans arranged at partner facilities with results sent directly to your portal.</p>
                        <span class="text-xs font-medium bg-blue-50 text-blue-700 px-3 py-1 rounded-full">Turnaround: 1–3 days</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-violet-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fa-slab fa-regular fa-clipboard text-violet-400 fa-lg"></i>

                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-lg mb-1">Pathology</h3>
                        <p class="text-slate-600 text-sm leading-relaxed mb-3">Tissue biopsy analysis, PAP smears, and cytology processed by board-certified pathologists. Detailed reports with clinical context included.</p>
                        <span class="text-xs font-medium bg-amber-50 text-amber-700 px-3 py-1 rounded-full">Turnaround: 3–7 days</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">

                        <i class="fa-duotone fa-solid fa-lightbulb-cfl-on fa-lg" style="--fa-primary-color: oklch(76.5% 0.177 163.223); --fa-primary-opacity: 0.4; --fa-secondary-color: oklch(76.5% 0.177 163.223); --fa-secondary-opacity: 1;"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-lg mb-1">Genetic Testing</h3>
                        <p class="text-slate-600 text-sm leading-relaxed mb-3">Hereditary cancer risk panels (BRCA, Lynch syndrome), pharmacogenomics, and carrier screening. Genetic counseling included with results.</p>
                        <span class="text-xs font-medium bg-violet-50 text-violet-700 px-3 py-1 rounded-full">Turnaround: 10–14 days</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- How to Order -->
        <h2 class="text-3xl font-serif font-light text-slate-800 mb-8 text-center">How to Order Tests</h2>
        <div class="bg-slate-800 rounded-2xl p-10 text-white">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="text-4xl font-serif font-light text-slate-300 mb-3">01</div>
                    <h3 class="font-semibold mb-2">Provider Order</h3>
                    <p class="text-slate-400 text-sm">Your MedHealth provider submits a test order directly from your visit. No paperwork required on your end.</p>
                </div>
                <div>
                    <div class="text-4xl font-serif font-light text-slate-300 mb-3">02</div>
                    <h3 class="font-semibold mb-2">Visit a Lab</h3>
                    <p class="text-slate-400 text-sm">Go to any MedHealth lab location or partner draw site. Check in using your patient ID or app QR code.</p>
                </div>
                <div>
                    <div class="text-4xl font-serif font-light text-slate-300 mb-3">03</div>
                    <h3 class="font-semibold mb-2">View Results</h3>
                    <p class="text-slate-400 text-sm">Results appear in your patient portal with clear explanations. Your care team is notified and will follow up as needed.</p>
                </div>
            </div>
            <div class="text-center mt-8">
                <a href="/contact" class="inline-block bg-white text-slate-800 font-semibold rounded-full px-8 py-3 text-sm hover:bg-slate-100 transition-all">Schedule a Lab Visit</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
