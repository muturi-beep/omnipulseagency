<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Fail loudly instead of emitting HTML warnings that break JSON.parse
ini_set('display_errors', '0');
error_reporting(E_ALL);

function respond($success, $message) {
    echo json_encode(['success' => (bool)$success, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    respond(false, 'Method not allowed.');
}

// Honeypot — silently accept and discard bot submissions
if (!empty($_POST['website'])) {
    respond(true, 'Thank you — your message has been received.');
}

$name     = trim($_POST['name']     ?? '');
$company  = trim($_POST['company']  ?? '');
$email    = trim($_POST['email']    ?? '');
$industry = trim($_POST['industry'] ?? '');
$need     = trim($_POST['need']     ?? '');
$timeline = trim($_POST['timeline'] ?? '');

$errors = [];
if ($name === '')                                  $errors[] = 'Name is required.';
if ($company === '')                               $errors[] = 'Company is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'A valid email is required.';
if ($industry === '')                              $errors[] = 'Please select a service area.';
if ($need === '')                                  $errors[] = 'Please describe your needs.';

if ($errors) {
    http_response_code(422);
    respond(false, implode(' ', $errors));
}

$to      = 'info@support.omnipulseagency.com';
$subject = 'New enquiry — ' . $company;

$body  = "Name:      $name\n";
$body .= "Company:   $company\n";
$body .= "Email:     $email\n";
$body .= "Service:   $industry\n";
$body .= "Timeline:  " . ($timeline ?: 'Not specified') . "\n";
$body .= "IP:        " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n\n";
$body .= "Message:\n$need\n";

// IMPORTANT: the From address must be a mailbox on YOUR domain,
// otherwise SPF/DMARC will reject it and mail() returns false.
$headers  = "From: OmniPulse Website <no-reply@omnipulseagency.com>\r\n";
$headers .= "Reply-To: $name <$email>\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = @mail($to, $subject, $body, $headers);

if ($sent) {
    respond(true, 'Thank you — your message has been sent. We\'ll reply within 24 hours.');
}

http_response_code(500);
respond(false, 'The server could not send the email. Please email info@support.omnipulseagency.com directly.');