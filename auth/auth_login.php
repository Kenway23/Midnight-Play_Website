<!DOCTYPE html>
<html>

<head>
    <title>Login | Midnight Play</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Modal Error Styles - Simple and Clean */
        .error-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-box {
            background: #171a21;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            border: 1px solid #2a475e;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .modal-icon {
            font-size: 50px;
            color: #ff4757;
            margin-bottom: 15px;
        }

        .modal-title {
            color: #ff4757;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .modal-message {
            color: #c7d5e0;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .modal-btn {
            background: #ff4757;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }

        .modal-btn:hover {
            background: #ff3742;
        }
    </style>
</head>

<body>

    <!-- Error Modal -->
    <div id="errorModal" class="error-modal">
        <div class="modal-box">
            <div class="modal-icon">⚠️</div>
            <h2 class="modal-title">Login Gagal</h2>
            <p class="modal-message" id="errorMessage">
                Username Atau Password Salah. Silakan coba lagi.
            </p>
            <button class="modal-btn" onclick="closeModal()">OK</button>
        </div>
    </div>

    <div class="auth-wrapper">
        <div class="auth-box">
            <h2>Midnight Play</h2>

            <form id="loginForm" action="auth_process.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button class="btn" type="submit">Login</button>
            </form>

            <p>
                Belum punya akun?
                <a href="auth_register.php">Register</a>
            </p>
        </div>
    </div>

    <script>
        // Function untuk menampilkan modal error
        function showErrorModal(message) {
            const modal = document.getElementById('errorModal');
            const errorMessage = document.getElementById('errorMessage');

            if (message) {
                errorMessage.textContent = message;
            }

            modal.style.display = 'flex';
        }

        // Function untuk menutup modal
        function closeModal() {
            document.getElementById('errorModal').style.display = 'none';
            document.querySelector('input[name="username"]').focus();
        }

        // Close modal ketika klik di luar modal
        document.getElementById('errorModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Close modal dengan ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Check for error flag from localStorage
        document.addEventListener('DOMContentLoaded', function () {
            if (localStorage.getItem('showLoginError') === 'true') {
                setTimeout(function () {
                    showErrorModal("username atau password salah. Silakan coba lagi.");
                    localStorage.removeItem('showLoginError');
                }, 100);
            }
        });

        // Client-side validation
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const username = this.username.value.trim();
            const password = this.password.value.trim();

            if (!username || !password) {
                e.preventDefault();
                showErrorModal("Please fill in both username and password fields.");
                return;
            }
        });
    </script>

</body>

</html>