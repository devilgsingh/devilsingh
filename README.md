<?php
session_start();

$msg = '';
$log_file = __DIR__ . '/sent_log.txt';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['Submit'] ?? '') === 'Send') {

    // --- Verify CAPTCHA ---
    $entered = $_POST['user_code'] ?? '';
    if (!isset($_SESSION['ckey']) || md5($entered) !== $_SESSION['ckey']) {
        $msg = 'ERROR: Invalid Verification Code';
    } else {

        // --- Basic sanitization (strip newlines to prevent header injection in the log too) ---
        $clean = function ($v) {
            $v = (string) $v;
            $v = str_replace(["\r", "\n"], '', $v);
            return trim($v);
        };

        $to        = $clean($_POST['toemail'] ?? '');
        $subject   = $clean($_POST['subject'] ?? '');
        $fromemail = $clean($_POST['fromemail'] ?? '');
        $fromname  = $clean($_POST['fromname'] ?? '');
        $message   = trim((string) ($_POST['message'] ?? '')); // body can have newlines

        // --- Validate email fields ---
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $msg = 'ERROR: "To" address is not a valid email';
        } elseif ($fromemail !== '' && !filter_var($fromemail, FILTER_VALIDATE_EMAIL)) {
            $msg = 'ERROR: "From" address is not a valid email';
        } else {
            // --- NOTHING is actually sent. We just log it locally for inspection. ---
            $entry  = str_repeat('-', 60) . "\n";
            $entry .= "Timestamp:  " . date('Y-m-d H:i:s') . "\n";
            $entry .= "From Name:  $fromname\n";
            $entry .= "From Email: $fromemail\n";
            $entry .= "To Email:   $to\n";
            $entry .= "Subject:    $subject\n";
            $entry .= "Message:\n$message\n";
            $entry .= str_repeat('-', 60) . "\n\n";

            file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);

            $msg = 'Logged locally (not sent). Check sent_log.txt to inspect it.';
        }

        unset($_SESSION['ckey']); // one-time use
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Email Format Tester (Local, No Sending)</title>
<style>
    body { background:#ffffcc; font-family: Arial, sans-serif; }
    .wrap { max-width: 600px; margin: 30px auto; padding: 15px; }
    h2, h3 { text-align: center; }
    label { font-weight: bold; display:block; margin-top: 12px; }
    input[type=text], textarea { width: 100%; box-sizing: border-box; padding: 6px; margin-top: 4px; }
    .row { margin-top: 10px; }
    .msg { text-align: center; color: red; font-weight: bold; }
    .success { color: green; }
    .note { background:#fff; border:1px solid #ddd; padding:10px; margin-top:20px; font-size: 0.9em; }
</style>
</head>
<body>
<div class="wrap">

<h2>Email Format Tester</h2>
<h3>Local sandbox only &mdash; nothing here is ever emailed anywhere.</h3>

<?php if ($msg): ?>
    <p class="msg <?= (strpos($msg, 'ERROR') === false ? 'success' : '') ?>">
        <?= htmlspecialchars($msg) ?>
    </p>
<?php endif; ?>

<form action="index.php" method="POST">
    <label>From Name:</label>
    <input type="text" name="fromname">

    <label>From Email:</label>
    <input type="text" name="fromemail">

    <label>To Email:</label>
    <input type="text" name="toemail">

    <label>Subject:</label>
    <input type="text" name="subject">

    <label>Your Message:</label>
    <textarea name="message" rows="5"></textarea>

    <div class="row">
        <label>Verification Code:</label>
        <img src="captcha.php" alt="captcha" style="vertical-align:middle;">
        <input type="text" name="user_code" size="10">
    </div>

    <div class="row">
        <input type="submit" name="Submit" value="Send">
        <input type="reset" value="Reset">
    </div>
</form>

<div class="note">
    <strong>How this differs from a real mailer:</strong> submitting this form never calls PHP's
    <code>mail()</code> function. It only writes what you entered to <code>sent_log.txt</code> on
    this server, so you can inspect exactly how the fields would be formatted, and confirm your
    CAPTCHA + validation logic works, with zero chance of anything reaching a real inbox.
</div>

</div>
</body>
</html>
