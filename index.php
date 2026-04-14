<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedHealth</title>
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

        .nav-item:hover::after {
            transform: scaleX(1);
        }

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

        .dropdown:hover > .nav-item::before {
            transform: translateY(-50%) rotate(180deg);
        }

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

        .dropdown-item:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200">

    <!-- Navigation -->
    <?php include 'components/home/nav.php'; ?>
    <!-- Hero Section -->
    <section class="pt-40 pb-20 px-10 text-center max-w-4xl mx-auto">
        <h1 class="text-6xl font-serif font-light text-slate-800 leading-tight mb-6 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            Healthcare that feels<br>like home
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            Experience a new approach to medical care — compassionate, personalized, and always there when you need us. Your health, our priority.
        </p>
        <a href="#" class="inline-block mt-10 px-10 py-4 bg-slate-800 text-white font-semibold text-sm rounded-full hover:bg-slate-700 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
            Get Started
        </a>
    </section>

    <script>
        $(document).ready(function() {
            // No click blocking - let all links work
            $(document).on('mousemove', function(e) {
                const x = (e.clientX / $(window).width() - 0.5) * 10;
                const y = (e.clientY / $(window).height() - 0.5) * 10;
                $('body').css('background-position', `${50 + x}% ${50 + y}%`);
            });
        });
    </script>

    <!-- Footer -->
    <?php include 'components/home/footer.php'; ?>
</body>
</html>
