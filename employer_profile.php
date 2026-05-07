<?php
require_once 'auth.php';
requireRole('employer');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    $stmt = $pdo->prepare("INSERT INTO employer_profiles (user_id, company_name) VALUES (?, ?)");
    $stmt->execute([$user_id, $_SESSION['username'] ?? '']);

    $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $company_name = trim($_POST['company_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $lat = trim($_POST['latitude'] ?? '14.5995');
    $lng = trim($_POST['longitude'] ?? '120.9842');

    if ($company_name === '') {
        $error = 'Company name is required.';
    }

    if (!$error) {
        $stmt = $pdo->prepare("
            UPDATE employer_profiles 
            SET company_name = ?, address = ?, description = ?, latitude = ?, longitude = ? 
            WHERE user_id = ?
        ");

        $stmt->execute([
            $company_name,
            $address,
            $description,
            $lat,
            $lng,
            $user_id
        ]);

        $message = 'Company profile updated successfully!';

        $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
    }
}
?>

<?php include 'header.php'; ?>

<div class="profile-page obj-width">

    <div class="profile-card">

        <div class="profile-header">
            <h2>Company Profile</h2>
            <p>Update your company information and set your office location for job seekers.</p>
        </div>

        <?php if ($message): ?>
            <div class="success-alert">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box profile-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="profile-grid">

                <div>
                    <div class="input-group">
                        <label>
                            <i class="fa-solid fa-building"></i>
                            Company Name
                        </label>

                        <input
                            type="text"
                            name="company_name"
                            value="<?php echo htmlspecialchars($profile['company_name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="input-group">
                        <label>
                            <i class="fa-solid fa-location-dot"></i>
                            Company Address
                        </label>

                        <div class="location-search-wrapper">
                            <input
                                type="text"
                                name="address"
                                id="address"
                                placeholder="Type company address, city, or province"
                                autocomplete="off"
                                value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>"
                            >

                            <div class="location-suggestions" id="locationSuggestions"></div>
                        </div>

                        <small class="location-hint" id="locationHint">
                            Type a place, then select a suggestion to update the map.
                        </small>
                    </div>

                    <div class="input-group">
                        <label>
                            <i class="fa-solid fa-file-lines"></i>
                            Company Description
                        </label>

                        <textarea
                            name="description"
                            rows="10"
                            placeholder="Tell job seekers about your company..."
                        ><?php echo htmlspecialchars($profile['description'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div>
                    <div class="map-section">
                        <div class="map-text">
                            <h3>Set Company Location</h3>
                            <p>
                                Select a location suggestion, click the map, or drag the marker to set your exact company location.
                            </p>
                        </div>

                        <div id="map"></div>
                    </div>

                    <div class="coordinates-grid">
                        <div class="input-group">
                            <label>Latitude</label>
                            <input
                                type="text"
                                name="latitude"
                                id="lat"
                                readonly
                                value="<?php echo htmlspecialchars($profile['latitude'] ?? '14.5995'); ?>"
                            >
                        </div>

                        <div class="input-group">
                            <label>Longitude</label>
                            <input
                                type="text"
                                name="longitude"
                                id="lng"
                                readonly
                                value="<?php echo htmlspecialchars($profile['longitude'] ?? '120.9842'); ?>"
                            >
                        </div>
                    </div>
                </div>

            </div>

            <button type="submit" class="save-profile-btn">
                Update Company Profile
            </button>

        </form>

    </div>

</div>

<script>
    var initialLat = <?php echo json_encode((float)($profile['latitude'] ?? 14.5995)); ?>;
    var initialLng = <?php echo json_encode((float)($profile['longitude'] ?? 120.9842)); ?>;

    var map = L.map('map').setView([initialLat, initialLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var marker = L.marker([initialLat, initialLng], {
        draggable: true,
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map);

    var addressInput = document.getElementById('address');
    var suggestionsBox = document.getElementById('locationSuggestions');
    var locationHint = document.getElementById('locationHint');
    var searchTimer = null;
    var lastSuggestionQuery = '';

    map.on('click', function(e) {
        moveMarker(e.latlng.lat, e.latlng.lng, false);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    marker.on('dragend', function() {
        var position = marker.getLatLng();
        moveMarker(position.lat, position.lng, false);
        reverseGeocode(position.lat, position.lng);
    });

    if (addressInput) {
        addressInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            var query = addressInput.value.trim();

            if (query.length < 3) {
                hideSuggestions();
                setLocationHint('Type at least 3 characters to show location suggestions.', '');
                return;
            }

            searchTimer = setTimeout(function() {
                fetchSuggestions(query);
            }, 500);
        });

        addressInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimer);
                fetchSuggestions(addressInput.value.trim());
            }
        });

        addressInput.addEventListener('blur', function() {
            setTimeout(hideSuggestions, 200);
        });

        addressInput.addEventListener('focus', function() {
            if (suggestionsBox && suggestionsBox.children.length > 0) {
                suggestionsBox.classList.add('active');
            }
        });
    }

    async function fetchSuggestions(query) {
        if (!query || query.length < 3) {
            return;
        }

        if (query === lastSuggestionQuery && suggestionsBox.children.length > 0) {
            suggestionsBox.classList.add('active');
            return;
        }

        lastSuggestionQuery = query;
        setLocationHint('Searching for matching places...', 'loading');
        showSuggestionsMessage('Searching...');

        try {
            var response = await fetch(
                'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=5&countrycodes=ph&q=' +
                encodeURIComponent(query)
            );

            if (!response.ok) {
                throw new Error('Location search failed.');
            }

            var results = await response.json();
            renderSuggestions(results);

        } catch (error) {
            showSuggestionsMessage('Unable to load suggestions. You can still click the map manually.');
            setLocationHint('Unable to load location suggestions. Check your internet connection.', 'error');
        }
    }

    function renderSuggestions(results) {
        suggestionsBox.innerHTML = '';

        if (!results || results.length === 0) {
            showSuggestionsMessage('No locations found. Try adding barangay, city, or province.');
            setLocationHint('No matching location found.', 'error');
            return;
        }

        results.forEach(function(result) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'location-suggestion-item';
            item.textContent = result.display_name;

            item.addEventListener('click', function() {
                selectLocation(result);
            });

            suggestionsBox.appendChild(item);
        });

        suggestionsBox.classList.add('active');
        setLocationHint('Select one of the suggested locations to update the map.', 'success');
    }

    function selectLocation(result) {
        var lat = parseFloat(result.lat);
        var lng = parseFloat(result.lon);

        addressInput.value = result.display_name;
        moveMarker(lat, lng, true);
        hideSuggestions();
        setLocationHint('Map updated from your selected company location. You may now save your profile.', 'success');
    }

    function showSuggestionsMessage(message) {
        suggestionsBox.innerHTML = '<div class="location-suggestion-empty">' + message + '</div>';
        suggestionsBox.classList.add('active');
    }

    function hideSuggestions() {
        if (suggestionsBox) {
            suggestionsBox.classList.remove('active');
        }
    }

    async function reverseGeocode(lat, lng) {
        setLocationHint('Updating address from selected map point...', 'loading');

        try {
            var response = await fetch(
                'https://nominatim.openstreetmap.org/reverse?format=json&lat=' +
                encodeURIComponent(lat) +
                '&lon=' +
                encodeURIComponent(lng) +
                '&zoom=18&addressdetails=1'
            );

            if (!response.ok) {
                throw new Error('Reverse search failed.');
            }

            var result = await response.json();

            if (result && result.display_name) {
                addressInput.value = result.display_name;
            }

            setLocationHint('Company location updated from the map. You may now save your profile.', 'success');

        } catch (error) {
            setLocationHint('Coordinates updated. You can type the address manually if needed.', 'success');
        }
    }

    function moveMarker(lat, lng, zoomIn) {
        var newLocation = L.latLng(lat, lng);
        marker.setLatLng(newLocation);

        if (zoomIn) {
            map.setView(newLocation, 16);
        } else {
            map.panTo(newLocation);
        }

        updateCoords(lat, lng);
    }

    function updateCoords(lat, lng) {
        document.getElementById('lat').value = Number(lat).toFixed(6);
        document.getElementById('lng').value = Number(lng).toFixed(6);
    }

    function setLocationHint(message, status) {
        if (!locationHint) {
            return;
        }

        locationHint.textContent = message;
        locationHint.className = 'location-hint';

        if (status) {
            locationHint.classList.add(status);
        }
    }
</script>

<?php include 'footer.php'; ?>
