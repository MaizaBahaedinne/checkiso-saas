<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're invited to join <?= esc($orgName) ?> on CheckISO</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #0d6efd; padding: 32px 40px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; letter-spacing: .02em; }
        .body { padding: 32px 40px; color: #343a40; line-height: 1.6; }
        .body p { margin: 0 0 16px; }
        .cta { text-align: center; margin: 28px 0; }
        .cta a { background: #0d6efd; color: #fff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: bold; font-size: 15px; display: inline-block; }
        .footer { background: #f8f9fa; padding: 16px 40px; font-size: 12px; color: #6c757d; text-align: center; }
        .url { word-break: break-all; color: #0d6efd; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>CheckISO</h1>
    </div>
    <div class="body">
        <p>Hi,</p>
        <p>
            <strong><?= esc($senderName) ?></strong> has invited you to join
            <strong><?= esc($orgName) ?></strong> on <strong>CheckISO</strong> —
            the ISO compliance management platform.
        </p>
        <p>Click the button below to accept the invitation. The link is valid for <strong>7 days</strong>.</p>
        <div class="cta">
            <a href="<?= esc($inviteUrl) ?>">Accept invitation</a>
        </div>
        <p>Or copy this link into your browser:</p>
        <p class="url"><?= esc($inviteUrl) ?></p>
        <p>If you did not expect this invitation, you can safely ignore this email.</p>
        <p>— The CheckISO Team</p>
    </div>
    <div class="footer">
        © <?= date('Y') ?> CheckISO · This is an automated message, please do not reply.
    </div>
</div>
</body>
</html>
