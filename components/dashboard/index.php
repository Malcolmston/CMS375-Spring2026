<?php

require_once __DIR__ . '/../../account/role.php';

use account\role;

session_start();

$user_id = $role = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
}

if (!$user_id || !$role) {
    header('Location: index.php');
    exit;
}

if (!Role::isValid($role)) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MedHealth</title>
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
    <?php include __DIR__ . '/../home/nav.php'; ?>

    <!-- Dashboard Content -->
    <section class="pt-32 pb-20 px-10">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-4xl font-serif font-light text-slate-800 mb-8 animate__animated animate__fadeInUp">
                Welcome to Your Dashboard
            </h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Dashboard cards will go here -->
                <div class="bg-white rounded-lg shadow-md p-6 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
                    <h2 class="text-xl font-semibold text-slate-800 mb-4">Quick Actions</h2>
                    <p class="text-slate-600">Access your frequently used features.</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <h2 class="text-xl font-semibold text-slate-800 mb-4">Recent Activity</h2>
                    <p class="text-slate-600">View your latest updates and notifications.</p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
                    <h2 class="text-xl font-semibold text-slate-800 mb-4">Statistics</h2>
                    <p class="text-slate-600">Overview of your account and activity.</p>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            $('.nav-item').on('click', function(e) {
                e.preventDefault();
                const text = $(this).text().toLowerCase().trim();
                console.log('Navigating to:', text);
            });

            $('.dropdown-item[href]').on('click', function(e) {
                // Allow default navigation
            });

            $('.nav-item').hover(
                function() {
                    $(this).addClass('bg-slate-100');
                },
                function() {
                    $(this).removeClass('bg-slate-100');
                }
            );

            $(document).on('mousemove', function(e) {
                const x = (e.clientX / $(window).width() - 0.5) * 10;
                const y = (e.clientY / $(window).height() - 0.5) * 10;
                $('body').css('background-position', `${50 + x}% ${50 + y}%`);
            });
        });
    </script>
</body>
</html>