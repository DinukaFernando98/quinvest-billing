<?php

use SilverStripe\ORM\DB;

class LoginPage extends Page
{
    private static $table_name = 'LoginPage';
    private static $controller_name = 'PageController';
    private static $description = 'Login page for the form submission portal';

    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        if (!LoginPage::get()->first()) {
            $page = LoginPage::create();
            $page->Title = 'Login';
            $page->URLSegment = 'login';
            $page->ParentID = 0;
            $page->ShowInMenus = false;
            $page->ShowInSearch = false;
            $page->write();
            $page->publishSingle();
            DB::alteration_message('Created "Login" page', 'created');
        }
    }
}
