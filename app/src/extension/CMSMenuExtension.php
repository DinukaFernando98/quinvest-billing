<?php

namespace App\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Admin\CMSMenu;

class CMSMenuExtension extends Extension
{
    public function init()
    {
        // Remove specific admin interfaces
        CMSMenu::remove_menu_class('SilverStripe\\CampaignAdmin\\CampaignAdmin');
        CMSMenu::remove_menu_class('SilverStripe\\VersionedAdmin\\ArchiveAdmin');
        CMSMenu::remove_menu_class('SilverStripe\\Reports\\ReportAdmin');
        
        // Or remove by URL segment
        CMSMenu::remove_menu_item('campaigns');
        CMSMenu::remove_menu_item('archive');
        CMSMenu::remove_menu_item('reports');
    }
}