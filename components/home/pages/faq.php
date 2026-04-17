<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        details summary {
            list-style: none;
            cursor: pointer;
        }
        details summary::-webkit-details-marker { display: none; }
        details summary .chevron {
            transition: transform 0.3s ease;
        }
        details[open] summary .chevron {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-slate-200">

    <?php include __DIR__ . '/../nav.php'; ?>

    <!-- Hero -->
    <section class="pt-40 pb-16 px-10 text-center max-w-4xl mx-auto">
        <h1 class="text-5xl font-serif font-light text-slate-800 leading-tight mb-6 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            Frequently Asked Questions
        </h1>
        <p class="text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            Find answers to common questions about MedHealth services, appointments, billing, and more.
        </p>
    </section>

    <section class="max-w-3xl mx-auto px-6 pb-20">
        <?php
        $faqs = [
            [
                'q' => 'Does MedHealth accept my insurance?',
                'a' => 'MedHealth accepts most major commercial insurance plans, including Blue Cross Blue Shield, Aetna, Cigna, UnitedHealth, and Humana. We also accept Medicare and Medicaid in most states. To confirm whether your specific plan is in-network, please contact our billing team at billing@medhealth.com or call us at (800) 555-0192 before your visit.',
            ],
            [
                'q' => 'How do I book an appointment?',
                'a' => 'You can book an appointment through our patient portal at any time, by calling your local MedHealth clinic directly, or by using our mobile app. Same-day appointments are often available for urgent concerns. Telemedicine visits can typically be booked and started within the hour.',
            ],
            [
                'q' => 'What should I bring to my first visit?',
                'a' => 'For your first visit, please bring a valid photo ID, your insurance card, a list of current medications and dosages, any relevant medical records or test results, and a list of questions or concerns you\'d like to discuss. If you are under 18, a parent or legal guardian must accompany you.',
            ],
            [
                'q' => 'How does telemedicine work at MedHealth?',
                'a' => 'Our telemedicine visits take place via secure video call directly in your browser — no app download required. After booking, you\'ll receive a link by email or text. Your provider reviews your medical history before the visit. You can receive diagnoses, treatment plans, and prescriptions sent to your pharmacy without leaving home.',
            ],
            [
                'q' => 'Can I get a prescription via a telemedicine visit?',
                'a' => 'Yes. MedHealth providers can prescribe most non-controlled medications during a telemedicine visit. Prescriptions are sent electronically to your preferred pharmacy. Controlled substances (such as certain pain medications or stimulants) require an in-person evaluation under federal and most state laws.',
            ],
            [
                'q' => 'How do I access my medical records?',
                'a' => 'Your visit notes, lab results, imaging reports, and care summaries are all available in the MedHealth patient portal within 24 hours of your visit. To request a formal medical records release for a third party, complete the Records Release form in your portal or contact our health information team. We process requests within 3–5 business days.',
            ],
            [
                'q' => 'What should I do in a medical emergency?',
                'a' => 'If you are experiencing a life-threatening emergency — including chest pain, difficulty breathing, stroke symptoms, or severe injury — call 911 immediately or go to your nearest emergency room. MedHealth is a primary and specialty care practice; we are not equipped to handle emergencies. For after-hours urgent concerns that are not life-threatening, our telemedicine team is available 7 days a week.',
            ],
            [
                'q' => 'How do I cancel or reschedule an appointment?',
                'a' => 'You can cancel or reschedule through the patient portal, our mobile app, or by calling the clinic directly. We ask that you give at least 24 hours notice when possible. Late cancellations and no-shows may be subject to a fee of up to $50, depending on your plan and the type of appointment.',
            ],
        ];
        ?>
        <div class="flex flex-col gap-4">
            <?php foreach ($faqs as $i => $faq): ?>
            <details class="bg-white rounded-2xl shadow-sm border border-slate-100 group" <?= $i === 0 ? 'open' : '' ?>>
                <summary class="px-6 py-5 flex items-center justify-between gap-4">
                    <span class="font-semibold text-slate-800 text-base"><?= $faq['q'] ?></span>
                    <svg class="chevron w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </summary>
                <div class="px-6 pb-5">
                    <div class="h-px bg-slate-100 mb-4"></div>
                    <p class="text-slate-600 text-sm leading-relaxed"><?= $faq['a'] ?></p>
                </div>
            </details>
            <?php endforeach; ?>
        </div>

        <div class="mt-12 bg-slate-800 rounded-2xl p-8 text-white text-center">
            <h3 class="text-xl font-semibold mb-2">Still have questions?</h3>
            <p class="text-slate-400 text-sm mb-6">Our patient support team is available Monday–Friday, 8am–6pm, and by telemedicine 7 days a week.</p>
            <a href="/contact" class="inline-block bg-white text-slate-800 font-semibold rounded-full px-8 py-3 text-sm hover:bg-slate-100 transition-all">Contact Us</a>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../footer.php'; ?>
</body>
</html>
