<?php
require_once 'auth.php';

/* ================= HOME JOB MAP DATA ================= */

$stmt = $pdo->query("
    SELECT
        j.id,
        j.title,
        j.salary,
        j.schedule,
        j.description,
        ep.company_name,
        ep.address,
        ep.latitude,
        ep.longitude
    FROM jobs j
    LEFT JOIN employer_profiles ep
    ON j.employer_id = ep.user_id
    WHERE j.status = 'open'
    AND ep.latitude IS NOT NULL
    AND ep.longitude IS NOT NULL
    AND ep.latitude != ''
    AND ep.longitude != ''
    ORDER BY j.id DESC
");

$home_jobs = $stmt->fetchAll();

include 'header.php';
?>

<div class="hero home-hero">
    <div class="hero-text">
        <h1>Find Your Next Part-Time Job</h1>
        <p>LakBay Tech helps unemployed individuals find local jobs that match their skills and availability. Reduce stress and find balance today.</p>
        
        <?php if (!isLoggedIn()): ?>
            <div class="search-container">
                <input type="text" placeholder="Search for jobs (e.g. Delivery, Clerk)...">
                <button class="search-btn" onclick="location.href='register.php'">Join Now</button>
            </div>
        <?php else: ?>
            <div class="hero-actions">
                <?php if ($_SESSION['role'] == 'user'): ?>
                    <a href="jobs.php" id="w-btn" class="hero-btn">Browse Jobs Near Me</a>
                <?php elseif ($_SESSION['role'] == 'employer'): ?>
                    <a href="post_job.php" id="w-btn" class="hero-btn">Post a New Job</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="home-map-panel">
        <div class="home-map-card">
            <div class="home-map-header">
                <div>
                    <h2>Interactive Job Map</h2>
                    <p>Available job locations are shown as markers.</p>
                </div>

                <span class="home-map-count">
                    <?php echo count($home_jobs); ?> Location<?php echo count($home_jobs) === 1 ? '' : 's'; ?>
                </span>
            </div>

            <div id="homeJobMap"></div>

            <?php if (empty($home_jobs)): ?>
                <div class="home-map-empty">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>No job locations available yet.</span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="home-map-badge home-map-badge-top">
            <i class="fa-solid fa-circle-check"></i>
            Verified Employers
        </div>
        
        <div class="home-map-badge home-map-badge-bottom">
            <i class="fa-solid fa-clock"></i>
            Flexible Schedules
        </div>
    </div>
</div>

<div class="grid">
    <div class="card">
        <i class="fa-solid fa-user-graduate" style="font-size: 2.5rem; color: #5cb299; margin-bottom: 1.5rem;"></i>
        <h3>For Job Seekers</h3>
        <p>Find jobs based on your availability, reduce financial stress, and prevent schedule burnout with our smart matching system.</p>
    </div>
    <div class="card">
        <i class="fa-solid fa-building" style="font-size: 2.5rem; color: #5cb299; margin-bottom: 1.5rem;"></i>
        <h3>For Employers</h3>
        <p>Post part-time job listings, view and manage applicants, and find local talent easily with our location-based platform.</p>
    </div>
    <div class="card">
        <i class="fa-solid fa-shield-halved" style="font-size: 2.5rem; color: #5cb299; margin-bottom: 1.5rem;"></i>
        <h3>Secure & Reliable</h3>
        <p>Our admin team monitors all postings and accounts to ensure a safe and productive environment for everyone.</p>
    </div>
</div>

<script>
    const homeJobs = <?php echo json_encode($home_jobs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    function escapeHTML(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatSalary(value) {
        const amount = Number(value || 0);
        return amount.toLocaleString('en-PH', {
            maximumFractionDigits: 0
        });
    }

    if (document.getElementById('homeJobMap')) {
        const homeMap = L.map('homeJobMap').setView([12.8797, 121.7740], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(homeMap);

        const jobIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        const markers = [];

        homeJobs.forEach(function(job) {
            const lat = Number(job.latitude);
            const lng = Number(job.longitude);

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const title = escapeHTML(job.title);
            const company = escapeHTML(job.company_name || 'Company');
            const address = escapeHTML(job.address || 'Location not specified');
            const schedule = escapeHTML(job.schedule || 'Schedule not specified');
            const salary = formatSalary(job.salary);
            const jobId = Number(job.id);

            const marker = L.marker([lat, lng], { icon: jobIcon }).addTo(homeMap);

            marker.bindPopup(`
                <div class="map-popup">
                    <h4>${title}</h4>
                    <p><b>${company}</b></p>
                    <p><i class="fa-solid fa-location-dot"></i> ${address}</p>
                    <p><i class="fa-solid fa-calendar-days"></i> ${schedule}</p>
                    <p><i class="fa-solid fa-money-bill-wave"></i> ₱${salary}</p>
                    <a href="job_details.php?id=${jobId}">View Job</a>
                </div>
            `);

            markers.push(marker);
        });

        if (markers.length > 0) {
            const group = L.featureGroup(markers);
            homeMap.fitBounds(group.getBounds().pad(0.18));
        }

        setTimeout(function() {
            homeMap.invalidateSize();
        }, 250);
    }
</script>

<?php include 'footer.php'; ?>
