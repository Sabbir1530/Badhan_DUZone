<?php
/**
 * Manage Users - Admin Only
 * Admin can:
 *   - View all users
 *   - Change any user's password
 *   - Change own password
 *   - Add new members
 *   - Delete members (not self)
 */
$pageTitle = 'Manage Users';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';
requireAdmin();

$pdo = getDBConnection();
$errors  = [];
$success = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $action = $_POST['action'] ?? '';

        // ---- Change Password ----
        if ($action === 'change_password') {
            $userId      = (int) ($_POST['user_id'] ?? 0);
            $newPassword  = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($newPassword) || strlen($newPassword) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([':password' => $hash, ':id' => $userId]);
                setFlash('success', 'Password updated successfully.');
                header('Location: ' . BASE_URL . 'manage_users.php');
                exit;
            }
        }

        // ---- Add New Member ----
        if ($action === 'add_member') {
            $username = trim($_POST['username'] ?? '');
            $name     = trim($_POST['name'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($name) || empty($password)) {
                $errors[] = 'All fields are required.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } else {
                // Check uniqueness
                $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u");
                $check->execute([':u' => $username]);
                if ($check->fetchColumn() > 0) {
                    $errors[] = 'Username already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name) VALUES (:u, :p, 'member', :n)");
                    $stmt->execute([':u' => $username, ':p' => $hash, ':n' => $name]);
                    setFlash('success', 'Member "' . $username . '" added successfully.');
                    header('Location: ' . BASE_URL . 'manage_users.php');
                    exit;
                }
            }
        }

        // ---- Delete Member ----
        if ($action === 'delete_member') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            // Can't delete self
            if ($userId === getUserId()) {
                $errors[] = 'You cannot delete your own account.';
            } else {
                // Only delete members, not admins
                $check = $pdo->prepare("SELECT role FROM users WHERE id = :id");
                $check->execute([':id' => $userId]);
                $role = $check->fetchColumn();
                if ($role !== 'member') {
                    $errors[] = 'Cannot delete admin accounts.';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'member'");
                    $stmt->execute([':id' => $userId]);
                    setFlash('success', 'Member deleted successfully.');
                    header('Location: ' . BASE_URL . 'manage_users.php');
                    exit;
                }
            }
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT id, username, role, name, created_at FROM users ORDER BY role ASC, id ASC")->fetchAll();
$csrfToken = generateCSRFToken();
?>

<div class="container">
    <?php
    $flash = getFlash();
    if ($flash): ?>
        <div class="flash <?= htmlspecialchars($flash['type']) ?>"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="flash error">
            <ul style="margin:0; padding-left:1.2rem;">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- User List -->
    <div class="card">
        <div class="card-header">👥 All Users</div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Change Password</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td>
                            <span class="badge" style="background:<?= $u['role'] === 'admin' ? 'var(--primary)' : 'var(--info)' ?>; color:white;">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm"
                                    onclick="openPasswordModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">
                                🔑 Change
                            </button>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'member'): ?>
                                <form method="POST" style="display:inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete user \'<?= htmlspecialchars($u['username']) ?>\'? This will also delete all their requisitions.');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="action" value="delete_member">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️ Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--gray); font-size:0.8rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add New Member -->
    <div class="card">
        <div class="card-header">➕ Add New Member</div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="add_member">
            <div class="form-row-3">
                <div class="form-group">
                    <label for="new_username">Username *</label>
                    <input type="text" id="new_username" name="username" class="form-control" 
                           placeholder="e.g. member2" required>
                </div>
                <div class="form-group">
                    <label for="new_name">Full Name *</label>
                    <input type="text" id="new_name" name="name" class="form-control" 
                           placeholder="e.g. John Doe" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password *</label>
                    <input type="password" id="new_password" name="password" class="form-control" 
                           placeholder="Min 6 characters" required minlength="6">
                </div>
            </div>
            <button type="submit" class="btn btn-success">➕ Add Member</button>
        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.5); z-index:2000; justify-content:center; align-items:center;">
    <div style="background:white; border-radius:12px; padding:2rem; max-width:420px; width:90%; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
        <h3 style="margin-bottom:0.5rem; color:var(--secondary);">🔑 Change Password</h3>
        <p id="pwdModalUser" style="margin-bottom:1rem; color:var(--gray);"></p>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="user_id" id="pwdModalUserId">
            <div class="form-group">
                <label>New Password *</label>
                <input type="password" name="new_password" class="form-control" 
                       placeholder="Min 6 characters" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" class="form-control" 
                       placeholder="Repeat password" required minlength="6">
            </div>
            <div style="display:flex; gap:0.8rem; margin-top:1rem;">
                <button type="submit" class="btn btn-success">Save Password</button>
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPasswordModal(userId, username) {
    document.getElementById('pwdModalUserId').value = userId;
    document.getElementById('pwdModalUser').textContent = 'User: ' + username;
    document.getElementById('passwordModal').style.display = 'flex';
}
function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}
document.getElementById('passwordModal').addEventListener('click', function(e) {
    if (e.target === this) closePasswordModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
