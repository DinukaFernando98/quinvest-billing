<?php

namespace App\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;

class RequiredForm extends DataObject
{
    private static $table_name = 'RequiredForm';
    
    private static $db = [
        'FormType' => 'Varchar(100)',
        'FormNumber' => 'Int',
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
        'FormNumber' => 'Form Number',
        'FormFile.Name' => 'File Name',
    ];
}