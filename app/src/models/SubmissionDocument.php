<?php

namespace App\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;

class SubmissionDocument extends DataObject
{
    private static $table_name = 'SubmissionDocument';
    
    private static $db = [
        'DocumentType' => 'Varchar(255)',
        'Description' => 'Text',
    ];
    
    private static $has_one = [
        'FormSubmission' => FormSubmission::class,
        'DocumentFile' => File::class,
    ];
    
    private static $owns = [
        'DocumentFile',
    ];
    
    private static $summary_fields = [
        'DocumentType' => 'Document Type',
        'DocumentFile.Name' => 'File Name',
        'Description' => 'Description',
    ];
    
    private static $searchable_fields = [
        'DocumentType',
        'Description',
    ];
}