<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Story | MedHealth</title>
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
            Our Story
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            From a single clinic to a nationwide healthcare network — built on the belief that every person deserves compassionate, accessible care.
        </p>
    </section>

    <!-- Founding Story -->
    <section class="max-w-5xl mx-auto px-6 pb-20">

        <!-- Mission -->
        <div class="bg-slate-800 rounded-2xl p-10 mb-10 text-white text-center">
            <h2 class="text-3xl font-serif font-light mb-4">Our Mission</h2>
            <p class="text-slate-300 text-lg leading-relaxed max-w-2xl mx-auto">
                To deliver compassionate, evidence-based healthcare that empowers individuals to live healthier, fuller lives — regardless of background, location, or circumstance.
            </p>
        </div>

        <!-- Core Values -->
        <h2 class="text-3xl font-serif font-light text-slate-800 mb-8 text-center">Our Core Values</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
                <div class="w-14 h-14 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-whiteboard fa-semibold fa-heart text-rose-400 fa-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">Compassion</h3>
                <p class="text-slate-600 text-sm leading-relaxed">We treat every patient as a whole person, listening with empathy and responding with genuine care.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
                <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-duotone fa-solid fa-lightbulb-cfl-on text-blue-400 fa-lg"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">Innovation</h3>
                <p class="text-slate-600 text-sm leading-relaxed">We embrace technology and research to continuously improve how care is delivered and experienced.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
                <div class="w-14 h-14 bg-emerald-50 rounded-full flex items-center justify-center mx-auto mb-4">

                    <i class="fa-regular fa-shield-check text-emerald-400 fa-lg"></i>

                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">Integrity</h3>
                <p class="text-slate-600 text-sm leading-relaxed">We are transparent, honest, and accountable in every decision we make — for our patients and our teams.</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 text-center">
                <div class="w-14 h-14 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-4">

                    <i class="fa-light fa-star text-amber-400 fa-lg"></i>

                </div>
                <h3 class="text-lg font-semibold text-slate-800 mb-2">Excellence</h3>
                <p class="text-slate-600 text-sm leading-relaxed">We hold ourselves to the highest clinical and operational standards, striving for better outcomes every day.</p>
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
