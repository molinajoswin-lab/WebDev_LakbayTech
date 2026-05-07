</main> <!-- end main-content -->

<footer style="background: rgba(0,0,0,0.2); padding: 4rem 0; margin-top: 4rem;">
    <div class="obj-width" style="text-align: center;">
        <h2 style="margin-bottom: 1rem;">LakBay Tech</h2>
        <p style="color: #e0e0e0; margin-bottom: 2rem;">Connecting unemployed individuals with part-time opportunities that fit their skills and schedule.</p>
        <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem; font-size: 0.9rem; color: #ccc;">
            <p>&copy; 2026 LakBay Tech. All rights reserved.</p>
            <p style="margin-top: 0.5rem;">Group Members: Galzote, Kiel Zedrick A. | Molina, Joswin A. | Yadao, John Paul T.</p>
        </div>
    </div>
</footer>



<div class="logout-modal-overlay" id="logoutModal" aria-hidden="true">
    <div class="logout-modal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle">
        <div class="logout-modal-icon">
            <i class="fa-solid fa-right-from-bracket"></i>
        </div>
        <h3 id="logoutModalTitle">Are you sure you want to log out?</h3>
        <p>You will need to log in again to access your account.</p>
        <div class="logout-modal-actions">
            <button type="button" class="logout-confirm-btn" id="confirmLogoutBtn">Yes</button>
            <button type="button" class="logout-cancel-btn" id="cancelLogoutBtn">No</button>
        </div>
    </div>
</div>

<script>
    const bar = document.getElementById('bar');
    const menu = document.getElementById('menu');
    const logoutModal = document.getElementById('logoutModal');
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    const cancelLogoutBtn = document.getElementById('cancelLogoutBtn');
    const logoutLinks = document.querySelectorAll('.js-logout-link');

    if (bar) {
        bar.addEventListener('click', () => {
            menu.classList.toggle('active');
        });
    }

    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (menu && bar && !menu.contains(e.target) && !bar.contains(e.target)) {
            menu.classList.remove('active');
        }
    });

    function openLogoutModal(event) {
        event.preventDefault();
        if (menu) {
            menu.classList.remove('active');
        }
        if (logoutModal) {
            logoutModal.classList.add('active');
            logoutModal.setAttribute('aria-hidden', 'false');
            if (cancelLogoutBtn) {
                cancelLogoutBtn.focus();
            }
        }
    }

    function closeLogoutModal() {
        if (logoutModal) {
            logoutModal.classList.remove('active');
            logoutModal.setAttribute('aria-hidden', 'true');
        }
    }

    logoutLinks.forEach((link) => {
        link.addEventListener('click', openLogoutModal);
    });

    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', () => {
            window.location.href = 'logout.php';
        });
    }

    if (cancelLogoutBtn) {
        cancelLogoutBtn.addEventListener('click', closeLogoutModal);
    }

    if (logoutModal) {
        logoutModal.addEventListener('click', (e) => {
            if (e.target === logoutModal) {
                closeLogoutModal();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeLogoutModal();
        }
    });
</script>
</body>
</html>
