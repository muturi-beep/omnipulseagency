<?php
/**
 * hash-generator.php — ONE-TIME USE ONLY
 * ------------------------------------------------------------------
 * Generates the password hash you paste into config.php's
 * ADMIN_PASSWORD_HASH constant. Uses a form (POST) rather than a
 * URL parameter, so special characters like # & + or spaces in your
 * password are handled correctly — a URL-based version can silently
 * truncate passwords containing those characters.
 *
 * HOW TO USE:
 *   1. Upload this file next to your other PHP files.
 *   2. Visit: https://yourdomain.com/hash-generator.php
 *   3. Type the exact password you want to log in with, submit.
 *   4. Copy the hash it outputs into config.php's ADMIN_PASSWORD_HASH.
 *   5. DELETE THIS FILE from your server immediately after — anyone
 *      who finds it can generate hashes for their own passwords too.
 * ------------------------------------------------------------------
 */

$hash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && $_POST['password'] !== '') {
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Password Hash Generator</title>
<style>
  body{font-family:Arial,sans-serif;max-width:520px;margin:60px auto;padding:0 20px;color:#1a1a2e}
  input[type=text]{width:100%;padding:10px;font-size:14px;border:1px solid #ccc;margin-bottom:12px}
  button{background:#ff5c00;color:#fff;border:none;padding:10px 18px;font-size:14px;cursor:pointer}
  textarea{width:100%;height:70px;font-family:monospace;margin-top:16px}
  .warn{color:#c0392b;font-weight:bold;margin-top:16px}
</style>
</head>
<body>
  <h2>Generate Admin Password Hash</h2>
  <form method="POST">
    <label>Password to hash (type it exactly as you'll log in with):</label>
    <input type="text" name="password" autocomplete="off" autofocus required>
    <button type="submit">Generate Hash</button>
  </form>

  <?php if ($hash): ?>
    <p>Copy this into config.php's <code>ADMIN_PASSWORD_HASH</code>:</p>
    <textarea readonly onclick="this.select()"><?= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') ?></textarea>
    <p class="warn">Delete this file (hash-generator.php) from your server now that you have the hash.</p>
  <?php endif; ?>
</body>
</html>