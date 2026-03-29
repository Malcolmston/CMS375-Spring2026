<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
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

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #475569;
            box-shadow: 0 0 0 3px rgba(71, 85, 105, 0.1);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200">

    <?php include __DIR__ . '/../nav.php'; ?>

    <!-- Hero -->
    <section class="pt-40 pb-16 px-10 text-center max-w-4xl mx-auto">
        <h1 class="text-5xl font-serif font-light text-slate-800 leading-tight mb-6 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            Contact Us
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            We're here to help. Reach out with questions, appointment requests, or anything on your mind.
        </p>
    </section>

    <section class="max-w-5xl mx-auto px-6 pb-20">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Contact Form -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
                <h2 class="text-2xl font-serif font-light text-slate-800 mb-6">Send Us a Message</h2>
                <form>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="name">Full Name</label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Jane Smith"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm bg-slate-50 transition-all"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">Email Address</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="jane@example.com"
                                class="w-full border border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm bg-slate-50 transition-all"
                            >
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="subject">Subject</label>
                        <select
                            id="subject"
                            name="subject"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm bg-slate-50 transition-all"
                        >
                            <option value="">Select a topic...</option>
                            <option>Appointment Request</option>
                            <option>Billing &amp; Insurance</option>
                            <option>Medical Records</option>
                            <option>Prescription Refill</option>
                            <option>Technical Support</option>
                            <option>General Question</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="message">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            placeholder="How can we help you?"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-slate-700 text-sm bg-slate-50 transition-all resize-none"
                        ></textarea>
                    </div>
                    <button
                        type="submit"
                        class="bg-slate-800 hover:bg-slate-700 text-white font-semibold rounded-full px-8 py-3 text-sm transition-all w-full md:w-auto"
                    >
                        Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="flex flex-col gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm mb-1">Main Office</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">1000 Holt Ave<br>Winter Park, FL 32789</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fa-slab fa-regular fa-phone"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm mb-1">Phone</h3>
                            <p class="text-slate-600 text-sm">(800) 555-0192</p>
                            <p class="text-slate-500 text-xs mt-0.5">Mon–Fri, 8am–6pm PT</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="fa-slab fa-regular fa-envelope"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm mb-1">Email</h3>
                            <p class="text-slate-600 text-sm">hello@medhealth.com</p>
                            <p class="text-slate-600 text-sm">billing@medhealth.com</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">


                    <!-- Hospital -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-hospital text-slate-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 text-sm mb-1">Hospital</h3>
                                <p class="text-slate-600 text-sm">24/7 Emergency Care</p>
                                <p class="text-slate-600 text-sm">Visiting: 9:00am – 8:00pm</p>
                                <p class="text-slate-500 text-xs mt-0.5">(800) 555-0911</p>
                            </div>
                        </div>
                    </div>

                    <!-- Urgent Care -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-truck-medical text-slate-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 text-sm mb-1">Urgent Care</h3>
                                <p class="text-slate-600 text-sm">Mon–Sun: 8:00am – 10:00pm</p>
                                <p class="text-slate-600 text-sm">No appointment needed</p>
                                <p class="text-slate-500 text-xs mt-0.5">(800) 555-0193</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pharmacy -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-pills text-slate-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 text-sm mb-1">Pharmacy</h3>
                                <p class="text-slate-600 text-sm">Mon–Fri: 8:00am – 8:00pm</p>
                                <p class="text-slate-600 text-sm">Sat–Sun: 9:00am – 5:00pm</p>
                                <p class="text-slate-500 text-xs mt-0.5">Drive-thru available</p>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Services -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-flask text-slate-600"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 text-sm mb-1">Lab Services</h3>
                                <p class="text-slate-600 text-sm">Mon–Fri: 6:00am – 5:00pm</p>
                                <p class="text-slate-600 text-sm">Saturday: 7:00am – 12:00pm</p>
                                <p class="text-slate-500 text-xs mt-0.5">Fasting tests before 10am</p>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 italic mt-4 px-2">Note: Please call ahead for appointment scheduling.
                        Not all hours for all locations listed.</p>

                </div>


                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            $('.nav-item').hover(
                function() { $(this).addClass('bg-slate-100'); },
                function() { $(this).removeClass('bg-slate-100'); }
            );

            $('form').on('submit', function(e) {
                e.preventDefault();
                const btn = $(this).find('button[type=submit]');
                btn.text('Message Sent!').addClass('bg-emerald-700').removeClass('bg-slate-800 hover:bg-slate-700');
                setTimeout(() => {
                    btn.text('Send Message').removeClass('bg-emerald-700').addClass('bg-slate-800 hover:bg-slate-700');
                }, 3000);
            });
        });
    </script>
</body>
</html>
