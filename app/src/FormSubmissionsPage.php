<?php

use SilverStripe\ORM\DB;

class FormSubmissionsPage extends Page
{
    private static $table_name = 'FormSubmissionsPage';
    private static $controller_name = 'PageController';
    private static $description = 'Multi-step form submissions page';

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        if (!FormSubmissionsPage::get()->first()) {
            $page = FormSubmissionsPage::create();
            $page->Title = 'Form Submissions';
            $page->URLSegment = 'form-submissions';
            $page->ParentID = 0;
            $page->ShowInMenus = true;
            $page->ShowInSearch = false;
            $page->write();
            $page->publishSingle();
            DB::alteration_message('Created "Form Submissions" page', 'created');
        }
    }
}
