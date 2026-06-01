<?php
require_once 'db.php';
$department_list = [];
$res = $conn->query("SELECT dept_name FROM departments ORDER BY dept_name ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $department_list[] = $row['dept_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Room & Hall Booking Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <header>
        <div class="logo">CRHB System</div>
        <div class="auth-nav">
            <button onclick="openModal('staffModal')">Staff Login</button>
            <button onclick="openModal('hodModal')">HOD Login</button>
            <button onclick="openModal('vpModal')">VP Login</button>
            <button class="admin-btn" onclick="openModal('adminModal')">Admin Login</button>
        </div>
    </header>

    <main class="hero">
        <h1>Simplify Your Campus Bookings</h1>
        <p>"Streamline the reservation of event halls, seminar rooms, and classrooms with our premium and seamless booking management platform."</p>
    </main>

    <!-- Staff Modal -->
    <div id="staffModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('staffModal')">&times;</span>
            
            <div id="staffLoginBox">
                <h2>Staff Login</h2>
                <div class="alert" id="staffLoginAlert"></div>
                <form id="staffLoginForm">
                    <input type="hidden" name="action" value="staff_login">
                    <div class="input-group">
                        <label>Staff ID</label>
                        <input type="text" name="staff_id" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn" id="staffLoginBtn">Login</button>
                </form>
                <div class="toggle-form">Don't have an account? <span onclick="toggleForm('staffLoginBox', 'staffSignupBox')">Sign Up</span></div>
            </div>

            <div id="staffSignupBox" class="hidden">
                <h2>Staff Sign Up</h2>
                <div class="alert" id="staffSignupAlert"></div>
                <form id="staffSignupForm">
                    <input type="hidden" name="action" value="staff_signup">
                    <div class="input-group">
                        <label>Staff ID</label>
                        <input type="text" name="staff_id" required>
                    </div>
                    <div class="input-group">
                        <label>Staff Name</label>
                        <input type="text" name="staff_name" required>
                    </div>
                    <div class="input-group">
                        <label>Department</label>
                        <select name="department" required>
                            <option value="" style="color:#000;">-- Select Department --</option>
                            <?php foreach($department_list as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" style="color:#000;"><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn" id="staffSignupBtn">Sign Up</button>
                </form>
                <div class="toggle-form">Already have an account? <span onclick="toggleForm('staffSignupBox', 'staffLoginBox')">Login</span></div>
            </div>
        </div>
    </div>

    <!-- HOD Modal -->
    <div id="hodModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('hodModal')">&times;</span>
            
            <div id="hodLoginBox">
                <h2>HOD Login</h2>
                <div class="alert" id="hodLoginAlert"></div>
                <form id="hodLoginForm">
                    <input type="hidden" name="action" value="hod_login">
                    <div class="input-group">
                        <label>HOD ID</label>
                        <input type="text" name="hod_id" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn" id="hodLoginBtn">Login</button>
                </form>
                <div class="toggle-form">Don't have an account? <span onclick="toggleForm('hodLoginBox', 'hodSignupBox')">Sign Up</span></div>
            </div>

            <div id="hodSignupBox" class="hidden">
                <h2>HOD Sign Up</h2>
                <div class="alert" id="hodSignupAlert"></div>
                <form id="hodSignupForm">
                    <input type="hidden" name="action" value="hod_signup">
                    <div class="input-group">
                        <label>HOD ID</label>
                        <input type="text" name="hod_id" required>
                    </div>
                    <div class="input-group">
                        <label>HOD Name</label>
                        <input type="text" name="hod_name" required>
                    </div>
                    <div class="input-group">
                        <label>Department</label>
                        <select name="department" required>
                            <option value="" style="color:#000;">-- Select Department --</option>
                            <?php foreach($department_list as $dept): ?>
                                <option value="<?= htmlspecialchars($dept) ?>" style="color:#000;"><?= htmlspecialchars($dept) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-btn" id="hodSignupBtn">Sign Up</button>
                </form>
                <div class="toggle-form">Already have an account? <span onclick="toggleForm('hodSignupBox', 'hodLoginBox')">Login</span></div>
            </div>
        </div>
    </div>

    <!-- VP Modal -->
    <div id="vpModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('vpModal')">&times;</span>
            <h2>Vice Principal Login</h2>
            <div class="alert" id="vpLoginAlert"></div>
            <form id="vpLoginForm">
                <input type="hidden" name="action" value="vp_login">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="submit-btn" id="vpLoginBtn">Login</button>
            </form>
        </div>
    </div>

    <!-- Admin Modal -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('adminModal')">&times;</span>
            <h2>Admin Login</h2>
            <div class="alert" id="adminLoginAlert"></div>
            <form id="adminLoginForm">
                <input type="hidden" name="action" value="admin_login">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="submit-btn" id="adminLoginBtn">Login</button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
