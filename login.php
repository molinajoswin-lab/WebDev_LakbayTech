<?php
require_once 'auth.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (login($_POST['username'], $_POST['password'])) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<?php include 'header.php'; ?>

<div class="login-container">
    
    <div class="login-card">

        <div class="login-header">
            <h2>Welcome Back</h2>
            <p>Login to your LakBay Tech account</p>
        </div>

        <?php if ($error): ?>
            <div class="error-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-user"></i> Username
                </label>

                <input 
                    type="text" 
                    name="username" 
                    placeholder="Enter your username"
                    required
                >
            </div>

            <div class="input-group">
                <label>
                    <i class="fa-solid fa-lock"></i> Password
                </label>

                <input 
                    type="password" 
                    name="password" 
                    placeholder="Enter your password"
                    required
                >
            </div>

            <button type="submit" class="login-btn">
                Login
            </button>

        </form>

        <div class="register-link">
            Don't have an account?
            <a href="register.php">Register here</a>
        </div>

    </div>

</div>

<?php include 'footer.php'; ?>