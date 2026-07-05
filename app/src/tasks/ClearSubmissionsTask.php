<?php

namespace App\Task;

use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Director;
use App\Model\FormSubmission;
use App\Model\BillingFormSubmission;

class ClearSubmissionsTask extends BuildTask
{
    private static $segment = 'ClearSubmissionsTask';

    protected $title = 'Clear All Submissions';

    protected $description = 'Deletes every FormSubmission, BillingFormSubmission, and all associated uploaded files. DEV USE ONLY.';

    public function run($request)
    {
        if (!Director::isDev()) {
            $this->log('This task can only be run in dev mode.');
            return;
        }

        $this->log('=== Clear All Submissions ===');

        // ── Billing submissions ────────────────────────────────────────────
        $billingDeleted = 0;
        $billingFiles   = 0;
        foreach (BillingFormSubmission::get() as $billing) {
            if ($billing->BillingFileID && $billing->BillingFile()->exists()) {
                $billing->BillingFile()->delete();
                $billingFiles++;
            }
            $billing->delete();
            $billingDeleted++;
        }
        $this->log("BillingFormSubmissions deleted: {$billingDeleted} ({$billingFiles} files)");

        // ── Form submissions (bottom-up through the ownership graph) ───────
        $submissionsDeleted = 0;
        $filesDeleted       = 0;

        foreach (FormSubmission::get() as $submission) {

            // AML records → UCPForms + ECDDForms first
            foreach ($submission->AMLRecords() as $aml) {
                foreach ($aml->UCPForms() as $ucp) {
                    if ($ucp->FormFileID && $ucp->FormFile()->exists()) {
                        $ucp->FormFile()->delete();
                        $filesDeleted++;
                    }
                    $ucp->delete();
                }
                foreach ($aml->ECDDForms() as $ecdd) {
                    if ($ecdd->FormFileID && $ecdd->FormFile()->exists()) {
                        $ecdd->FormFile()->delete();
                        $filesDeleted++;
                    }
                    $ecdd->delete();
                }
                if ($aml->AMLFileID && $aml->AMLFile()->exists()) {
                    $aml->AMLFile()->delete();
                    $filesDeleted++;
                }
                if ($aml->FormQCIDID && $aml->FormQCID()->exists()) {
                    $aml->FormQCID()->delete();
                    $filesDeleted++;
                }
                $aml->delete();
            }

            // Client info files
            foreach ($submission->ClientInfo() as $client) {
                foreach (['OwnershipProof', 'FormA1File', 'FormA2File', 'FormA3File', 'FormA4File', 'FormBFile'] as $rel) {
                    if ($client->{$rel . 'ID'}) {
                        $file = $client->$rel();
                        if ($file->exists()) {
                            $file->delete();
                            $filesDeleted++;
                        }
                    }
                }
                $client->delete();
            }

            // Transaction documents
            foreach ($submission->Documents() as $doc) {
                if ($doc->DocumentFileID && $doc->DocumentFile()->exists()) {
                    $doc->DocumentFile()->delete();
                    $filesDeleted++;
                }
                $doc->delete();
            }

            $submission->delete();
            $submissionsDeleted++;
        }

        $this->log("FormSubmissions deleted: {$submissionsDeleted} ({$filesDeleted} files)");

        // ── Physical asset folder ──────────────────────────────────────────
        $assetsDir = Director::publicFolder() . '/assets/submissions';
        if (is_dir($assetsDir)) {
            $this->deleteDirectory($assetsDir);
            $this->log("Removed physical folder: assets/submissions/");
        }

        $this->log('Done.');
    }

    private function deleteDirectory(string $path): void
    {
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($path);
    }

    private function log(string $msg): void
    {
        echo Director::is_cli() ? $msg . PHP_EOL : $msg . '<br>' . PHP_EOL;
    }
}
