<!-- Drug Interaction Widget — include at bottom of any staff dashboard -->
<!-- Requires jQuery and Font Awesome already loaded -->

<div id="drug-widget-fab"
     class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center shadow-lg cursor-pointer transition-all hover:scale-105"
     title="Drug Interaction Checker">
    <i class="fas fa-pills text-lg"></i>
</div>

<div id="drug-widget-drawer"
     class="fixed bottom-0 right-0 z-50 w-full md:w-[420px] bg-white rounded-t-2xl md:rounded-tl-2xl md:rounded-tr-none shadow-2xl border border-slate-200 transform translate-y-full transition-transform duration-300 ease-out">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
        <div class="flex items-center gap-2">
            <i class="fas fa-pills text-indigo-500"></i>
            <h3 class="font-semibold text-slate-800">Drug Interaction Checker</h3>
        </div>
        <button id="drug-widget-close" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Medicine 1</label>
            <div class="relative">
                <input id="dw-med1-input" type="text" placeholder="Search medicine..."
                       autocomplete="off"
                       class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition text-sm">
                <input type="hidden" id="dw-med1-id">
                <div id="dw-med1-results" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg hidden max-h-48 overflow-y-auto"></div>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Medicine 2</label>
            <div class="relative">
                <input id="dw-med2-input" type="text" placeholder="Search medicine..."
                       autocomplete="off"
                       class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none transition text-sm">
                <input type="hidden" id="dw-med2-id">
                <div id="dw-med2-results" class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg hidden max-h-48 overflow-y-auto"></div>
            </div>
        </div>

        <button id="dw-check-btn"
                class="w-full px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed">
            Check Interaction
        </button>

        <div id="dw-result" class="hidden"></div>
    </div>
</div>

<script>
(function($) {
    var $fab    = $('#drug-widget-fab');
    var $drawer = $('#drug-widget-drawer');
    var $close  = $('#drug-widget-close');
    var timers  = {};

    $fab.on('click', function() {
        $drawer.removeClass('translate-y-full');
        $fab.addClass('hidden');
    });

    $close.on('click', function() {
        $drawer.addClass('translate-y-full');
        $fab.removeClass('hidden');
        $('#dw-result').addClass('hidden').html('');
    });

    function setupAutocomplete(inputId, resultsId, hiddenId) {
        var $input   = $('#' + inputId);
        var $results = $('#' + resultsId);
        var $hidden  = $('#' + hiddenId);

        $input.on('input', function() {
            var q = $(this).val().trim();
            $hidden.val('');
            clearTimeout(timers[inputId]);
            if (q.length < 2) { $results.addClass('hidden').html(''); return; }
            timers[inputId] = setTimeout(function() {
                $.getJSON('/api/search-medicine', { q: q }, function(data) {
                    if (!data || !data.length) {
                        $results.html('<div class="px-4 py-2 text-sm text-slate-400">No results</div>').removeClass('hidden');
                        return;
                    }
                    var html = '';
                    $.each(data, function(i, m) {
                        html += '<div class="dw-option px-4 py-2 text-sm hover:bg-indigo-50 cursor-pointer" data-id="' + m.id + '" data-name="' + $('<div>').text(m.generic_name).html() + '">' +
                                    '<span class="font-medium">' + $('<div>').text(m.generic_name).html() + '</span>' +
                                    (m.brand_name ? '<span class="text-slate-400 text-xs ml-1">(' + $('<div>').text(m.brand_name).html() + ')</span>' : '') +
                                '</div>';
                    });
                    $results.html(html).removeClass('hidden');
                });
            }, 300);
        });

        $results.on('click', '.dw-option', function() {
            $input.val($(this).data('name'));
            $hidden.val($(this).data('id'));
            $results.addClass('hidden');
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#' + inputId + ', #' + resultsId).length) {
                $results.addClass('hidden');
            }
        });
    }

    setupAutocomplete('dw-med1-input', 'dw-med1-results', 'dw-med1-id');
    setupAutocomplete('dw-med2-input', 'dw-med2-results', 'dw-med2-id');

    $('#dw-check-btn').on('click', function() {
        var m1 = $('#dw-med1-id').val();
        var m2 = $('#dw-med2-id').val();
        if (!m1 || !m2) {
            $('#dw-result').html('<div class="p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm">Please select both medicines.</div>').removeClass('hidden');
            return;
        }
        $('#dw-result').html('<div class="text-sm text-slate-400 text-center py-2">Checking...</div>').removeClass('hidden');
        $.getJSON('/api/check-interaction', { m1: m1, m2: m2 }, function(data) {
            if (!data || $.isEmptyObject(data) || ($.isArray(data) && !data.length)) {
                $('#dw-result').html('<div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm"><i class="fas fa-check-circle mr-2"></i>No known interaction found.</div>');
                return;
            }
            var item = $.isArray(data) ? data[0] : data;
            var sev  = (item.severity || '').toLowerCase();
            var cls  = sev === 'major'  ? 'bg-red-50 border-red-200 text-red-800' :
                       sev === 'moderate' ? 'bg-orange-50 border-orange-200 text-orange-800' :
                       'bg-yellow-50 border-yellow-200 text-yellow-800';
            var html = '<div class="p-3 rounded-lg border text-sm ' + cls + '">' +
                '<div class="font-semibold mb-1 capitalize"><i class="fas fa-exclamation-triangle mr-1"></i>' + (item.severity || 'Interaction') + ' Interaction</div>';
            if (item.description)    html += '<p class="mb-1">' + $('<div>').text(item.description).html() + '</p>';
            if (item.recommendation) html += '<p class="text-xs opacity-80"><strong>Recommendation:</strong> ' + $('<div>').text(item.recommendation).html() + '</p>';
            html += '</div>';
            $('#dw-result').html(html);
        }).fail(function() {
            $('#dw-result').html('<div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">Error checking interaction. Please try again.</div>');
        });
    });
})(jQuery);
</script>
