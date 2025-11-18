<?php

namespace App\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Assets\File;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;

class FormSubmission extends DataObject
{
    private static $table_name = 'FormSubmission';
    
    private static $db = [
        'SerialNumber' => 'Varchar(100)',
        'SalespersonName' => 'Varchar(255)',
        'RESNumber' => 'Varchar(100)',
        'PropertyAddress' => 'Text',
        'TransactionType' => 'Enum("Sale,Lease")',
        'Representing' => 'Text', // JSON encoded array
        'Status' => 'Enum("Draft,Submitted,Approved,Rejected","Draft")',
        'SubmittedDate' => 'Datetime',
    ];
    
    private static $has_one = [
        'SubmittedBy' => Member::class,
    ];
    
    private static $has_many = [
        'ClientInfo' => ClientInfo::class,
        'AMLRecords' => AMLRecord::class,
        'Documents' => SubmissionDocument::class,
    ];
    
    private static $summary_fields = [
        'SerialNumber' => 'Serial Number',
        'SalespersonName' => 'Salesperson',
        'RESNumber' => 'RES Number',
        'TransactionType' => 'Transaction Type',
        'Status' => 'Status',
        'SubmittedDate.Nice' => 'Submitted Date',
    ];
    
    private static $searchable_fields = [
        'SerialNumber',
        'SalespersonName',
        'RESNumber',
        'TransactionType',
        'Status',
    ];
    
    private static $default_sort = 'Created DESC';
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
        $fields->addFieldToTab('Root.ClientInfo', 
            GridField::create(
                'ClientInfo',
                'Client Information',
                $this->ClientInfo(),
                GridFieldConfig_RecordEditor::create()
            )
        );
        
        $fields->addFieldToTab('Root.AMLRecords', 
            GridField::create(
                'AMLRecords',
                'AML Records',
                $this->AMLRecords(),
                GridFieldConfig_RecordEditor::create()
            )
        );
        
        $fields->addFieldToTab('Root.Documents', 
            GridField::create(
                'Documents',
                'Documents',
                $this->Documents(),
                GridFieldConfig_RecordEditor::create()
            )
        );
        
        return $fields;
    }
    
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        
        if (!$this->SerialNumber) {
            $this->SerialNumber = $this->generateSerialNumber();
        }
        
        if (!$this->SubmittedDate && $this->Status === 'Submitted') {
            $this->SubmittedDate = date('Y-m-d H:i:s');
        }
    }
    
    private function generateSerialNumber()
    {
        $timestamp = time();
        $random = rand(1000, 9999);
        return "QIC-{$timestamp}-{$random}";
    }
    
    public function getRepresentingArray()
    {
            if (empty($this->Representing)) {
        return [];
    }

    $decoded = json_decode($this->Representing, true);

    return is_array($decoded) ? $decoded : [];
    }
    
    public function setRepresentingArray($array)
    {
        $this->Representing = json_encode($array);
    }
}