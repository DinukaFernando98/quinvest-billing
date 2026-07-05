<?php

namespace {

    use App\Model\BillingFormSubmission;
    use SilverStripe\CMS\Controllers\ContentController;
    use SilverStripe\Forms\RequiredFields;
    use SilverStripe\ORM\ArrayList;
    use SilverStripe\Security\Security;
    use SilverStripe\Security\Member;
    use SilverStripe\Control\HTTPRequest;
    use SilverStripe\Control\HTTPResponse;
    use SilverStripe\Forms\Form;
    use SilverStripe\Forms\FieldList;
    use SilverStripe\Forms\TextField;
    use SilverStripe\Forms\TextareaField;
    use SilverStripe\Forms\DropdownField;
    use SilverStripe\Forms\OptionsetField;
    use SilverStripe\Forms\CheckboxSetField;
    use SilverStripe\Forms\FileField;
    use SilverStripe\Forms\HiddenField;
    use SilverStripe\Forms\FormAction;
    use SilverStripe\Forms\CompositeField;
    use SilverStripe\Forms\LiteralField;
    use SilverStripe\Forms\PasswordField;
    use SilverStripe\AssetAdmin\Forms\UploadField;
    use SilverStripe\Assets\File;
    use SilverStripe\Assets\Upload;
    use SilverStripe\ORM\ValidationException;
    use App\Model\FormSubmission;
    use App\Model\ClientInfo;
    use App\Model\AMLRecord;
    use App\Model\SubmissionDocument;
    use App\Model\RequiredForm;
    use App\Model\ECDDForm;
    use App\Model\UCPForm;
    use App\Email\ResendEmail;
    use SilverStripe\Core\Injector\Injector;
    use SilverStripe\View\Requirements;
    use SilverStripe\View\SSViewer;
    use SilverStripe\View\ThemeResourceLoader;
    use SilverStripe\Security\IdentityStore;
    use SilverStripe\Security\PasswordValidator;
    use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;

    /**
     * @template T of Page
     * @extends ContentController<T>
     */
    class PageController extends ContentController
    {
        private static $allowed_actions = [
            'MultiStepForm',
            'LoginForm',
            'RegistrationForm',
            'doLogin',
            'doRegister',
            'nextStep',
            'previousStep',
            'submitForm',
            'saveStep',
            'submitFinalForm',
            'logout',
            'BillingForm',
            'submitBillingForm',
            'updateBillingForm',
            'dashboard',
            'viewSubmission',
            'downloadFile',
            'getSubmissionDetail'
        ];

        protected function init()
        {
            parent::init();

            $theme = SSViewer::get_themes();
            $theme = reset($theme);

            Requirements::css("public/resources/themes/quinvest/css/main.css");
            Requirements::javascript("public/resources/themes/quinvest/js/main.js");

            // Handle step parameter from URL
            $step = $this->getRequest()->getVar('step');
            if ($step && is_numeric($step)) {
                $step = (int)$step;
                if ($step >= 1 && $step <= 5) {
                    $this->getRequest()->getSession()->set('FormStep', $step);
                }
            }

            // Require login to access the form submissions page
            if ($this->IsFormPage() && !$this->IsLoggedIn()) {
                return $this->redirect('/login');
            }

            // Logged-in users don't need the login page
            if ($this->IsLoginPage() && $this->IsLoggedIn()) {
                return $this->redirect('/form-submissions');
            }
        }

        public function logout()
        {
            $member = Security::getCurrentUser();
            if ($member) {
                Security::setCurrentUser(null);
            }
            $this->getRequest()->getSession()->clear('FormSubmissionID');
            $this->getRequest()->getSession()->clear('FormStep');
            return $this->redirect('/');
        }

        public function getCurrentMember()
        {
            return Security::getCurrentUser();
        }

        public function IsFormPage()
        {
            return $this->request->getURL() === 'form-submissions';
        }

        public function IsLoginPage()
        {
            return $this->request->getURL() === 'login';
        }

        public function IsHomePage()
        {
            return $this->request->getURL() === '';
        }

        public function IsGuidelinesPage()
        {
            return $this->request->getURL() === 'guidelines';
        }

        public function IsBillingPage()
        {
            return strpos($this->request->getURL(), 'billing') !== false;
        }

        public function IsDashboardPage()
        {
            $url = $this->request->getURL();
            return strpos($url, 'dashboard') !== false
                || strpos($url, 'edit-submission') !== false
                || strpos($url, 'submission-details') !== false;
        }

        public function IsLoggedIn()
        {
            return Security::getCurrentUser() !== null;
        }

        /**
         * Login Form
         */
        public function LoginForm()
        {
            $fields = FieldList::create(
                TextField::create('RESNumber', 'RES Number')
                    ->setAttribute('placeholder', 'Enter your RES number')
                    ->setAttribute('autocomplete', 'username')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required'),

                PasswordField::create('Password', 'Password')
                    ->setAttribute('placeholder', 'Enter your password')
                    ->setAttribute('autocomplete', 'current-password')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required')
            );

            $actions = FieldList::create(
                FormAction::create('doLogin', 'Login')
                    ->addExtraClass('btn btn-primary btn-block')
                    ->setUseButtonTag(true)
            );

            $validator = RequiredFields::create('RESNumber', 'Password');

            $form = Form::create($this, 'LoginForm', $fields, $actions, $validator);
            $form->setFormMethod('POST');
            $form->addExtraClass('login-form');

            return $form;
        }

        /**
         * Registration Form
         */
        public function RegistrationForm()
        {
            $fields = FieldList::create(
                TextField::create('FirstName', 'First Name')
                    ->setAttribute('placeholder', 'Enter your first name')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required'),

                TextField::create('Surname', 'Last Name')
                    ->setAttribute('placeholder', 'Enter your last name')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required'),

                TextField::create('RESNumber', 'RES Number')
                    ->setAttribute('placeholder', 'Enter your RES number')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required'),

                PasswordField::create('Password', 'Password')
                    ->setAttribute('placeholder', 'Enter your password (min 8 characters)')
                    ->setAttribute('autocomplete', 'new-password')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required'),

                PasswordField::create('ConfirmPassword', 'Confirm Password')
                    ->setAttribute('placeholder', 'Confirm your password')
                    ->setAttribute('autocomplete', 'new-password')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required')
            );

            $actions = FieldList::create(
                FormAction::create('doRegister', 'Create Account')
                    ->addExtraClass('btn btn-primary btn-block')
                    ->setUseButtonTag(true)
            );

            $validator = RequiredFields::create('FirstName', 'Surname', 'RESNumber', 'Password', 'ConfirmPassword');

            $form = Form::create($this, 'RegistrationForm', $fields, $actions, $validator);
            $form->setFormMethod('POST');
            $form->addExtraClass('registration-form');

            return $form;
        }

        /**
         * Handle Login
         */
        public function doLogin($data, $form)
        {
            // Find member by RES Number
            $member = Member::get()->filter('RESNumber', $data['RESNumber'])->first();

            if (!$member) {
                $form->sessionMessage('*Invalid RES number or password, please try again!', 'bad');
                return $this->redirectBack();
            }

            if (!password_verify($data['Password'], $member->Password)) {
                $form->sessionMessage('*Invalid RES number or password, please try again!', 'bad');
                return $this->redirectBack();
            }

            // Log in the member
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($member, false, $this->getRequest());

            $form->sessionMessage('Login successful!', 'good');

            // Redirect to the form submissions page, starting at step 1
            $this->getRequest()->getSession()->set('FormStep', 1);
            return $this->redirect('/form-submissions');
        }

        public function LogoutURL()
        {
            return Security::logout_url() . "&BackURL=/";
        }

        /**
         * Handle Registration
         */
        public function doRegister($data, $form)
        {
            // Check if passwords match
            if ($data['Password'] !== $data['ConfirmPassword']) {
                $form->sessionMessage('Passwords do not match', 'bad');
                return $this->redirectBack();
            }

            // Check password length
            if (strlen($data['Password']) < 8) {
                $form->sessionMessage('Password must be at least 8 characters long', 'bad');
                return $this->redirectBack();
            }

            // Check if RES Number already exists
            $existingMember = Member::get()->filter('RESNumber', $data['RESNumber'])->first();
            if ($existingMember) {
                $form->sessionMessage('A user with this RES Number already exists', 'bad');
                return $this->redirectBack();
            }

            // Create new member
            $member = Member::create();
            $member->FirstName = $data['FirstName'];
            $member->Surname = $data['Surname'];
            $member->RESNumber = $data['RESNumber'];

            // Set email as RES Number + domain (temporary, can be changed by admin)
            $member->Email = $data['RESNumber'] . '@quinvest.com';

            try {
                $member->write();

                // Set password (this will hash it automatically)
                $member->changePassword($data['Password']);

                // Log in the new member
                $identityStore = Injector::inst()->get(IdentityStore::class);
                $identityStore->logIn($member, false, $this->getRequest());

                $this->getRequest()->getSession()->set('FormStep', 1);
                return $this->redirect('/sign-up?registered=1');
            } catch (ValidationException $e) {
                $form->sessionMessage('Error creating account: ' . $e->getMessage(), 'bad');
                return $this->redirectBack();
            }
        }

        public function BillingForm(): Form
        {
            // Check whether we're editing an existing billing submission owned by this member
            $existingBilling = null;
            $editID = $this->getRequest()->getVar('edit');
            if ($editID) {
                $member = Security::getCurrentUser();
                if ($member) {
                    $existingBilling = BillingFormSubmission::get()
                        ->filter(['ID' => $editID, 'UploadedByID' => $member->ID])
                        ->first();
                }
            }

            $fields = FieldList::create(
                TextField::create('SerialNumber', 'Serial Number')
                    ->setAttribute('placeholder', 'Enter your submission serial number')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required'),

                FileField::create('BillingFile', 'Upload Billing Form')
                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx')
                    ->addExtraClass('form-input')
            );

            $actionName = 'submitBillingForm';
            $actionLabel = 'Submit Billing Form';

            if ($existingBilling) {
                $fields->dataFieldByName('SerialNumber')->setValue($existingBilling->SerialNumber);
                $fields->dataFieldByName('BillingFile')
                    ->setTitle('Replace Billing Form')
                    ->setRightTitle('Leave empty to keep the currently uploaded file');

                if ($existingBilling->BillingFile()->exists()) {
                    $fields->insertBefore('BillingFile', LiteralField::create('ExistingBillingFile', sprintf(
                        '<p class="existing-file-notice">Current file: <a href="%s" target="_blank">%s</a></p>',
                        $existingBilling->BillingFile()->getURL(),
                        $existingBilling->BillingFile()->Name
                    )));
                }

                $fields->push(HiddenField::create('BillingSubmissionID', '', $existingBilling->ID));

                $actionName = 'updateBillingForm';
                $actionLabel = 'Update Billing Form';
            }

            $actions = FieldList::create(
                FormAction::create($actionName, $actionLabel)
                    ->addExtraClass('btn btn-success')
                    ->setUseButtonTag(true)
            );

            $validator = RequiredFields::create('SerialNumber');

            $form = Form::create($this, 'BillingForm', $fields, $actions, $validator);
            $form->setFormMethod('POST');
            $form->setEncType(Form::ENC_TYPE_MULTIPART);
            $form->addExtraClass('multi-step-form billing-form');
            $form->setAttribute('data-step', 'billing');

            return $form;
        }

        public function submitBillingForm($data, $form)
        {
            ini_set('max_execution_time', 120);
            ini_set('memory_limit', '256M');

            $serial = $data['SerialNumber'] ?? null;

            if (!$serial) {
                $form->sessionMessage('Please provide the serial number.', 'bad');
                return $this->redirectBack();
            }

            $submission = FormSubmission::get()->filter('SerialNumber', $serial)->first();
            if (!$submission) {
                $form->sessionMessage("No matching form submission found for Serial Number: {$serial}", 'bad');
                return $this->redirectBack();
            }

            $existingBilling = BillingFormSubmission::get()->filter('SerialNumber', $serial)->first();
            if ($existingBilling) {
                $form->sessionMessage("A billing form has already been submitted for Serial Number: {$serial}. Please use the Edit button from your dashboard to update it.", 'bad');
                return $this->redirectBack();
            }

            if (!isset($_FILES['BillingFile']) || empty($_FILES['BillingFile']['tmp_name'])) {
                $form->sessionMessage('Please upload a billing file.', 'bad');
                return $this->redirectBack();
            }

            $billing = BillingFormSubmission::create();
            $billing->SerialNumber = $serial;
            $billing->FormSubmissionID = $submission->ID;
            $billing->UploadedByID = Security::getCurrentUser()->ID ?? 0;

            $upload = Upload::create();
            $upload->getValidator()->setAllowedMaxFileSize(['*' => 52428800]);

            try {
                $file = File::create();
                $upload->loadIntoFile($_FILES['BillingFile'], $file, 'billing-forms/');

                if ($file && $file->exists()) {
                    $file->publishSingle();
                    $billing->BillingFileID = $file->ID;
                } else {
                    throw new \Exception('File upload failed');
                }
            } catch (\Exception $e) {
                $form->sessionMessage('File upload failed: ' . $e->getMessage(), 'bad');
                return $this->redirectBack();
            }

            $billing->write();
            $this->sendBillingNotificationEmail($billing);

            $form->sessionMessage('Billing form submitted successfully!', 'good');
            return $this->redirectBack();
        }

        public function updateBillingForm($data, $form)
        {
            ini_set('max_execution_time', 120);
            ini_set('memory_limit', '256M');

            $member = Security::getCurrentUser();
            if (!$member) {
                return $this->redirect('/Security/login?BackURL=' . urlencode($this->getRequest()->getURL()));
            }

            $billingID = $data['BillingSubmissionID'] ?? null;
            if (!$billingID) {
                $form->sessionMessage('Billing submission not found.', 'bad');
                return $this->redirectBack();
            }

            $billing = BillingFormSubmission::get()
                ->filter(['ID' => $billingID, 'UploadedByID' => $member->ID])
                ->first();

            if (!$billing) {
                $form->sessionMessage('Permission denied or billing submission not found.', 'bad');
                return $this->redirectBack();
            }

            $serial = $data['SerialNumber'] ?? null;
            if (!$serial) {
                $form->sessionMessage('Please provide the serial number.', 'bad');
                return $this->redirectBack();
            }

            $submission = FormSubmission::get()->filter('SerialNumber', $serial)->first();
            if (!$submission) {
                $form->sessionMessage("No matching form submission found for Serial Number: {$serial}", 'bad');
                return $this->redirectBack();
            }

            $billing->SerialNumber = $serial;
            $billing->FormSubmissionID = $submission->ID;

            if (isset($_FILES['BillingFile']) && !empty($_FILES['BillingFile']['tmp_name'])) {
                $upload = Upload::create();
                $upload->getValidator()->setAllowedMaxFileSize(['*' => 52428800]);

                try {
                    $file = File::create();
                    $upload->loadIntoFile($_FILES['BillingFile'], $file, 'billing-forms/');

                    if ($file && $file->exists()) {
                        $file->publishSingle();
                        $billing->BillingFileID = $file->ID;
                    } else {
                        throw new \Exception('File upload failed');
                    }
                } catch (\Exception $e) {
                    $form->sessionMessage('File upload failed: ' . $e->getMessage(), 'bad');
                    return $this->redirectBack();
                }
            }

            $billing->write();
            $this->sendBillingUpdateNotificationEmail($billing);

            $form->sessionMessage('Billing form updated successfully! Administrators have been notified.', 'good');
            return $this->redirectBack();
        }

        public function MultiStepForm()
        {
            // Get or create draft submission
            $submissionID = $this->getRequest()->getSession()->get('FormSubmissionID');
            $submission = $submissionID ? FormSubmission::get()->byID($submissionID) : null;

            if (!$submission) {
                $submission = FormSubmission::create();
                $submission->Status = 'Draft';
                $currentMember = Security::getCurrentUser();
                $submission->SalespersonName = $currentMember->FirstName . ' ' . $currentMember->Surname;
                $submission->RESNumber = $currentMember->RESNumber;
                $submission->write();
                $this->getRequest()->getSession()->set('FormSubmissionID', $submission->ID);
            }

            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;

            return $this->createStepForm($currentStep, $submission);
        }

        private function createStepForm($step, $submission)
        {
            $fields = FieldList::create();
            $actions = FieldList::create();
            $required = [];

            // Hidden field for step tracking
            $fields->push(HiddenField::create('CurrentStep', '', $step));
            if ($submission) {
                $fields->push(HiddenField::create('SubmissionID', '', $submission->ID));
            }

            switch ($step) {
                case 1:
                    $fields->push(TextareaField::create('PropertyAddress', 'Address of Property (Full address with postal code)')
                        ->setRows(3)
                        ->setValue($submission->PropertyAddress));

                    $fields->push(OptionsetField::create('TransactionType', 'Transaction Type', [
                        'Sale' => 'Sale',
                        'Lease' => 'Lease'
                    ])->setValue($submission->TransactionType));

                    $representingArray = $submission->getRepresentingArray();
                    $fields->push(OptionsetField::create('Representing', 'Who are you representing?', [
                        'Seller' => 'Seller',
                        'Buyer' => 'Buyer',
                        'Landlord' => 'Landlord',
                        'Tenant' => 'Tenant'
                    ])->setValue($representingArray ? reset($representingArray) : null));

                    $required = ['PropertyAddress', 'TransactionType', 'Representing'];
                    break;

                case 2:
                    $fields = $this->buildStep3Fields($submission);
                    $required = $this->getStep3Required($submission);
                    break;

                case 3:
                    $fields = $this->buildStep4Fields($submission);
                    $required = $this->getStep4Required($submission);
                    break;

                case 4:
                    $fields = $this->buildStep5Fields($submission);
                    $required = $this->getStep5Required($submission);
                    break;

                case 5:
                    $fields = $this->buildStep6Fields($submission);
                    $required = [];
                    break;
            }

            // Add navigation buttons
            if ($step > 1) {
                $actions->push(FormAction::create('previousStep', 'Previous')
                    ->addExtraClass('btn btn-secondary')
                    ->setUseButtonTag(true));
            }

            if ($step < 5) {
                $actions->push(FormAction::create('nextStep', 'Next')
                    ->addExtraClass('btn btn-primary')
                    ->setUseButtonTag(true));
            } elseif ($step == 5) {
                $actions->push(FormAction::create('submitForm', 'Submit Form')
                    ->addExtraClass('btn btn-success')
                    ->setUseButtonTag(true));
            }

            $form = Form::create(
                $this,
                'MultiStepForm',
                $fields,
                $actions,
                RequiredFields::create($required)
            );

            $form->setFormMethod('POST');
            $form->setEncType('multipart/form-data');
            $form->addExtraClass('multi-step-form');
            $form->setAttribute('data-step', $step);

            return $form;
        }

        private function buildStep3Fields($submission)
        {
            $fields = FieldList::create();
            $fields->push(HiddenField::create('CurrentStep', '', 2));
            $fields->push(HiddenField::create('SubmissionID', '', $submission->ID));

            $representing = $submission->getRepresentingArray();

            if (!empty($representing)) {
                foreach ($representing as $index => $party) {
                    $clientInfo = $submission->ClientInfo()->filter(['PartyType' => $party])->first();

                    // Create or get existing client info for file attachment
                    if (!$clientInfo) {
                        $clientInfo = ClientInfo::create();
                        $clientInfo->FormSubmissionID = $submission->ID;
                        $clientInfo->PartyType = $party;
                        $clientInfo->write();
                    }

                    // Check which files already exist for this client
                    $existingFileNames = [];
                    foreach (['OwnershipProofID', 'FormA1FileID', 'FormA2FileID', 'FormA3FileID', 'FormA4FileID', 'FormBFileID'] as $idField) {
                        $existingFileNames[$idField] = '';
                        if ($clientInfo->{$idField}) {
                            $existingFile = File::get()->byID($clientInfo->{$idField});
                            if ($existingFile && $existingFile->exists()) {
                                $existingFileNames[$idField] = $existingFile->Name;
                            }
                        }
                    }

                    // Use FileField instead of UploadField for Ownership Proof
                    $fileField = FileField::create("OwnershipProof_{$party}", 'Ownership Proof')
                        ->setDescription(in_array($party, ['Seller', 'Landlord']) ?
                            'Required (excluding listed companies)' :
                            'Optional - Allowed file types: pdf, jpg, jpeg, png, doc, docx')
                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

                    $wrapperContent = [
                        LiteralField::create(
                            "ClientHeader{$party}",
                            "<h3 class='client-section-title'>{$party} Information</h3>"
                        ),

                        OptionsetField::create("ClientType_{$party}", 'Is your client an:', [
                            'Individual' => 'Individual',
                            'Entity' => 'Entity'
                        ])->setValue($clientInfo ? $clientInfo->ClientType : null),

                        DropdownField::create("ClientActingType_{$party}", 'Select specific type', [
                            '' => '-- Select --',
                            'Individual acting for himself' => 'Individual acting for himself',
                            'Individual acting on behalf of another individual' => 'Individual acting on behalf of another individual',
                            'Individual acting on behalf of another (Entity/Legal arrangement)' => 'Individual acting on behalf of another (Entity/Legal arrangement)',
                            'Entity acting for himself' => 'Entity acting for himself',
                            'Entity acting on behalf of another individual' => 'Entity acting on behalf of another individual',
                            'Entity acting on behalf of another (Entity/Legal arrangement)' => 'Entity acting on behalf of another (Entity/Legal arrangement)',
                        ])->setValue($clientInfo ? $clientInfo->ClientActingTypeSelection : null),

                        LiteralField::create("FormsHeader_{$party}",
                            "<div class='required-forms-header'><strong>Required Forms (shown based on selection above):</strong></div>"
                        ),

                        FileField::create("FormA1File_{$party}", 'Form A1 — Customer Particulars Form (For Individual)')
                            ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'),
                    ];

                    if ($existingFileNames['FormA1FileID']) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingFormA1File_{$party}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                                <strong>Currently uploaded file:</strong> {$existingFileNames['FormA1FileID']}
                                <br><small>Upload a new file to replace the existing one.</small>
                            </div>"
                        );
                    }

                    $wrapperContent[] = FileField::create("FormA2File_{$party}", 'Form A2 — Customer Particulars Form (For Entity/Legal Arrangement)')
                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

                    if ($existingFileNames['FormA2FileID']) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingFormA2File_{$party}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                                <strong>Currently uploaded file:</strong> {$existingFileNames['FormA2FileID']}
                                <br><small>Upload a new file to replace the existing one.</small>
                            </div>"
                        );
                    }

                    $wrapperContent[] = FileField::create("FormA3File_{$party}", 'Form A3 — Particulars of Individual your Client is acting on behalf of')
                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

                    if ($existingFileNames['FormA3FileID']) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingFormA3File_{$party}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                                <strong>Currently uploaded file:</strong> {$existingFileNames['FormA3FileID']}
                                <br><small>Upload a new file to replace the existing one.</small>
                            </div>"
                        );
                    }

                    $wrapperContent[] = FileField::create("FormA4File_{$party}", 'Form A4 — Particulars of Legal Person your Client is acting on behalf of')
                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

                    if ($existingFileNames['FormA4FileID']) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingFormA4File_{$party}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                                <strong>Currently uploaded file:</strong> {$existingFileNames['FormA4FileID']}
                                <br><small>Upload a new file to replace the existing one.</small>
                            </div>"
                        );
                    }

                    $wrapperContent[] = FileField::create("FormBFile_{$party}", 'Form B — Risk Determination and Screening Checklist')
                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

                    if ($existingFileNames['FormBFileID']) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingFormBFile_{$party}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                                <strong>Currently uploaded file:</strong> {$existingFileNames['FormBFileID']}
                                <br><small>Upload a new file to replace the existing one.</small>
                            </div>"
                        );
                    }

                    $wrapperContent[] = $fileField;

                    if ($existingFileNames['OwnershipProofID']) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingFile_{$party}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                                <strong>Currently uploaded file:</strong> {$existingFileNames['OwnershipProofID']}
                                <br><small>Upload a new file to replace the existing one.</small>
                            </div>"
                        );
                    }

                    $wrapperContent[] = HiddenField::create("PartyType_{$party}", '', $party);
                    $wrapperContent[] = HiddenField::create("ClientInfoID_{$party}", '', $clientInfo->ID);

                    $wrapper = CompositeField::create($wrapperContent);

                    $wrapper->addExtraClass('client-section');
                    $fields->push($wrapper);
                }
            }

            return $fields;
        }

        private function getStep3Required($submission)
        {
            $required = [];
            $representing = $submission->getRepresentingArray();

            foreach ($representing as $index => $party) {
                $required[] = "ClientType_{$party}";
                $required[] = "ClientActingType_{$party}";
            }

            return $required;
        }

        private function buildStep4Fields($submission)
        {
            $fields = FieldList::create();
            $fields->push(HiddenField::create('CurrentStep', '', 3));
            $fields->push(HiddenField::create('SubmissionID', '', $submission->ID));

            $representing = $submission->getRepresentingArray();

            foreach ($representing as $index => $party) {
                $amlRecord = $submission->AMLRecords()->filter(['PartyType' => $party])->first();

                // Create or get existing AML record for file attachment
                if (!$amlRecord) {
                    $amlRecord = AMLRecord::create();
                    $amlRecord->FormSubmissionID = $submission->ID;
                    $amlRecord->PartyType = $party;
                    $amlRecord->write();
                }

                // Check for existing files
                $existingAMLFile = null;
                $existingAMLFileName = '';
                if ($amlRecord->AMLFileID) {
                    $existingAMLFile = File::get()->byID($amlRecord->AMLFileID);
                    if ($existingAMLFile && $existingAMLFile->exists()) {
                        $existingAMLFileName = $existingAMLFile->Name;
                    }
                }

                $existingFormQCFile = null;
                $existingFormQCFileName = '';
                if ($amlRecord->FormQCIDID) {
                    $existingFormQCFile = File::get()->byID($amlRecord->FormQCIDID);
                    if ($existingFormQCFile && $existingFormQCFile->exists()) {
                        $existingFormQCFileName = $existingFormQCFile->Name;
                    }
                }

                // Check for existing multiple files
                $existingUCPForms = $amlRecord->UCPForms();
                $existingECDDForms = $amlRecord->ECDDForms();

                $wrapperContent = [
                    LiteralField::create(
                        "AMLHeader{$index}",
                        "<h3 class='aml-section-title'>{$party} - AML Search & Forms</h3>"
                    ),

                    OptionsetField::create("AMLCompleted_{$index}", 'Have you conducted an AML search?', [
                        '1' => 'Yes',
                        '0' => 'No'
                    ])->setValue($amlRecord ? (string)$amlRecord->AMLCompleted : null),

                    FileField::create("AMLFile_{$index}", 'Upload AML PDF file')

                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'),
                ];

                // Add AML file existing notice right after AML file field
                if ($existingAMLFileName) {
                    $wrapperContent[] = LiteralField::create(
                        "ExistingAMLFile_{$index}",
                        "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                    <strong>Currently uploaded AML file:</strong> {$existingAMLFileName}
                    <br><small>Upload a new file to replace the existing one.</small>
                </div>"
                    );
                }

                $wrapperContent = array_merge($wrapperContent, [
                    OptionsetField::create("FormBSection2_{$index}", 'Was any component under section 2 of Form B checked "Yes"?', [
                        '1' => 'Yes',
                        '0' => 'No'
                    ])->setValue($amlRecord ? (string)$amlRecord->FormBSection2Checked : null),

                    FileField::create("FormQCID_{$index}", 'Upload Form QCI-D')

                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'),
                ]);

                // Add Form QCI-D existing notice right after Form QCI-D field
                if ($existingFormQCFileName) {
                    $wrapperContent[] = LiteralField::create(
                        "ExistingFormQCID_{$index}",
                        "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                    <strong>Currently uploaded Form QCI-D:</strong> {$existingFormQCFileName}
                    <br><small>Upload a new file to replace the existing one.</small>
                </div>"
                    );
                }

                $wrapperContent = array_merge($wrapperContent, [
                    OptionsetField::create("OtherPartyRepresented_{$index}", 'Is the other party Represented?', [
                        '1' => 'Yes',
                        '0' => 'No'
                    ])->setValue($amlRecord ? (string)$amlRecord->OtherPartyRepresented : null),

                    OptionsetField::create("UCPType_{$index}", 'Is the other party (UCP) an:', [
                        'Individual' => 'Individual',
                        'Entity' => 'Entity'
                    ])->setValue($amlRecord ? $amlRecord->UCPType : null),

                    DropdownField::create("UCPActingType_{$index}", 'Specific UCP type:', [
                        '' => '-- Select type --',
                        'UCP (Individual) acting for himself' => 'UCP (Individual) acting for himself',
                        'UCP (Individual) acting on behalf of another individual' => 'UCP (Individual) acting on behalf of another individual',
                        'UCP (Individual) acting on behalf of another (Entity/Legal Arrangement)' => 'UCP (Individual) acting on behalf of another (Entity/Legal Arrangement)',
                        'UCP (Entity/Legal Arrangement) acting for himself' => 'UCP (Entity/Legal Arrangement) acting for himself',
                        'UCP (Entity/Legal Arrangement) acting on behalf of another individual' => 'UCP (Entity/Legal Arrangement) acting on behalf of another individual',
                        'UCP (Entity/Legal Arrangement) acting on behalf of another (Entity/Legal Arrangement)' => 'UCP (Entity/Legal Arrangement) acting on behalf of another (Entity/Legal Arrangement)',
                    ])->setValue($amlRecord ? $amlRecord->UCPActingType : null),

                    FileField::create("UCPForms_{$index}", 'Upload UCP Forms')
                        ->setDescription('Multiple files allowed')
                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx')
                        ->setAttribute('multiple', 'multiple'),
                ]);

                // Add UCP Forms existing notice right after UCP Forms field
                if ($existingUCPForms->count() > 0) {
                    $fileNames = [];
                    foreach ($existingUCPForms as $form) {
                        if ($form->FormFile() && $form->FormFile()->exists()) {
                            $fileNames[] = $form->FormFile()->Name;
                        }
                    }
                    if (!empty($fileNames)) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingUCPForms_{$index}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                        <strong>Currently uploaded UCP Forms:</strong> " . implode(', ', $fileNames) . "
                        <br><small>Upload new files to add to or replace existing ones.</small>
                    </div>"
                        );
                    }
                }

                $wrapperContent = array_merge($wrapperContent, [
                    OptionsetField::create("ECDDRequired_{$index}", 'After checking the CDD and AML forms, is ECDD warranted?', [
                        '1' => 'Yes',
                        '0' => 'No'
                    ])->setValue($amlRecord ? (string)$amlRecord->ECDDRequired : null),

                    FileField::create("ECDDForm_{$index}", 'Upload ECDD Form')

                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'),
                ]);

                // Add ECDD Form existing notice right after ECDD Form field
                $ecddFiles = [];
                $qcibFiles = [];

                if ($existingECDDForms->count() > 0) {
                    foreach ($existingECDDForms as $form) {
                        if ($form->FormFile() && $form->FormFile()->exists()) {
                            $fileName = $form->FormFile()->Name;
                            // Determine file type based on form type
                            if ($form->FormType === 'ECDDForm') {
                                $ecddFiles[] = $fileName;
                            } elseif ($form->FormType === 'FormQCIB') {
                                $qcibFiles[] = $fileName;
                            } else {
                                // Default based on filename if form type not set
                                if (stripos($fileName, 'ecdd') !== false) {
                                    $ecddFiles[] = $fileName;
                                } elseif (stripos($fileName, 'qcib') !== false || stripos($fileName, 'qci-b') !== false) {
                                    $qcibFiles[] = $fileName;
                                } else {
                                    $ecddFiles[] = $fileName;
                                }
                            }
                        }
                    }

                    if (!empty($ecddFiles)) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingECDDForm_{$index}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                        <strong>Currently uploaded ECDD Form:</strong> " . implode(', ', $ecddFiles) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                        );
                    }
                }

                $wrapperContent = array_merge($wrapperContent, [
                    FileField::create("FormQCIB_{$index}", 'Upload Form QCI-B')

                        ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'),
                ]);

                // Add Form QCI-B existing notice right after Form QCI-B field
                if (!empty($qcibFiles)) {
                    $wrapperContent[] = LiteralField::create(
                        "ExistingFormQCIB_{$index}",
                        "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                    <strong>Currently uploaded Form QCI-B:</strong> " . implode(', ', $qcibFiles) . "
                    <br><small>Upload a new file to replace the existing one.</small>
                </div>"
                    );
                }

                $wrapperContent = array_merge($wrapperContent, [
                    OptionsetField::create("EAApproval_{$index}", "Has EA's approval been obtained?", [
                        '1' => 'Yes',
                        '0' => 'No'
                    ])->setValue($amlRecord ? (string)$amlRecord->EAApprovalObtained : null),

                    HiddenField::create("AMLPartyType_{$index}", '', $party),
                    HiddenField::create("AMLRecordID_{$index}", '', $amlRecord->ID)
                ]);

                $wrapper = CompositeField::create($wrapperContent);
                $wrapper->addExtraClass('aml-section');
                $fields->push($wrapper);
            }

            return $fields;
        }

        private function buildStep5Fields($submission)
        {
            $fields = FieldList::create();
            $fields->push(HiddenField::create('CurrentStep', '', 4));
            $fields->push(HiddenField::create('SubmissionID', '', $submission->ID));

            $transactionType = $submission->TransactionType;

            // Check for existing documents
            $existingDocuments = $submission->Documents();

            // Group existing documents by type
            $existingDocsByType = [];
            foreach ($existingDocuments as $doc) {
                $existingDocsByType[$doc->DocumentType][] = $doc;
            }

            // Transaction-specific document
            if ($transactionType === 'Sale') {
                $existingOptionToPurchase = $existingDocsByType['Option To Purchase / Sales Agreement'] ?? [];
                $fields->push(FileField::create('OptionToPurchase', 'Option To Purchase / Sales Agreement')

                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'));

                // Show existing file info
                if (!empty($existingOptionToPurchase)) {
                    $fileNames = [];
                    foreach ($existingOptionToPurchase as $doc) {
                        if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                            $fileNames[] = $doc->DocumentFile()->Name;
                        }
                    }
                    if (!empty($fileNames)) {
                        $fields->push(LiteralField::create(
                            'ExistingOptionToPurchase',
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                        <strong>Currently uploaded file:</strong> " . implode(', ', $fileNames) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                        ));
                    }
                }
            } else {
                $existingTenancyAgreement = $existingDocsByType['Tenancy Agreement / Letter Of Intent / Letter Of Offer'] ?? [];
                $fields->push(FileField::create('TenancyAgreement', 'Tenancy Agreement / Letter Of Intent / Letter Of Offer')

                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'));

                // Show existing file info
                if (!empty($existingTenancyAgreement)) {
                    $fileNames = [];
                    foreach ($existingTenancyAgreement as $doc) {
                        if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                            $fileNames[] = $doc->DocumentFile()->Name;
                        }
                    }
                    if (!empty($fileNames)) {
                        $fields->push(LiteralField::create(
                            'ExistingTenancyAgreement',
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                        <strong>Currently uploaded file:</strong> " . implode(', ', $fileNames) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                        ));
                    }
                }
            }

            // CEA Agreement
            $existingCEAAgreement = $existingDocsByType['CEA Agreement'] ?? [];
            $fields->push(OptionsetField::create('HasCEAAgreement', 'Is there a CEA Agreement?', [
                '1' => 'Yes',
                '0' => 'No'
            ]));

            $fields->push(FileField::create('CEAAgreement', 'Upload CEA Agreement')

                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'));

            if (!empty($existingCEAAgreement)) {
                $fileNames = [];
                foreach ($existingCEAAgreement as $doc) {
                    if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                        $fileNames[] = $doc->DocumentFile()->Name;
                    }
                }
                if (!empty($fileNames)) {
                    $fields->push(LiteralField::create(
                        'ExistingCEAAgreement',
                        "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                    <strong>Currently uploaded file:</strong> " . implode(', ', $fileNames) . "
                    <br><small>Upload a new file to replace the existing one.</small>
                </div>"
                    ));
                }
            }

            // Co-broke Agreement
            $existingCobrokeAgreement = $existingDocsByType['Co-broke Agreement'] ?? [];
            $fields->push(OptionsetField::create('HasCobrokeAgreement', 'Is there a Co-broke Agreement?', [
                '1' => 'Yes',
                '0' => 'No'
            ]));

            $fields->push(FileField::create('CobrokeAgreement', 'Upload Co-broke Agreement')

                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'));

            if (!empty($existingCobrokeAgreement)) {
                $fileNames = [];
                foreach ($existingCobrokeAgreement as $doc) {
                    if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                        $fileNames[] = $doc->DocumentFile()->Name;
                    }
                }
                if (!empty($fileNames)) {
                    $fields->push(LiteralField::create(
                        'ExistingCobrokeAgreement',
                        "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                    <strong>Currently uploaded file:</strong> " . implode(', ', $fileNames) . "
                    <br><small>Upload a new file to replace the existing one.</small>
                </div>"
                    ));
                }
            }

            // Commission Agreement
            $existingCommissionAgreement = $existingDocsByType['Commission Agreement'] ?? [];
            $fields->push(OptionsetField::create('HasCommissionAgreement', 'Is there a Commission Agreement?', [
                '1' => 'Yes',
                '0' => 'No'
            ]));

            $fields->push(FileField::create('CommissionAgreement', 'Upload Commission Agreement')

                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'));

            if (!empty($existingCommissionAgreement)) {
                $fileNames = [];
                foreach ($existingCommissionAgreement as $doc) {
                    if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                        $fileNames[] = $doc->DocumentFile()->Name;
                    }
                }
                if (!empty($fileNames)) {
                    $fields->push(LiteralField::create(
                        'ExistingCommissionAgreement',
                        "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                    <strong>Currently uploaded file:</strong> " . implode(', ', $fileNames) . "
                    <br><small>Upload a new file to replace the existing one.</small>
                </div>"
                    ));
                }
            }

            // HDB Approval (Lease only)
            if ($transactionType === 'Lease') {
                $existingHDBApproval = $existingDocsByType['HDB Approval Letter'] ?? [];
                $fields->push(OptionsetField::create('HasHDBApproval', 'Is there HDB Approval letter for subletting?', [
                    '1' => 'Yes',
                    '0' => 'No'
                ]));

                $fields->push(FileField::create('HDBApproval', 'Upload HDB Approval letter')

                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'));

                if (!empty($existingHDBApproval)) {
                    $fileNames = [];
                    foreach ($existingHDBApproval as $doc) {
                        if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                            $fileNames[] = $doc->DocumentFile()->Name;
                        }
                    }
                    if (!empty($fileNames)) {
                        $fields->push(LiteralField::create(
                            'ExistingHDBApproval',
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                        <strong>Currently uploaded file:</strong> " . implode(', ', $fileNames) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                        ));
                    }
                }
            }

            // Other Documents
            $existingOtherDocuments = $existingDocsByType['Other Documents'] ?? [];
            $fields->push(OptionsetField::create('HasOtherDocuments', 'Are there any other documents?', [
                '1' => 'Yes',
                '0' => 'No'
            ]));

            $fields->push(TextField::create('OtherDocumentsDescription', 'Please specify'));

            $fields->push(FileField::create('OtherDocuments', 'Upload other documents (Max 3 files)')
                ->setDescription('Multiple files allowed')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx')
                ->setAttribute('multiple', 'multiple'));

            if (!empty($existingOtherDocuments)) {
                $fileNames = [];
                foreach ($existingOtherDocuments as $doc) {
                    if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                        $fileNames[] = $doc->DocumentFile()->Name;
                    }
                }
                if (!empty($fileNames)) {
                    $fields->push(LiteralField::create(
                        'ExistingOtherDocuments',
                        "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                    <strong>Currently uploaded files:</strong> " . implode(', ', $fileNames) . "
                    <br><small>Upload new files to add to or replace existing ones.</small>
                </div>"
                    ));
                }
            }

            // Add initialization script for Step 5 conditional fields
            $initScript = "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Step 5 conditional fields based on saved values
        const triggers = ['HasCEAAgreement', 'HasCobrokeAgreement', 'HasCommissionAgreement', 'HasHDBApproval', 'HasOtherDocuments'];
        
        triggers.forEach(trigger => {
            const field = document.querySelector('input[name=\"' + trigger + '\"]:checked');
            if (field) {
                const event = new Event('change');
                field.dispatchEvent(event);
            }
        });
    });
    </script>
    ";

            $fields->push(LiteralField::create('Step5InitScript', $initScript));

            return $fields;
        }

        private function getStep4Required($submission)
        {
            $required = [];
            $representing = $submission->getRepresentingArray();

            foreach ($representing as $index => $party) {
                $required[] = "AMLCompleted_{$index}";
            }

            return $required;
        }

        private function getStep5Required($submission)
        {
            $required = [];
            $transactionType = $submission->TransactionType;

            if ($transactionType === 'Sale') {
                // $required[] = 'OptionToPurchase';
            } else {
                // $required[] = 'TenancyAgreement';
            }

            // $required[] = 'HasCEAAgreement';
            // $required[] = 'HasCobrokeAgreement';
            // $required[] = 'HasCommissionAgreement';
            // $required[] = 'HasOtherDocuments';

            return $required;
        }

        private function buildStep6Fields($submission)
        {
            $fields = FieldList::create();
            $fields->push(HiddenField::create('CurrentStep', '', 5));
            $fields->push(HiddenField::create('SubmissionID', '', $submission->ID));

            $reviewHTML = '<div class="review-content">';

            // Step 1 Review
            $reviewHTML .= '<div class="review-section">';
            $reviewHTML .= '<h3 class="review-title">Login Information</h3>';
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">Salesperson Name:</span>';
            $reviewHTML .= '<span class="review-value">' . htmlspecialchars($submission->SalespersonName) . '</span>';
            $reviewHTML .= '</div>';
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">RES Number:</span>';
            $reviewHTML .= '<span class="review-value">' . htmlspecialchars($submission->RESNumber) . '</span>';
            $reviewHTML .= '</div>';
            $reviewHTML .= '</div>';

            // Step 2 Review
            $reviewHTML .= '<div class="review-section">';
            $reviewHTML .= '<h3 class="review-title">Property Details</h3>';
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">Property Address:</span>';
            $reviewHTML .= '<span class="review-value">' . nl2br(htmlspecialchars($submission->PropertyAddress)) . '</span>';
            $reviewHTML .= '</div>';
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">Transaction Type:</span>';
            $reviewHTML .= '<span class="review-value">' . htmlspecialchars($submission->TransactionType) . '</span>';
            $reviewHTML .= '</div>';
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">Representing:</span>';
            $reviewHTML .= '<span class="review-value">' . implode(', ', $submission->getRepresentingArray()) . '</span>';
            $reviewHTML .= '</div>';
            $reviewHTML .= '</div>';

            // Detailed Client Information Records
            $clientInfoRecords = $submission->ClientInfo();
            $reviewHTML .= '<div class="review-section">';
            $reviewHTML .= '<h3 class="review-title">Client Information Records</h3>';

            if ($clientInfoRecords->count() > 0) {
                foreach ($clientInfoRecords as $clientInfo) {
                    $reviewHTML .= '<div class="review-subsection">';
                    $reviewHTML .= '<h4 class="review-subtitle">' . htmlspecialchars($clientInfo->PartyType) . '</h4>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">Client Type:</span>';
                    $reviewHTML .= '<span class="review-value">' . htmlspecialchars($clientInfo->ClientType) . '</span>';
                    $reviewHTML .= '</div>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">Acting Type:</span>';
                    $reviewHTML .= '<span class="review-value">' . htmlspecialchars($clientInfo->ClientActingTypeSelection) . '</span>';
                    $reviewHTML .= '</div>';

                    $fileLink = function($fileID, $label) {
                        if (!$fileID) return '<div class="review-item"><span class="review-label">' . $label . ':</span><span class="review-value">Not uploaded</span></div>';
                        $f = \SilverStripe\Assets\File::get()->byID($fileID);
                        if ($f && $f->exists()) {
                            return '<div class="review-item"><span class="review-label">' . $label . ':</span><span class="review-value"><a href="' . $f->getURL() . '" target="_blank">' . htmlspecialchars($f->Name) . '</a></span></div>';
                        }
                        return '<div class="review-item"><span class="review-label">' . $label . ':</span><span class="review-value">Uploaded (unavailable)</span></div>';
                    };
                    $reviewHTML .= $fileLink($clientInfo->OwnershipProofID, 'Ownership Proof');
                    $reviewHTML .= $fileLink($clientInfo->FormA1FileID, 'Form A1');
                    $reviewHTML .= $fileLink($clientInfo->FormA2FileID, 'Form A2');
                    $reviewHTML .= $fileLink($clientInfo->FormA3FileID, 'Form A3');
                    $reviewHTML .= $fileLink($clientInfo->FormA4FileID, 'Form A4');
                    $reviewHTML .= $fileLink($clientInfo->FormBFileID, 'Form B');
                    $reviewHTML .= '</div>';
                }
            } else {
                $reviewHTML .= '<div class="review-item">';
                $reviewHTML .= '<span class="review-value">No client information records found.</span>';
                $reviewHTML .= '</div>';
            }
            $reviewHTML .= '</div>';

            // Detailed AML Records
            $amlRecords = $submission->AMLRecords();
            $reviewHTML .= '<div class="review-section">';
            $reviewHTML .= '<h3 class="review-title">AML Records</h3>';

            if ($amlRecords->count() > 0) {
                foreach ($amlRecords as $amlRecord) {
                    $reviewHTML .= '<div class="review-subsection">';
                    $reviewHTML .= '<h4 class="review-subtitle">' . htmlspecialchars($amlRecord->PartyType) . '</h4>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">AML Search Completed:</span>';
                    $reviewHTML .= '<span class="review-value">' . ($amlRecord->AMLCompleted ? 'Yes' : 'No') . '</span>';
                    $reviewHTML .= '</div>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">Form B Section 2 Checked:</span>';
                    $reviewHTML .= '<span class="review-value">' . ($amlRecord->FormBSection2Checked ? 'Yes' : 'No') . '</span>';
                    $reviewHTML .= '</div>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">Other Party Represented:</span>';
                    $reviewHTML .= '<span class="review-value">' . ($amlRecord->OtherPartyRepresented ? 'Yes' : 'No') . '</span>';
                    $reviewHTML .= '</div>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">UCP Type:</span>';
                    $reviewHTML .= '<span class="review-value">' . htmlspecialchars($amlRecord->UCPType) . '</span>';
                    $reviewHTML .= '</div>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">UCP Acting Type:</span>';
                    $reviewHTML .= '<span class="review-value">' . htmlspecialchars($amlRecord->UCPActingType) . '</span>';
                    $reviewHTML .= '</div>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">ECDD Required:</span>';
                    $reviewHTML .= '<span class="review-value">' . ($amlRecord->ECDDRequired ? 'Yes' : 'No') . '</span>';
                    $reviewHTML .= '</div>';
                    $reviewHTML .= '<div class="review-item">';
                    $reviewHTML .= '<span class="review-label">EA Approval Obtained:</span>';
                    $reviewHTML .= '<span class="review-value">' . ($amlRecord->EAApprovalObtained ? 'Yes' : 'No') . '</span>';
                    $reviewHTML .= '</div>';

                    $amlFileLink = function($fileID, $label) {
                        if (!$fileID) return '<div class="review-item"><span class="review-label">' . $label . ':</span><span class="review-value">Not uploaded</span></div>';
                        $f = \SilverStripe\Assets\File::get()->byID($fileID);
                        if ($f && $f->exists()) {
                            return '<div class="review-item"><span class="review-label">' . $label . ':</span><span class="review-value"><a href="' . $f->getURL() . '" target="_blank">' . htmlspecialchars($f->Name) . '</a></span></div>';
                        }
                        return '<div class="review-item"><span class="review-label">' . $label . ':</span><span class="review-value">Uploaded (unavailable)</span></div>';
                    };
                    $reviewHTML .= $amlFileLink($amlRecord->AMLFileID, 'AML File');
                    $reviewHTML .= $amlFileLink($amlRecord->FormQCIDID, 'Form QCI-D');

                    foreach ($amlRecord->UCPForms() as $ucpForm) {
                        if ($ucpForm->FormFile() && $ucpForm->FormFile()->exists()) {
                            $reviewHTML .= '<div class="review-item"><span class="review-label">UCP Form:</span><span class="review-value"><a href="' . $ucpForm->FormFile()->getURL() . '" target="_blank">' . htmlspecialchars($ucpForm->FormFile()->Name) . '</a></span></div>';
                        }
                    }

                    foreach ($amlRecord->ECDDForms() as $ecddForm) {
                        if ($ecddForm->FormFile() && $ecddForm->FormFile()->exists()) {
                            $label = $ecddForm->FormType === 'FormQCIB' ? 'Form QCI-B' : 'ECDD Form';
                            $reviewHTML .= '<div class="review-item"><span class="review-label">' . $label . ':</span><span class="review-value"><a href="' . $ecddForm->FormFile()->getURL() . '" target="_blank">' . htmlspecialchars($ecddForm->FormFile()->Name) . '</a></span></div>';
                        }
                    }
                }
            } else {
                $reviewHTML .= '<div class="review-item">';
                $reviewHTML .= '<span class="review-value">No AML records found.</span>';
                $reviewHTML .= '</div>';
            }
            $reviewHTML .= '</div>';

            $reviewHTML .= '</div>';

            // Step 5 Documents
            $documents = $submission->Documents();
            $reviewHTML .= '<div class="review-section">';
            $reviewHTML .= '<h3 class="review-title">Transaction Documents</h3>';
            if ($documents->count() > 0) {
                foreach ($documents as $doc) {
                    $reviewHTML .= '<div class="review-subsection">';
                    $reviewHTML .= '<div class="review-item"><span class="review-label">Type:</span><span class="review-value">' . htmlspecialchars($doc->DocumentType) . '</span></div>';
                    if ($doc->Description) {
                        $reviewHTML .= '<div class="review-item"><span class="review-label">Description:</span><span class="review-value">' . htmlspecialchars($doc->Description) . '</span></div>';
                    }
                    if ($doc->DocumentFile() && $doc->DocumentFile()->exists()) {
                        $reviewHTML .= '<div class="review-item"><span class="review-label">File:</span><span class="review-value"><a href="' . $doc->DocumentFile()->getURL() . '" target="_blank">' . htmlspecialchars($doc->DocumentFile()->Name) . '</a></span></div>';
                    } else {
                        $reviewHTML .= '<div class="review-item"><span class="review-label">File:</span><span class="review-value">Not uploaded</span></div>';
                    }
                    $reviewHTML .= '</div>';
                }
            } else {
                $reviewHTML .= '<div class="review-item"><span class="review-value">No transaction documents added.</span></div>';
            }
            $reviewHTML .= '</div>';

            $reviewHTML .= '<div class="alert alert-info mt-3">';
            $reviewHTML .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            $reviewHTML .= '<circle cx="12" cy="12" r="10"/>';
            $reviewHTML .= '<line x1="12" y1="16" x2="12" y2="12"/>';
            $reviewHTML .= '<line x1="12" y1="8" x2="12.01" y2="8"/>';
            $reviewHTML .= '</svg>';
            $reviewHTML .= 'Please review all information carefully before submitting. Once submitted, you will receive a unique serial number for billing purposes.';
            $reviewHTML .= '</div>';

            $fields->push(LiteralField::create('ReviewContent', $reviewHTML));

            return $fields;
        }

        public function nextStep($data, $form)
        {
            $submission = FormSubmission::get()->byID($data['SubmissionID']);
            if (!$submission) {
                $form->sessionMessage('Submission not found', 'bad');
                return $this->redirectBack();
            }

            $currentStep = (int)$data['CurrentStep'];

            // DEBUG: Log all data to see file structure
            error_log("========================================");
            error_log("STEP {$currentStep} - FULL DATA DUMP");
            error_log("========================================");
            error_log(print_r($data, true));
            error_log("========================================");
            error_log("FILES ARRAY:");
            error_log(print_r($_FILES, true));
            error_log("========================================");

            // Save data for current step
            try {
                $this->saveStepData($currentStep, $data, $submission, $form);
            } catch (\Exception $e) {
                // Error message
                $form->sessionMessage("Error saving step {$currentStep}: " . $e->getMessage(), 'bad');
                error_log("Step {$currentStep} save error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                return $this->redirectBack();
            }

            // Move to next step using URL parameter
            $nextStep = $currentStep + 1;
            $this->getRequest()->getSession()->set('FormStep', $nextStep);

            return $this->redirect($this->Link() . '?step=' . $nextStep . '#form-start');
        }

        public function previousStep($data, $form)
        {
            $currentStep = (int)$data['CurrentStep'];
            $previousStep = max(1, $currentStep - 1);

            $this->getRequest()->getSession()->set('FormStep', $previousStep);

            return $this->redirect($this->Link() . '?step=' . $previousStep . '#form-start');
        }

        public function submitForm($data, $form)
        {
            $submission = FormSubmission::get()->byID($data['SubmissionID']);
            if (!$submission) {
                $form->sessionMessage('Submission not found', 'bad');
                return $this->redirectBack();
            }

            $submission->Status = 'Submitted';
            $submission->SubmittedDate = date('Y-m-d H:i:s');
            $submission->write();

            // Send email notification
            $this->sendNotificationEmail($submission);

            // Clear session
            $this->getRequest()->getSession()->clear('FormSubmissionID');
            $this->getRequest()->getSession()->clear('FormStep');

            // Redirect to success page with serial number
            return $this->redirect($this->Link() . '?success=1&serial=' . $submission->SerialNumber);
        }

        public function saveStep($data, $form)
        {
            $submissionID = $data['SubmissionID'] ?? $this->getRequest()->getSession()->get('FormSubmissionID');
            $submission = FormSubmission::get()->byID($submissionID);

            if (!$submission) {
                $form->sessionMessage('Submission not found', 'bad');
                return $this->redirectBack();
            }

            $currentStep = (int)($data['CurrentStep'] ?? 1);
            $targetStep = isset($data['TargetStep']) ? (int)$data['TargetStep'] : $currentStep + 1;
            $shouldSaveData = !isset($data['ShouldSaveData']) || (bool)$data['ShouldSaveData'];

            // Only save data if we're moving forward AND shouldSaveData is true
            if ($targetStep > $currentStep && $shouldSaveData) {
                try {
                    $this->saveStepData($currentStep, $data, $submission, $form);
                } catch (\Exception $e) {
                    $form->sessionMessage("Error saving step {$currentStep}: " . $e->getMessage(), 'bad');
                    error_log("Step {$currentStep} save error: " . $e->getMessage());
                    return $this->redirectBack();
                }
            }

            // Update session to target step
            $this->getRequest()->getSession()->set('FormStep', $targetStep);

            return $this->redirectBack();
        }

        private function saveStepData($step, $data, $submission, $form = null)
        {
            switch ($step) {
                case 1:
                    $submission->PropertyAddress = $data['PropertyAddress'];
                    $submission->TransactionType = $data['TransactionType'];
                    $representing = $data['Representing'] ?? null;
                    if ($representing) {
                        $submission->setRepresentingArray(
                            is_array($representing) ? $representing : [$representing]
                        );
                    }
                    $submission->write();
                    break;

                case 2:
                    $this->saveStep3Data($data, $submission, $form);
                    break;

                case 3:
                    $this->saveStep4Data($data, $submission, $form);
                    break;

                case 4:
                    $this->saveStep5Data($data, $submission, $form);
                    break;
            }
        }

        private function saveStep3Data($data, $submission, $form = null)
        {
            $representing = $submission->getRepresentingArray();

            error_log("=== Step 3 File Upload Debug ===");
            error_log("Representing parties: " . print_r($representing, true));

            foreach ($representing as $index => $party) {
                error_log("Processing party: {$party}");

                // Get ClientInfo by ID from hidden field using party name
                $clientInfoID = $data["ClientInfoID_{$party}"] ?? null;

                if ($clientInfoID) {
                    $clientInfo = ClientInfo::get()->byID($clientInfoID);
                } else {
                    // Fallback: find by party type
                    $clientInfo = $submission->ClientInfo()->filter(['PartyType' => $party])->first();
                }

                if (!$clientInfo) {
                    $clientInfo = ClientInfo::create();
                    $clientInfo->FormSubmissionID = $submission->ID;
                    $clientInfo->PartyType = $party;
                }

                $clientInfo->ClientType = $data["ClientType_{$party}"] ?? null;
                $clientInfo->ClientActingTypeSelection = $data["ClientActingType_{$party}"] ?? null;
                $clientInfo->ActingType = $data["ClientActingType_{$party}"] ?? null;
                $clientInfo->write();

                error_log("ClientInfo ID: {$clientInfo->ID}");

                // Handle all file uploads for this party — stored under the submission's own folder
                $submissionFolder = 'submissions/' . $submission->SerialNumber . '/client-info';
                $fileUploads = [
                    "OwnershipProof_{$party}" => ['field' => 'OwnershipProofID', 'folder' => $submissionFolder],
                    "FormA1File_{$party}"     => ['field' => 'FormA1FileID',     'folder' => $submissionFolder],
                    "FormA2File_{$party}"     => ['field' => 'FormA2FileID',     'folder' => $submissionFolder],
                    "FormA3File_{$party}"     => ['field' => 'FormA3FileID',     'folder' => $submissionFolder],
                    "FormA4File_{$party}"     => ['field' => 'FormA4FileID',     'folder' => $submissionFolder],
                    "FormBFile_{$party}"      => ['field' => 'FormBFileID',      'folder' => $submissionFolder],
                ];

                foreach ($fileUploads as $fieldName => $config) {
                    if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
                        try {
                            $upload = Upload::create();
                            $file = File::create();
                            $upload->loadIntoFile($_FILES[$fieldName], $file, $config['folder'] . '/');
                            if ($file && $file->exists()) {
                                $file->publishSingle();
                                $clientInfo->{$config['field']} = $file->ID;
                                $clientInfo->write();
                            }
                        } catch (\Exception $e) {
                            error_log("Error uploading {$fieldName}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        private function saveStep4Data($data, $submission, $form = null)
        {
            $representing = $submission->getRepresentingArray();

            foreach ($representing as $index => $party) {
                $amlRecord = $submission->AMLRecords()->filter(['PartyType' => $party])->first();

                if (!$amlRecord) {
                    $amlRecord = AMLRecord::create();
                    $amlRecord->FormSubmissionID = $submission->ID;
                    $amlRecord->PartyType = $party;
                }

                $amlRecord->AMLCompleted = isset($data["AMLCompleted_{$index}"]) ? (bool)$data["AMLCompleted_{$index}"] : false;
                $amlRecord->FormBSection2Checked = isset($data["FormBSection2_{$index}"]) ? (bool)$data["FormBSection2_{$index}"] : false;
                $amlRecord->OtherPartyRepresented = isset($data["OtherPartyRepresented_{$index}"]) ? (bool)$data["OtherPartyRepresented_{$index}"] : false;
                $amlRecord->UCPType = $data["UCPType_{$index}"] ?? null;
                $amlRecord->UCPActingType = $data["UCPActingType_{$index}"] ?? null;
                $amlRecord->ECDDRequired = isset($data["ECDDRequired_{$index}"]) ? (bool)$data["ECDDRequired_{$index}"] : false;
                $amlRecord->EAApprovalObtained = isset($data["EAApproval_{$index}"]) ? (bool)$data["EAApproval_{$index}"] : false;
                $amlRecord->write();

                error_log("=== Step 4 File Upload Debug ===");

                // All AML files stored under the submission's own folder
                $amlFolder = 'submissions/' . $submission->SerialNumber . '/aml';

                // Handle single file uploads using FileField method
                $this->handleFileFieldUpload("AMLFile_{$index}", $amlRecord, 'AMLFileID', $amlFolder);
                $this->handleFileFieldUpload("FormQCID_{$index}", $amlRecord, 'FormQCIDID', $amlFolder);

                // Handle multiple file uploads
                $this->handleMultipleFileFieldUpload("UCPForms_{$index}", $amlRecord, 'UCPForm', $amlFolder);

                // Handle ECDD Form and Form QCI-B as separate multiple file uploads
                $this->handleECDFileUpload("ECDDForm_{$index}", $amlRecord, 'ECDDForm', $amlFolder);
                $this->handleECDFileUpload("FormQCIB_{$index}", $amlRecord, 'FormQCIB', $amlFolder);

                error_log("=========================");
            }
        }

        /**
         * Handle ECDD file uploads (single files saved as multiple file relationships)
         */
        private function handleECDFileUpload($fieldName, $amlRecord, $formType, $folder)
        {
            error_log("Processing ECDD file for {$fieldName}...");

            if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
                $upload = Upload::create();

                try {
                    $file = File::create();
                    $upload->loadIntoFile($_FILES[$fieldName], $file, $folder . '/');

                    if ($file && $file->exists()) {
                        $file->publishSingle();

                        // Create ECDDForm record
                        $ecddForm = ECDDForm::create();
                        $ecddForm->AMLRecordID = $amlRecord->ID;
                        $ecddForm->FormFileID = $file->ID;
                        $ecddForm->FormType = $formType;
                        $ecddForm->write();

                        error_log("✓ {$formType} uploaded! File ID: {$file->ID}, ECDDForm ID: {$ecddForm->ID}");
                    } else {
                        error_log("✗ File upload failed for {$fieldName}");
                    }
                } catch (\Exception $e) {
                    error_log("✗ Error uploading {$fieldName}: " . $e->getMessage());
                }
            } else {
                error_log("No file uploaded for {$fieldName}");
            }
        }

        /**
         * Handle single file upload using FileField method (same as billing form)
         */
        private function handleFileFieldUpload($fieldName, $record, $relationName, $folder)
        {
            error_log("Processing {$fieldName}...");

            if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
                $upload = Upload::create();

                try {
                    $file = File::create();
                    $upload->loadIntoFile($_FILES[$fieldName], $file, $folder . '/');

                    if ($file && $file->exists()) {
                        $file->publishSingle();
                        $record->$relationName = $file->ID;
                        $record->write();
                        error_log("✓ {$fieldName} uploaded! File ID: {$file->ID}");
                    } else {
                        error_log("✗ File upload failed for {$fieldName}");
                    }
                } catch (\Exception $e) {
                    error_log("✗ Error uploading {$fieldName}: " . $e->getMessage());
                }
            } else {
                error_log("No file uploaded for {$fieldName}");
            }
        }

        /**
         * Handle multiple file uploads using FileField method
         */
        private function handleMultipleFileFieldUpload($fieldName, $amlRecord, $modelClass, $folder)
        {
            error_log("Processing multiple files for {$fieldName}...");

            if (isset($_FILES[$fieldName]) && is_array($_FILES[$fieldName]['tmp_name'])) {
                $fileCount = count($_FILES[$fieldName]['tmp_name']);
                error_log("Found {$fileCount} files for {$fieldName}");

                for ($i = 0; $i < $fileCount; $i++) {
                    if (!empty($_FILES[$fieldName]['tmp_name'][$i])) {
                        $fileData = [
                            'name' => $_FILES[$fieldName]['name'][$i],
                            'type' => $_FILES[$fieldName]['type'][$i],
                            'tmp_name' => $_FILES[$fieldName]['tmp_name'][$i],
                            'error' => $_FILES[$fieldName]['error'][$i],
                            'size' => $_FILES[$fieldName]['size'][$i]
                        ];

                        $upload = Upload::create();

                        try {
                            $file = File::create();
                            $upload->loadIntoFile($fileData, $file, $folder . '/');

                            if ($file && $file->exists()) {
                                $file->publishSingle();

                                // Create related record
                                $className = "App\\Model\\{$modelClass}";
                                $relatedRecord = $className::create();
                                $relatedRecord->AMLRecordID = $amlRecord->ID;
                                $relatedRecord->FormFileID = $file->ID;
                                $relatedRecord->FormType = $modelClass;
                                $relatedRecord->write();

                                error_log("✓ {$modelClass} uploaded! File ID: {$file->ID}");
                            } else {
                                error_log("✗ File upload failed for {$fieldName}[{$i}]");
                            }
                        } catch (\Exception $e) {
                            error_log("✗ Error uploading {$fieldName}[{$i}]: " . $e->getMessage());
                        }
                    }
                }
            } else {
                error_log("No multiple files found for {$fieldName}");
                // Check if it's a single file upload instead of multiple
                if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name']) && !is_array($_FILES[$fieldName]['tmp_name'])) {
                    error_log("Found single file for {$fieldName}, treating as multiple file upload");
                    $upload = Upload::create();

                    try {
                        $file = File::create();
                        $upload->loadIntoFile($_FILES[$fieldName], $file, $folder . '/');

                        if ($file && $file->exists()) {
                            $file->publishSingle();

                            // Create related record
                            $className = "App\\Model\\{$modelClass}";
                            $relatedRecord = $className::create();
                            $relatedRecord->AMLRecordID = $amlRecord->ID;
                            $relatedRecord->FormFileID = $file->ID;
                            $relatedRecord->FormType = $modelClass;
                            $relatedRecord->write();

                            error_log("✓ {$modelClass} uploaded as single file! File ID: {$file->ID}");
                        }
                    } catch (\Exception $e) {
                        error_log("✗ Error uploading single file for {$fieldName}: " . $e->getMessage());
                    }
                }
            }
        }

        private function saveStep5Data($data, $submission, $form = null)
        {
            error_log("=== Step 5 Document Upload Debug ===");

            $documentTypes = [
                'OptionToPurchase' => 'Option To Purchase / Sales Agreement',
                'TenancyAgreement' => 'Tenancy Agreement / Letter Of Intent / Letter Of Offer',
                'CEAAgreement' => 'CEA Agreement',
                'CobrokeAgreement' => 'Co-broke Agreement',
                'CommissionAgreement' => 'Commission Agreement',
                'HDBApproval' => 'HDB Approval Letter',
                'OtherDocuments' => 'Other Documents'
            ];

            foreach ($documentTypes as $fieldName => $documentType) {
                error_log("Processing {$fieldName}...");

                if (isset($_FILES[$fieldName])) {
                    if (is_array($_FILES[$fieldName]['tmp_name'])) {
                        // Multiple files
                        $fileCount = count($_FILES[$fieldName]['tmp_name']);
                        error_log("Found {$fileCount} files for {$fieldName}");

                        for ($i = 0; $i < $fileCount; $i++) {
                            if (!empty($_FILES[$fieldName]['tmp_name'][$i])) {
                                $fileData = [
                                    'name' => $_FILES[$fieldName]['name'][$i],
                                    'type' => $_FILES[$fieldName]['type'][$i],
                                    'tmp_name' => $_FILES[$fieldName]['tmp_name'][$i],
                                    'error' => $_FILES[$fieldName]['error'][$i],
                                    'size' => $_FILES[$fieldName]['size'][$i]
                                ];

                                $this->saveDocumentFile($fileData, $submission, $documentType, $data);
                            }
                        }
                    } else {
                        // Single file
                        if (!empty($_FILES[$fieldName]['tmp_name'])) {
                            $this->saveDocumentFile($_FILES[$fieldName], $submission, $documentType, $data);
                        }
                    }
                } else {
                    error_log("No files found for {$fieldName}");
                }
            }

            error_log("=========================");
        }

        /**
         * Save individual document file using FileField method
         */
        private function saveDocumentFile($fileData, $submission, $documentType, $formData)
        {
            $upload = Upload::create();

            try {
                $file = File::create();
                $upload->loadIntoFile($fileData, $file, 'submissions/' . $submission->SerialNumber . '/documents/');

                if ($file && $file->exists()) {
                    $file->publishSingle();

                    $doc = SubmissionDocument::create();
                    $doc->FormSubmissionID = $submission->ID;
                    $doc->DocumentType = $documentType;
                    $doc->DocumentFileID = $file->ID;

                    if ($documentType === 'Other Documents' && isset($formData['OtherDocumentsDescription'])) {
                        $doc->Description = $formData['OtherDocumentsDescription'];
                    }

                    $doc->write();

                    error_log("✓ Document saved! Type: {$documentType}, File ID: {$file->ID}");
                } else {
                    error_log("✗ File upload failed for {$documentType}");
                }
            } catch (\Exception $e) {
                error_log("✗ Error uploading {$documentType}: " . $e->getMessage());
            }
        }

        private function sendNotificationEmail($submission)
        {
            $to = [
                'felicia.teo@quinvest-chambers.com.sg',
                'Ian.loh@quinvest-chambers.com.sg',
                'wendy.low@quinvest-chambers.com.sg',
                'dinukasf2@gmail.com', // temp test
            ];

            $subject = "{$submission->SalespersonName} has completed the forms submission";
            $body = "<p>A new form has been submitted by <strong>{$submission->SalespersonName}</strong>.</p>
                    <p><strong>Serial Number:</strong> {$submission->SerialNumber}</p>
                    <p><strong>RES Number:</strong> {$submission->RESNumber}</p>
                    <p><strong>Transaction Type:</strong> {$submission->TransactionType}</p>
                    <p><strong>Property Address:</strong> {$submission->PropertyAddress}</p>
                    <p><strong>Submitted Date:</strong> {$submission->SubmittedDate}</p>
                    <p><strong>Status:</strong> {$submission->Status}</p>

                    <h3>Client Information:</h3>";

            // Add client information
            $clientInfoRecords = $submission->ClientInfo();
            if ($clientInfoRecords->count() > 0) {
                foreach ($clientInfoRecords as $clientInfo) {
                    $body .= "<p><strong>{$clientInfo->PartyType}:</strong> {$clientInfo->ClientType} - {$clientInfo->ClientActingTypeSelection}</p>";
                }
            }

            // Add AML information
            $amlRecords = $submission->AMLRecords();
            if ($amlRecords->count() > 0) {
                $body .= "<h3>AML Records:</h3>";
                foreach ($amlRecords as $amlRecord) {
                    $body .= "<p><strong>{$amlRecord->PartyType}:</strong> AML Completed: " . ($amlRecord->AMLCompleted ? 'Yes' : 'No') .
                        ", ECDD Required: " . ($amlRecord->ECDDRequired ? 'Yes' : 'No') .
                        ", EA Approval: " . ($amlRecord->EAApprovalObtained ? 'Yes' : 'No') . "</p>";
                }
            }

            $body .= "<p>Please review the submission in the <a href=\"https://billing.quinvest-chambers.com.sg/admin/form-submissions\">admin panel</a>.</p>
                    <p>Thank you!</p>";

            ResendEmail::send($to, $subject, $body);
        }

        private function sendBillingNotificationEmail($billingSubmission)
        {
            $to = [
                'felicia.teo@quinvest-chambers.com.sg',
                'Ian.loh@quinvest-chambers.com.sg',
                'wendy.low@quinvest-chambers.com.sg',
                'dinukasf2@gmail.com', // temp test
            ];

            $originalSubmission = $billingSubmission->FormSubmission();
            $uploadedBy = Member::get()->byID($billingSubmission->UploadedByID);

            $uploaderName = $uploadedBy ? ($uploadedBy->FirstName . ' ' . $uploadedBy->Surname) : $originalSubmission->SalespersonName;
            $subject = "{$uploaderName} has submitted the billing form";
            $body = "<p>A new billing form has been submitted for Serial Number: <strong>{$billingSubmission->SerialNumber}</strong></p>
                    <p><strong>Submission Details:</strong></p>
                    <p><strong>Salesperson:</strong> {$originalSubmission->SalespersonName}</p>
                    <p><strong>RES Number:</strong> {$originalSubmission->RESNumber}</p>
                    <p><strong>Transaction Type:</strong> {$originalSubmission->TransactionType}</p>
                    <p><strong>Property Address:</strong> {$originalSubmission->PropertyAddress}</p>
                    <p><strong>Upload Date:</strong> " . date('Y-m-d H:i:s') . "</p>

                    <p>Please review the billing submission in the <a href=\"https://billing.quinvest-chambers.com.sg/admin/billing-forms\">admin panel</a>.</p>
                    <p>Thank you!</p>";

            ResendEmail::send($to, $subject, $body);
        }

        private function sendBillingUpdateNotificationEmail($billingSubmission)
        {
            $to = [
                'felicia.teo@quinvest-chambers.com.sg',
                'Ian.loh@quinvest-chambers.com.sg',
                'wendy.low@quinvest-chambers.com.sg',
                'dinukasf2@gmail.com', // temp test
            ];

            $originalSubmission = $billingSubmission->FormSubmission();

            $subject = "Billing Form Submission Updated - {$originalSubmission->SerialNumber}";
            $body = "<p>A billing form submission has been updated for Serial Number: <strong>{$billingSubmission->SerialNumber}</strong></p>
                    <p><strong>Submission Details:</strong></p>
                    <p><strong>Salesperson:</strong> {$originalSubmission->SalespersonName}</p>
                    <p><strong>RES Number:</strong> {$originalSubmission->RESNumber}</p>
                    <p><strong>Transaction Type:</strong> {$originalSubmission->TransactionType}</p>
                    <p><strong>Property Address:</strong> {$originalSubmission->PropertyAddress}</p>
                    <p><strong>Updated Date:</strong> " . date('Y-m-d H:i:s') . "</p>

                    <p>Please review the updated billing submission in the <a href=\"https://billing.quinvest-chambers.com.sg/admin/billing-forms\">admin panel</a>.</p>
                    <p>Thank you!</p>";

            ResendEmail::send($to, $subject, $body);
        }

        public function getSession()
        {
            return $this->getRequest()->getSession();
        }

        public function getCurrentFormStep()
        {
            return $this->getRequest()->getSession()->get('FormStep') ?: 1;
        }

        public function getFormSubmissionID()
        {
            return $this->getRequest()->getSession()->get('FormSubmissionID');
        }

        public function getCurrentFormStepPercentage()
        {
            $currentStep = $this->getCurrentFormStep();
            return ($currentStep / 5) * 100;
        }

        public function getStepClass($step)
        {
            $currentStep = $this->getCurrentFormStep();

            if ($step == $currentStep) {
                return 'active';
            } elseif ($step < $currentStep) {
                return 'completed';
            }

            return '';
        }

        public function getCurrentStep()
        {
            return $this->getCurrentFormStep();
        }

        public function getStepTitle()
        {
            $step = $this->getCurrentFormStep();

            $titles = [
                1 => 'Step 1: Property Details',
                2 => 'Step 2: Client Information',
                3 => 'Step 3: AML Search & Forms',
                4 => 'Step 4: Document Upload',
                5 => 'Step 5: Review & Submit'
            ];

            return $titles[$step] ?? 'Form Submission';
        }

        public function getCurrentStepPercentage()
        {
            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;
            return ($currentStep / 5) * 100;
        }

        public function RequestVar($key)
        {
            return $this->getRequest()->getVar($key);
        }

        public function dashboard()
        {
            // Check if user is logged in
            if (!$this->IsLoggedIn()) {
                return $this->redirect('/Security/login?BackURL=/dashboard');
            }

            return [];
        }

        // Add view submission method
        public function viewSubmission()
        {
            $member = Security::getCurrentUser();
            if (!$member) {
                return $this->redirect('/Security/login?BackURL=/dashboard');
            }

            $request = $this->getRequest();
            $id = $request->param('ID');

            if ($id) {
                $submission = FormSubmission::get()->byID($id);
                if ($submission && $submission->RESNumber === $member->RESNumber) {
                    // Redirect to the new submission detail page
                    return $this->redirect("/submission-details/{$id}");
                }
            }

            return $this->redirect('/dashboard');
        }

        // Add download file method
        public function downloadFile()
        {
            $member = Security::getCurrentUser();
            if (!$member) {
                return $this->redirect('/Security/login?BackURL=/dashboard');
            }

            $request = $this->getRequest();
            $id = $request->param('ID');

            if ($id) {
                $file = File::get()->byID($id);
                if ($file && $file->exists()) {
                    // Check if user has permission to download this file
                    // You might want to add additional permission checks here
                    return HTTPRequest::send_file(
                        $file->getString(),
                        $file->getName(),
                        $file->getMimeType()
                    );
                }
            }

            return $this->httpError(404, 'File not found');
        }

        // Add method to get current user submissions
        public function getUserSubmissions()
        {
            $member = Security::getCurrentUser();
            if (!$member) {
                return ArrayList::create();
            }

            return FormSubmission::get()
                ->filter([
                    'RESNumber' => $member->RESNumber,
                    'Status:not' => 'Draft'
                ])
                ->sort('Created DESC');
        }

        // Add method to get user's billing submissions
        public function getUserBillingSubmissions()
        {
            $member = Security::getCurrentUser();
            if (!$member) {
                return ArrayList::create();
            }

            return BillingFormSubmission::get()
                ->filter([
                    'UploadedByID' => $member->ID
                ])
                ->sort('Created DESC');
        }

        public function getSubmissionDetail(HTTPRequest $request)
        {
            $member = Security::getCurrentUser();
            if (!$member) {
                return $this->jsonResponse(['success' => false, 'message' => 'Not authenticated']);
            }

            $id = $request->param('ID');
            if (!$id) {
                return $this->jsonResponse(['success' => false, 'message' => 'No submission ID provided']);
            }

            $submission = FormSubmission::get()->byID($id);
            if (!$submission || $submission->RESNumber !== $member->RESNumber) {
                return $this->jsonResponse(['success' => false, 'message' => 'Submission not found or access denied']);
            }

            // Render submission detail HTML
            $html = $this->renderSubmissionDetailHTML($submission);

            return $this->jsonResponse([
                'success' => true,
                'html' => $html
            ]);
        }

        // Helper method for JSON responses
        private function jsonResponse($data, $statusCode = 200)
        {
            $response = HTTPResponse::create(json_encode($data), $statusCode);
            $response->addHeader('Content-Type', 'application/json');
            return $response;
        }
    }
}
