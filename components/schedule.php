<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }

        .day-cell { min-height: 96px; }
        .day-cell.other-month { opacity: 0.35; }
        .day-cell.today .day-num {
            background: #4f46e5;
            color: #fff;
            border-radius: 9999px;
            width: 28px; height: 28px;
            display: flex; align-items: center; justify-content: center;
        }
        .event-pill {
            font-size: 11px; line-height: 1.3;
            padding: 2px 7px;
            border-radius: 999px;
            cursor: pointer;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 100%;
        }
        /* Modal backdrop */
        #modal-backdrop {
            background: rgba(15,23,42,0.35);
            backdrop-filter: blur(2px);
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50">

<!-- Back Button -->
<a href="/dashboard" class="fixed top-4 left-4 z-30 w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center cursor-pointer shadow-md text-slate-600 hover:text-indigo-600 transition-colors">
    <i class="fas fa-arrow-left"></i>
</a>

<!-- ════ CALENDAR SHELL ════ -->
<div class="max-w-5xl mx-auto px-4 py-8 pt-16">

    <!-- Header row -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 id="cal-title" class="text-3xl font-light text-slate-800" style="font-family:'DM Serif Display',serif;"></h1>
            <p class="text-sm text-slate-400 mt-0.5">Click any day to add an event</p>
        </div>
        <div class="flex items-center gap-2">
            <button id="btn-today" class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-sm transition-colors">
                Today
            </button>
            <button id="btn-prev" class="w-9 h-9 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:border-indigo-200 shadow-sm transition-colors">
                <i class="fas fa-chevron-left text-xs"></i>
            </button>
            <button id="btn-next" class="w-9 h-9 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-500 hover:text-indigo-600 hover:border-indigo-200 shadow-sm transition-colors">
                <i class="fas fa-chevron-right text-xs"></i>
            </button>
        </div>
    </div>

    <!-- Day-of-week header -->
    <div class="grid grid-cols-7 mb-1">
        <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
        <div class="text-center text-[11px] font-semibold text-slate-400 uppercase tracking-widest py-2"><?= $d ?></div>
        <?php endforeach; ?>
    </div>

    <!-- Calendar grid -->
    <div id="cal-grid" class="grid grid-cols-7 border-l border-t border-slate-200 rounded-2xl overflow-hidden shadow-sm bg-white animate__animated animate__fadeIn">
        <!-- filled by JS -->
    </div>

</div>

<div id="modal-backdrop" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div id="modal" class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md animate__animated animate__fadeInUp animate__faster">
        <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-slate-100">
            <h2 id="modal-title" class="text-lg font-semibold text-slate-800" style="font-family:'DM Serif Display',serif;"></h2>
            <button id="modal-close" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <!-- Event list for selected day -->
        <div id="modal-events" class="px-6 pt-3 pb-1 space-y-2 max-h-48 overflow-y-auto"></div>

        <!-- Add-event form -->
        <form id="event-form" class="px-6 pb-5 pt-4 space-y-4 border-t border-slate-100 mt-2">

            <!-- Type selector -->
            <div class="grid grid-cols-3 gap-1.5 bg-slate-100 rounded-xl p-1">
                <button type="button" class="type-btn active rounded-lg py-1.5 text-xs font-semibold transition-all" data-type="medication">
                    <i class="fas fa-pills mr-1"></i>Medication
                </button>
                <button type="button" class="type-btn rounded-lg py-1.5 text-xs font-semibold transition-all" data-type="appointment">
                    <i class="fas fa-user-md mr-1"></i>Appointment
                </button>
                <button type="button" class="type-btn rounded-lg py-1.5 text-xs font-semibold transition-all" data-type="other">
                    <i class="fas fa-calendar-plus mr-1"></i>Other
                </button>
            </div>
            <input type="hidden" id="event-type" value="medication">

            <!-- Title -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Title</label>
                <input id="event-title" type="text" maxlength="60" placeholder="e.g. Metformin 500mg"
                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
            </div>

            <!-- Time + Color row -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Time</label>
                    <input id="event-time" type="time"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Color</label>
                    <input type="hidden" id="event-color" value="indigo">
                    <div id="color-picker" class="relative">
                        <button type="button" id="color-trigger"
                                class="w-full flex items-center gap-2 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 bg-white hover:border-indigo-300 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                            <span id="color-swatch" class="w-3.5 h-3.5 rounded-sm flex-shrink-0 bg-indigo-500"></span>
                            <span id="color-label">Indigo</span>
                            <i class="fas fa-chevron-down text-slate-400 text-[10px] ml-auto"></i>
                        </button>
                        <div id="color-dropdown"
                             class="hidden absolute z-10 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden max-h-48 overflow-y-auto">
                            <!-- populated from COLOR_CLASSES by JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medication-only fields -->
            <div id="fields-medication" class="space-y-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Dosage <span class="normal-case font-normal">(optional)</span></label>
                    <input id="event-dosage" type="text" maxlength="40" placeholder="e.g. 500mg"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                </div>
            </div>

            <!-- Appointment-only fields -->
            <div id="fields-appointment" class="hidden space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Doctor / Provider</label>
                        <input id="event-doctor" type="text" maxlength="60" placeholder="Dr. Smith"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Duration</label>
                        <select id="event-duration"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition bg-white">
                            <option value="">—</option>
                            <option value="15 min">15 min</option>
                            <option value="30 min">30 min</option>
                            <option value="45 min">45 min</option>
                            <option value="1 hr">1 hr</option>
                            <option value="1.5 hr">1.5 hr</option>
                            <option value="2 hr">2 hr</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Location <span class="normal-case font-normal">(optional)</span></label>
                    <input id="event-location" type="text" maxlength="80" placeholder="e.g. Orlando Health Clinic"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                </div>
            </div>

            <!-- Other-only fields -->
            <div id="fields-other" class="hidden space-y-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Notes <span class="normal-case font-normal">(optional)</span></label>
                    <textarea id="event-notes" rows="2" maxlength="200" placeholder="Any additional details…"
                              class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition resize-none"></textarea>
                </div>
            </div>

            <!-- Repeat toggle -->
            <div class="border border-slate-200 rounded-xl p-3 space-y-3">
                <label class="flex items-center justify-between cursor-pointer select-none">
                    <span class="text-sm font-medium text-slate-700"><i class="fas fa-redo text-slate-400 mr-2 text-xs"></i>Repeat</span>
                    <div class="relative">
                        <input type="checkbox" id="repeat-toggle" class="sr-only peer">
                        <div class="w-9 h-5 bg-slate-200 rounded-full peer-checked:bg-indigo-500 transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                    </div>
                </label>
                <div id="repeat-fields" class="hidden space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Frequency</label>
                            <select id="repeat-freq"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition bg-white">
                                <option value="daily">Daily</option>
                                <option value="weekly" selected>Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">End after</label>
                            <select id="repeat-end-type"
                                    class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition bg-white">
                                <option value="occurrences">N times</option>
                                <option value="date">On date</option>
                            </select>
                        </div>
                    </div>
                    <div id="repeat-end-occurrences">
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Occurrences</label>
                        <input id="repeat-count" type="number" min="2" max="365" value="4"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                    </div>
                    <div id="repeat-end-date" class="hidden">
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">End date</label>
                        <input id="repeat-until" type="date"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                    </div>
                </div>
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                <i class="fas fa-plus mr-1.5"></i>Add event
            </button>
        </form>
    </div>
</div>

<script>
$(function () {

    const STORE_KEY = 'medhealth_schedule_v1';
    function loadEvents() { try { return JSON.parse(localStorage.getItem(STORE_KEY)) || {}; } catch(e) { return {}; } }
    function saveEvents(e) { localStorage.setItem(STORE_KEY, JSON.stringify(e)); }

    const today    = new Date();
    let   viewYear = today.getFullYear();
    let   viewMonth= today.getMonth();
    let   selectedDateKey = null;

    const COLOR_CLASSES = {
        indigo:  { pill: 'bg-indigo-100 text-indigo-700',   dot: 'bg-indigo-500'  },
        emerald: { pill: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
        rose:    { pill: 'bg-rose-100 text-rose-700',        dot: 'bg-rose-500'    },
        amber:   { pill: 'bg-amber-100 text-amber-700',      dot: 'bg-amber-500'   },
        sky:     { pill: 'bg-sky-100 text-sky-700',          dot: 'bg-sky-500'     },
        lime:    { pill: 'bg-lime-100 text-lime-700',        dot: 'bg-lime-500'    },
        green:   { pill: 'bg-green-100 text-green-700',      dot: 'bg-green-500'   },
        yellow:  { pill: 'bg-yellow-100 text-yellow-700',    dot: 'bg-yellow-500'  },
        orange:  { pill: 'bg-orange-100 text-orange-700',    dot: 'bg-orange-500'  },
        red:     { pill: 'bg-red-100 text-red-700',          dot: 'bg-red-500'     },
        purple:  { pill: 'bg-purple-100 text-purple-700',    dot: 'bg-purple-500'  },
        blue:    { pill: 'bg-blue-100 text-blue-700',        dot: 'bg-blue-500'    },
        cyan:    { pill: 'bg-cyan-100 text-cyan-700',         dot: 'bg-cyan-500'    },
        fuchsia: { pill: 'bg-fuchsia-100 text-fuchsia-700',  dot: 'bg-fuchsia-500' },
        pink:    { pill: 'bg-pink-100 text-pink-700',         dot: 'bg-pink-500'    },
    };
    const TYPE_ICON = { medication: 'fa-pills', appointment: 'fa-user-md', other: 'fa-calendar-plus' };
    const MONTHS = ['January','February','March','April','May','June',
                    'July','August','September','October','November','December'];

    /**
     * Formats a date into 'YYYY-MM-DD' key format.
     * @param {number} y - Year.
     * @param {number} m - Month (0-11).
     * @param {number} d - Day of the month.
     * @returns {string} Date key in 'YYYY-MM-DD' format.
     */
    function dateKey(y, m, d) {
        return `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    }

    /**
     * Adds days to a date and returns the formatted date key.
     * @param {string} s - Date string in 'YYYY-MM-DD' format.
     * @param {number} n - Number of days to add.
     * @returns {string} Formatted date key in 'YYYY-MM-DD' format.
     */
    function addDays(s, n)   { const d = new Date(s + 'T00:00:00'); d.setDate(d.getDate() + n); return dateKey(d.getFullYear(), d.getMonth(), d.getDate()); }

    /**
     * Adds weeks to a date and returns the formatted date key.
     * @param {string} s - Date string in 'YYYY-MM-DD' format.
     * @param {number} n - Number of weeks to add.
     * @returns {string} Formatted date key in 'YYYY-MM-DD' format.
     */
    function addWeeks(s, n)  { return addDays(s, n * 7); }

    /**
     * Adds months to a date and returns the formatted date key.
     * @param {string} s - Date string in 'YYYY-MM-DD' format.
     * @param {number} n - Number of months to add.
     * @returns {string} Formatted date key in 'YYYY-MM-DD' format.
     */
    function addMonths(s, n) { const d = new Date(s + 'T00:00:00'); d.setMonth(d.getMonth() + n); return dateKey(d.getFullYear(), d.getMonth(), d.getDate()); }

    /**
     * Sorts an array of events by their time in ascending order.
     * @param {Array} arr - Array of event objects.
     * @returns {Array} Sorted array of event objects.
     */
    function sortByTime(arr) {
        return arr.sort((a, b) => {
            if (!a.time && !b.time) return 0; if (!a.time) return 1; if (!b.time) return -1;
            return a.time.localeCompare(b.time);
        });
    }

    /**
     * Renders the calendar grid and populates it with events.
     */
    function renderCalendar() {
        const events   = loadEvents();
        const $grid    = $('#cal-grid').empty();
        $('#cal-title').text(MONTHS[viewMonth] + ' ' + viewYear);

        const firstDay    = new Date(viewYear, viewMonth, 1).getDay();
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
        const daysInPrev  = new Date(viewYear, viewMonth, 0).getDate();
        const todayKey    = dateKey(today.getFullYear(), today.getMonth(), today.getDate());

        for (let i = 0; i < 42; i++) {
            let cy = viewYear, cm = viewMonth, cd, other = false;
            if (i < firstDay) {
                cm = viewMonth - 1; if (cm < 0) { cm = 11; cy--; }
                cd = daysInPrev - firstDay + i + 1; other = true;
            } else if (i >= firstDay + daysInMonth) {
                cm = viewMonth + 1; if (cm > 11) { cm = 0; cy++; }
                cd = i - firstDay - daysInMonth + 1; other = true;
            } else { cd = i - firstDay + 1; }

            const key    = dateKey(cy, cm, cd);
            const dayEvs = events[key] || [];

            const $cell = $('<div>', {
                class: 'day-cell border-r border-b border-slate-200 p-2 cursor-pointer transition-colors hover:bg-slate-50'
                     + (other           ? ' other-month' : '')
                     + (key === todayKey ? ' today'       : ''),
                'data-key': key
            });

            $('<div>', { class: 'day-num text-sm font-medium text-slate-700 mb-1 w-7 h-7 flex items-center justify-center' })
                .text(cd).appendTo($cell);

            dayEvs.slice(0, 3).forEach(function (ev) {
                const cls  = COLOR_CLASSES[ev.color] || COLOR_CLASSES.indigo;
                const icon = TYPE_ICON[ev.type] || 'fa-calendar';
                const lbl  = (ev.time ? ev.time + ' ' : '') + ev.title;
                $('<div>', { class: 'event-pill ' + cls.pill, title: lbl })
                    .html(`<i class="fas ${icon} mr-1 opacity-60 text-[9px]"></i>${lbl}`)
                    .appendTo($cell);
            });
            if (dayEvs.length > 3) {
                $('<div>', { class: 'text-[10px] text-slate-400 mt-0.5 pl-1' })
                    .text('+' + (dayEvs.length - 3) + ' more').appendTo($cell);
            }

            $cell.on('click', function () { openModal(key); });
            $grid.append($cell);
        }
    }

    /**
     * Opens the event modal for a given date key.
     * @param {string} key - Date key in 'YYYY-MM-DD' format.
     */
    function openModal(key) {
        selectedDateKey = key;
        const [y, m, d] = key.split('-').map(Number);
        $('#modal-title').text(MONTHS[m - 1] + ' ' + d + ', ' + y);
        renderModalEvents();
        resetForm();
        $('#modal-backdrop').removeClass('hidden');
        setTimeout(() => $('#event-title').focus(), 150);
    }

    /**
     * Closes the event modal and resets the selected date key.
     */
    function closeModal() {
        $('#modal-backdrop').addClass('hidden');
        selectedDateKey = null;
    }

    /**
     * Resets the event form fields to their default values.
     */
    function resetForm() {
        $('#event-title, #event-dosage, #event-doctor, #event-location, #event-notes').val('');
        $('#event-time').val('');
        $('#event-duration').val('');
        $('#repeat-toggle').prop('checked', false);
        $('#repeat-fields').addClass('hidden');
        $('#repeat-freq').val('weekly');
        $('#repeat-end-type').val('occurrences');
        $('#repeat-count').val('4');
        $('#repeat-until').val('');
        $('#repeat-end-occurrences').removeClass('hidden');
        $('#repeat-end-date').addClass('hidden');
        switchType('medication');
        resetColorPicker();
    }

    /**
     * Renders the event list within the modal for the selected date.
     */
    function renderModalEvents() {
        const dayEvents = (loadEvents()[selectedDateKey] || []);
        const $list     = $('#modal-events').empty();

        if (dayEvents.length === 0) {
            $list.append($('<p>', { class: 'text-sm text-slate-400 py-1' }).text('No events yet.')); return;
        }

        dayEvents.forEach(function (ev, idx) {
            const cls  = COLOR_CLASSES[ev.color] || COLOR_CLASSES.indigo;
            const icon = TYPE_ICON[ev.type] || 'fa-calendar';
            const $row = $('<div>', { class: 'flex items-start gap-2 group py-0.5' });

            $('<span>', { class: 'w-2 h-2 rounded-full flex-shrink-0 mt-1.5 ' + cls.dot }).appendTo($row);

            const $info = $('<div>', { class: 'flex-1 min-w-0' }).appendTo($row);
            $('<div>', { class: 'flex items-center gap-1.5 text-sm text-slate-800 font-medium' })
                .html(`<i class="fas ${icon} text-[10px] opacity-50"></i> ${ev.title}`).appendTo($info);

            const meta = [];
            if (ev.time) meta.push(ev.time);
            if (ev.type === 'medication'  && ev.dosage)   meta.push(ev.dosage);
            if (ev.type === 'appointment' && ev.doctor)   meta.push(ev.doctor);
            if (ev.type === 'appointment' && ev.duration) meta.push(ev.duration);
            if (ev.type === 'appointment' && ev.location) meta.push(ev.location);
            if (ev.type === 'other'       && ev.notes)    meta.push(ev.notes);
            if (ev.recurring) meta.push('<span class="text-indigo-500"><i class="fas fa-redo text-[9px]"></i> repeating</span>');
            if (meta.length) $('<div>', { class: 'text-xs text-slate-400 mt-0.5' }).html(meta.join(' · ')).appendTo($info);

            $('<button>', {
                class: 'opacity-0 group-hover:opacity-100 w-6 h-6 rounded flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 transition-all flex-shrink-0',
                title: 'Delete'
            }).html('<i class="fas fa-times text-xs"></i>')
              .on('click', function () { deleteEvent(idx, ev); }).appendTo($row);

            $list.append($row);
        });
    }

    /**
     * Deletes an event from the schedule and updates the calendar and modal.
     * @param {number} idx - Index of the event to delete.
     * @param {object} ev - Event object to delete.
     */
    function deleteEvent(idx, ev) {
        const events = loadEvents();
        if (ev.recurringId) {
            const all = confirm('Delete all occurrences of this repeating event?\n\nOK = all   Cancel = just this one');
            if (all) {
                Object.keys(events).forEach(function (k) {
                    events[k] = (events[k] || []).filter(e => e.recurringId !== ev.recurringId);
                    if (!events[k].length) delete events[k];
                });
                saveEvents(events); renderModalEvents(); renderCalendar(); return;
            }
        }
        const arr = events[selectedDateKey] || [];
        arr.splice(idx, 1);
        if (!arr.length) delete events[selectedDateKey]; else events[selectedDateKey] = arr;
        saveEvents(events); renderModalEvents(); renderCalendar();
    }

    /**
     * Handles the submission of the event form.
     * Validates form fields and creates a new event or updates an existing one.
     */
    $('#event-form').on('submit', function (e) {
        e.preventDefault();
        const title = $('#event-title').val().trim();
        if (!title) { $('#event-title').focus(); return; }

        const type = $('#event-type').val();
        const ev   = { type, title, time: $('#event-time').val() || null, color: $('#event-color').val() };

        if (type === 'medication') {
            const d = $('#event-dosage').val().trim();    if (d)   ev.dosage   = d;
        } else if (type === 'appointment') {
            const doc = $('#event-doctor').val().trim();  if (doc) ev.doctor   = doc;
            const dur = $('#event-duration').val();       if (dur) ev.duration = dur;
            const loc = $('#event-location').val().trim();if (loc) ev.location = loc;
        } else {
            const n = $('#event-notes').val().trim();     if (n)   ev.notes    = n;
        }

        const datesToWrite = [selectedDateKey];
        if ($('#repeat-toggle').is(':checked')) {
            ev.recurring   = true;
            ev.recurringId = Date.now() + '-' + Math.random().toString(36).slice(2);
            const freq    = $('#repeat-freq').val();
            const endType = $('#repeat-end-type').val();
            const maxN    = endType === 'occurrences' ? (parseInt($('#repeat-count').val(), 10) || 4) : 365;
            const until   = endType === 'date' ? $('#repeat-until').val() : null;
            let cur = selectedDateKey;
            for (let n = 1; n < maxN; n++) {
                const next = freq === 'daily' ? addDays(cur, 1) : freq === 'weekly' ? addWeeks(cur, 1) : addMonths(cur, 1);
                if (until && next > until) break;
                datesToWrite.push(next); cur = next;
            }
        }

        const events = loadEvents();
        datesToWrite.forEach(function (key) {
            events[key] = sortByTime((events[key] || []).concat(Object.assign({}, ev)));
        });
        saveEvents(events);
        renderModalEvents(); renderCalendar();
        $('#event-title').val('').focus();
    });

    /**
     * Switches the event type and updates the form fields accordingly.
     * @param {string} type - The new event type to switch to.
     */
    function switchType(type) {
        $('#event-type').val(type);
        $('.type-btn').removeClass('bg-white shadow text-indigo-700').addClass('text-slate-500');
        $(`.type-btn[data-type="${type}"]`).addClass('bg-white shadow text-indigo-700').removeClass('text-slate-500');
        $('#fields-medication, #fields-appointment, #fields-other').addClass('hidden');
        $(`#fields-${type}`).removeClass('hidden');
    }
    $(document).on('click', '.type-btn', function () { switchType($(this).data('type')); });

    /**
     * Toggles the visibility of repeat fields based on the repeat toggle state.
     */
    $('#repeat-toggle').on('change', function () { $('#repeat-fields').toggleClass('hidden', !this.checked); });

    /**
     * Handles the change event for repeat end type selection.
     * Toggles visibility of occurrence and date fields based on selected end type.
     */
    $('#repeat-end-type').on('change', function () {
        const byDate = $(this).val() === 'date';
        $('#repeat-end-occurrences').toggleClass('hidden', byDate);
        $('#repeat-end-date').toggleClass('hidden', !byDate);
    });


    /**
     * Resets the color picker to its default state.
     */
    // Build color dropdown from COLOR_CLASSES
    (function buildColorDropdown() {
        const $dd = $('#color-dropdown');
        Object.keys(COLOR_CLASSES).forEach(function (key) {
            const label = key.charAt(0).toUpperCase() + key.slice(1);
            const swatch = COLOR_CLASSES[key].dot;
            $('<div>', {
                class: 'color-option flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 cursor-pointer transition-colors',
                'data-value': key,
                'data-label': label,
                'data-swatch': swatch
            }).html(`<span class="w-3.5 h-3.5 rounded-sm flex-shrink-0 ${swatch}"></span><span class="text-sm text-slate-700">${label}</span>`)
              .appendTo($dd);
        });
    })();

    function setColorPicker(key) {
        const label  = key.charAt(0).toUpperCase() + key.slice(1);
        const swatch = COLOR_CLASSES[key] ? COLOR_CLASSES[key].dot : COLOR_CLASSES.indigo.dot;
        $('#event-color').val(key);
        $('#color-label').text(label);
        $('#color-swatch').attr('class', 'w-3.5 h-3.5 rounded-sm flex-shrink-0 ' + swatch);
        $('#color-dropdown').addClass('hidden');
    }

    function resetColorPicker() { setColorPicker('indigo'); }

    $('#color-trigger').on('click', function (e) { e.stopPropagation(); $('#color-dropdown').toggleClass('hidden'); });
    $(document).on('click', function (e) { if (!$(e.target).closest('#color-picker').length) $('#color-dropdown').addClass('hidden'); });
    $(document).on('click', '.color-option', function () { setColorPicker($(this).data('value')); });

    /**
     * Handles the click event for navigation buttons.
     * Updates the view month and year, and renders the calendar accordingly.
     */
    $('#btn-prev').on('click', function () { if (--viewMonth < 0)  { viewMonth = 11; viewYear--; } renderCalendar(); });
    $('#btn-next').on('click', function () { if (++viewMonth > 11) { viewMonth = 0;  viewYear++; } renderCalendar(); });
    $('#btn-today').on('click', function () { viewYear = today.getFullYear(); viewMonth = today.getMonth(); renderCalendar(); });

    /**
     * Initializes the schedule component by setting default event type, rendering calendar, and handling modal close events.
     */
    $('#modal-close').on('click', closeModal);
    $('#modal-backdrop').on('click', function (e) { if ($(e.target).is('#modal-backdrop')) closeModal(); });
    $(document).on('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    // initialize all the things
    switchType('medication');
    renderCalendar();
});</script>
</body>
</html>
