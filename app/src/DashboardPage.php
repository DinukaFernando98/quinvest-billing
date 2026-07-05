<?php

use SilverStripe\ORM\DB;

class DashboardPage extends \Page
{
    private static $table_name = 'DashboardPage';
    private static $controller_name = 'PageController';
    private static $description = 'Past submissions dashboard for logged-in users';

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        if (!DashboardPage::get()->first()) {
            $page = DashboardPage::create();
            $page->Title = 'Past Submissions';
            $page->URLSegment = 'dashboard';
            $page->ParentID = 0;
            $page->ShowInMenus = false;
            $page->ShowInSearch = false;
            $page->write();
            $page->publishSingle();
            DB::alteration_message('Created "Past Submissions" page', 'created');
        }
    }
}
