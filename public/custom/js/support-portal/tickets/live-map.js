let map;
let ticketMarkers = {};
let engineerMarkers = {};
let blinkIntervals = {};
let refreshInterval;

/* ============================
   INITIALIZE MAP
============================ */
function initGoogleMap() {

    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 21.1458, lng: 79.0882 },
        zoom: 12,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: true
    });

    startLiveRefresh();
}

/* ============================
   AUTO REFRESH
============================ */
function startLiveRefresh() {
    fetchLiveData();
    refreshInterval = setInterval(fetchLiveData, 5000);
}

function stopLiveRefresh() {
    clearInterval(refreshInterval);
}

/* ============================
   FETCH LIVE DATA
============================ */
function fetchLiveData() {

    $.ajax({
        url: '/tickets/live-map-data',
        type: 'GET',
        success: function (response) {

            if (response.status) {
                renderMap(response);
            }
        },
        error: function (xhr) {
            console.error("Live map fetch error:", xhr.responseText);
        }
    });
}

/* ============================
   MAIN RENDER FUNCTION
============================ */
function renderMap(data) {

    clearMarkers();

    renderTechnicianPanel(data.engineers);

    // Engineers available for assignment
    let availableEngineers = data.engineers.filter(e =>
        e.status === 'active' || e.status === 'moving'
    );

    // Add engineer markers
    data.engineers.forEach(engineer => {
        if (engineer.lat && engineer.lng) {
            addEngineerMarker(engineer);
        }
    });

    // Add ticket markers and nearest logic
    data.tickets.forEach(ticket => {

        if (ticket.status === 'open') {

            addTicketMarker(ticket);

            let nearest = findNearestEngineer(ticket, availableEngineers);

            if (nearest) {
                blinkEngineer(nearest.id);
                drawLine(ticket, nearest);

                availableEngineers = availableEngineers.filter(e => e.id !== nearest.id);
            }
        }
    });
}

/* ============================
   ADD TICKET MARKER
============================ */
function addTicketMarker(ticket) {

    ticketMarkers[ticket.id] = new google.maps.Marker({
        position: { lat: parseFloat(ticket.lat), lng: parseFloat(ticket.lng) },
        map: map,
        icon: "http://maps.google.com/mapfiles/ms/icons/red-dot.png",
        title: ticket.company_name
    });
}

/* ============================
   ADD ENGINEER MARKER
============================ */
function addEngineerMarker(engineer) {

    const marker = new google.maps.Marker({
        position: {
            lat: parseFloat(engineer.lat),
            lng: parseFloat(engineer.lng)
        },
        map: map,
        title: engineer.name,
        icon: {
            path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
            scale: 6,
            fillColor: engineer.status === 'moving' ? '#ffc107' : '#28a745',
            fillOpacity: 1,
            strokeWeight: 1,
            rotation: 0
        },
        label: {
            text: engineer.name,
            color: "#000",
            fontSize: "12px",
            fontWeight: "bold"
        }
    });

    engineerMarkers[engineer.id] = marker;
}


/* ============================
   FIND NEAREST ENGINEER
============================ */
function findNearestEngineer(ticket, engineers) {

    let minDistance = Infinity;
    let nearest = null;

    engineers.forEach(engineer => {

        let distance = calculateDistance(
            ticket.lat, ticket.lng,
            engineer.lat, engineer.lng
        );

        if (distance < minDistance) {
            minDistance = distance;
            nearest = engineer;
        }
    });

    return nearest;
}

/* ============================
   HAVERSINE DISTANCE
============================ */
function calculateDistance(lat1, lon1, lat2, lon2) {

    const R = 6371;

    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;

    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) *
        Math.sin(dLon / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

/* ============================
   BLINK ENGINEER
============================ */
function blinkEngineer(engineerId) {

    let marker = engineerMarkers[engineerId];

    if (!marker) return;

    if (blinkIntervals[engineerId]) return;

    let visible = true;

    blinkIntervals[engineerId] = setInterval(() => {
        marker.setVisible(visible);
        visible = !visible;
    }, 500);
}

/* ============================
   DRAW LINE
============================ */
function drawLine(ticket, engineer) {

    new google.maps.Polyline({
        path: [
            { lat: parseFloat(ticket.lat), lng: parseFloat(ticket.lng) },
            { lat: parseFloat(engineer.lat), lng: parseFloat(engineer.lng) }
        ],
        geodesic: true,
        strokeColor: "#00c853",
        strokeOpacity: 1.0,
        strokeWeight: 3,
        map: map
    });
}

/* ============================
   CLEAR MARKERS
============================ */
function clearMarkers() {

    Object.values(ticketMarkers).forEach(marker => marker.setMap(null));
    Object.values(engineerMarkers).forEach(marker => marker.setMap(null));

    ticketMarkers = {};
    engineerMarkers = {};

    Object.values(blinkIntervals).forEach(interval => clearInterval(interval));
    blinkIntervals = {};
}

/* ============================
   TECHNICIAN SIDE PANEL
============================ */
function renderTechnicianPanel(engineers) {

    let container = document.getElementById("technicianList");
    container.innerHTML = "<h6 class='mb-3'>Engineers</h6>";

    engineers.forEach(engineer => {
        let colorClass =
            engineer.status === 'active' ? 'success' :
            engineer.status === 'moving' ? 'warning' :
            'secondary';

        let statusText =
            engineer.status === 'active' ? '🟢 Active' :
            engineer.status === 'moving' ? '🟡 Moving' :
            '⚪ Offline';

        container.innerHTML += `
            <div class="engineer-card mb-3"
                 data-id="${engineer.id}"
                 style="cursor:pointer;">
                <div class="d-flex align-items-center p-2 border rounded shadow-sm bg-white">

                    <div class="flex-shrink-0 me-3">
                        <img src="https://ui-avatars.com/api/?name=${engineer.name}&size=40&background=0d6efd&color=fff"
                             class="rounded-circle">
                    </div>

                    <div class="flex-grow-1">
                        <div class="fw-semibold">
                            ${engineer.name}
                            <span class="badge bg-${colorClass}">${statusText}</span>
                        </div>
                        <small class="text-muted">
                            ${engineer.address ? engineer.address : 'Location updating...'}
                        </small>
                    </div>

                </div>
            </div>
        `;
    });

    // 🔥 Add click behavior
    document.querySelectorAll(".engineer-card").forEach(card => {

        card.addEventListener("click", function() {

            let engineerId = this.dataset.id;
            let marker = engineerMarkers[engineerId];

            if (marker) {

                map.setCenter(marker.getPosition());
                map.setZoom(16);

                marker.setAnimation(google.maps.Animation.BOUNCE);

                setTimeout(() => {
                    marker.setAnimation(null);
                }, 1500);
            }

        });

    });
}


/* ============================
   GOOGLE MAP CALLBACK
============================ */
window.initGoogleMap = initGoogleMap;
