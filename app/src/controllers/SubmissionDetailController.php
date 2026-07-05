<?php

namespace App\Pages;

use App\Model\ECDDForm;
use App\Model\SubmissionDocument;
use PageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Security\Security;
use SilverStripe\Security\Member;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use App\Model\FormSubmission;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\Form;

class SubmissionDetailPageController extends PageController
{
    private static $allowed_actions = [
        'view',
        'edit',
        'EditForm',
        'doSave',
        'cancel',
        'uploadFile',
        'deleteFile'
    ];
    
    private static $url_handlers = [
        '$ID!' => 'view',
        '$ID/edit' => 'edit',
        '$ID/save' => 'doSave'
    ];
    
    protected $submission;
    
    protected function init()
    {
        parent::init();
        
        $member = Security::getCurrentUser();
        if (!$member) {
            return $this->redirect('/Security/login?BackURL=' . urlencode($this->getRequest()->getURL()));
        }
        
        // Add CSS/JS requirements
        Requirements::css('public/resources/themes/quinvest/css/main.css');
        Requirements::javascript('public/resources/themes/quinvest/js/main.js');
    }
    
    public function index()
    {
        return $this->redirect('/dashboard');
    }
    
    public function view(HTTPRequest $request)
    {
        $id = $request->param('ID');
        
        if (!$id || !is_numeric($id)) {
            return $this->httpError(404, 'Invalid submission ID');
        }
        
        $member = Security::getCurrentUser();
        $this->submission = FormSubmission::get()->byID($id);
        
        // Security check
        if (!$this->submission) {
            return $this->httpError(404, 'Submission not found');
        }
        
        if ($this->submission->RESNumber !== $member->RESNumber) {
            return $this->httpError(403, 'You do not have permission to view this submission');
        }
        
        return [
            'Submission' => $this->submission,
            'IsEditMode' => false
        ];
    }
    
    public function edit(HTTPRequest $request)
    {
        $id = $request->param('ID');
        
        if (!$id || !is_numeric($id)) {
            return $this->httpError(404, 'Invalid submission ID');
        }
        
        $member = Security::getCurrentUser();
        $this->submission = FormSubmission::get()->byID($id);
        
        // Security check
        if (!$this->submission) {
            return $this->httpError(404, 'Submission not found');
        }
        
        if ($this->submission->RESNumber !== $member->RESNumber) {
            return $this->httpError(403, 'You do not have permission to edit this submission');
        }
        
        // Check if submission can be edited
        if (!in_array($this->submission->Status, ['Declined', 'Approved'])) {
            $this->setFlashMessage('This submission cannot be edited.', 'warning');
            return $this->redirect($this->Link('view/' . $id));
        }
        
        $this->setTemplate('SubmissionDetailPage_Edit');
        
        return [
            'Submission' => $this->submission,
            'EditForm' => $this->EditForm(),
            'IsEditMode' => true
        ];
    }
    
    public function EditForm()
    {
        if (!$this->submission) {
            return null;
        }
        
        $fields = FieldList::create(
            HiddenField::create('SubmissionID', '', $this->submission->ID),
            
            // Property Information
            LiteralField::create(
                'SectionHeader1',
                '<h3 class="section-header">Property Information</h3>'
            ),
            
            TextareaField::create('PropertyAddress', 'Property Address')
                ->setRows(3)
                ->setValue($this->submission->PropertyAddress)
                ->addExtraClass('form-input'),
            
            OptionsetField::create('TransactionType', 'Transaction Type', [
                'Sale' => 'Sale',
                'Lease' => 'Lease'
            ])->setValue($this->submission->TransactionType),
            
            CheckboxSetField::create('Representing', 'Who are you representing?', [
                'Seller' => 'Seller',
                'Buyer' => 'Buyer',
                'Landlord' => 'Landlord',
                'Tenant' => 'Tenant'
            ])->setValue($this->submission->getRepresentingArray()),
            
            // Status Update
            LiteralField::create(
                'SectionHeader2',
                '<h3 class="section-header">Update Status</h3>'
            ),
            
            DropdownField::create('Status', 'Status', [
                'Draft' => 'Draft - Continue Editing Later',
                'Submitted' => 'Submit for Review'
            ])->setValue($this->submission->Status)
                ->setDescription('Changing to "Submit for Review" will notify administrators.')
        );
        
        $actions = FieldList::create(
            FormAction::create('doSave', 'Save Changes')
                ->addExtraClass('btn btn-primary'),
            FormAction::create('cancel', 'Cancel')
                ->addExtraClass('btn btn-secondary')
        );
        
        $validator = RequiredFields::create(['PropertyAddress', 'TransactionType']);
        
        $form = Form::create(
            $this,
            'EditForm',
            $fields,
            $actions,
            $validator
        );
        
        $form->setFormMethod('POST');
        $form->setFormAction($this->Link('save/' . $this->submission->ID));
        
        return $form;
    }
    
    public function doSave($data, $form)
    {
        $submissionID = $data['SubmissionID'] ?? null;
        
        if (!$submissionID) {
            $form->sessionMessage('Submission not found', 'bad');
            return $this->redirectBack();
        }
        
        $member = Security::getCurrentUser();
        $submission = FormSubmission::get()->byID($submissionID);
        
        // Security check
        if (!$submission || $submission->RESNumber !== $member->RESNumber) {
            $form->sessionMessage('You do not have permission to edit this submission', 'bad');
            return $this->redirectBack();
        }
        
        try {
            // Update basic information
            $submission->PropertyAddress = $data['PropertyAddress'];
            $submission->TransactionType = $data['TransactionType'];
            
            // Handle representing data
            if (isset($data['Representing'])) {
                $submission->setRepresentingArray($data['Representing']);
            }
            
            // Update status
            $oldStatus = $submission->Status;
            $newStatus = $data['Status'];
            $submission->Status = $newStatus;
            
            if ($newStatus === 'Submitted' && $oldStatus !== 'Submitted') {
                $submission->SubmittedDate = date('Y-m-d H:i:s');
            }
            
            $submission->write();
            
            // Handle file uploads if any
            $this->handleFileUploads($data, $submission);
            
            // Send notification if status changed to Submitted
            if ($newStatus === 'Submitted' && $oldStatus !== 'Submitted') {
                $this->sendSubmissionEmail($submission);
            }
            
            // Send update notification
            $this->sendUpdateEmail($submission, $member);
            
            $form->sessionMessage('Submission updated successfully!', 'good');
            
            return $this->redirect($this->Link('view/' . $submission->ID));
            
        } catch (\Exception $e) {
            $form->sessionMessage('Error saving submission: ' . $e->getMessage(), 'bad');
            error_log('Submission save error: ' . $e->getMessage());
            return $this->redirectBack();
        }
    }
    
    private function handleFileUploads($data, $submission)
    {
        // Handle document uploads
        $documentFields = [
            'OptionToPurchase' => 'Option To Purchase / Sales Agreement',
            'TenancyAgreement' => 'Tenancy Agreement / Letter Of Intent / Letter Of Offer',
            'CEAAgreement' => 'CEA Agreement',
            'CobrokeAgreement' => 'Co-broke Agreement',
            'CommissionAgreement' => 'Commission Agreement',
            'HDBApproval' => 'HDB Approval Letter',
            'OtherDocuments' => 'Other Documents'
        ];
        
        foreach ($documentFields as $fieldName => $documentType) {
            if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
                $this->uploadDocument($_FILES[$fieldName], $submission, $documentType, $data);
            }
        }
        
        // Handle client info file uploads
        $clientInfoRecords = $submission->ClientInfo();
        foreach ($clientInfoRecords as $clientInfo) {
            $partyType = $clientInfo->PartyType;
            $fieldName = "OwnershipProof_{$partyType}";
            
            if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
                $this->uploadClientInfoFile($clientInfo, $_FILES[$fieldName]);
            }
        }
        
        // Handle AML file uploads
        $amlRecords = $submission->AMLRecords();
        foreach ($amlRecords as $amlRecord) {
            $partyType = $amlRecord->PartyType;
            
            // AML File
            $fieldName = "AMLFile_{$partyType}";
            if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
                $this->uploadAMLFile($amlRecord, $_FILES[$fieldName], 'AMLFileID', 'aml-files');
            }
            
            // Form QCI-D
            $fieldName = "FormQCID_{$partyType}";
            if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
                $this->uploadAMLFile($amlRecord, $_FILES[$fieldName], 'FormQCIDID', 'form-qcid');
            }
        }
    }
    
    private function uploadDocument($fileData, $submission, $documentType, $formData)
    {
        $upload = Upload::create();
        
        try {
            $file = File::create();
            $upload->loadIntoFile($fileData, $file, 'transaction-documents/');
            
            if ($file && $file->exists()) {
                $file->publishSingle();
                
                // Check if document of this type already exists
                $existingDoc = $submission->Documents()->filter([
                    'DocumentType' => $documentType
                ])->first();
                
                if ($existingDoc) {
                    // Update existing document
                    if ($existingDoc->DocumentFileID) {
                        $oldFile = File::get()->byID($existingDoc->DocumentFileID);
                        if ($oldFile) {
                            $oldFile->delete();
                        }
                    }
                    
                    $existingDoc->DocumentFileID = $file->ID;
                    
                    if ($documentType === 'Other Documents' && isset($formData['OtherDocumentsDescription'])) {
                        $existingDoc->Description = $formData['OtherDocumentsDescription'];
                    }
                    
                    $existingDoc->write();
                } else {
                    // Create new document
                    $doc = SubmissionDocument::create();
                    $doc->FormSubmissionID = $submission->ID;
                    $doc->DocumentType = $documentType;
                    $doc->DocumentFileID = $file->ID;
                    
                    if ($documentType === 'Other Documents' && isset($formData['OtherDocumentsDescription'])) {
                        $doc->Description = $formData['OtherDocumentsDescription'];
                    }
                    
                    $doc->write();
                }
                
                return true;
            }
        } catch (\Exception $e) {
            error_log("Error uploading document {$documentType}: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function uploadClientInfoFile($clientInfo, $fileData)
    {
        $upload = Upload::create();
        
        try {
            $file = File::create();
            $upload->loadIntoFile($fileData, $file, 'ownership-proofs/');
            
            if ($file && $file->exists()) {
                $file->publishSingle();
                
                // Delete old file if exists
                if ($clientInfo->OwnershipProofID) {
                    $oldFile = File::get()->byID($clientInfo->OwnershipProofID);
                    if ($oldFile) {
                        $oldFile->delete();
                    }
                }
                
                $clientInfo->OwnershipProofID = $file->ID;
                $clientInfo->write();
                
                return true;
            }
        } catch (\Exception $e) {
            error_log("Error uploading ownership proof: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function uploadAMLFile($amlRecord, $fileData, $fieldName, $folder)
    {
        $upload = Upload::create();
        
        try {
            $file = File::create();
            $upload->loadIntoFile($fileData, $file, $folder . '/');
            
            if ($file && $file->exists()) {
                $file->publishSingle();
                
                // Delete old file if exists
                $oldFileID = $amlRecord->$fieldName;
                if ($oldFileID) {
                    $oldFile = File::get()->byID($oldFileID);
                    if ($oldFile) {
                        $oldFile->delete();
                    }
                }
                
                $amlRecord->$fieldName = $file->ID;
                $amlRecord->write();
                
                return true;
            }
        } catch (\Exception $e) {
            error_log("Error uploading {$fieldName}: " . $e->getMessage());
        }
        
        return false;
    }
    
    private function sendSubmissionEmail($submission)
    {
        $to = [
            'felicia.teo@quinvest-chambers.com.sg',
            'Ian.loh@quinvest-chambers.com.sg',
            'wendy.low@quinvest-chambers.com.sg'
        ];
        
        $subject = "New Form Submission - {$submission->SerialNumber}";
        $body = "<p>A new form has been submitted for review.</p>
                <p><strong>Serial Number:</strong> {$submission->SerialNumber}</p>
                <p><strong>Salesperson:</strong> {$submission->SalespersonName}</p>
                <p><strong>RES Number:</strong> {$submission->RESNumber}</p>
                <p><strong>Property Address:</strong> {$submission->PropertyAddress}</p>
                <p><strong>Transaction Type:</strong> {$submission->TransactionType}</p>
                <p><strong>Submitted Date:</strong> {$submission->SubmittedDate}</p>
                <p>Please review the submission in the <a href=\"https://billing.quinvest-chambers.com.sg/admin/form-submissions/EditForm/field/FormSubmission/item/{$submission->ID}/edit\">admin panel</a>.</p>";
        
        $email = Email::create()
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body);
        
        try {
            $email->send();
        } catch (\Exception $e) {
            error_log("Failed to send submission email: " . $e->getMessage());
        }
    }
    
    private function sendUpdateEmail($submission, $member)
    {
        // Send to admin
        $adminTo = [
            'felicia.teo@quinvest-chambers.com.sg',
            'Ian.loh@quinvest-chambers.com.sg',
            'wendy.low@quinvest-chambers.com.sg'
        ];
        
        $subject = "Form Submission Updated - {$submission->SerialNumber}";
        $body = "<p>The following form submission has been updated:</p>
                <p><strong>Serial Number:</strong> {$submission->SerialNumber}</p>
                <p><strong>Salesperson:</strong> {$submission->SalespersonName}</p>
                <p><strong>RES Number:</strong> {$submission->RESNumber}</p>
                <p><strong>Updated By:</strong> {$member->FirstName} {$member->Surname}</p>
                <p><strong>Updated Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p><strong>Status:</strong> {$submission->Status}</p>
                <p>Please review the updated submission in the <a href=\"https://billing.quinvest-chambers.com.sg/admin/form-submissions/EditForm/field/FormSubmission/item/{$submission->ID}/edit\">admin panel</a>.</p>";
        
        $adminEmail = Email::create()
            ->setTo($adminTo)
            ->setSubject($subject)
            ->setBody($body);
        
        // Send to user
        $userSubject = "Your Form Submission Has Been Updated - {$submission->SerialNumber}";
        $userBody = "<p>Dear {$member->FirstName},</p>
                    <p>Your form submission has been successfully updated.</p>
                    <p><strong>Submission Details:</strong></p>
                    <p><strong>Serial Number:</strong> {$submission->SerialNumber}</p>
                    <p><strong>Property Address:</strong> {$submission->PropertyAddress}</p>
                    <p><strong>Transaction Type:</strong> {$submission->TransactionType}</p>
                    <p><strong>Status:</strong> {$submission->Status}</p>
                    <p><strong>Updated:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p>You can view your submission at any time by visiting your <a href=\"https://billing.quinvest-chambers.com.sg/dashboard\">dashboard</a>.</p>
                    <p>Thank you,<br>Quinvest Chambers</p>";
        
        $userEmail = Email::create()
            ->setTo($member->Email)
            ->setSubject($userSubject)
            ->setBody($userBody);
        
        try {
            $adminEmail->send();
            $userEmail->send();
        } catch (\Exception $e) {
            error_log("Failed to send update emails: " . $e->getMessage());
        }
    }
    
    public function cancel($data, $form)
    {
        $submissionID = $data['SubmissionID'] ?? null;
        
        if ($submissionID) {
            return $this->redirect($this->Link('view/' . $submissionID));
        }
        
        return $this->redirect('/dashboard');
    }
    
    // Renamed from flashMessage to setFlashMessage to avoid conflicts
    public function setFlashMessage($message, $type = 'good')
    {
        $session = $this->getRequest()->getSession();
        $session->set("FlashMessage", [
            'Message' => $message,
            'Type' => $type
        ]);
    }
    
    public function getFlashMessage()
    {
        $session = $this->getRequest()->getSession();
        $message = $session->get("FlashMessage");
        $session->clear("FlashMessage");
        return $message;
    }
}