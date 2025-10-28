<?php

namespace App\PageController;

use PageController;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Security;
use Quinvest\Model\FormSubmission;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;

class FormPageController extends PageController
{
    private static $allowed_actions = [
        'TransactionForm',
        'saveStep',
        'submitFinalForm',
    ];

    private static $url_handlers = [
        'TransactionForm' => 'TransactionForm',
    ];

    public function index(HTTPRequest $request)
    {
        // Check if user is logged in
        $member = Security::getCurrentUser();
        if (!$member) {
            return $this->redirect('Security/login');
        }

        return $this->renderWith(['FormPage', 'Page']);
    }

    public function TransactionForm()
    {
        $member = Security::getCurrentUser();
        if (!$member) {
            return $this->redirect('Security/login');
        }

        // Get or create form submission
        $submission = FormSubmission::get()->filter([
            'MemberID' => $member->ID,
            'Status' => 'draft'
        ])->first();

        if (!$submission) {
            $submission = FormSubmission::create();
            $submission->MemberID = $member->ID;
            $submission->Status = 'draft';
            $submission->CurrentStep = 1;
            $submission->write();
        }

        $currentStep = $submission->CurrentStep ?: 1;

        $fields = $this->getStepFields($currentStep, $submission);
        
        $actions = FieldList::create();
        
        if ($currentStep > 1) {
            $actions->push(FormAction::create('previousStep', 'Previous')
                ->addExtraClass('btn-secondary'));
        }
        
        if ($currentStep < 14) {
            $actions->push(FormAction::create('saveStep', 'Next')
                ->addExtraClass('btn-primary'));
        } else {
            $actions->push(FormAction::create('submitFinalForm', 'Submit')
                ->addExtraClass('btn-primary'));
        }

        $required = $this->getRequiredFields($currentStep);

        $form = Form::create($this, 'TransactionForm', $fields, $actions, $required);
        $form->loadDataFrom($submission);
        $form->addExtraClass('multi-step-form');

        return $form;
    }

    protected function getStepFields($step, $submission)
    {
        $fields = FieldList::create(
            HiddenField::create('ID'),
            HiddenField::create('CurrentStep', '', $step)
        );

        switch ($step) {
            case 1: // Basic Information
                $fields->push(TextField::create('Salesperson', 'Name of Salesperson')->setAttribute('required', true));
                $fields->push(TextField::create('RESNumber', 'RES Number')->setAttribute('required', true));
                $fields->push(TextareaField::create('PropertyAddress', 'Address of Property')->setAttribute('required', true)->setRows(3));
                break;

            case 2: // Amicus
                $fields->push(OptionsetField::create('UsedAmicus', 'Have you used Amicus to fill in all necessary forms?', [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]));
                $fields->push(FileField::create('AmicusScreenshot', 'Upload Amicus Submission Screenshot')
                    ->setFolderName('form-submissions/amicus'));
                break;

            case 3: // Transaction Type
                $fields->push(OptionsetField::create('TransactionType', 'Is this a Sale or Lease transaction?', [
                    'sale' => 'Sale',
                    'lease' => 'Lease'
                ]));
                break;

            case 4: // Representation
                $label2 = $submission->TransactionType === 'sale' ? 'Buyer' : 'Tenant';
                $label1 = $submission->TransactionType === 'sale' ? 'Seller' : 'Landlord';
                
                $fields->push(OptionsetField::create('Representation', 'Who are you representing?', [
                    'seller' => $label1,
                    'buyer' => $label2
                ]));
                break;

            case 5: // Client Type
                $fields->push(OptionsetField::create('ClientType', 'Is your client an:', [
                    'entity' => 'Entity',
                    'individual' => 'Individual',
                    'indiv_behalf_indiv' => 'Individual acting on behalf of another individual',
                    'indiv_himself' => 'Individual acting for himself',
                    'indiv_behalf_entity' => 'Individual acting on behalf of another (Entity/Legal arrangement)',
                    'entity_behalf_indiv' => 'Entity acting on behalf of another individual',
                    'entity_himself' => 'Entity acting for himself',
                    'entity_behalf_entity' => 'Entity acting on behalf of another (Entity/Legal arrangement)'
                ]));
                break;

            case 6: // AML Search
                $fields->push(OptionsetField::create('AMLSearchConducted', 'Have you conducted an AML search?', [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]));
                $fields->push(FileField::create('AMLFile', 'Upload AML PDF File')
                    ->setFolderName('form-submissions/aml'));
                break;

            case 7: // Form B Check
                $fields->push(OptionsetField::create('FormBCheckedYes', 'Was any component under section 2 of Form B checked \'Yes\'?', [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]));
                $fields->push(FileField::create('FormQCID', 'Upload Form QCI-D')
                    ->setFolderName('form-submissions/qci-d'));
                break;

            case 8: // Other Party
                $fields->push(OptionsetField::create('OtherPartyRepresented', 'Is the other party represented?', [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]));
                break;

            case 9: // Other Party Type
                if ($submission->OtherPartyRepresented === 'no') {
                    $fields->push(OptionsetField::create('OtherPartyType', 'The other party is an:', [
                        'ucp_indiv_behalf_indiv' => '(UCP) Individual acting on behalf of another individual',
                        'ucp_indiv_himself' => '(UCP) Individual acting for himself',
                        'ucp_indiv_behalf_entity' => '(UCP) Individual acting on behalf of another (Entity/Legal arrangement)',
                        'entity' => 'Entity',
                        'individual' => 'Individual',
                        'ucp_entity_behalf_indiv' => '(UCP) Entity acting on behalf of another individual',
                        'ucp_entity_himself' => '(UCP) Entity acting for himself',
                        'ucp_entity_behalf_entity' => '(UCP) Entity acting on behalf of another (Entity/Legal arrangement)'
                    ]));
                }
                break;

            case 10: // ECDD
                $fields->push(OptionsetField::create('ECDDWarranted', 'After checking the CDD and AML forms, is an ECDD warranted?', [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]));
                $fields->push(FileField::create('ECDDForm', 'Upload ECDD Form')
                    ->setFolderName('form-submissions/ecdd'));
                $fields->push(FileField::create('FormQCIB', 'Upload Form QCI-B')
                    ->setFolderName('form-submissions/qci-b'));
                $fields->push(OptionsetField::create('EAApprovalObtained', 'Has EA\'s approval been obtained?', [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]));
                break;

            case 11: // Sale Documents
                if ($submission->TransactionType === 'sale') {
                    $fields->push(OptionsetField::create('HasOTP', 'Is there an OTP?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('OTPFile', 'Upload OTP')->setFolderName('form-submissions/sale'));
                    
                    $fields->push(OptionsetField::create('HasCEAAgreementSale', 'Is there a CEA Agreement?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('CEAAgreementSaleFile', 'Upload CEA Agreement')->setFolderName('form-submissions/sale'));
                    
                    $fields->push(OptionsetField::create('HasCobrokeSale', 'Is there a Co-broke Agreement?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('CobrokeSaleFile', 'Upload Co-broke Agreement')->setFolderName('form-submissions/sale'));
                    
                    $fields->push(OptionsetField::create('HasCommissionSale', 'Is there a Commission Agreement (non-residential)?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('CommissionSaleFile', 'Upload Commission Agreement')->setFolderName('form-submissions/sale'));
                    
                    $fields->push(OptionsetField::create('HasOwnershipSale', 'Is there Ownership Proof?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('OwnershipSaleFile', 'Upload Ownership Proof')->setFolderName('form-submissions/sale'));
                }
                break;

            case 12: // Lease Documents
                if ($submission->TransactionType === 'lease') {
                    $fields->push(OptionsetField::create('HasTenancyAgreement', 'Is there a Tenancy Agreement?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('TenancyAgreementFile', 'Upload Tenancy Agreement')->setFolderName('form-submissions/lease'));
                    
                    $fields->push(OptionsetField::create('HasCEAAgreementLease', 'Is there a CEA Agreement?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('CEAAgreementLeaseFile', 'Upload CEA Agreement')->setFolderName('form-submissions/lease'));
                    
                    $fields->push(OptionsetField::create('HasCobrokeLease', 'Is there a Co-broke Agreement?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('CobrokeLeaseFile', 'Upload Co-broke Agreement')->setFolderName('form-submissions/lease'));
                    
                    $fields->push(OptionsetField::create('HasCommissionLease', 'Is there a Commission Agreement (nrp)?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('CommissionLeaseFile', 'Upload Commission Agreement')->setFolderName('form-submissions/lease'));
                    
                    $fields->push(OptionsetField::create('HasHDBApproval', 'Is there HDB Approval letter for subletting?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('HDBApprovalFile', 'Upload HDB Approval Letter')->setFolderName('form-submissions/lease'));
                    
                    $fields->push(OptionsetField::create('HasOwnershipLease', 'Is there Ownership Proof?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('OwnershipLeaseFile', 'Upload Ownership Proof')->setFolderName('form-submissions/lease'));
                    
                    $fields->push(OptionsetField::create('HasAuthorizationLetter', 'Is there an Authorization Letter?', ['yes' => 'Yes', 'no' => 'No']));
                    $fields->push(FileField::create('AuthorizationLetterFile', 'Upload Authorization Letter')->setFolderName('form-submissions/lease'));
                }
                break;

            case 13: // Other Documents
                $fields->push(OptionsetField::create('HasOtherDocuments', 'Are there any other documents?', ['yes' => 'Yes', 'no' => 'No']));
                $fields->push(TextField::create('OtherDocumentsDescription', 'Please specify:'));
                break;

            case 14: // Review
                $fields->push(\SilverStripe\Forms\LiteralField::create('review', $this->generateReviewHTML($submission)));
                break;
        }

        return $fields;
    }

    protected function getRequiredFields($step)
    {
        $required = [];

        switch ($step) {
            case 1:
                $required = ['Salesperson', 'RESNumber', 'PropertyAddress'];
                break;
            case 2:
                $required = ['UsedAmicus'];
                break;
            case 3:
                $required = ['TransactionType'];
                break;
            case 4:
                $required = ['Representation'];
                break;
            case 5:
                $required = ['ClientType'];
                break;
            case 6:
                $required = ['AMLSearchConducted'];
                break;
        }

        return RequiredFieldsValidator::create($required);
    }

    public function saveStep($data, $form)
    {
        try {
            $submission = FormSubmission::get()->byID($data['ID']);
            if (!$submission) {
                $submission = FormSubmission::create();
                $submission->MemberID = Security::getCurrentUser()->ID;
            }

            $form->saveInto($submission);
            
            // Move to next step
            $currentStep = (int)$data['CurrentStep'];
            $nextStep = $this->getNextStep($currentStep, $submission);
            $submission->CurrentStep = $nextStep;
            
            $submission->write();

            return $this->redirectBack();
        } catch (ValidationException $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
            return $this->redirectBack();
        }
    }

    public function submitFinalForm($data, $form)
    {
        try {
            $submission = FormSubmission::get()->byID($data['ID']);
            if (!$submission) {
                throw new \Exception('Submission not found');
            }

            $form->saveInto($submission);
            $submission->Status = 'submitted';
            $submission->SubmittedDate = date('Y-m-d H:i:s');
            $submission->CurrentStep = 15;
            $submission->write();

            $this->getRequest()->getSession()->set('SubmissionID', $submission->ID);

            return $this->redirect($this->Link('success'));
        } catch (\Exception $e) {
            $form->sessionMessage($e->getMessage(), 'bad');
            return $this->redirectBack();
        }
    }

    protected function getNextStep($currentStep, $submission)
    {
        // Skip logic based on answers
        if ($currentStep === 6 && $submission->AMLSearchConducted === 'no') {
            return 6; // Stay on same step
        }

        if ($currentStep === 8 && $submission->OtherPartyRepresented === 'yes') {
            return 10; // Skip step 9
        }

        if ($currentStep === 10) {
            if ($submission->ECDDWarranted === 'yes' && $submission->EAApprovalObtained === 'no') {
                return 10; // Stay on same step
            }
            // Go to transaction-specific documents
            return $submission->TransactionType === 'sale' ? 11 : 12;
        }

        if ($currentStep === 11 || $currentStep === 12) {
            return 13;
        }

        return $currentStep + 1;
    }

    protected function generateReviewHTML($submission)
    {
        $html = '<div class="review-content">';
        $html .= '<h4>Summary</h4>';
        $html .= '<p><strong>Salesperson:</strong> ' . $submission->Salesperson . '</p>';
        $html .= '<p><strong>RES Number:</strong> ' . $submission->RESNumber . '</p>';
        $html .= '<p><strong>Property Address:</strong> ' . $submission->PropertyAddress . '</p>';
        $html .= '<p><strong>Transaction Type:</strong> ' . ucfirst($submission->TransactionType) . '</p>';
        $html .= '<p><strong>Representation:</strong> ' . ucfirst($submission->Representation) . '</p>';
        $html .= '</div>';
        
        return $html;
    }

    public function success()
    {
        $submissionID = $this->getRequest()->getSession()->get('SubmissionID');
        $submission = FormSubmission::get()->byID($submissionID);
        
        return $this->customise([
            'Submission' => $submission
        ])->renderWith(['FormPage_success', 'Page']);
    }
}