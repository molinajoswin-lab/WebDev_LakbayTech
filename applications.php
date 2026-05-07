<?php
require_once 'auth.php';
requireRole('user');

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT a.*, j.title, ep.company_name 
                     FROM applications a 
                     JOIN jobs j ON a.job_id = j.id 
                     JOIN employer_profiles ep ON j.employer_id = ep.user_id 
                     WHERE a.user_id = ? 
                     ORDER BY a.applied_at DESC");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();
?>
<?php include 'header.php'; ?>

<div class="card">
    <h2>My Job Applications</h2>
    <p style="color: #666; margin-bottom: 2rem;">Track the status of your submitted applications.</p>
    
    <?php if (empty($applications)): ?>
        <div style="text-align: center; padding: 3rem;">
            <i class="fa-solid fa-paper-plane" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
            <p>You haven't applied for any jobs yet. <a href="jobs.php" style="color: #1f4a40; font-weight: 700;">Browse jobs here</a>.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td style="font-weight: 700; color: #1f4a40;"><?php echo htmlspecialchars($app['title']); ?></td>
                            <td><?php echo htmlspecialchars($app['company_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $app['status']; ?>">
                                    <?php echo $app['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
