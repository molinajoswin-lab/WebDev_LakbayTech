<?php
require_once 'auth.php';
requireRole('employer');

$user_id = $_SESSION['user_id'];

/* ================= FETCH JOBS ================= */

$stmt = $pdo->prepare("
    SELECT * 
    FROM jobs 
    WHERE employer_id = ?
    ORDER BY created_at DESC
");

$stmt->execute([$user_id]);

$jobs = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>

<div class="manage-page obj-width">

    <div class="manage-card">

        <!-- HEADER -->

        <div class="manage-header">

            <div>

                <h2>Manage My Job Postings</h2>

                <p>
                    Track and manage your active job listings.
                </p>

            </div>

            <a
                href="post_job.php"
                class="post-job-btn"
            >

                <i class="fa-solid fa-plus"></i>

                Post New Job

            </a>

        </div>

        <!-- EMPTY -->

        <?php if (empty($jobs)): ?>

            <div class="empty-state">

                <i class="fa-solid fa-folder-open"></i>

                <h3>No jobs posted yet</h3>

                <p>
                    Start posting jobs to find workers.
                </p>

            </div>

        <?php else: ?>

            <!-- TABLE -->

            <div class="table-wrapper">

                <table class="jobs-table">

                    <thead>

                        <tr>

                            <th>Job Title</th>

                            <th>Posted Date</th>

                            <th>Status</th>

                            <th>Applicants</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($jobs as $job): ?>

                            <?php

                            $stmt = $pdo->prepare("
                                SELECT COUNT(*)
                                FROM applications
                                WHERE job_id = ?
                            ");

                            $stmt->execute([
                                $job['id']
                            ]);

                            $applicant_count =
                                $stmt->fetchColumn();

                            ?>

                            <tr>

                                <!-- TITLE -->

                                <td>

                                    <div class="job-title">

                                        <?php
                                        echo htmlspecialchars(
                                            $job['title']
                                        );
                                        ?>

                                    </div>

                                </td>

                                <!-- DATE -->

                                <td>

                                    <?php
                                    echo date(
                                        'M d, Y',
                                        strtotime(
                                            $job['created_at']
                                        )
                                    );
                                    ?>

                                </td>

                                <!-- STATUS -->

                                <td>

                                    <span class="
                                        status-badge
                                        status-<?php
                                        echo $job['status'];
                                        ?>
                                    ">

                                        <?php
                                        echo ucfirst(
                                            $job['status']
                                        );
                                        ?>

                                    </span>

                                </td>

                                <!-- APPLICANTS -->

                                <td>

                                    <div class="applicant-count">

                                        <?php
                                        echo $applicant_count;
                                        ?>

                                    </div>

                                </td>

                                <!-- ACTION -->

                                <td>

                                    <a
                                        href="
                                        view_applicants.php?job_id=<?php
                                        echo $job['id'];
                                        ?>
                                        "
                                        class="view-link"
                                    >

                                        <button
                                            class="view-btn"
                                        >

                                            View Applicants

                                        </button>

                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php include 'footer.php'; ?>