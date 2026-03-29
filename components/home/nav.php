<nav class="fixed inset-x-6 top-6 z-50 animate__animated animate__fadeInDown">
    <div class="bg-white/95 backdrop-blur-md border border-slate-200/60 rounded-2xl shadow-lg">
        <div class="px-5 py-3 flex items-center justify-between">
            <!-- Logo -->
            <?php include __DIR__ . '/logo.html'; ?>

            <!-- Nav Items -->
            <div class="flex items-center gap-1">
                <!-- Home -->
                <div class="nav-item relative px-4 py-2 text-slate-700 font-medium text-sm cursor-pointer hover:text-slate-900 rounded-lg transition-all duration-200">Home</div>

                <!-- About Dropdown -->
                <?php include __DIR__ . '/about.html'; ?>

                <!-- Services Dropdown -->
                <?php include __DIR__ . '/service.html'; ?>

                <!-- More Dropdown -->
                <?php include __DIR__ . '/more.html'; ?>

                <!-- Sign In -->
                <?php include __DIR__ . '/join.html'; ?>
            </div>
        </div>
    </div>
</nav>
