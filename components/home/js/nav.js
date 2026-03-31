$(document).ready(function() {
    $('.nav-item').hover(
        function() { $(this).addClass('bg-slate-100'); },
        function() { $(this).removeClass('bg-slate-100'); }
    );
});
