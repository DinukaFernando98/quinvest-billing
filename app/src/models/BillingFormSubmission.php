<?php

namespace App\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Assets\File;
use App\Model\FormSubmission;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\ReadonlyField;

class BillingFormSubmission extends DataObject
{
    private static $table_name = 'BillingFormSubmission';

    private static $db = [
        'SerialNumber' => 'Varchar(100)', // Must match a FormSubmission
    ];

    private static $has_one = [
        'FormSubmission' => FormSubmission::class,
        'UploadedBy' => Member::class,
        'BillingFile' => File::class,
    ];

    private static $summary_fields = [
        'SerialNumber' => 'Serial Number',
        'FormSubmission.SalespersonName' => 'Salesperson',
        'Created.Nice' => 'Submitted Date',
        'getFileSize' => 'File Size',
        'getDownloadLink' => 'Download',
    ];

    private static $owns = [
        'BillingFile',
    ];

    // Add searchable fields
    private static $searchable_fields = [
        'SerialNumber',
        'FormSubmission.SerialNumber',
        'FormSubmission.SalespersonName',
        'UploadedBy.Email',
    ];
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Remove the FormSubmission and UploadedBy dropdown fields
        $fields->removeByName('FormSubmissionID');
        $fields->removeByName('UploadedByID');

        // Make only existing fields read-only
        $fields->makeFieldReadonly('SerialNumber');

        // Remove Created and LastEdited fields if they exist, we'll add custom ones
        $fields->removeByName('Created');
        $fields->removeByName('LastEdited');

        // Add Created and LastEdited as read-only display fields
        if ($this->isInDB()) {
            $createdField = ReadonlyField::create(
                'CreatedDisplay',
                'Created',
                $this->dbObject('Created')->Nice()
            );
            $fields->addFieldToTab('Root.Main', $createdField);
        }

                // Ensure BillingFile field is visible and clickable
        $billingFileField = $fields->dataFieldByName('BillingFile');
        if ($billingFileField) {
            // Remove and re-add to ensure proper positioning
            $fields->removeByName('BillingFile');

            // Create a download link for the existing file
            if ($this->BillingFile()->exists()) {
                $downloadLink = sprintf(
                    '<div class="field">' .
                        '<label class="form__field-label">Billing File</label>' .
                        '<div class="form__field-holder">' .
                        '<a href="%s" target="_blank" class="btn btn-primary" download>Download Billing File</a>' .
                        '<p class="form__field-description">File: %s (%s)</p>' .
                        '</div></div>',
                    $this->BillingFile()->getURL(),
                    $this->BillingFile()->Name,
                    $this->getFileSize()
                );

                $fields->addFieldToTab('Root.Main', LiteralField::create('BillingFileDownload', $downloadLink));
            } else {
                // Show the upload field if no file exists (though this shouldn't happen for existing records)
                $fields->addFieldToTab('Root.Main', $billingFileField);
            }
        }

        // Add a link to the related FormSubmission
        if ($this->FormSubmission()->exists()) {
            $submissionLink = sprintf(
                '<div class="field">' .
                    '<div class="form__field-holder">' .
                    '<a href="%s" target="_blank" class="btn btn-primary">View Form Submission</a>' .
                    '<p class="form__field-description">Click to view the original form submission details</p>' .
                    '</div></div>',
                '/admin/form-submissions/App-Model-FormSubmission/EditForm/field/App-Model-FormSubmission/item/' . $this->FormSubmissionID . '/edit'
            );

            $fields->addFieldToTab('Root.Main', LiteralField::create('SubmissionLink', $submissionLink));
        } else {
            $fields->addFieldToTab('Root.Main', LiteralField::create(
                'NoSubmission',
                '<div class="alert alert-warning">No related form submission found</div>'
            ));
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->SerialNumber && !$this->FormSubmissionID) {
            $submission = FormSubmission::get()->filter('SerialNumber', $this->SerialNumber)->first();
            if ($submission) {
                $this->FormSubmissionID = $submission->ID;
            } else {
                user_error("No matching FormSubmission found for SerialNumber: {$this->SerialNumber}", E_USER_ERROR);
            }
        }
    }

    // Helper method to get file size
    public function getFileSize()
    {
        if ($this->BillingFile()->exists()) {
            $size = $this->BillingFile()->getAbsoluteSize();
            return $this->formatFileSize($size);
        }
        return '-';
    }

    // Helper method for download link (used in grid field)
    public function getDownloadLink()
    {
        if ($this->BillingFile()->exists()) {
            return $this->BillingFile()->getURL();
        }
        return null;
    }

    // Format file size for display
    private function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 Bytes';

        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    // Permission methods
    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return false; // Billing forms should not be editable after submission
    }

    public function canDelete($member = null)
    {
        return true; // Allow admins to delete if needed
    }

    public function canCreate($member = null, $context = [])
    {
        return false; // Billing forms should only be created through the form
    }
}
