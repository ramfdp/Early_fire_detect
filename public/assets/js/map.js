const buildingLocations = {
    'Wisma Krakatau': [-6.001660, 106.043480],
    'CM-1': [-6.006261, 106.038939],
    'CM-2': [-6.005918, 105.992274],
    'CM-3': [-5.998242, 106.030167],
    'Antartika': [-5.989688, 106.019075]
};

let fireMap;
let buildingMarkers = {};

function generateRandomTemperature(min, max) {
    return (Math.random() * (max - min) + min).toFixed(1);
}

function getTemperatureStatus(temp) {
    temp = parseFloat(temp);
    if (temp < 43) return 'normal';
    if (temp >= 43 && temp < 53) return 'siaga';
    return 'kebakaran';
}

function initMap() {
    fireMap = L.map('fire-map', {
        minZoom: 13,
        maxZoom: 18,
        zoomControl: true
    }).setView([-6.0144, 106.0577], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        subdomains: 'abc',
        tileSize: 256,
        updateWhenIdle: true,
        keepBuffer: 2
    }).addTo(fireMap);

    addBuildingMarkers();

    const locations = Object.values(buildingLocations);
    if (locations.length > 0) {
        const bounds = L.latLngBounds(locations);
        fireMap.fitBounds(bounds.pad(0.1));
    }
    
    addMapLegend();
}

function addBuildingMarkers() {
    Object.keys(buildingLocations).forEach(building => {
        const markerIcon = createMarkerIcon('normal');
        const marker = L.marker(buildingLocations[building], {
            icon: markerIcon,
            draggable: false
        }).addTo(fireMap);

        marker.bindPopup(`<b>${building}</b><br>Status: Normal<br>Temperature: Checking...`);
        buildingMarkers[building] = marker;
    });
}

function createMarkerIcon(status) {
    let color = '#32a932';
    if (status === 'siaga') {
        color = '#f59c1a';
    } else if (status === 'kebakaran') {
        color = '#ff3e3e';
    }

    return L.divIcon({
        className: 'custom-map-marker',
        html: `<div class="marker-pin" style="background-color: ${color}; width: 30px; height: 30px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.3);"></div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
}

function addMapLegend() {
    const legend = L.control({ position: 'bottomright' });

    legend.onAdd = function (map) {
        const div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = `
                <div class="map-legend-title"><b>Status Suhu</b></div>
                <div class="map-legend-item">
                    <div class="map-legend-color" style="background-color: #32a932;"></div>
                    <div>Normal (< 43°C)</div>
                </div>
                <div class="map-legend-item">
                    <div class="map-legend-color" style="background-color: #f59c1a;"></div>
                    <div>Siaga (43°C - 52°C)</div>
                </div>
                <div class="map-legend-item">
                    <div class="map-legend-color" style="background-color: #ff3e3e;"></div>
                    <div>Kebakaran (> 53°C)</div>
                </div>
            `;
        return div;
    };

    legend.addTo(fireMap);
}

function updateMapMarkers() {
    Object.keys(buildingLocations).forEach(building => {
        const cardId = `building-${building.toLowerCase().replace(/\s+/g, '-')}`;
        const card = document.getElementById(cardId);
        
        if (card && buildingMarkers[building]) {
            const statusElement = card.querySelector('.status-label');
            const tempElement = card.querySelector('.temperature-display');

            if (statusElement && tempElement) {
                const status = statusElement.textContent.toLowerCase();
                const temp = tempElement.textContent;

                const newIcon = createMarkerIcon(status);
                buildingMarkers[building].setIcon(newIcon);

                const markerElement = buildingMarkers[building].getElement();
                if (markerElement) {
                    markerElement.classList.remove('status-normal', 'status-siaga', 'status-kebakaran');
                    markerElement.classList.add(`status-${status}`);
                }
                
                let statusColor = '#32a932'; // normal
                if (status === 'siaga') statusColor = '#f59c1a';
                else if (status === 'kebakaran') statusColor = '#ff3e3e';

                buildingMarkers[building].getPopup().setContent(`
                        <b>${building}</b><br>
                        <b>Status:</b> <span style="color: ${statusColor}">${status.toUpperCase()}</span><br>
                        <b>Temperature:</b> ${temp}
                    `);

                if (status === 'kebakaran' && buildingMarkers[building].getPopup && !buildingMarkers[building].getPopup().isOpen()) {
                    buildingMarkers[building].openPopup();
                }
            }
        }
    });
}

function getMonthName(monthIndex) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return months[monthIndex];
}

document.addEventListener('DOMContentLoaded', function () {
    initMap();

    if (window.initialBuildingData && typeof window.initialBuildingData === 'object') {
        Object.keys(window.initialBuildingData).forEach(building => {
            const data = window.initialBuildingData[building];
            if (data && data.status === 'kebakaran') {
                $.gritter.add({
                    title: 'PERINGATAN KEBAKARAN!',
                    text: `Suhu di ${building} mencapai ${data.temperature_value}°C! Segera lakukan evakuasi!`,
                    sticky: false,
                    time: '3000',
                    class_name: 'my-sticky-class gritter-danger'
                });
            } else if (data && data.status === 'siaga') {
                $.gritter.add({
                    title: 'Peringatan Siaga!',
                    text: `Suhu di ${building} mencapai ${data.temperature_value}°C! Perhatikan kondisi sekitar!`,
                    sticky: false,
                    time: '3000',
                    class_name: 'gritter-warning'
                });
            }
        });
    }

    updateMapMarkers();
    setTimeout(updateBuildingTemperatures, 60000);

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (fireMap && e.target.getAttribute('href') === '#fire-map-tab') { // Pastikan fireMap ada
            setTimeout(function () {
                fireMap.invalidateSize();
                const locations = Object.values(buildingLocations);
                if (locations.length > 0) {
                    const bounds = L.latLngBounds(locations);
                    fireMap.fitBounds(bounds.pad(0.1));
                }
            }, 50);
        }
    });
});

function updateBuildingTemperatures() {
    const buildings = document.querySelectorAll('.building-card');

    buildings.forEach(card => {
        const buildingTitleElement = card.querySelector('.card-title');
        if (!buildingTitleElement) return;
        const buildingTitle = buildingTitleElement.textContent;

        const temperature = generateRandomTemperature(38, 59);
        const status = getTemperatureStatus(temperature);

        const tempDisplay = card.querySelector('.temperature-display');
        if (tempDisplay) tempDisplay.textContent = `${temperature}°C`;

        const statusDisplay = card.querySelector('.status-label');
        if (statusDisplay) statusDisplay.textContent = status.toUpperCase();
        
        const timestampDisplay = card.querySelector('.text-white.mt-3'); // Hati-hati jika selector ini tidak unik per card
        if (timestampDisplay) {
             const now = new Date();
             const formattedTime = `${String(now.getDate()).padStart(2, '0')} ${getMonthName(now.getMonth())} ${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
             timestampDisplay.textContent = `Updated: ${formattedTime}`;
        }

        card.className = 'card building-card';
        if (status === 'normal') {
            card.classList.add('status-normal');
        } else if (status === 'siaga') {
            card.classList.add('status-siaga');
            $.gritter.add({
                title: 'Peringatan Siaga!',
                text: `Suhu di ${buildingTitle} mencapai ${temperature}°C! Perhatikan kondisi sekitar!`,
                sticky: false,
                time: '3000',
                class_name: 'gritter-warning'
            });
        } else if (status === 'kebakaran') {
            card.classList.add('status-kebakaran');
            $.gritter.add({
                title: 'PERINGATAN KEBAKARAN!',
                text: `Suhu di ${buildingTitle} mencapai ${temperature}°C! Segera lakukan evakuasi!`,
                sticky: false,
                time: '3000',
                class_name: 'my-sticky-class gritter-danger'
            });
        }
    });

    updateMapMarkers();

    const lastUpdateElement = document.querySelector('.last-update-time');
    if (lastUpdateElement) {
        const now = new Date();
        const formattedTime = `${String(now.getDate()).padStart(2, '0')} ${getMonthName(now.getMonth())} ${now.getFullYear()} ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}`;
        lastUpdateElement.textContent = formattedTime;
    }

    setTimeout(updateBuildingTemperatures, 60000);
}

$(document).ready(function () {
    $(document).on('click', '.edit-user-btn', function () {
        const userId = $(this).data('id');
        const name = $(this).data('name');
        const email = $(this).data('email');
        const role = $(this).data('role');

        $('#editUserId').val(userId);
        $('#editName').val(name);
        $('#editEmail').val(email);
        $('#editRole').val(role);

        $('#editUserModal').modal('show');
    });

    $('#addNewUserBtn').click(function () {
        $('#addUserModal').modal('show');
    });
});