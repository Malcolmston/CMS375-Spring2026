<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Primary Care | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .dropdown-menu {
            opacity: 0;
            visibility: hidden;
            transform: translateX(-50%) translateY(-10px);
            transition: all 0.3s ease;
        }
        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }
        .nav-item::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #1e293b, transparent);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        .nav-item:hover::after { transform: scaleX(1); }
        .dropdown > .nav-item::before {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 4px solid #1e293b;
            transition: transform 0.3s ease;
        }
        .dropdown:hover > .nav-item::before { transform: translateY(-50%) rotate(180deg); }
        .dropdown-item:hover {
            padding-left: 24px;
            background: rgba(30, 41, 59, 0.05);
        }
        .dropdown-item::before {
            content: '';
            width: 5px;
            height: 5px;
            background: #1e293b;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .dropdown-item:hover::before { opacity: 1; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200">

    <?php include __DIR__ . '/../nav.php'; ?>

    <!-- Hero -->
    <section class="pt-40 pb-16 px-10 text-center max-w-4xl mx-auto">
        <h1 class="text-5xl font-serif font-light text-slate-800 leading-tight mb-6 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            Primary Care
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            Your health starts here. Our primary care physicians take the time to truly know you — your history, your goals, and your concerns.
        </p>
        <a href="/contact" class="inline-block mt-8 bg-slate-800 hover:bg-slate-700 text-white font-semibold rounded-full px-8 py-3 text-sm transition-all animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
            Book an Appointment
        </a>
    </section>

    <section class="max-w-5xl mx-auto px-6 pb-20">

        <!-- Description -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-10 mb-10">
            <h2 class="text-3xl font-serif font-light text-slate-800 mb-5">Comprehensive, Continuous Care</h2>
            <p class="text-slate-600 leading-relaxed mb-4">
                Primary care is the foundation of good health. At MedHealth, your primary care physician is your long-term partner — someone who tracks your health over years, not just individual visits. We believe in proactive care: catching problems before they become serious and managing chronic conditions so they don't define your life.
            </p>
            <p class="text-slate-600 leading-relaxed">
                Each MedHealth primary care patient is supported by a dedicated care team including a physician, a medical assistant, and a care coordinator. Together, we ensure you always know what's next and never fall through the cracks.
            </p>
        </div>

        <!-- What's Included -->
        <h2 class="text-3xl font-serif font-light text-slate-800 mb-8 text-center">What's Included</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
            <?php
            $services = [
                ['title' => 'Annual Physicals', 'desc' => 'Comprehensive yearly wellness exams including blood work, screenings, and personalized health goals.', 'color' => 'blue'],
                ['title' => 'Preventive Care', 'desc' => 'Age-appropriate screenings, immunizations, and counseling to keep you healthy before problems arise.', 'color' => 'emerald'],
                ['title' => 'Chronic Disease Management', 'desc' => 'Ongoing care plans for diabetes, hypertension, asthma, heart disease, and other chronic conditions.', 'color' => 'amber'],
                ['title' => 'Sick Visits', 'desc' => 'Same-day or next-day appointments for acute illnesses, infections, and unexpected health concerns.', 'color' => 'rose'],
                ['title' => 'Mental Health Screening', 'desc' => 'Routine screening for depression, anxiety, and substance use, with warm referrals to behavioral health.', 'color' => 'violet'],
                ['title' => 'Prescription Management', 'desc' => 'Medication refills, prior authorizations, and medication reconciliation all handled by your care team.', 'color' => 'slate'],
            ];
            foreach ($services as $s): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="w-2 h-8 bg-<?= $s['color'] ?>-300 rounded-full mb-4"></div>
                <h3 class="font-semibold text-slate-800 mb-2"><?= $s['title'] ?></h3>
                <p class="text-slate-600 text-sm leading-relaxed"><?= $s['desc'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- How to Book -->
        <div class="bg-slate-800 rounded-2xl p-10 text-white">
            <h2 class="text-3xl font-serif font-light mb-6 text-center">How to Book</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div>
                    <div class="text-4xl font-serif font-light text-slate-300 mb-3">01</div>
                    <h3 class="font-semibold mb-2">Create Your Account</h3>
                    <p class="text-slate-400 text-sm">Sign up on our patient portal or call any MedHealth clinic directly.</p>
                </div>
                <div>
                    <div class="text-4xl font-serif font-light text-slate-300 mb-3">02</div>
                    <h3 class="font-semibold mb-2">Choose Your Doctor</h3>
                    <p class="text-slate-400 text-sm">Browse physician profiles, read bios, and select the right fit for you.</p>
                </div>
                <div>
                    <div class="text-4xl font-serif font-light text-slate-300 mb-3">03</div>
                    <h3 class="font-semibold mb-2">Book Your Visit</h3>
                    <p class="text-slate-400 text-sm">Pick a time that works — in-person or via telemedicine — and you're set.</p>
                </div>
            </div>
            <div class="text-center mt-8">
                <a href="/contact" class="inline-block bg-white text-slate-800 font-semibold rounded-full px-8 py-3 text-sm hover:bg-slate-100 transition-all">Get Started Today</a>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            $('.nav-item').hover(
                function() { $(this).addClass('bg-slate-100'); },
                function() { $(this).removeClass('bg-slate-100'); }
            );
        });
    </script>
</body>
</html>
