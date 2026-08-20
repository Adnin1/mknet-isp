<?php
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

$message = '';

// Add User
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add'])){
  if (!csrf_verify()) { $message = "Session expired, please try again."; }
  else {
    $email = trim((string)$_POST['email']);
    $password = password_hash((string)$_POST['password'], PASSWORD_DEFAULT);
    $speed = (int)$_POST['speed'];
    $name = trim((string)($_POST['full_name'] ?? ''));

    $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $check->execute([$email]);
    if ($check->fetch()) {
      $message = "A user with that email already exists.";
    } else {
      $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, package_speed_mbps, referral_code) VALUES (?,?,?,?,?)");
      $stmt->execute([$name, $email, $password, $speed, strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))]);
      $message = "User created successfully.";
    }
  }
}

// Update User
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['update'])){
  if (!csrf_verify()) { $message = "Session expired, please try again."; }
  else {
    $id = (int)$_POST['id'];
    $email = trim((string)$_POST['email']);
    $speed = (int)$_POST['speed'];
    $name = trim((string)($_POST['full_name'] ?? ''));

    if(!empty($_POST['password'])){
      $password = password_hash((string)$_POST['password'], PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, package_speed_mbps=?, password=? WHERE id=?");
      $stmt->execute([$name, $email, $speed, $password, $id]);
    } else {
      $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, package_speed_mbps=? WHERE id=?");
      $stmt->execute([$name, $email, $speed, $id]);
    }
    $message = "User updated successfully.";
  }
}

// Toggle account status (pause/resume from admin side)
if(isset($_GET['toggle_status'])){
  $id = (int)$_GET['toggle_status'];
  $pdo->prepare("UPDATE users SET account_status = IF(account_status='active','paused','active') WHERE id=?")->execute([$id]);
}

// Toggle Regular Customer status
if(isset($_GET['toggle_regular'])){
  $id = (int)$_GET['toggle_regular'];
  $pdo->prepare("UPDATE users SET is_regular = IF(is_regular=1,0,1) WHERE id=?")->execute([$id]);
  $message = "User customer tier updated.";
}

// Delete User
if(isset($_GET['delete'])){
  $id = (int)$_GET['delete'];
  $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
  $message = "User deleted.";
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editUser = null;
if ($editId) {
  $e = $pdo->prepare("SELECT * FROM users WHERE id=?");
  $e->execute([$editId]);
  $editUser = $e->fetch();
}

$search = trim((string)($_GET['q'] ?? ''));
if ($search !== '') {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email LIKE ? OR full_name LIKE ? OR phone LIKE ? ORDER BY id DESC");
  $like = "%$search%";
  $stmt->execute([$like, $like, $like]);
} else {
  $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC LIMIT 100");
}
$users = $stmt->fetchAll();
?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Manage Users</h2>
  <?php if ($message): ?><p style="color:#0a6;"><?= h($message) ?></p><?php endif; ?>

  <div class="card">
    <h3><?= $editUser ? 'Edit User #' . h($editUser['id']) : 'Add New User' ?></h3>
    <form method="post">
      <?= csrf_field() ?>
      <?php if ($editUser): ?><input type="hidden" name="id" value="<?= h($editUser['id']) ?>"><?php endif; ?>
      <input type="text" name="full_name" placeholder="Full Name" value="<?= h($editUser['full_name'] ?? '') ?>">
      <input type="email" name="email" placeholder="Email" value="<?= h($editUser['email'] ?? '') ?>" required>
      <input type="password" name="password" placeholder="<?= $editUser ? 'New password (leave blank to keep current)' : 'Password' ?>" <?= $editUser ? '' : 'required' ?>>
      <input type="number" name="speed" placeholder="Package Speed (Mbps)" value="<?= h($editUser['package_speed_mbps'] ?? '') ?>" required>
      <button type="submit" name="<?= $editUser ? 'update' : 'add' ?>"><?= $editUser ? 'Update User' : 'Add User' ?></button>
      <?php if ($editUser): ?><a href="manage_users.php" class="btn small">Cancel</a><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <h3>All Users</h3>
    <form method="get" style="margin-bottom:10px;">
      <input type="text" name="q" placeholder="Search by name, email, or phone" value="<?= h($search) ?>">
      <button type="submit">Search</button>
      <?php if ($search): ?><a href="manage_users.php" class="btn small">Clear</a><?php endif; ?>
    </form>
    <table>
      <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Speed</th><th>Tier</th><th>Status</th><th>Actions</th></tr>
      <?php if (empty($users)): ?>
        <tr><td colspan="8">No users found.</td></tr>
      <?php endif; ?>
      <?php foreach($users as $u): ?>
      <tr>
        <td>#<?= h($u['id']) ?></td>
        <td><?= !empty($u['name']) ? h($u['name']) : (!empty($u['full_name']) ? h($u['full_name']) : '—') ?></td>
        <td><?= h($u['email']) ?></td>
        <td><?= h($u['phone'] ?: '—') ?></td>
        <td><?= h($u['package_speed_mbps']) ?> Mbps</td>
        <td>
          <a href="?toggle_regular=<?= h($u['id']) ?>" style="text-decoration:none;">
            <?= (!empty($u['is_regular'])) ? '⭐ <span style="color:#34d399; font-weight:700;">Regular</span>' : '🆕 <span style="color:#fbbf24;">New User</span>' ?>
          </a>
        </td>
        <td><?= ($u['account_status'] ?? 'active') === 'paused' ? '<span class="badge badge-low">Paused</span>' : '<span class="badge badge-high">Active</span>' ?></td>
        <td>
          <a href="?edit=<?= h($u['id']) ?>">Edit</a> |
          <a href="?toggle_status=<?= h($u['id']) ?>"><?= ($u['account_status'] ?? 'active') === 'paused' ? 'Resume' : 'Pause' ?></a> |
          <a href="?delete=<?= h($u['id']) ?>" onclick="return confirm('Delete this user? This cannot be undone.')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
