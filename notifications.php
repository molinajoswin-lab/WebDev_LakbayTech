<?php
require_once 'auth.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Mark all as read
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>
<?php include 'header.php'; ?>

<div style="display: flex; justify-content: center; padding: 40px 0;">
    <div class="card" style="width: 100%; max-width: 800px; margin: 0;">
        <h2 style="margin-bottom: 10px;">Notifications</h2>
        <p style="color: #666; margin-bottom: 30px;">Stay updated on your application status and account activity.</p>
        
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 60px 0;">
                <i class="fa-solid fa-bell-slash" style="font-size: 3.5rem; color: #ddd; margin-bottom: 20px; display: block;"></i>
                <p style="color: #888;">No notifications yet.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($notifications as $notif): ?>
                    <div style="background: #f9f9f9; padding: 20px; border-radius: 15px; border-left: 5px solid #5cb299; display: flex; gap: 20px; align-items: center; transition: 0.3s;">
                        <div style="background: #fff; width: 50px; height: 50px; border-radius: 50%; display: flex; justify-content: center; align-items: center; box-shadow: 0 3px 10px rgba(0,0,0,0.05); flex-shrink: 0;">
                            <i class="fa-solid fa-bell" style="color: #5cb299; font-size: 1.2rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <p style="color: #1f4a40; font-weight: 600; margin-bottom: 5px; line-height: 1.4;"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <small style="color: #888; display: flex; align-items: center; gap: 5px;">
                                <i class="fa-solid fa-clock" style="font-size: 0.8rem;"></i> 
                                <?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
