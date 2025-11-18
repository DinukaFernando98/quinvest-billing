<?php

namespace App\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;

class AMLRecord extends DataObject
{
    private static $table_name = 'AMLRecord';
    
    private static $db = [
        'PartyType' => 'Varchar(100)',
        'AMLCompleted' => 'Boolean',
        'FormBSection2Checked' => 'Boolean',
        'OtherPartyRepresented' => 'Boolean',
        'UCPType' => 'Enum("Individual,Entity")',
        'UCPActingType' => 'Varchar(255)',
        'ECDDRequired' => 'Boolean',
        'EAApprovalObtained' => 'Boolean',
    ];
    
    private static $has_one = [
        'FormSubmission' => FormSubmission::class,
        'AMLFile' => File::class,
        'FormQCID' => File::class,
        // Remove ECDDForm and FormQCIB single file relationships
    ];
    
    private static $has_many = [
        // 'RequiredForms' => RequiredForm::class,
        'ECDDForms' => ECDDForm::class, // Use this for both ECDD Form and Form QCI-B
        'UCPForms' => UCPForm::class,
    ];
    
    private static $owns = [
        'AMLFile',
        'FormQCID',
    ];
    
    private static $summary_fields = [
        'PartyType' => 'Party Type',
        'AMLCompleted.Nice' => 'AML Completed',
        'ECDDRequired.Nice' => 'ECDD Required',
    ];
}