<?php

namespace App\Task;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use App\Email\ResendEmail;

class TestResendEmailTask extends BuildTask
{
    private static $segment = 'TestResendEmailTask';

    protected $title = 'Test Resend Email';

    protected $description = 'Sends a test email via the Resend API to verify the integration. DEV USE ONLY.';

    public function run($request)
    {
        if (!Director::isDev()) {
            echo 'This task can only be run in dev mode.' . PHP_EOL;
            return;
        }

        $to      = 'dinukasf2@gmail.com';
        $subject = 'Resend Test — Quinvest Billing';
        $body    = '<p>This is a test email sent from the Quinvest Billing local environment.</p>'
                 . '<p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>'
                 . '<p>If you received this, the Resend API integration is working correctly.</p>';

        echo "Sending test email to {$to} ..." . PHP_EOL;

        $ok = ResendEmail::send($to, $subject, $body);

        echo $ok
            ? "Done — check your inbox at {$to}." . PHP_EOL
            : "Failed — check silverstripe.log for the API error." . PHP_EOL;
    }
}
