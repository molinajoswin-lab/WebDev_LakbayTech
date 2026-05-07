<?php
require_once 'auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user_id = register(
        $_POST['username'],
        $_POST['password'],
        $_POST['email'],
        $_POST['role']
    );

    if ($user_id) {

        // Initialize profile
        if ($_POST['role'] == 'user') {

            $stmt = $pdo->prepare("
                INSERT INTO profiles (user_id, full_name)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $user_id,
                $_POST['username']
            ]);

        } else if ($_POST['role'] == 'employer') {

            $stmt = $pdo->prepare("
                INSERT INTO employer_profiles (user_id, company_name)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $user_id,
                $_POST['username']
            ]);
        }

        $success = "Registration successful!";

    } else {

        $error = "Username or email already exists.";
    }
}
?>

<?php include 'header.php'; ?>

<div class="register-container">

    <div class="register-card">

        <div class="register-header">
            <h2>Create Account</h2>
            <p>Join LakBay Tech and start your journey</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-box">
                <i class="fa-solid fa-circle-check"></i>
                <?php echo $success; ?>

                <a href="login.php">
                    Login here
                </a>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-user"></i>
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    placeholder="Choose a username"
                    required
                >
            </div>

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-envelope"></i>
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >
            </div>

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-lock"></i>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Create a password"
                    required
                >
            </div>

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-users"></i>
                    Register As
                </label>

                <select name="role" required>
                    <option value="user">
                        Job Seeker (Unemployed)
                    </option>

                    <option value="employer">
                        Employer (Company)
                    </option>
                </select>
            </div>

            <button type="submit" class="register-btn">
                Register Now
            </button>

        </form>

        <div class="login-link">
            Already have an account?
            <a href="login.php">Login here</a>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>