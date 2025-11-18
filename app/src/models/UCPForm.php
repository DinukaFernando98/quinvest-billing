<?php

namespace App\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;

class UCPForm extends DataObject
{
    private static $table_name = 'UCPForm';
    
    private static $db = [
        'FormType' => 'Varchar(100)',
    ];
    
    private static $has_one = [
        'AMLRecord' => AMLRecord::class,
        'FormFile' => File::class,
    ];
    
    private static $owns = [
        'FormFile',
    ];
    
    private static $summary_fields = [
        'FormType' => 'Form Type',
        'FormFile.Name' => 'File Name',
    ];
}