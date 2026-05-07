<?php
require_once 'auth.php';
requireRole('user');
ensureProfileResumeColumn();

$job_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = 'success';

$stmt = $pdo->prepare("SELECT resume_file FROM profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_profile = $stmt->fetch();
$has_resume = !empty($user_profile['resume_file']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply'])) {
    if (!$has_resume) {
        $message = 'Please upload your resume in your profile before applying for a job.';
        $message_type = 'error';
    } else {
        // Check if already applied
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE job_id = ? AND user_id = ?");
        $stmt->execute([$job_id, $user_id]);

        if ($stmt->fetch()) {
            $message = 'You have already applied for this job.';
            $message_type = 'error';
        } else {
            $stmt = $pdo->prepare("INSERT INTO applications (job_id, user_id) VALUES (?, ?)");
            $stmt->execute([$job_id, $user_id]);
            $message = 'Application submitted successfully!';
            $message_type = 'success';
        }
    }
}

$stmt = $pdo->prepare("
    SELECT j.*, ep.company_name, ep.description as company_desc, ep.address, ep.latitude, ep.longitude 
    FROM jobs j 
    JOIN employer_profiles ep ON j.employer_id = ep.user_id 
    WHERE j.id = ?
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    header("Location: jobs.php");
    exit();
}
?>
<?php include 'header.php'; ?>

<div style="margin-bottom: 2rem;">
    <a href="jobs.php" style="color: #fff; text-decoration: none; font-weight: 700;">
        <i class="fa-solid fa-arrow-left"></i> Back to Job Listings
    </a>
</div>

<div class="grid job-details-grid">
    <div>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap;">
                <h2 style="margin: 0;"><?php echo htmlspecialchars($job['title']); ?></h2>
                <span class="badge badge-open" style="font-size: 1.2rem; padding: 0.6rem 1.5rem;">PHP <?php echo number_format($job['salary'], 2); ?></span>
            </div>
            
            <div style="display: flex; gap: 2rem; margin-bottom: 2rem; color: #666; flex-wrap: wrap;">
                <span><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($job['company_name']); ?></span>
                <span><i class="fa-solid fa-calendar-days"></i> <?php echo htmlspecialchars($job['schedule']); ?></span>
            </div>
            
            <h3 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 0.5rem;">Job Description</h3>
            <p style="line-height: 1.6; margin-bottom: 2rem;"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
            
            <h3 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 0.5rem;">Requirements</h3>
            <p style="line-height: 1.6; margin-bottom: 2rem;"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
            
            <?php if ($message): ?>
                <div class="<?php echo $message_type === 'error' ? 'error-box' : 'success-alert'; ?>" style="text-align: center; justify-content: center;">
                    <i class="fa-solid <?php echo $message_type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!$message || $message_type === 'error'): ?>
                <?php if (!$has_resume): ?>
                    <div class="resume-warning">
                        <strong>Resume required:</strong>
                        Upload your resume in your profile before submitting applications.
                        <a href="profile.php">Go to Profile</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="apply" value="1">
                        <button type="submit" style="font-size: 1.2rem; padding: 1.2rem;">
                            Apply for this Position
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div>
        <div class="card">
            <h3>About Employer</h3>
            <p style="font-weight: 700; color: #1f4a40; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($job['company_name']); ?></p>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 1.5rem;"><?php echo nl2br(htmlspecialchars($job['company_desc'])); ?></p>
            
            <h3 style="font-size: 1.1rem;">Location</h3>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">
                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($job['address']); ?>
            </p>
            <div id="map" style="height: 250px;"></div>
        </div>
    </div>
</div>

<script>
    var jobLat = <?php echo json_encode((float)($job['latitude'] ?? 14.5995)); ?>;
    var jobLng = <?php echo json_encode((float)($job['longitude'] ?? 120.9842)); ?>;
    
    var map = L.map('map').setView([jobLat, jobLng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    
    L.marker([jobLat, jobLng], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map).bindPopup("<b><?php echo addslashes($job['company_name']); ?></b>");
</script>
<?php include 'footer.php'; ?>
