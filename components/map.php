<?php
session_start();

function json_parse($json) {
    $decoded = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;

    // validate structure
    foreach ($decoded as $item) {
        if (!isset($item['point']) || !is_array($item['point']) || count($item['point']) !== 2) {
            return null;
        }
    }

    return $decoded;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents("php://input");
    $mapData = json_parse($input);

    if ($mapData === null) {
        http_response_code(400);
        exit;
    }

    // store in session
    $_SESSION['mapData'] = $mapData;

    http_response_code(200);
    exit;
}

// For GET requests, load from session if available
$mapData = $_SESSION['mapData'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map | MedHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://kit.fontawesome.com/1ad3aa32da.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        #map { position: fixed; inset: 0; }
    </style>
</head>
<body class="bg-slate-50">

    <!-- Back Button -->
    <a href="/dashboard" class="fixed top-4 right-4 z-30 w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center cursor-pointer shadow-md text-slate-600 hover:text-indigo-600 transition-colors">
        <i class="fas fa-arrow-left"></i>
    </a>

    <!-- Get Location Button -->
    <button id="location-btn" class="fixed bottom-6 right-6 z-30 w-14 h-14 bg-indigo-600 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-indigo-700 transition-colors">
        <i class="fas fa-location-arrow text-lg"></i>
    </button>

    <!-- Context Menu -->
    <div id="context-menu" class="hidden fixed bg-white border border-slate-200 rounded-xl shadow-xl min-w-[160px] z-50 overflow-hidden">
        <div id="ctx-goto" class="flex items-center gap-2.5 px-4 py-3 cursor-pointer hover:bg-slate-100 transition-colors">
            <i class="fas fa-directions w-4 text-center text-slate-500"></i>
            <span>Go to</span>
        </div>
        <div id="ctx-address" class="flex items-center gap-2.5 px-4 py-3 cursor-pointer hover:bg-slate-100 transition-colors">
            <i class="fas fa-map-pin w-4 text-center text-slate-500"></i>
            <span>Get address</span>
        </div>
        <div id="ctx-call" class="flex items-center gap-2.5 px-4 py-3 cursor-pointer hover:bg-slate-100 transition-colors">
            <i class="fas fa-phone w-4 text-center text-slate-500"></i>
            <span>Call</span>
        </div>
        <div id="ctx-schedule" class="flex items-center gap-2.5 px-4 py-3 cursor-pointer hover:bg-indigo-50 text-indigo-600 transition-colors">
            <i class="fas fa-calendar-plus w-4 text-center"></i>
            <span>Schedule appointment</span>
        </div>
        <div id="ctx-hide" class="flex items-center gap-2.5 px-4 py-3 cursor-pointer hover:bg-slate-100 transition-colors">
            <i class="fas fa-eye-slash w-4 text-center text-slate-500"></i>
            <span>Hide</span>
        </div>
        <div id="ctx-remove" class="flex items-center gap-2.5 px-4 py-3 cursor-pointer hover:bg-red-50 text-red-500 transition-colors">
            <i class="fas fa-trash w-4 text-center"></i>
            <span>Remove</span>
        </div>
    </div>

    <!-- Schedule Appointment Modal -->
    <div id="schedule-backdrop" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4" style="background:rgba(15,23,42,0.35);backdrop-filter:blur(2px);">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-sm animate__animated animate__fadeInUp animate__faster">
            <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800" style="font-family:'DM Serif Display',serif;">Schedule Appointment</h2>
                    <p id="sched-institution-name" class="text-sm text-slate-400 mt-0.5"></p>
                </div>
                <button id="sched-close" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <form id="sched-form" class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Date</label>
                    <input id="sched-date" type="date" required
                           class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Time</label>
                        <input id="sched-time" type="time"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-widest mb-1">Duration</label>
                        <select id="sched-duration"
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
                <button type="submit"
                        class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition-colors shadow-sm">
                    <i class="fas fa-calendar-check mr-1.5"></i>Save to Schedule
                </button>
            </form>
        </div>
    </div>

    <!-- Toast -->
    <div id="sched-toast" class="hidden fixed bottom-24 left-1/2 -translate-x-1/2 z-50 px-5 py-3 bg-emerald-600 text-white text-sm font-medium rounded-xl shadow-lg animate__animated animate__fadeInUp">
        <i class="fas fa-check mr-2"></i>Appointment saved to your schedule
    </div>

    <!-- Map -->
    <div id="map"></div>

    <!-- Location Details Sidebar -->
    <aside id="location-sidebar" class="fixed top-0 right-0 w-80 h-screen bg-white border-l border-slate-200 shadow-xl overflow-y-auto translate-x-full transition-transform duration-300 z-40">
        <div class="p-5">
            <button id="sidebar-close" class="absolute top-4 right-4 w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <i class="fas fa-times"></i>
            </button>
            <h2 id="location-name" class="text-xl font-semibold text-slate-800 mt-2" style="font-family: 'DM Serif Display', serif;"></h2>
            <p id="location-address" class="text-sm text-slate-500 mt-2"></p>
            <p id="location-description" class="text-slate-700 mt-3 mb-5 text-sm"></p>

            <div class="space-y-3">
                <a href="#" class="block w-full py-3 text-center rounded-xl font-medium text-white transition-colors" id="apple-maps-btn" style="background: #007AFF;">
                    <i class="fas fa-map-marked-alt mr-2"></i>Apple Maps
                </a>
                <a href="#" class="block w-full py-3 text-center rounded-xl font-medium text-white transition-colors" id="google-maps-btn" style="background: #4285F4;">
                    <i class="fas fa-map-marker-alt mr-2"></i>Google Maps
                </a>
            </div>
        </div>
    </aside>

<script>
    /**
     * Initializes an interactive map with markers and associated functionality, using the Leaflet library.
     *
     * Features include:
     * - Displaying markers for predefined locations with popups and sidebars showing additional information.
     * - Context menu interaction for specific actions (e.g., getting directions, making phone calls).
     * - Automatically adjusting map bounds to include all markers.
     * - Sidebar with detailed location information.
     * - Integration with Apple Maps and Google Maps for navigation.
     * - User geolocation support for navigating to their current location.
     *
     * The main components of the functionality are:
     *
     * - Markers:
     *   - Created from a list of predefined coordinates and associated metadata.
     *   - Clickable for displaying associated information in a sidebar.
     *   - Context menu accessible via right-click for additional operations.
     * - Context Menu:
     *   - Allows actions like directing to the location, fetching an address, hiding/removing markers, or making phone calls.
     * - Sidebar:
     *   - Provides a detailed view of a selected location with metadata such as name, address, description, and phone.
     *   - Includes buttons for navigation with Apple Maps and Google Maps.
     * - Map Interaction:
     *   - Adjusts the view to include all markers.
     *   - Enables user geolocation to find their current position on the map.
     *
     * Dependencies:
     * - Leaflet.js: Handles map rendering and marker creation.
     * - jQuery: Facilitates DOM manipulation and AJAX requests for dynamic updates.
     *
     * Notes:
     * - The `coords` array contains the predefined locations with details such as coordinates, name, address, description, and phone.
     * - The context menu adjusts dynamically to avoid being rendered off-screen.
     * - The `#context-menu` and `#location-sidebar` elements must be present in the DOM for proper functionality.
     *
     * Usage:
     * This function does not accept parameters and must be invoked within a script that manages its lifecycle.
     */
$(function () {
    let contextLocation = null;
    let selectedMarker   = null;

    const coords = <?php echo json_encode($mapData); ?>; /*[
        {
            point: [28.5383, -81.3792],
            name: 'Orlando',
            address: '123 Main St, Orlando, FL',
            description: 'This is a description of the location.',
            phone: '555-123-4567'
        },
        {
            point: [28.55, -81.40],
            name: 'Location 2',
            address: '456 Example Ave',
            description: 'Another place.',
            phone: '555-987-6543'
        }
    ];*/

    const map = L.map('map').setView(coords[0].point, 12);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);


    function showContextMenu(nativeEvent, loc) {
        const $menu = $('#context-menu');
        $menu.css({ left: nativeEvent.clientX, top: nativeEvent.clientY })
             .removeClass('hidden');

        // Adjust if off-screen
        const rect = $menu[0].getBoundingClientRect();
        if (rect.right  > window.innerWidth)  $menu.css('left', window.innerWidth  - rect.width  - 10);
        if (rect.bottom > window.innerHeight) $menu.css('top',  window.innerHeight - rect.height - 10);
    }

    function hideContextMenu() {
        $('#context-menu').addClass('hidden');
    }

    function showSidebar(loc) {
        $('#location-name').text(loc.name);
        $('#location-address').text(loc.address);
        $('#location-description').text(loc.description);
        $('#apple-maps-btn').attr('href',  `https://maps.apple.com/?daddr=${loc.point[0]},${loc.point[1]}`);
        $('#google-maps-btn').attr('href', `https://www.google.com/maps/dir/?api=1&destination=${loc.point[0]},${loc.point[1]}`);
        $('#location-sidebar').removeClass('translate-x-full');
    }

    function closeSidebar() {
        $('#location-sidebar').addClass('translate-x-full');
    }

    const bounds = [];

    coords.forEach(function (loc) {
        bounds.push(loc.point);
        const marker = L.marker(loc.point).addTo(map);

        marker.on('click', function () {
            showSidebar(loc);
        });

        // Bind contextmenu directly on the marker — reliable, no proximity math needed
        marker.on('contextmenu', function (e) {
            e.originalEvent.preventDefault();
            selectedMarker  = marker;
            contextLocation = loc;
            showContextMenu(e.originalEvent, loc);
        });
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }


    map.on('click', hideContextMenu);

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#context-menu').length) {
            hideContextMenu();
        }
    });


    $('#ctx-goto').on('click', function () {
        if (contextLocation) map.setView(contextLocation.point, 16);
        hideContextMenu();
    });

    $('#ctx-address').on('click', function () {
        if (!contextLocation) { hideContextMenu(); return; }
        const [lat, lng] = contextLocation.point;
        $.getJSON(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .done(function (data) {
                contextLocation.address = data.display_name || 'Address not found';
                showSidebar(contextLocation);
            })
            .fail(function () {
                contextLocation.address = 'Could not get address';
                showSidebar(contextLocation);
            });
        hideContextMenu();
    });

    $('#ctx-call').on('click', function () {
        if (contextLocation && contextLocation.phone) {
            window.location.href = 'tel:' + contextLocation.phone;
        } else if (contextLocation) {
            contextLocation.phone = 'No phone available';
            showSidebar(contextLocation);
        }
        hideContextMenu();
    });

    $('#ctx-hide').on('click', function () {
        if (selectedMarker) map.removeLayer(selectedMarker);
        hideContextMenu();
    });

    $('#ctx-remove').on('click', function () {
        if (selectedMarker) {
            map.removeLayer(selectedMarker);
            selectedMarker  = null;
            contextLocation = null;
        }
        hideContextMenu();
    });


    $('#sidebar-close').on('click', closeSidebar);

    // ── Schedule appointment ─────────────────────────────────────────────

    const SCHEDULE_KEY = 'medhealth_schedule_v1';

    function loadSchedule() { try { return JSON.parse(localStorage.getItem(SCHEDULE_KEY)) || {}; } catch(e) { return {}; } }
    function saveSchedule(s) { localStorage.setItem(SCHEDULE_KEY, JSON.stringify(s)); }

    function padZ(n) { return String(n).padStart(2, '0'); }
    function dateKey(d) { return `${d.getFullYear()}-${padZ(d.getMonth()+1)}-${padZ(d.getDate())}`; }

    $('#ctx-schedule').on('click', function () {
        if (!contextLocation) { hideContextMenu(); return; }
        hideContextMenu();

        // Pre-fill date to today
        const today = new Date();
        $('#sched-date').val(`${today.getFullYear()}-${padZ(today.getMonth()+1)}-${padZ(today.getDate())}`);
        $('#sched-time').val('');
        $('#sched-duration').val('');
        $('#sched-institution-name').text(contextLocation.name || '');
        $('#schedule-backdrop').removeClass('hidden');
        setTimeout(() => $('#sched-date').focus(), 150);
    });

    $('#sched-close').on('click', function () { $('#schedule-backdrop').addClass('hidden'); });
    $('#schedule-backdrop').on('click', function (e) { if ($(e.target).is('#schedule-backdrop')) $('#schedule-backdrop').addClass('hidden'); });

    $('#sched-form').on('submit', function (e) {
        e.preventDefault();
        const dateStr = $('#sched-date').val();
        if (!dateStr) return;

        const ev = {
            type:     'appointment',
            title:    contextLocation.name || 'Appointment',
            time:     $('#sched-time').val()     || null,
            duration: $('#sched-duration').val() || null,
            location: contextLocation.address    || '',
            color:    'sky',
        };
        if (!ev.duration) delete ev.duration;

        const schedule = loadSchedule();
        const arr = schedule[dateStr] || [];
        arr.push(ev);
        arr.sort((a, b) => {
            if (!a.time && !b.time) return 0; if (!a.time) return 1; if (!b.time) return -1;
            return a.time.localeCompare(b.time);
        });
        schedule[dateStr] = arr;
        saveSchedule(schedule);

        $('#schedule-backdrop').addClass('hidden');

        // Toast
        const $toast = $('#sched-toast').removeClass('hidden animate__fadeOutDown').addClass('animate__fadeInUp');
        setTimeout(function () {
            $toast.removeClass('animate__fadeInUp').addClass('animate__fadeOutDown');
            setTimeout(() => $toast.addClass('hidden'), 500);
        }, 2500);
    });

    // ── get user location ────────────────────────────────────────────────

    $('#location-btn').on('click', function () {
        const $btn = $(this);
        $btn.html('<i class="fas fa-spinner fa-spin text-lg"></i>');

        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            $btn.html('<i class="fas fa-location-arrow text-lg"></i>');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (position) {
                const { latitude: lat, longitude: lng } = position.coords;
                map.setView([lat, lng], 14);

                const userIcon = L.divIcon({
                    className: 'bg-indigo-600 rounded-full border-4 border-white shadow-lg',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });

                L.marker([lat, lng], { icon: userIcon }).addTo(map)
                    .bindPopup('Your location').openPopup();

                $btn.html('<i class="fas fa-location-arrow text-lg"></i>');
            },
            function (error) {
                let message = 'Unable to get your location';
                if (error.code === error.PERMISSION_DENIED)       message = 'Location access denied. Please enable location permissions.';
                else if (error.code === error.POSITION_UNAVAILABLE) message = 'Location information unavailable.';
                alert(message);
                $btn.html('<i class="fas fa-location-arrow text-lg"></i>');
            }
        );
    });
});
</script>
</body>
</html>
