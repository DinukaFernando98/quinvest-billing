<?php

namespace Quinvest\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Assets\File;
use SilverStripe\AssetAdmin\Forms\UploadField;

class FormSubmission extends DataObject
{
    private static $table_name = 'FormSubmission';

    private static $db = [
        'SerialNumber' => 'Varchar(255)',
        'CurrentStep' => 'Int',
        
        // Step 1: Basic Information
        'Salesperson' => 'Varchar(255)',
        'RESNumber' => 'Varchar(255)',
        'PropertyAddress' => 'Text',
        
        // Step 2: Amicus
        'UsedAmicus' => 'Varchar(10)',
        
        // Step 3: Transaction Type
        'TransactionType' => 'Varchar(50)', // sale or lease
        
        // Step 4: Representation
        'Representation' => 'Varchar(50)', // seller/buyer or landlord/tenant
        
        // Step 5: Client Type
        'ClientType' => 'Varchar(100)',
        
        // Step 6: AML Search
        'AMLSearchConducted' => 'Varchar(10)',
        
        // Step 7: Form B Check
        'FormBCheckedYes' => 'Varchar(10)',
        
        // Step 8: Other Party
        'OtherPartyRepresented' => 'Varchar(10)',
        
        // Step 9: Other Party Type
        'OtherPartyType' => 'Varchar(100)',
        
        // Step 10: ECDD
        'ECDDWarranted' => 'Varchar(10)',
        'EAApprovalObtained' => 'Varchar(10)',
        
        // Step 11: Sale Documents
        'HasOTP' => 'Varchar(10)',
        'HasCEAAgreementSale' => 'Varchar(10)',
        'HasCobrokeSale' => 'Varchar(10)',
        'HasCommissionSale' => 'Varchar(10)',
        'HasOwnershipSale' => 'Varchar(10)',
        
        // Step 12: Lease Documents
        'HasTenancyAgreement' => 'Varchar(10)',
        'HasCEAAgreementLease' => 'Varchar(10)',
        'HasCobrokeLease' => 'Varchar(10)',
        'HasCommissionLease' => 'Varchar(10)',
        'HasHDBApproval' => 'Varchar(10)',
        'HasOwnershipLease' => 'Varchar(10)',
        'HasAuthorizationLetter' => 'Varchar(10)',
        
        // Step 13: Other Documents
        'HasOtherDocuments' => 'Varchar(10)',
        'OtherDocumentsDescription' => 'Text',
        
        // Billing
        'BillingSubmitted' => 'Boolean',
        'BillingFileName' => 'Varchar(255)',
        
        // Status
        'Status' => 'Varchar(50)', // draft, submitted, completed
        'SubmittedDate' => 'Datetime',
    ];

    private static $has_one = [
        'Member' => Member::class,
        
        // File uploads - Step 2
        'AmicusScreenshot' => File::class,
        
        // File uploads - Step 6
        'AMLFile' => File::class,
        
        // File uploads - Step 7
        'FormQCID' => File::class,
        
        // File uploads - Step 10
        'ECDDForm' => File::class,
        'FormQCIB' => File::class,
        
        // File uploads - Step 11 (Sale)
        'OTPFile' => File::class,
        'CEAAgreementSaleFile' => File::class,
        'CobrokeSaleFile' => File::class,
        'CommissionSaleFile' => File::class,
        'OwnershipSaleFile' => File::class,
        
        // File uploads - Step 12 (Lease)
        'TenancyAgreementFile' => File::class,
        'CEAAgreementLeaseFile' => File::class,
        'CobrokeLeaseFile' => File::class,
        'CommissionLeaseFile' => File::class,
        'HDBApprovalFile' => File::class,
        'OwnershipLeaseFile' => File::class,
        'AuthorizationLetterFile' => File::class,
        
        // Billing Form
        'BillingFormFile' => File::class,
    ];

    private static $has_many = [
        'SupportingDocuments' => SupportingDocument::class,
        'OtherDocuments' => OtherDocument::class,
    ];

    private static $summary_fields = [
        'SerialNumber' => 'Serial Number',
        'Member.Email' => 'User Email',
        'Salesperson' => 'Salesperson',
        'PropertyAddress' => 'Property Address',
        'TransactionType' => 'Transaction Type',
        'Status' => 'Status',
        'SubmittedDate.Nice' => 'Submitted Date',
    ];

    private static $searchable_fields = [
        'SerialNumber',
        'Salesperson',
        'RESNumber',
        'TransactionType',
        'Status',
        'Member.Email',
    ];

    private static $default_sort = 'Created DESC';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
        $fields->addFieldsToTab('Root.Main', [
            \SilverStripe\Forms\ReadonlyField::create('SerialNumber', 'Serial Number'),
            \SilverStripe\Forms\ReadonlyField::create('Status', 'Status'),
            \SilverStripe\Forms\ReadonlyField::create('SubmittedDate', 'Submitted Date'),
        ]);

        return $fields;
    }

    public function canView($member = null)
    {
        return true;
    }

    public function canEdit($member = null)
    {
        return \SilverStripe\Security\Permission::check('CMS_ACCESS_FormSubmissionAdmin', 'any', $member);
    }

    public function canDelete($member = null)
    {
        return \SilverStripe\Security\Permission::check('CMS_ACCESS_FormSubmissionAdmin', 'any', $member);
    }

    public function canCreate($member = null, $context = [])
    {
        return true;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        // Generate serial number if not exists
        if (!$this->SerialNumber) {
            $this->SerialNumber = 'SN-' . time() . '-' . strtoupper(substr(md5(uniqid()), 0, 9));
        }
        
        // Set submitted date when status changes to submitted
        if ($this->Status === 'submitted' && !$this->SubmittedDate) {
            $this->SubmittedDate = date('Y-m-d H:i:s');
        }
    }

    public function getTitle()
    {
        return $this->SerialNumber . ' - ' . $this->Salesperson;
    }
}