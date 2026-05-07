<?php
require_once 'auth.php';
requireRole('admin');

// Stats
$user_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$employer_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employer'")->fetchColumn();
$job_count = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$app_count = $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();

// Recent Jobs
$recent_jobs = $pdo->query("SELECT j.*, ep.company_name FROM jobs j JOIN employer_profiles ep ON j.employer_id = ep.user_id ORDER BY j.created_at DESC LIMIT 5")->fetchAll();

// Recent Users
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<?php include 'header.php'; ?>

<h2 style="margin-bottom: 2rem;">Admin Dashboard</h2>

<div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
    <div class="card" style="text-align: center; padding: 1.5rem;">
        <i class="fa-solid fa-users" style="font-size: 2rem; color: #5cb299; margin-bottom: 1rem;"></i>
        <h3 style="margin: 0; font-size: 2rem;"><?php echo $user_count; ?></h3>
        <p style="color: #666; font-weight: 600;">Job Seekers</p>
    </div>
    <div class="card" style="text-align: center; padding: 1.5rem;">
        <i class="fa-solid fa-building" style="font-size: 2rem; color: #5cb299; margin-bottom: 1rem;"></i>
        <h3 style="margin: 0; font-size: 2rem;"><?php echo $employer_count; ?></h3>
        <p style="color: #666; font-weight: 600;">Employers</p>
    </div>
    <div class="card" style="text-align: center; padding: 1.5rem;">
        <i class="fa-solid fa-briefcase" style="font-size: 2rem; color: #5cb299; margin-bottom: 1rem;"></i>
        <h3 style="margin: 0; font-size: 2rem;"><?php echo $job_count; ?></h3>
        <p style="color: #666; font-weight: 600;">Total Jobs</p>
    </div>
    <div class="card" style="text-align: center; padding: 1.5rem;">
        <i class="fa-solid fa-file-invoice" style="font-size: 2rem; color: #5cb299; margin-bottom: 1rem;"></i>
        <h3 style="margin: 0; font-size: 2rem;"><?php echo $app_count; ?></h3>
        <p style="color: #666; font-weight: 600;">Applications</p>
    </div>
</div>

<div class="grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <div class="card">
        <h3 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 0.5rem; margin-bottom: 1rem;">Recent Job Postings</h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Company</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_jobs as $job): ?>
                        <tr>
                            <td style="font-weight: 700;"><?php echo htmlspecialchars($job['title']); ?></td>
                            <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                            <td><span class="badge badge-<?php echo $job['status']; ?>"><?php echo $job['status']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <h3 style="border-bottom: 2px solid #f0f0f0; padding-bottom: 0.5rem; margin-bottom: 1rem;">Recent Registrations</h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td style="font-weight: 700;"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td style="text-transform: capitalize;"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo date('M d', strtotime($user['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
