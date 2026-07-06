<?php
/**
 * contact.php — OmniPulse contact form handler
 * ------------------------------------------------------------------
 * Receives the POST from the contact form (services.html), validates
 * and sanitizes it, saves it to your Hostinger database, then emails
 * your team a notification. Responds with JSON so the page's fetch()
 * handler can show an inline success/error message without a reload.
 *
 * Requires config.php in the same folder — that's where the DB
 * credentials and email settings live now (shared with admin.php).
 * ------------------------------------------------------------------
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// -----------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------
function respond(bool $success, string $message): void {
    http_response_code($success ? 200 : 400);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

function clean(string $value): string {
    return trim(filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW));
}

// -----------------------------------------------------------------
// Method guard
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// -----------------------------------------------------------------
// Honeypot — a hidden "website" field. Real visitors never fill it;
// bots that auto-fill every field usually do. Silently pretend
// success so bots don't learn the trap worked.
// -----------------------------------------------------------------
if (!empty($_POST['website'])) {
    respond(true, 'Thanks — we will be in touch shortly.');
}

// -----------------------------------------------------------------
// Collect + validate fields
// -----------------------------------------------------------------
$name     = clean($_POST['name']     ?? '');
$company  = clean($_POST['company']  ?? '');
$email    = clean($_POST['email']    ?? '');
$industry = clean($_POST['industry'] ?? '');
$need     = clean($_POST['need']     ?? '');
$timeline = clean($_POST['timeline'] ?? '');

if ($name === '' || $company === '' || $email === '' || $industry === '' || $need === '') {
    respond(false, 'Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}

// Basic length guards against abuse
if (mb_strlen($name) > 150 || mb_strlen($company) > 200 || mb_strlen($need) > 5000) {
    respond(false, 'One of your fields is too long — please shorten it and try again.');
}

// -----------------------------------------------------------------
// Save to database (this is the source of truth — the message is
// safe in your DB even if the email notification below fails)
// -----------------------------------------------------------------
try {
    $pdo = getPdo();
    $stmt = $pdo->prepare(
        'INSERT INTO contact_submissions (name, company, email, industry, need, timeline, ip_address)
         VALUES (:name, :company, :email, :industry, :need, :timeline, :ip)'
    );
    $stmt->execute([
        ':name'     => $name,
        ':company'  => $company,
        ':email'    => $email,
        ':industry' => $industry,
        ':need'     => $need,
        ':timeline' => $timeline !== '' ? $timeline : null,
        ':ip'       => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
} catch (Throwable $e) {
    error_log('contact.php DB error: ' . $e->getMessage());
    respond(false, 'Something went wrong saving your message. Please email us directly at ' . RECIPIENT_EMAIL . '.');
}

// -----------------------------------------------------------------
// Build + send the email notification (best-effort — the message
// is already saved above even if this fails)
// -----------------------------------------------------------------
function sendEmail(string $name, string $company, string $email, string $industry, string $need, string $timeline): bool {
    $subject = SITE_NAME . ' — New Enquiry from ' . $company . ' (' . $industry . ')';

    $body  = "New enquiry received via the " . SITE_NAME . " website contact form.\n\n";
    $body .= "Name:      $name\n";
    $body .= "Company:   $company\n";
    $body .= "Email:     $email\n";
    $body .= "Industry:  $industry\n";
    $body .= "Timeline:  " . ($timeline !== '' ? $timeline : 'Not specified') . "\n\n";
    $body .= "What they need:\n$need\n";

    $headers   = [];
    $headers[] = 'From: ' . SITE_NAME . ' Website <' . FROM_ADDRESS . '>';
    $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    return @mail(RECIPIENT_EMAIL, $subject, $body, implode("\r\n", $headers));
}

sendEmail($name, $company, $email, $industry, $need, $timeline);

respond(true, "Thanks {$name} — we've received your enquiry and will respond within 24 hours.");