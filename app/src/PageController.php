<?php

namespace {

    use App\Model\BillingFormSubmission;
    use SilverStripe\CMS\Controllers\ContentController;
    use SilverStripe\Forms\Validation\RequiredFieldsValidator;
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
    use SilverStripe\Control\Email\Email;

    /**
     * @template T of Page
     * @extends ContentController<T>
     */
    class PageController extends ContentController
    {
        private static $allowed_actions = [
            'MultiStepForm',
            'saveStep',
            'submitFinalForm',
            'logout',
            'BillingForm',
            'submitBillingForm'
        ];

        protected function init()
        {
            parent::init();

            // Handle step parameter from URL
            $step = $this->getRequest()->getVar('step');
            if ($step && is_numeric($step)) {
                $step = (int)$step;
                if ($step >= 1 && $step <= 6) {
                    $this->getRequest()->getSession()->set('FormStep', $step);
                }
            }
        }

        public function logout()
        {
            $member = Security::getCurrentUser();
            if ($member) {
                Security::setCurrentUser(null);
            }
            return $this->redirect('/Security/login');
        }

        public function getCurrentMember()
        {
            return Security::getCurrentUser();
        }

        public function IsFormPage()
        {
            return $this->request->getURL() === 'form-submission';
        }

        public function IsGuidelinesPage()
        {
            return $this->request->getURL() === 'guidelines';
        }

        public function BillingForm(): Form
        {
            $fields = FieldList::create(
                TextField::create('SerialNumber', 'Serial Number')
                    ->setAttribute('placeholder', 'Enter your submission serial number')
                    ->addExtraClass('form-input')
                    ->setAttribute('required', 'required'),

                FileField::create('BillingFile', 'Upload Billing Form')
                    ->setDescription('Allowed file types: pdf, jpg, jpeg, png, doc, docx')
                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx')
                    ->addExtraClass('form-input')
            );

            $actions = FieldList::create(
                FormAction::create('submitBillingForm', 'Submit Billing Form')
                    ->addExtraClass('btn btn-success')
                    ->setUseButtonTag(true)
            );

            $validator = RequiredFieldsValidator::create('SerialNumber');

            $form = Form::create($this, 'BillingForm', $fields, $actions, $validator);
            $form->setFormMethod('POST');
            $form->setEncType(Form::ENC_TYPE_MULTIPART);
            $form->addExtraClass('multi-step-form billing-form');
            $form->setAttribute('data-step', 'billing');

            return $form;
        }

        public function submitBillingForm($data, $form)
        {
            $serial = $data['SerialNumber'] ?? null;

            if (!$serial) {
                $form->sessionMessage('Please provide the serial number.', 'bad');
                return $this->redirectBack();
            }

            // Check if FormSubmission exists
            $submission = FormSubmission::get()->filter('SerialNumber', $serial)->first();
            if (!$submission) {
                $form->sessionMessage("No matching form submission found for Serial Number: {$serial}", 'bad');
                return $this->redirectBack();
            }

            // Check if file was uploaded
            if (!isset($_FILES['BillingFile']) || empty($_FILES['BillingFile']['tmp_name'])) {
                $form->sessionMessage('Please upload a billing file.', 'bad');
                return $this->redirectBack();
            }

            // Create BillingFormSubmission
            $billing = BillingFormSubmission::create();
            $billing->SerialNumber = $serial;
            $billing->FormSubmissionID = $submission->ID;
            $billing->UploadedByID = Security::getCurrentUser()->ID ?? 0;

            // Handle file upload with FileField
            $upload = Upload::create();

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

            $form->sessionMessage('Billing form submitted successfully!', 'good');
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
                $submission->write();
                $this->getRequest()->getSession()->set('FormSubmissionID', $submission->ID);
            }

            // Get current step from session or URL parameter
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
            $fields->push(HiddenField::create('SubmissionID', '', $submission->ID));

            switch ($step) {
                case 1:
                    $fields->push(TextField::create('SalespersonName', 'Name of Salesperson')
                        ->setAttribute('placeholder', 'Enter salesperson name')
                        ->setValue($submission->SalespersonName));

                    $fields->push(TextField::create('RESNumber', 'RES Number')
                        ->setAttribute('placeholder', 'Enter RES number')
                        ->setValue($submission->RESNumber));

                    $required = ['SalespersonName', 'RESNumber'];
                    break;

                case 2:
                    $fields->push(TextareaField::create('PropertyAddress', 'Address of Property')
                        ->setRows(3)
                        ->setValue($submission->PropertyAddress));

                    $fields->push(OptionsetField::create('TransactionType', 'Transaction Type', [
                        'Sale' => 'Sale',
                        'Lease' => 'Lease'
                    ])->setValue($submission->TransactionType));

                    $fields->push(CheckboxSetField::create('Representing', 'Who are you representing?', [
                        'Seller' => 'Seller',
                        'Buyer' => 'Buyer',
                        'Landlord' => 'Landlord',
                        'Tenant' => 'Tenant'
                    ])->setValue($submission->getRepresentingArray()));

                    $required = ['PropertyAddress', 'TransactionType', 'Representing'];
                    break;

                case 3:
                    $fields = $this->buildStep3Fields($submission);
                    $required = $this->getStep3Required($submission);
                    break;

                case 4:
                    $fields = $this->buildStep4Fields($submission);
                    $required = $this->getStep4Required($submission);
                    break;

                case 5:
                    $fields = $this->buildStep5Fields($submission);
                    $required = $this->getStep5Required($submission);
                    break;

                case 6:
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

            if ($step < 6) {
                $actions->push(FormAction::create('nextStep', 'Next')
                    ->addExtraClass('btn btn-primary')
                    ->setUseButtonTag(true));
            } else {
                $actions->push(FormAction::create('submitForm', 'Submit Form')
                    ->addExtraClass('btn btn-success')
                    ->setUseButtonTag(true));
            }

            $form = Form::create(
                $this,
                'MultiStepForm',
                $fields,
                $actions,
                RequiredFieldsValidator::create($required)
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
            $fields->push(HiddenField::create('CurrentStep', '', 3));
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

                    // Check if file already exists
                    $existingFile = null;
                    $existingFileName = '';
                    if ($clientInfo->OwnershipProofID) {
                        $existingFile = File::get()->byID($clientInfo->OwnershipProofID);
                        if ($existingFile && $existingFile->exists()) {
                            $existingFileName = $existingFile->Name;
                        }
                    }

                    // Use FileField instead of UploadField for Ownership Proof
                    $fileField = FileField::create("OwnershipProof_{$party}", 'Ownership Proof')
                        ->setDescription(in_array($party, ['Seller', 'Landlord']) ?
                            'Required - Allowed file types: pdf, jpg, jpeg, png, doc, docx' :
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

                        $fileField,

                        HiddenField::create("PartyType_{$party}", '', $party),
                        HiddenField::create("ClientInfoID_{$party}", '', $clientInfo->ID)
                    ];

                    // Add existing file info if file exists
                    if ($existingFileName) {
                        $wrapperContent[] = LiteralField::create(
                            "ExistingFile_{$party}",
                            "<div class='existing-file-info alert alert-info' style='margin-top: 10px; padding: 10px;'>
                                <strong>Currently uploaded file:</strong> {$existingFileName}
                                <br><small>Upload a new file to replace the existing one.</small>
                            </div>"
                        );
                    }

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
            $fields->push(HiddenField::create('CurrentStep', '', 4));
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
                        ->setDescription('Allowed file type: pdf')
                        ->setAttribute('accept', '.pdf'),
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
                        ->setDescription('Allowed file type: pdf')
                        ->setAttribute('accept', '.pdf'),
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

                    FileField::create("UCPForms_{$index}", 'Upload UCP Forms')
                        ->setDescription('Multiple files allowed - Allowed file type: pdf')
                        ->setAttribute('accept', '.pdf')
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
                        ->setDescription('Allowed file type: pdf')
                        ->setAttribute('accept', '.pdf'),
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
                        ->setDescription('Allowed file type: pdf')
                        ->setAttribute('accept', '.pdf'),
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
            $fields->push(HiddenField::create('CurrentStep', '', 5));
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
                    ->setDescription('Allowed file type: pdf')
                    ->setAttribute('accept', '.pdf'));

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
                    ->setDescription('Allowed file type: pdf')
                    ->setAttribute('accept', '.pdf'));

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
                ->setDescription('Allowed file type: pdf')
                ->setAttribute('accept', '.pdf'));

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
                ->setDescription('Allowed file type: pdf')
                ->setAttribute('accept', '.pdf'));

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
                ->setDescription('Allowed file type: pdf')
                ->setAttribute('accept', '.pdf'));

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
                    ->setDescription('Allowed file type: pdf')
                    ->setAttribute('accept', '.pdf'));

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
                ->setDescription('Multiple files allowed - Allowed file type: pdf')
                ->setAttribute('accept', '.pdf')
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
    $fields->push(HiddenField::create('CurrentStep', '', 6));
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
            
            // Show ownership proof status
            $ownershipProofStatus = $clientInfo->OwnershipProofID ? 'Uploaded' : 'Not Uploaded';
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">Ownership Proof:</span>';
            $reviewHTML .= '<span class="review-value">' . $ownershipProofStatus . '</span>';
            $reviewHTML .= '</div>';
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
            $reviewHTML .= '<span class="review-label">ECDD Required:</span>';
            $reviewHTML .= '<span class="review-value">' . ($amlRecord->ECDDRequired ? 'Yes' : 'No') . '</span>';
            $reviewHTML .= '</div>';
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">EA Approval Obtained:</span>';
            $reviewHTML .= '<span class="review-value">' . ($amlRecord->EAApprovalObtained ? 'Yes' : 'No') . '</span>';
            $reviewHTML .= '</div>';
            
            // Show file upload statuses
            $amlFileStatus = $amlRecord->AMLFileID ? 'Uploaded' : 'Not Uploaded';
            $formQCIDStatus = $amlRecord->FormQCIDID ? 'Uploaded' : 'Not Uploaded';
            
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">AML File:</span>';
            $reviewHTML .= '<span class="review-value">' . $amlFileStatus . '</span>';
            $reviewHTML .= '</div>';
            $reviewHTML .= '<div class="review-item">';
            $reviewHTML .= '<span class="review-label">Form QCI-D:</span>';
            $reviewHTML .= '<span class="review-value">' . $formQCIDStatus . '</span>';
            $reviewHTML .= '</div>';
        
        }
    } else {
        $reviewHTML .= '<div class="review-item">';
        $reviewHTML .= '<span class="review-value">No AML records found.</span>';
        $reviewHTML .= '</div>';
    }
    $reviewHTML .= '</div>';

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

            return $this->redirect($this->Link() . '?step=' . $nextStep);
        }

        public function previousStep($data, $form)
        {
            $currentStep = (int)$data['CurrentStep'];
            $previousStep = max(1, $currentStep - 1);

            $this->getRequest()->getSession()->set('FormStep', $previousStep);

            return $this->redirect($this->Link() . '?step=' . $previousStep);
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
                    $submission->SalespersonName = $data['SalespersonName'];
                    $submission->RESNumber = $data['RESNumber'];
                    $submission->write();
                    break;

                case 2:
                    $submission->PropertyAddress = $data['PropertyAddress'];
                    $submission->TransactionType = $data['TransactionType'];
                    $submission->setRepresentingArray($data['Representing']);
                    $submission->write();
                    break;

                case 3:
                    $this->saveStep3Data($data, $submission, $form);
                    break;

                case 4:
                    $this->saveStep4Data($data, $submission, $form);
                    break;

                case 5:
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

                // Handle file upload with FileField (same as billing form)
                $fieldName = "OwnershipProof_{$party}";
                if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
                    $upload = Upload::create();

                    try {
                        $file = File::create();
                        $upload->loadIntoFile($_FILES[$fieldName], $file, 'ownership-proofs/');

                        if ($file && $file->exists()) {
                            $file->publishSingle();
                            $clientInfo->OwnershipProofID = $file->ID;
                            $clientInfo->write();
                            error_log("✓ Ownership proof uploaded! File ID: {$file->ID}");
                        } else {
                            error_log("✗ File upload failed for OwnershipProof_{$party}");
                        }
                    } catch (\Exception $e) {
                        error_log("✗ Error uploading OwnershipProof_{$party}: " . $e->getMessage());
                    }
                } else {
                    error_log("No file uploaded for OwnershipProof_{$party}");
                }
            }

            error_log("=========================");
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
                $amlRecord->ECDDRequired = isset($data["ECDDRequired_{$index}"]) ? (bool)$data["ECDDRequired_{$index}"] : false;
                $amlRecord->EAApprovalObtained = isset($data["EAApproval_{$index}"]) ? (bool)$data["EAApproval_{$index}"] : false;
                $amlRecord->write();

                error_log("=== Step 4 File Upload Debug ===");

                // Handle single file uploads using FileField method
                $this->handleFileFieldUpload("AMLFile_{$index}", $amlRecord, 'AMLFileID', 'aml-files');
                $this->handleFileFieldUpload("FormQCID_{$index}", $amlRecord, 'FormQCIDID', 'form-qcid');

                // Handle multiple file uploads
                $this->handleMultipleFileFieldUpload("UCPForms_{$index}", $amlRecord, 'UCPForm', 'ucp-forms');

                // Handle ECDD Form and Form QCI-B as separate multiple file uploads
                $this->handleECDFileUpload("ECDDForm_{$index}", $amlRecord, 'ECDDForm', 'ecdd-forms');
                $this->handleECDFileUpload("FormQCIB_{$index}", $amlRecord, 'FormQCIB', 'form-qcib');

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
                $upload->loadIntoFile($fileData, $file, 'transaction-documents/');

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
                'wendy.low@quinvest-chambers.com.sg'
            ];

            $subject = "New Form Submission - {$submission->SerialNumber}";
            $body = "A new form has been submitted by {$submission->SalespersonName}.\n\n";
            $body .= "Serial Number: {$submission->SerialNumber}\n";
            $body .= "RES Number: {$submission->RESNumber}\n";
            $body .= "Transaction Type: {$submission->TransactionType}\n";
            $body .= "Property Address: {$submission->PropertyAddress}\n\n";
            $body .= "Please review the submission in the admin panel.";

            $email = Email::create()
                ->setTo($to)
                ->setSubject($subject)
                ->setBody($body);

            try {
                $email->send();
            } catch (\Exception $e) {
                // Log error but don't stop submission
                error_log("Failed to send notification email: " . $e->getMessage());
            }
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
            return ($currentStep / 6) * 100;
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
                1 => 'Step 1: Login Information',
                2 => 'Step 2: Property Details',
                3 => 'Step 3: Client Information',
                4 => 'Step 4: AML Search & Forms',
                5 => 'Step 5: Document Upload',
                6 => 'Step 6: Review & Submit'
            ];

            return $titles[$step] ?? 'Form Submission';
        }

        public function getCurrentStepPercentage()
        {
            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;
            return ($currentStep / 6) * 100;
        }

        public function getStep1Class()
        {
            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;
            return $currentStep == 1 ? 'active' : ($currentStep > 1 ? 'completed' : '');
        }

        public function getStep2Class()
        {
            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;
            return $currentStep == 2 ? 'active' : ($currentStep > 2 ? 'completed' : '');
        }

        public function getStep3Class()
        {
            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;
            return $currentStep == 3 ? 'active' : ($currentStep > 3 ? 'completed' : '');
        }

        public function getStep4Class()
        {
            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;
            return $currentStep == 4 ? 'active' : ($currentStep > 4 ? 'completed' : '');
        }

        public function getStep5Class()
        {
            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;
            return $currentStep == 5 ? 'active' : ($currentStep > 5 ? 'completed' : '');
        }

        public function getStep6Class()
        {
            $currentStep = $this->getRequest()->getSession()->get('FormStep') ?: 1;
            return $currentStep == 6 ? 'active' : '';
        }
    }
}
