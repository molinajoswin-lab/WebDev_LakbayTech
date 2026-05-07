<?php
require_once 'auth.php';
requireRole('user');

$user_id = $_SESSION['user_id'];

/* ================= USER PROFILE ================= */

$stmt = $pdo->prepare("
    SELECT * 
    FROM profiles 
    WHERE user_id = ?
");

$stmt->execute([$user_id]);

$user_profile = $stmt->fetch();

/* ================= FETCH OPEN JOBS ================= */

$stmt = $pdo->query("
    SELECT 
        j.*,
        ep.company_name,
        ep.latitude,
        ep.longitude,
        ep.address
    FROM jobs j
    LEFT JOIN employer_profiles ep
    ON j.employer_id = ep.user_id
    WHERE j.status = 'open'
    ORDER BY j.id DESC
");

$jobs = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>

<div class="jobs-page obj-width">

    <!-- ================= MAP ================= -->

    <div class="map-card">

        <div class="map-header">

            <div>
                <h2>Interactive Job Map</h2>
                <p>
                    <i class="fa-solid fa-location-dot"></i>
                    Showing jobs near your location
                </p>
            </div>

        </div>

        <div id="map"></div>

    </div>

    <!-- ================= JOBS ================= -->

    <div class="jobs-header">
        <h2>Available Opportunities</h2>
    </div>

    <div class="jobs-grid">

        <?php if (empty($jobs)): ?>

            <div class="empty-card">

                <i class="fa-solid fa-briefcase"></i>

                <h3>No jobs available yet</h3>

                <p>
                    Check back later for new opportunities.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($jobs as $job): ?>

                <div class="job-card">

                    <div>

                        <div class="job-top">

                            <h3>
                                <?php
                                echo htmlspecialchars($job['title']);
                                ?>
                            </h3>

                            <span class="salary-badge">
                                ₱<?php
                                echo number_format(
                                    $job['salary'],
                                    0
                                );
                                ?>
                            </span>

                        </div>

                        <p class="company-name">

                            <i class="fa-solid fa-building"></i>

                            <?php
                            echo htmlspecialchars(
                                $job['company_name']
                                ?? 'Unknown Company'
                            );
                            ?>

                        </p>

                        <p class="job-schedule">

                            <i class="fa-solid fa-calendar-days"></i>

                            <?php
                            echo htmlspecialchars(
                                $job['schedule']
                            );
                            ?>

                        </p>

                        <p class="job-description">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    substr(
                                        $job['description'],
                                        0,
                                        120
                                    )
                                )
                            );
                            ?>...

                        </p>

                    </div>

                    <a
                        href="job_details.php?id=<?php echo $job['id']; ?>"
                        class="view-btn-link"
                    >

                        <button class="view-btn">
                            View Details
                        </button>

                    </a>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

<!-- ================= MAP SCRIPT ================= -->

<script>

    // Default location (Manila)
    var userLat = <?php
        echo $user_profile['latitude']
        ?? '14.5995';
    ?>;

    var userLng = <?php
        echo $user_profile['longitude']
        ?? '120.9842';
    ?>;

    // Initialize map
    var map = L.map('map').setView(
        [userLat, userLng],
        13
    );

    // OpenStreetMap tiles
    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution:
            '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    // ================= USER ICON =================

    var userIcon = L.icon({

        iconUrl:
        'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',

        shadowUrl:
        'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',

        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]

    });

    // ================= JOB ICON =================

    var jobIcon = L.icon({

        iconUrl:
        'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',

        shadowUrl:
        'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',

        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]

    });

    // ================= USER MARKER =================

    L.marker(
        [userLat, userLng],
        {icon: userIcon}
    )
    .addTo(map)
    .bindPopup(
        "<b>Your Location</b><br><?php
        echo addslashes(
            $user_profile['address']
            ?? 'Set your address in profile'
        );
        ?>"
    )
    .openPopup();

    // ================= JOB MARKERS =================

    var markers = [];

    <?php foreach ($jobs as $job): ?>

        <?php if (
            !empty($job['latitude']) &&
            !empty($job['longitude'])
        ): ?>

            var marker = L.marker(
                [
                    <?php echo $job['latitude']; ?>,
                    <?php echo $job['longitude']; ?>
                ],
                {icon: jobIcon}
            )

            .addTo(map)

            .bindPopup(`

                <div style="padding:5px;">

                    <h4 style="
                        margin:0 0 6px;
                        color:#1f4a40;
                    ">
                        <?php
                        echo addslashes(
                            $job['title']
                        );
                        ?>
                    </h4>

                    <p style="
                        margin:0 0 10px;
                        font-size:0.85rem;
                    ">
                        <b>
                        <?php
                        echo addslashes(
                            $job['company_name']
                            ?? 'Company'
                        );
                        ?>
                        </b>
                    </p>

                    <a
                        href="job_details.php?id=<?php
                        echo $job['id'];
                        ?>"
                        style="
                            display:inline-block;
                            background:#1f4a40;
                            color:#fff;
                            padding:6px 12px;
                            border-radius:8px;
                            text-decoration:none;
                            font-size:0.8rem;
                            font-weight:700;
                        "
                    >
                        View Job
                    </a>

                </div>

            `);

            markers.push(marker);

        <?php endif; ?>

    <?php endforeach; ?>

    // ================= FIT MAP =================

    if (markers.length > 0) {

        var group = new L.featureGroup([
            L.marker([userLat, userLng]),
            ...markers
        ]);

        map.fitBounds(
            group.getBounds().pad(0.1)
        );
    }

</script>

<?php include 'footer.php'; ?>