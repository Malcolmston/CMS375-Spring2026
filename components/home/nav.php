<nav class="fixed inset-x-6 top-6 z-50 animate__animated animate__fadeInDown">
    <div class="bg-white/95 backdrop-blur-md border border-slate-200/60 rounded-2xl shadow-lg">
        <div class="px-5 py-3 flex items-center justify-between">
            <!-- Logo -->
            <?php include __DIR__ . '/logo.html'; ?>

            <!-- Desktop Nav Items -->
            <div class="hidden md:flex items-center gap-1">
                <div class="nav-item relative px-4 py-2 text-slate-700 font-medium text-sm cursor-pointer hover:text-slate-900 rounded-lg transition-all duration-200">Home</div>
                <?php include __DIR__ . '/about.html'; ?>
                <?php include __DIR__ . '/service.html'; ?>
                <?php include __DIR__ . '/more.html'; ?>
                <?php include __DIR__ . '/join.html'; ?>
            </div>

            <!-- Hamburger (mobile only) -->
            <button id="mobile-menu-toggle"
                    class="md:hidden flex flex-col justify-center items-center w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition-all gap-1.5 px-2.5"
                    aria-label="Toggle menu" aria-expanded="false">
                <span class="hamburger-line block w-full h-0.5 bg-slate-700 rounded-full transition-all duration-300"></span>
                <span class="hamburger-line block w-full h-0.5 bg-slate-700 rounded-full transition-all duration-300"></span>
                <span class="hamburger-line block w-full h-0.5 bg-slate-700 rounded-full transition-all duration-300"></span>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-100 px-4 pb-4 pt-3 overflow-y-auto max-h-[70vh]">

            <a href="/" class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">Home</a>

            <!-- About -->
            <div class="mt-1">
                <button class="mobile-section-toggle w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-500 uppercase tracking-wide hover:bg-slate-50 transition-colors">
                    About
                    <svg class="section-chevron w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="mobile-section-body hidden pl-3 mt-1 space-y-0.5">
                    <a href="/about/story" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Our Story</a>
                    <a href="/about/team"  class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Team</a>
                    <a href="/about/careers" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Careers</a>
                </div>
            </div>

            <!-- Services -->
            <div class="mt-1">
                <button class="mobile-section-toggle w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-500 uppercase tracking-wide hover:bg-slate-50 transition-colors">
                    Services
                    <svg class="section-chevron w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="mobile-section-body hidden pl-3 mt-1 space-y-0.5">
                    <a href="/services/primary-care"  class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Primary Care</a>
                    <a href="/services/specialists"   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Specialists</a>
                    <a href="/services/telemedicine"  class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Telemedicine</a>
                    <a href="/services/lab"           class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Lab Services</a>
                </div>
            </div>

            <!-- More -->
            <div class="mt-1">
                <button class="mobile-section-toggle w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold text-slate-500 uppercase tracking-wide hover:bg-slate-50 transition-colors">
                    More
                    <svg class="section-chevron w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="mobile-section-body hidden pl-3 mt-1 space-y-0.5">
                    <a href="/blog"    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Blog</a>
                    <a href="/faq"     class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">FAQ</a>
                    <a href="/contact" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Contact</a>
                    <a href="/privacy" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">Privacy</a>
                </div>
            </div>

            <!-- Account -->
            <div class="mt-1 pt-3 border-t border-slate-100 space-y-0.5">
                <a href="/patient/login.html" class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">Patient Login</a>
                <a href="/staff/login.html"   class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">Staff Login</a>
                <a href="/admin/login.html"   class="flex items-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">Admin Login</a>
            </div>
        </div>
    </div>
</nav>

<script>
$(function () {
    // Hamburger toggle
    $('#mobile-menu-toggle').on('click', function () {
        const $menu   = $('#mobile-menu');
        const $lines  = $(this).find('.hamburger-line');
        const opening = $menu.hasClass('hidden');

        $menu.toggleClass('hidden');
        $(this).attr('aria-expanded', opening);

        if (opening) {
            $lines.eq(0).css('transform', 'translateY(8px) rotate(45deg)');
            $lines.eq(1).css('opacity', '0');
            $lines.eq(2).css('transform', 'translateY(-8px) rotate(-45deg)');
        } else {
            $lines.css({ transform: '', opacity: '' });
        }
    });

    // Accordion sections
    $('.mobile-section-toggle').on('click', function () {
        const $body    = $(this).next('.mobile-section-body');
        const $chevron = $(this).find('.section-chevron');
        const opening  = $body.hasClass('hidden');

        $body.toggleClass('hidden');
        $chevron.css('transform', opening ? 'rotate(180deg)' : '');
    });
});
</script>
