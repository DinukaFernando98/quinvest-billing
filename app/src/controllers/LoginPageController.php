<?php

namespace Quinvest\Controllers;

use PageController;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use SilverStripe\Security\Security;

class LoginPageController extends PageController
{
    private static $allowed_actions = [
        'LoginForm',
    ];

    public function LoginForm()
    {
        return Security::singleton()->getDefaultLoginForm();
    }
}