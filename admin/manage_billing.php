<?php
require_once __DIR__ . '/../config.php';
session_start();
if(!isset($_SESSION['admin_id'])){
  header("Location: login.php");
  exit;
}

// Add Bill
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['add'])){
  $user_id = (int)$_POST['user_id'];
  $amount = (float)$_POST['amount'];
  $due_date = $_POST['due_date'];
  $auto_renew = isset($_POST['auto_renew']) ? 1 : 0;

  $stmt = $pdo->prepare("INSERT INTO billing (user_id, amount, due_date, status, auto_renew) VALUES (?,?,?,'pending',?)");
  $stmt->execute([$user_id,$amount,$due_date,$auto_renew]);
}

// Mark Paid + Loyalty Points + Notification
if(isset($_GET['pay'])){
  $id = (int)$_GET['pay'];
  $pdo->prepare("UPDATE billing SET status='paid', paid_date=NOW() WHERE id=?")->execute([$id]);

  // Fetch user info (parameterized - the old version used string interpolation here, which was an SQL-injection risk)
  $billStmt = $pdo->prepare("SELECT b.user_id, u.email FROM billing b JOIN users u ON b.user_id=u.id WHERE b.id=?");
  $billStmt->execute([$id]);
  $bill = $billStmt->fetch();

  if ($bill) {
    // Loyalty points update
    $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points + 10 WHERE id=?")->execute([$bill['user_id']]);

    // Email notification (simple PHP mail)
    if(function_exists('mail')){
      @mail($bill['email'], "Payment Confirmation", "Your payment has been received successfully!", "From: no-reply@yourdomain.com");
    }
  }
}

// Delete Bill
if(isset($_GET['delete'])){
  $id = (int)$_GET['delete'];
  $pdo->prepare("DELETE FROM billing WHERE id=?")->execute([$id]);
}

$bills = $pdo->query("SELECT b.*, u.email FROM billing b JOIN users u ON b.user_id=u.id ORDER BY b.id DESC")->fetchAll();
$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>
<link rel="stylesheet" href="style.css">

<?php include __DIR__ . '/sidebar.php'; ?>
<?php include __DIR__ . '/header.php'; ?>

<div class="main">
  <h2>Manage Billing</h2>

  <!-- Add Bill -->
  <div class="card">
    <h3>Create New Bill</h3>
    <form method="post">
      <select name="user_id" required>
        <option value="">Select User</option>
        <?php foreach($users as $u): ?>
          <option value="<?= $u['id'] ?>"><?= h($u['email']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="number" step="0.01" name="amount" placeholder="Amount" required>
      <input type="date" name="due_date" required>
      <label><input type="checkbox" name="auto_renew"> Auto-Renew</label>
      <button type="submit" name="add">Add Bill</button>
    </form>
  </div>

  <!-- Bills List -->
  <div class="card">
    <h3>All Bills</h3>
    <table>
      <tr><th>ID</th><th>User</th><th>Amount</th><th>Status</th><th>Due Date</th><th>Paid Date</th><th>Auto-Renew</th><th>Actions</th></tr>
      <?php foreach($bills as $b): ?>
      <tr>
        <td><?= $b['id'] ?></td>
        <td><?= h($b['email']) ?></td>
        <td><?= h($b['amount']) ?></td>
        <td>
          <?php if($b['status']=='pending'): ?>
            <span class="badge badge-low">Pending</span>
          <?php else: ?>
            <span class="badge badge-high">Paid</span>
          <?php endif; ?>
        </td>
        <td><?= h($b['due_date']) ?></td>
        <td><?= h($b['paid_date']) ?></td>
        <td>
          <?= isset($b['auto_renew']) ? ($b['auto_renew'] ? "✅" : "❌") : "-" ?>
        </td>
        <td>
          <?php if($b['status']=='pending'): ?>
            <a href="manage_billing.php?pay=<?= $b['id'] ?>">Mark Paid</a> |
          <?php endif; ?>
          <a href="manage_billing.php?delete=<?= $b['id'] ?>" onclick="return confirm('Delete this bill?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
