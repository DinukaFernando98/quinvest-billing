<?php

namespace App\Admin;

use App\Model\FormSubmission;
use App\Model\ClientInfo;
use App\Model\AMLRecord;
use App\Model\SubmissionDocument;
use SilverStripe\Admin\ModelAdmin;

class FormSubmissionAdmin extends ModelAdmin
{
    private static $managed_models = [
        FormSubmission::class
    ];
    
    private static $url_segment = 'form-submissions';
    
    private static $menu_title = 'Form Submissions';
    
    private static $menu_icon_class = 'font-icon-p-alt';
}