<?php

namespace App\Extension;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\PasswordField;

class MemberExtension extends DataExtension
{
    private static $db = [
        'RESNumber' => 'Varchar(100)'
    ];

    private static $indexes = [
        'RESNumber' => true
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.Main',
            TextField::create('RESNumber', 'RES Number')
                ->setDescription('Real Estate Salesperson Number'),
            'Email'
        );

        $fields->addFieldToTab(
            'Root.Main',
            PasswordField::create('NewPassword', 'Set New Password')
                ->setDescription('Leave blank to keep the current password. Fill in to change the password for this user.')
        );
    }

    public function onBeforeWrite()
    {
        $newPassword = $this->owner->record['NewPassword'] ?? null;
        if ($newPassword) {
            $this->owner->changePassword($newPassword);
        }
    }
}