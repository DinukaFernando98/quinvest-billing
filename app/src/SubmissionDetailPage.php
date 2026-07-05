<?php

namespace App\Pages;

use Page;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;

class SubmissionDetailPage extends Page
{
    private static $table_name = 'SubmissionDetailPage';
    
    private static $description = 'Page for viewing and editing form submissions';
    
    private static $icon_class = 'font-icon-p-alt';
    
    private static $allowed_children = [];
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Content');
        $fields->removeByName('Metadata');
        
        return $fields;
    }
}