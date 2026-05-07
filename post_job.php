<?php
require_once 'auth.php';
requireRole('employer');

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

/* ================= POST JOB ================= */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $schedule_choice = trim($_POST['schedule_select'] ?? '');
    $custom_schedule = trim($_POST['custom_schedule'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $schedule = $schedule_choice === 'custom' ? $custom_schedule : $schedule_choice;

    if ($title === '') {
        $error = 'Job title is required.';
    } elseif ($description === '') {
        $error = 'Job description is required.';
    } elseif ($schedule === '') {
        $error = 'Please select a schedule or enter a custom schedule.';
    } elseif ($salary === '' || !is_numeric($salary) || $salary < 0) {
        $error = 'Please enter a valid salary amount.';
    }

    if (!$error) {
        $stmt = $pdo->prepare("
            INSERT INTO jobs
            (
                employer_id,
                title,
                description,
                requirements,
                schedule,
                salary,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id,
            $title,
            $description,
            $requirements,
            $schedule,
            $salary,
            'open'
        ]);

        $message = 'Job posted successfully!';
    }
}
?>

<?php include 'header.php'; ?>

<div class="post-job-page obj-width">

    <div class="post-job-card">

        <div class="post-job-header">
            <h2>Post a New Part-Time Job</h2>
            <p>Fill in the details below to find the right candidate.</p>
        </div>

        <?php if ($message): ?>
            <div class="success-alert">
                <div>
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>

                <a href="manage_jobs.php">Manage Jobs</a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box profile-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-briefcase"></i>
                    Job Title
                </label>

                <input
                    type="text"
                    name="title"
                    placeholder="e.g. Delivery Rider, Shop Assistant"
                    value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-file-lines"></i>
                    Job Description
                </label>

                <textarea
                    name="description"
                    rows="6"
                    placeholder="Describe the responsibilities and duties..."
                    required
                ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-list-check"></i>
                    Requirements
                </label>

                <textarea
                    name="requirements"
                    rows="4"
                    placeholder="Skills, experience, or qualifications..."
                ><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
            </div>

            <div class="post-grid">

                <div>
                    <div class="input-group">
                        <label>
                            <i class="fa-solid fa-calendar-days"></i>
                            Schedule
                        </label>

                        <?php
                        $posted_schedule_choice = $_POST['schedule_select'] ?? '';
                        $posted_custom_schedule = $_POST['custom_schedule'] ?? '';
                        ?>

                        <select name="schedule_select" id="jobScheduleSelect" required>
                            <option value="">Select job schedule</option>

                            <?php foreach ($schedule_options as $option): ?>
                                <option
                                    value="<?php echo htmlspecialchars($option); ?>"
                                    <?php echo $posted_schedule_choice === $option ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($option); ?>
                                </option>
                            <?php endforeach; ?>

                            <option value="custom" <?php echo $posted_schedule_choice === 'custom' ? 'selected' : ''; ?>>
                                Custom Schedule
                            </option>
                        </select>
                    </div>

                    <div class="input-group custom-schedule-group" id="jobCustomScheduleGroup">
                        <label>
                            <i class="fa-solid fa-pen-to-square"></i>
                            Custom Schedule
                        </label>

                        <input
                            type="text"
                            name="custom_schedule"
                            id="jobCustomSchedule"
                            placeholder="e.g. Monday, Wednesday, Friday - 7PM to 10PM"
                            value="<?php echo htmlspecialchars($posted_custom_schedule); ?>"
                        >
                    </div>
                </div>

                <div class="input-group">
                    <label>
                        <i class="fa-solid fa-peso-sign"></i>
                        Salary (PHP)
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="salary"
                        placeholder="e.g. 500.00"
                        value="<?php echo htmlspecialchars($_POST['salary'] ?? ''); ?>"
                        required
                    >
                </div>

            </div>

            <button type="submit" class="post-btn">
                Post Job Listing
            </button>

        </form>

    </div>

</div>

<script>
    var jobScheduleSelect = document.getElementById('jobScheduleSelect');
    var jobCustomScheduleGroup = document.getElementById('jobCustomScheduleGroup');
    var jobCustomSchedule = document.getElementById('jobCustomSchedule');

    function toggleJobCustomSchedule() {
        var isCustom = jobScheduleSelect.value === 'custom';
        jobCustomScheduleGroup.style.display = isCustom ? 'block' : 'none';
        jobCustomSchedule.required = isCustom;

        if (!isCustom) {
            jobCustomSchedule.value = '';
        }
    }

    if (jobScheduleSelect) {
        jobScheduleSelect.addEventListener('change', toggleJobCustomSchedule);
        toggleJobCustomSchedule();
    }
</script>

<?php include 'footer.php'; ?>
