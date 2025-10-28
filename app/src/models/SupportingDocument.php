<?php

namespace Quinvest\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\File;

class SupportingDocument extends DataObject
{
    private static $table_name = 'SupportingDocument';

    private static $db = [
        'Title' => 'Varchar(255)',
    ];

    private static $has_one = [
        'Document' => File::class,
        'FormSubmission' => FormSubmission::class,
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Document.Name' => 'File Name',
    ];

    private static $owns = [
        'Document',
    ];
}