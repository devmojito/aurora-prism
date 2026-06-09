<?php
/**
 * Aurora Prism Consulting — contact form handler (Hostinger / PHP mail).
 * Receives the modal form and emails it to the company mailbox.
 */

header('Content-Type: application/json; charset=utf-8');

// Same-origin only
header('Access-Control-Allow-Origin: ' . (($_SERVER['HTTP_ORIGIN'] ?? '') ?: '*'));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Accept JSON body or classic form-encoded
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$clean = function ($v) {
    return trim(preg_replace('/[\r\n]+/', ' ', (string)($v ?? '')));
};

$name    = $clean($data['name']    ?? '');
$email   = $clean($data['email']   ?? '');
$company = $clean($data['company'] ?? '');
$topic   = $clean($data['topic']   ?? '');
$message = trim((string)($data['message'] ?? ''));

// Basic validation + honeypot
if (!empty($data['website'] ?? '')) {           // bots fill hidden field
    echo json_encode(['ok' => true]);            // pretend success
    exit;
}
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please provide a name and a valid email.']);
    exit;
}

$to      = 'hello@auroraprismconsulting.com';
$subject = 'New enquiry' . ($topic !== '' ? " — {$topic}" : '') . " — {$name}";

$body  = "New strategy enquiry from the website\n";
$body .= "----------------------------------------\n\n";
$body .= "Name:     {$name}\n";
$body .= "Email:    {$email}\n";
$body .= "Company:  " . ($company !== '' ? $company : '—') . "\n";
$body .= "Topic:    " . ($topic   !== '' ? $topic   : '—') . "\n\n";
$body .= "Message:\n" . ($message !== '' ? $message : '(none provided)') . "\n";

// From must be a real mailbox on the sending domain for Hostinger to accept it.
$fromAddr = 'hello@auroraprismconsulting.com';
$headers  = [];
$headers[] = 'From: Aurora Prism Website <' . $fromAddr . '>';
$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$sent = @mail($to, $subject, $body, implode("\r\n", $headers), '-f' . $fromAddr);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail could not be sent.']);
}
