<?php

namespace App\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Assets\File;
use SilverStripe\ORM\HasManyList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\LiteralField;

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
        'Status' => 'Enum("Draft,Submitted,Approved,Declined","Draft")',
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
        'SubmittedDate',
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

        // Files tab — all downloadable files in one place
        $filesHTML = '<table style="width:100%;border-collapse:collapse;">'
            . '<thead><tr style="background:#f0f0f0;">'
            . '<th style="padding:8px;text-align:left;border:1px solid #ddd;">Category</th>'
            . '<th style="padding:8px;text-align:left;border:1px solid #ddd;">File</th>'
            . '<th style="padding:8px;text-align:left;border:1px solid #ddd;">Download</th>'
            . '</tr></thead><tbody>';

        // Client info files
        foreach ($this->ClientInfo() as $clientInfo) {
            $party = htmlspecialchars($clientInfo->PartyType);
            $fileFields = [
                'OwnershipProof' => 'Ownership Proof',
                'FormA1File'     => 'Form A1',
                'FormA2File'     => 'Form A2',
                'FormA3File'     => 'Form A3',
                'FormA4File'     => 'Form A4',
                'FormBFile'      => 'Form B',
            ];
            foreach ($fileFields as $relation => $label) {
                $fileID = $clientInfo->{$relation . 'ID'};
                if ($fileID) {
                    $file = File::get()->byID($fileID);
                    if ($file && $file->exists()) {
                        $filesHTML .= '<tr><td style="padding:6px;border:1px solid #ddd;">' . $party . ' — ' . $label . '</td>'
                            . '<td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($file->Name) . '</td>'
                            . '<td style="padding:6px;border:1px solid #ddd;"><a href="' . $file->getURL() . '" target="_blank" download>Download</a></td></tr>';
                    }
                }
            }
        }

        // AML record files
        foreach ($this->AMLRecords() as $aml) {
            $party = htmlspecialchars($aml->PartyType);
            if ($aml->AMLFileID) {
                $file = File::get()->byID($aml->AMLFileID);
                if ($file && $file->exists()) {
                    $filesHTML .= '<tr><td style="padding:6px;border:1px solid #ddd;">' . $party . ' — AML File</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($file->Name) . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;"><a href="' . $file->getURL() . '" target="_blank" download>Download</a></td></tr>';
                }
            }
            if ($aml->FormQCIDID) {
                $file = File::get()->byID($aml->FormQCIDID);
                if ($file && $file->exists()) {
                    $filesHTML .= '<tr><td style="padding:6px;border:1px solid #ddd;">' . $party . ' — Form QCI-D</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($file->Name) . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;"><a href="' . $file->getURL() . '" target="_blank" download>Download</a></td></tr>';
                }
            }
            foreach ($aml->UCPForms() as $ucp) {
                if ($ucp->FormFile() && $ucp->FormFile()->exists()) {
                    $filesHTML .= '<tr><td style="padding:6px;border:1px solid #ddd;">' . $party . ' — UCP Form</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($ucp->FormFile()->Name) . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;"><a href="' . $ucp->FormFile()->getURL() . '" target="_blank" download>Download</a></td></tr>';
                }
            }
            foreach ($aml->ECDDForms() as $ecdd) {
                if ($ecdd->FormFile() && $ecdd->FormFile()->exists()) {
                    $label = $ecdd->FormType === 'FormQCIB' ? 'Form QCI-B' : 'ECDD Form';
                    $filesHTML .= '<tr><td style="padding:6px;border:1px solid #ddd;">' . $party . ' — ' . $label . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($ecdd->FormFile()->Name) . '</td>'
                        . '<td style="padding:6px;border:1px solid #ddd;"><a href="' . $ecdd->FormFile()->getURL() . '" target="_blank" download>Download</a></td></tr>';
                }
            }
        }

        // Transaction documents
        foreach ($this->Documents() as $doc) {
            if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                $filesHTML .= '<tr><td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($doc->DocumentType) . '</td>'
                    . '<td style="padding:6px;border:1px solid #ddd;">' . htmlspecialchars($doc->DocumentFile()->Name) . '</td>'
                    . '<td style="padding:6px;border:1px solid #ddd;"><a href="' . $doc->DocumentFile()->getURL() . '" target="_blank" download>Download</a></td></tr>';
            }
        }

        $filesHTML .= '</tbody></table>';

        $fields->addFieldToTab('Root.Files', LiteralField::create('AllFiles', $filesHTML));

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