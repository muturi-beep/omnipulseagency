<?php
/**
 * admin.php — password-protected inbox for contact form submissions
 * ------------------------------------------------------------------
 * Visit this page, log in with the password you set via
 * hash-generator.php, and view/manage every enquiry saved by
 * contact.php. Requires config.php in the same folder.
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/config.php';

session_start();

// -----------------------------------------------------------------
// Handle login
// -----------------------------------------------------------------
$loginError = '';
if (isset($_POST['login_password'])) {
    if (password_verify($_POST['login_password'], ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
    } else {
        $loginError = 'Incorrect password.';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

$isAuthenticated = !empty($_SESSION['admin_authenticated']);

// -----------------------------------------------------------------
// Handle mark-as-read / delete actions (only once logged in)
// -----------------------------------------------------------------
if ($isAuthenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getPdo();
        if (isset($_POST['mark_read_id'])) {
            $stmt = $pdo->prepare('UPDATE contact_submissions SET is_read = 1 WHERE id = :id');
            $stmt->execute([':id' => (int) $_POST['mark_read_id']]);
        } elseif (isset($_POST['delete_id'])) {
            $stmt = $pdo->prepare('DELETE FROM contact_submissions WHERE id = :id');
            $stmt->execute([':id' => (int) $_POST['delete_id']]);
        }
        header('Location: admin.php');
        exit;
    } catch (Throwable $e) {
        error_log('admin.php action error: ' . $e->getMessage());
    }
}

// -----------------------------------------------------------------
// Load submissions
// -----------------------------------------------------------------
$submissions = [];
$dbError = '';
if ($isAuthenticated) {
    try {
        $pdo = getPdo();
        $submissions = $pdo->query('SELECT * FROM contact_submissions ORDER BY created_at DESC')->fetchAll();
    } catch (Throwable $e) {
        error_log('admin.php load error: ' . $e->getMessage());
        $dbError = 'Could not load submissions. Check config.php database credentials.';
    }
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enquiries — <?= h(SITE_NAME) ?> Admin</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:#f0f0e8;color:#1a1a2e;font-family:'IBM Plex Sans',Arial,sans-serif;padding:40px 24px}
  .wrap{max-width:1100px;margin:0 auto}
  h1{font-size:22px;margin-bottom:4px}
  .sub{color:#5a5a72;font-size:13px;margin-bottom:28px}
  .login-box{max-width:360px;margin:80px auto;background:#fff;border:1px solid #d4d4c8;padding:32px}
  .login-box h1{text-align:center;margin-bottom:20px}
  input[type=password]{width:100%;padding:11px 14px;border:1px solid #d4d4c8;font-size:14px;margin-bottom:14px}
  button{background:#ff5c00;color:#fff;border:none;padding:11px 20px;font-size:13px;font-weight:600;letter-spacing:.5px;cursor:pointer;text-transform:uppercase}
  button:hover{background:#ff8800}
  .error{color:#c0392b;font-size:13px;margin-bottom:12px}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #d4d4c8}
  th,td{padding:12px 14px;border-bottom:1px solid #e8e8e0;text-align:left;font-size:13px;vertical-align:top}
  th{background:#f6f6ef;font-weight:600;text-transform:uppercase;font-size:10px;letter-spacing:1px;color:#5a5a72}
  tr.unread{background:#fff7f0}
  .need-cell{max-width:280px;white-space:pre-wrap}
  .actions{display:flex;gap:6px;flex-wrap:wrap}
  .actions form{display:inline}
  .btn-sm{padding:6px 10px;font-size:10px}
  .btn-danger{background:#c0392b}
  .btn-danger:hover{background:#e74c3c}
  .badge{display:inline-block;padding:3px 8px;font-size:9px;letter-spacing:.5px;text-transform:uppercase;border-radius:2px}
  .badge-unread{background:#ff5c00;color:#fff}
  .badge-read{background:#e8e8e0;color:#5a5a72}
  .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
  .logout{font-size:12px;color:#5a5a72;text-decoration:none}
  .logout:hover{color:#ff5c00}
  .empty{padding:40px;text-align:center;color:#5a5a72;background:#fff;border:1px solid #d4d4c8}
</style>
</head>
<body>

<?php if (!$isAuthenticated): ?>

  <div class="login-box">
    <h1>🔒 Enquiries Login</h1>
    <?php if ($loginError): ?><div class="error"><?= h($loginError) ?></div><?php endif; ?>
    <form method="POST">
      <input type="password" name="login_password" placeholder="Password" autofocus required>
      <button type="submit" style="width:100%">Log In</button>
    </form>
  </div>

<?php else: ?>

  <div class="wrap">
    <div class="topbar">
      <div>
        <h1>Enquiries</h1>
        <div class="sub"><?= count($submissions) ?> total · <?= h(SITE_NAME) ?> contact form</div>
      </div>
      <a class="logout" href="admin.php?logout=1">Log Out →</a>
    </div>

    <?php if ($dbError): ?>
      <div class="error"><?= h($dbError) ?></div>
    <?php elseif (empty($submissions)): ?>
      <div class="empty">No enquiries yet.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Status</th>
            <th>Date</th>
            <th>Name</th>
            <th>Company</th>
            <th>Email</th>
            <th>Industry</th>
            <th>Timeline</th>
            <th>What they need</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($submissions as $row): ?>
            <tr class="<?= $row['is_read'] ? '' : 'unread' ?>">
              <td>
                <?php if ($row['is_read']): ?>
                  <span class="badge badge-read">Read</span>
                <?php else: ?>
                  <span class="badge badge-unread">New</span>
                <?php endif; ?>
              </td>
              <td><?= h(date('M j, Y g:ia', strtotime($row['created_at']))) ?></td>
              <td><?= h($row['name']) ?></td>
              <td><?= h($row['company']) ?></td>
              <td><a href="mailto:<?= h($row['email']) ?>"><?= h($row['email']) ?></a></td>
              <td><?= h($row['industry']) ?></td>
              <td><?= h($row['timeline'] ?: '—') ?></td>
              <td class="need-cell"><?= h($row['need']) ?></td>
              <td class="actions">
                <?php if (!$row['is_read']): ?>
                  <form method="POST">
                    <input type="hidden" name="mark_read_id" value="<?= (int) $row['id'] ?>">
                    <button type="submit" class="btn-sm">Mark Read</button>
                  </form>
                <?php endif; ?>
                <form method="POST" onsubmit="return confirm('Delete this enquiry permanently?')">
                  <input type="hidden" name="delete_id" value="<?= (int) $row['id'] ?>">
                  <button type="submit" class="btn-sm btn-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

<?php endif; ?>

</body>
</html>