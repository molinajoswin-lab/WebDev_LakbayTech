<?php
require_once 'auth.php';
requireRole('employer');
ensureProfileResumeColumn();

$job_id = $_GET['job_id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Verify job belongs to employer
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->execute([$job_id, $user_id]);
$job = $stmt->fetch();

if (!$job) {
    header("Location: manage_jobs.php");
    exit();
}

if (isset($_POST['action']) && isset($_POST['app_id'])) {
    $status = $_POST['action'] == 'accept' ? 'accepted' : 'rejected';

    $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ? AND job_id = ?");
    $stmt->execute([$status, $_POST['app_id'], $job_id]);
    
    // Notify user only if the application belongs to this employer's job
    $stmt = $pdo->prepare("SELECT user_id FROM applications WHERE id = ? AND job_id = ?");
    $stmt->execute([$_POST['app_id'], $job_id]);
    $applicant_id = $stmt->fetchColumn();

    if ($applicant_id) {
        $msg = "Your application for '" . $job['title'] . "' has been " . $status . ".";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$applicant_id, $msg]);
    }
}

$stmt = $pdo->prepare("
    SELECT a.*, p.full_name, p.skills, p.schedule, p.work_status, p.resume_file, p.profile_photo
    FROM applications a 
    JOIN profiles p ON a.user_id = p.user_id 
    WHERE a.job_id = ? 
    ORDER BY a.applied_at DESC
");
$stmt->execute([$job_id]);
$applicants = $stmt->fetchAll();
?>
<?php include 'header.php'; ?>

<div class="card applicants-page-card">
    <a href="manage_jobs.php" class="back-link">&larr; Back to Jobs</a>
    <h2>Applicants for: <?php echo htmlspecialchars($job['title']); ?></h2>
    
    <?php if (empty($applicants)): ?>
        <p>No applications yet.</p>
    <?php else: ?>
        <?php foreach ($applicants as $app): ?>
            <div class="card applicant-card">
                <div class="applicant-layout">
                    <div class="applicant-main">
                        <div class="applicant-profile-heading">
                            <?php if (!empty($app['profile_photo'])): ?>
                                <img
                                    src="<?php echo htmlspecialchars($app['profile_photo']); ?>"
                                    alt="Applicant display photo"
                                    class="applicant-photo"
                                >
                            <?php else: ?>
                                <div class="applicant-photo applicant-photo-placeholder">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            <?php endif; ?>

                            <div>
                                <h3><?php echo htmlspecialchars($app['full_name']); ?></h3>
                                <p class="applicant-subtext">Employee Profile</p>
                            </div>
                        </div>

                        <p><strong>Skills:</strong> <?php echo htmlspecialchars($app['skills']); ?></p>
                        <p><strong>Availability:</strong> <?php echo htmlspecialchars($app['schedule']); ?></p>
                        <p><strong>Work Status:</strong> <?php echo htmlspecialchars($app['work_status']); ?></p>
                        <p><strong>Applied on:</strong> <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></p>

                        <?php if (!empty($app['resume_file'])): ?>
                            <p>
                                <strong>Resume:</strong>
                                <a href="<?php echo htmlspecialchars($app['resume_file']); ?>" target="_blank" class="resume-link">
                                    View Resume
                                </a>
                            </p>
                        <?php else: ?>
                            <p><strong>Resume:</strong> No resume uploaded</p>
                        <?php endif; ?>
                    </div>

                    <div class="applicant-actions">
                        <p>
                            Current Status:
                            <span class="badge badge-<?php echo htmlspecialchars($app['status']); ?>">
                                <?php echo strtoupper(htmlspecialchars($app['status'])); ?>
                            </span>
                        </p>

                        <?php if ($app['status'] == 'pending'): ?>
                            <form method="POST" class="applicant-action-form">
                                <input type="hidden" name="app_id" value="<?php echo htmlspecialchars($app['id']); ?>">
                                <button type="submit" name="action" value="accept" class="accept-btn">Accept</button>
                                <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
