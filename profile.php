<?php
require_once 'auth.php';
requireRole('user');
ensureProfileResumeColumn();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

$schedule_options = [
    'Weekdays - Morning Shift (8:00 AM - 12:00 PM)',
    'Weekdays - Afternoon Shift (1:00 PM - 5:00 PM)',
    'Weekdays - Evening Shift (6:00 PM - 10:00 PM)',
    'Weekends - Morning Shift (8:00 AM - 12:00 PM)',
    'Weekends - Afternoon Shift (1:00 PM - 5:00 PM)',
    'Weekends - Evening Shift (6:00 PM - 10:00 PM)',
    'Flexible Schedule'
];

/* ================= FETCH PROFILE ================= */

$stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    $stmt = $pdo->prepare("INSERT INTO profiles (user_id, full_name) VALUES (?, ?)");
    $stmt->execute([$user_id, $_SESSION['username'] ?? '']);

    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
}

/* ================= UPDATE PROFILE ================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $schedule_choice = trim($_POST['schedule_select'] ?? '');
    $custom_schedule = trim($_POST['custom_schedule'] ?? '');
    $work_status = trim($_POST['work_status'] ?? 'available');
    $lat = trim($_POST['latitude'] ?? '14.5995');
    $lng = trim($_POST['longitude'] ?? '120.9842');

    $schedule = $schedule_choice === 'custom' ? $custom_schedule : $schedule_choice;
    $existing_resume = $profile['resume_file'] ?? '';
    $resume_file = $existing_resume;
    $existing_profile_photo = $profile['profile_photo'] ?? '';
    $profile_photo = $existing_profile_photo;

    if ($full_name === '') {
        $error = 'Full name is required.';
    } elseif ($schedule === '') {
        $error = 'Please select a schedule or enter a custom schedule.';
    }

    if (!$error) {
        $has_new_photo = isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($has_new_photo) {
            $allowed_photo_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            $allowed_photo_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            $max_photo_size = 2 * 1024 * 1024;
            $original_photo_name = $_FILES['profile_photo']['name'];
            $photo_extension = strtolower(pathinfo($original_photo_name, PATHINFO_EXTENSION));

            if ($_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Profile photo upload failed. Please try again.';
            } elseif (!in_array($photo_extension, $allowed_photo_extensions)) {
                $error = 'Profile photo must be a JPG, PNG, or WEBP image.';
            } elseif ($_FILES['profile_photo']['size'] > $max_photo_size) {
                $error = 'Profile photo must not exceed 2MB.';
            } else {
                $photo_info = @getimagesize($_FILES['profile_photo']['tmp_name']);
                $photo_mime = $photo_info['mime'] ?? '';

                if (!$photo_info || !in_array($photo_mime, $allowed_photo_mimes)) {
                    $error = 'Uploaded profile photo is not a valid image file.';
                } else {
                    $photo_upload_dir = __DIR__ . '/uploads/profile_photos';

                    if (!is_dir($photo_upload_dir)) {
                        mkdir($photo_upload_dir, 0775, true);
                    }

                    $safe_token = bin2hex(random_bytes(8));
                    $photo_file_name = 'profile_' . $user_id . '_' . time() . '_' . $safe_token . '.' . $photo_extension;
                    $photo_target_path = $photo_upload_dir . '/' . $photo_file_name;

                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $photo_target_path)) {
                        $profile_photo = 'uploads/profile_photos/' . $photo_file_name;

                        if ($existing_profile_photo && $existing_profile_photo !== $profile_photo) {
                            $old_photo = __DIR__ . '/' . $existing_profile_photo;

                            if (is_file($old_photo)) {
                                unlink($old_photo);
                            }
                        }
                    } else {
                        $error = 'Unable to save the uploaded profile photo.';
                    }
                }
            }
        }
    }

    if (!$error) {
        $has_new_resume = isset($_FILES['resume_file']) && $_FILES['resume_file']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($has_new_resume) {
            $allowed_extensions = ['pdf', 'doc', 'docx'];
            $max_size = 5 * 1024 * 1024;
            $original_name = $_FILES['resume_file']['name'];
            $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if ($_FILES['resume_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Resume upload failed. Please try again.';
            } elseif (!in_array($extension, $allowed_extensions)) {
                $error = 'Resume must be a PDF, DOC, or DOCX file.';
            } elseif ($_FILES['resume_file']['size'] > $max_size) {
                $error = 'Resume file must not exceed 5MB.';
            } else {
                $upload_dir = __DIR__ . '/uploads/resumes';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0775, true);
                }

                $file_name = 'resume_' . $user_id . '_' . time() . '.' . $extension;
                $target_path = $upload_dir . '/' . $file_name;

                if (move_uploaded_file($_FILES['resume_file']['tmp_name'], $target_path)) {
                    $resume_file = 'uploads/resumes/' . $file_name;

                    if ($existing_resume && $existing_resume !== $resume_file) {
                        $old_file = __DIR__ . '/' . $existing_resume;

                        if (is_file($old_file)) {
                            unlink($old_file);
                        }
                    }
                } else {
                    $error = 'Unable to save the uploaded resume.';
                }
            }
        } elseif (!$existing_resume) {
            $error = 'Resume upload is required for job seeker profiles.';
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("
            UPDATE profiles 
            SET 
                full_name = ?,
                address = ?,
                skills = ?,
                schedule = ?,
                work_status = ?,
                latitude = ?,
                longitude = ?,
                resume_file = ?,
                profile_photo = ?
            WHERE user_id = ?
        ");

        $stmt->execute([
            $full_name,
            $address,
            $skills,
            $schedule,
            $work_status,
            $lat,
            $lng,
            $resume_file,
            $profile_photo,
            $user_id
        ]);

        $message = 'Profile updated successfully!';

        $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
    }
}

$current_schedule = $profile['schedule'] ?? '';
$is_custom_schedule = $current_schedule !== '' && !in_array($current_schedule, $schedule_options);
$current_resume = $profile['resume_file'] ?? '';
$current_resume_name = $current_resume ? basename($current_resume) : '';
$current_profile_photo = $profile['profile_photo'] ?? '';
?>

<?php include 'header.php'; ?>

<div class="profile-page obj-width">

    <div class="profile-card">

        <div class="profile-header">
            <h2>My Profile</h2>
            <p>Manage your personal information, display photo, resume, schedule, and location.</p>
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

        <form method="POST" enctype="multipart/form-data">

            <div class="profile-grid">

                <div>

                    <div class="profile-photo-section">
                        <div class="profile-photo-preview-wrap">
                            <?php if ($current_profile_photo): ?>
                                <img
                                    src="<?php echo htmlspecialchars($current_profile_photo); ?>"
                                    alt="Employee display photo"
                                    class="profile-photo-preview"
                                    id="profilePhotoPreview"
                                >
                            <?php else: ?>
                                <div class="profile-photo-placeholder" id="profilePhotoPlaceholder">
                                    <i class="fa-solid fa-user"></i>
                                </div>

                                <img
                                    src=""
                                    alt="Employee display photo preview"
                                    class="profile-photo-preview hidden"
                                    id="profilePhotoPreview"
                                >
                            <?php endif; ?>
                        </div>

                        <div class="profile-photo-upload">
                            <label for="profilePhoto">
                                <i class="fa-solid fa-camera"></i>
                                Display Photo
                            </label>

                            <input
                                type="file"
                                name="profile_photo"
                                id="profilePhoto"
                                accept="image/jpeg,image/png,image/webp"
                            >

                            <small class="field-hint">
                                Optional. Accepted images: JPG, PNG, or WEBP. Maximum file size: 2MB.
                            </small>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>
                            <i class="fa-solid fa-user"></i>
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="input-group">
                        <label>
                            <i class="fa-solid fa-location-dot"></i>
                            Current Address
                        </label>

                        <div class="location-search-wrapper">
                            <input
                                type="text"
                                name="address"
                                id="address"
                                placeholder="Type barangay, city, or province"
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
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            Skills
                        </label>

                        <textarea
                            name="skills"
                            rows="4"
                            placeholder="e.g. Driving, Cooking, Customer Service"
                        ><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                    </div>

                    <div class="input-group">
                        <label>
                            <i class="fa-solid fa-calendar-days"></i>
                            Availability / Schedule
                        </label>

                        <select name="schedule_select" id="scheduleSelect" required>
                            <option value="">Select your availability</option>

                            <?php foreach ($schedule_options as $option): ?>
                                <option
                                    value="<?php echo htmlspecialchars($option); ?>"
                                    <?php echo $current_schedule === $option ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($option); ?>
                                </option>
                            <?php endforeach; ?>

                            <option value="custom" <?php echo $is_custom_schedule ? 'selected' : ''; ?>>
                                Custom Schedule
                            </option>
                        </select>
                    </div>

                    <div class="input-group custom-schedule-group" id="customScheduleGroup">
                        <label>
                            <i class="fa-solid fa-pen-to-square"></i>
                            Custom Schedule
                        </label>

                        <input
                            type="text"
                            name="custom_schedule"
                            id="customSchedule"
                            placeholder="e.g. Monday, Wednesday, Friday - 7PM to 10PM"
                            value="<?php echo $is_custom_schedule ? htmlspecialchars($current_schedule) : ''; ?>"
                        >
                    </div>

                    <div class="input-group resume-upload-group">
                        <label for="resumeFile">
                            <i class="fa-solid fa-file-arrow-up"></i>
                            Resume / CV
                        </label>

                        <?php if ($current_resume): ?>
                            <div class="uploaded-file-card">
                                <div class="uploaded-file-icon">
                                    <i class="fa-solid fa-file-lines"></i>
                                </div>

                                <div class="uploaded-file-info">
                                    <strong>Resume already uploaded</strong>
                                    <span><?php echo htmlspecialchars($current_resume_name); ?></span>
                                    <a href="<?php echo htmlspecialchars($current_resume); ?>" target="_blank">
                                        View current resume
                                    </a>
                                </div>
                            </div>

                            <small class="field-hint">
                                Your current resume will remain saved. Choose a new file only if you want to replace it.
                            </small>
                        <?php else: ?>
                            <div class="uploaded-file-card missing-file-card">
                                <div class="uploaded-file-icon">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>

                                <div class="uploaded-file-info">
                                    <strong>No resume uploaded yet</strong>
                                    <span>Please upload your resume before saving your profile.</span>
                                </div>
                            </div>

                            <small class="field-hint">
                                Resume upload is required for first-time profile setup.
                            </small>
                        <?php endif; ?>

                        <input
                            type="file"
                            name="resume_file"
                            id="resumeFile"
                            accept=".pdf,.doc,.docx"
                            <?php echo !$current_resume ? 'required' : ''; ?>
                        >

                        <small class="field-hint">
                            Accepted files: PDF, DOC, or DOCX. Maximum file size: 5MB.
                        </small>

                        <p class="selected-file-text" id="selectedResumeText">
                            <?php echo $current_resume ? 'No new file selected. Current resume will be kept.' : 'No file selected yet.'; ?>
                        </p>
                    </div>

                    <div class="input-group">
                        <label>
                            <i class="fa-solid fa-briefcase"></i>
                            Work Status
                        </label>

                        <select name="work_status">
                            <option value="available" <?php echo (($profile['work_status'] ?? '') == 'available') ? 'selected' : ''; ?>>
                                Available for Work
                            </option>

                            <option value="busy" <?php echo (($profile['work_status'] ?? '') == 'busy') ? 'selected' : ''; ?>>
                                Busy / Working
                            </option>
                        </select>
                    </div>

                </div>

                <div>

                    <div class="map-section">
                        <div class="map-text">
                            <h3>Set Your Location</h3>
                            <p>
                                Select a location suggestion, click the map, or drag the marker to set your exact location.
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
                Save Profile Changes
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
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
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
        setLocationHint('Map updated from your selected location. You may now save your profile.', 'success');
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

            setLocationHint('Location updated from the map. You may now save your profile.', 'success');

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

    var profilePhotoInput = document.getElementById('profilePhoto');
    var profilePhotoPreview = document.getElementById('profilePhotoPreview');
    var profilePhotoPlaceholder = document.getElementById('profilePhotoPlaceholder');

    if (profilePhotoInput && profilePhotoPreview) {
        profilePhotoInput.addEventListener('change', function() {
            var file = profilePhotoInput.files && profilePhotoInput.files[0];

            if (!file) {
                return;
            }

            if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
                alert('Please select a JPG, PNG, or WEBP image.');
                profilePhotoInput.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('Profile photo must not exceed 2MB.');
                profilePhotoInput.value = '';
                return;
            }

            var reader = new FileReader();

            reader.onload = function(e) {
                profilePhotoPreview.src = e.target.result;
                profilePhotoPreview.classList.remove('hidden');

                if (profilePhotoPlaceholder) {
                    profilePhotoPlaceholder.style.display = 'none';
                }
            };

            reader.readAsDataURL(file);
        });
    }

    var resumeFileInput = document.getElementById('resumeFile');
    var selectedResumeText = document.getElementById('selectedResumeText');
    var hasCurrentResume = <?php echo json_encode((bool)$current_resume); ?>;

    if (resumeFileInput && selectedResumeText) {
        resumeFileInput.addEventListener('change', function() {
            var file = resumeFileInput.files && resumeFileInput.files[0];

            if (file) {
                selectedResumeText.textContent = 'Selected new resume: ' + file.name;
                selectedResumeText.classList.add('active');
            } else {
                selectedResumeText.textContent = hasCurrentResume
                    ? 'No new file selected. Current resume will be kept.'
                    : 'No file selected yet.';
                selectedResumeText.classList.remove('active');
            }
        });
    }

    var scheduleSelect = document.getElementById('scheduleSelect');
    var customScheduleGroup = document.getElementById('customScheduleGroup');
    var customSchedule = document.getElementById('customSchedule');

    function toggleCustomSchedule() {
        var isCustom = scheduleSelect.value === 'custom';
        customScheduleGroup.style.display = isCustom ? 'block' : 'none';
        customSchedule.required = isCustom;

        if (!isCustom) {
            customSchedule.value = '';
        }
    }

    if (scheduleSelect) {
        scheduleSelect.addEventListener('change', toggleCustomSchedule);
        toggleCustomSchedule();
    }
</script>

<?php include 'footer.php'; ?>
