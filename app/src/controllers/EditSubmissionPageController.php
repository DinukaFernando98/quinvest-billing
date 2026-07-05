<?php

namespace App\Controller;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Security\Security;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\FileField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Upload;
use App\Model\FormSubmission;
use App\Model\ClientInfo;
use App\Model\AMLRecord;
use App\Model\SubmissionDocument;
use App\Model\ECDDForm;
use App\Model\UCPForm;
use SilverStripe\Control\Email\Email;
use SilverStripe\View\Requirements;
use PageController;

class EditSubmissionPageController extends PageController
{
    private static $allowed_actions = [
        'index',
        'EditForm',
        'saveEditForm',
    ];

    protected $submission;
    protected $member;

    protected function init()
    {
        parent::init();

        $this->member = Security::getCurrentUser();
        if (!$this->member) {
            return $this->redirect('/Security/login?BackURL=' . urlencode($this->getRequest()->getURL()));
        }

        // Show success toast when redirected back with ?success=1
        Requirements::customScript('
            document.addEventListener("DOMContentLoaded", function() {
                if (window.location.search.includes("success=1")) {
                    var popup = document.createElement("div");
                    popup.className = "alert alert-success alert-floating";
                    popup.style.cssText = "position:fixed;top:100px;right:20px;min-width:300px;z-index:3000;animation:slideInRight .3s ease-out;";
                    popup.textContent = "Submission updated successfully! Administrators have been notified.";
                    document.body.appendChild(popup);
                    setTimeout(function() {
                        popup.style.animation = "slideOutRight .3s ease-out";
                        setTimeout(function() { popup.remove(); }, 300);
                    }, 5000);
                }
            });
        ', 'edit-page-init');
    }

    public function index(HTTPRequest $request)
    {
        $id = $request->getVar('request');
        if (!$id) {
            return $this->httpError(404, 'Submission ID not provided');
        }

        $this->submission = FormSubmission::get()->byID($id);
        if (!$this->submission) {
            return $this->httpError(404, 'Submission not found');
        }

        if ($this->submission->RESNumber !== $this->member->RESNumber) {
            return $this->httpError(403, 'You do not have permission to edit this submission');
        }

        if (in_array($this->submission->Status, ['Declined', 'Approved'])) {
            return $this->httpError(400, 'This submission cannot be edited as it has already been ' . $this->submission->Status);
        }

        return [
            'Title' => 'Edit Submission #' . $this->submission->SerialNumber,
            'Form' => $this->EditForm(),
            'Submission' => $this->submission,
        ];
    }

    public function EditForm()
    {
        if (!$this->submission) {
            $request = $this->getRequest();
            $id = $request->getVar('request') ?: $request->postVar('SubmissionID');
            $this->submission = $id ? FormSubmission::get()->byID($id) : null;
            if (!$this->submission) {
                return null;
            }
        }

        $submission = $this->submission;
        $fields = FieldList::create();
        $fields->push(HiddenField::create('SubmissionID', '', $submission->ID));

        // ── Section 1: Property Details ─────────────────────────────────────
        $representingArr = $submission->getRepresentingArray();
        $representingValue = reset($representingArr) ?: null;

        $propSection = CompositeField::create([
            LiteralField::create('PropHeader', "<h3 class='client-section-title'>Property Details</h3>"),

            TextField::create('SalespersonName', 'Salesperson Name')
                ->setValue($submission->SalespersonName)
                ->setAttribute('readonly', 'readonly'),

            TextField::create('RESNumber', 'RES Number')
                ->setValue($submission->RESNumber)
                ->setAttribute('readonly', 'readonly'),

            TextareaField::create('PropertyAddress', 'Property Address')
                ->setValue($submission->PropertyAddress)
                ->setRows(3),

            OptionsetField::create('TransactionType', 'Transaction Type', [
                'Sale' => 'Sale',
                'Lease' => 'Lease',
            ])->setValue($submission->TransactionType),

            OptionsetField::create('Representing', 'Who are you representing?', [
                'Seller' => 'Seller',
                'Buyer' => 'Buyer',
                'Landlord' => 'Landlord',
                'Tenant' => 'Tenant',
            ])->setValue($representingValue),
        ]);
        $propSection->addExtraClass('client-section');
        $fields->push($propSection);

        // ── Section 2: Client Information (per party) ───────────────────────
        $representing = $submission->getRepresentingArray();

        foreach ($representing as $party) {
            $clientInfo = $submission->ClientInfo()->filter(['PartyType' => $party])->first();

            // Pre-load existing file names for all 6 file fields
            $existingFiles = [];
            foreach (['OwnershipProofID', 'FormA1FileID', 'FormA2FileID', 'FormA3FileID', 'FormA4FileID', 'FormBFileID'] as $idField) {
                $existingFiles[$idField] = '';
                if ($clientInfo && $clientInfo->{$idField}) {
                    $f = File::get()->byID($clientInfo->{$idField});
                    if ($f && $f->exists()) {
                        $existingFiles[$idField] = $f->Name;
                    }
                }
            }

            $clientContent = [
                LiteralField::create("ClientHeader{$party}", "<h3 class='client-section-title'>{$party} Information</h3>"),

                OptionsetField::create("ClientType_{$party}", 'Is your client an:', [
                    'Individual' => 'Individual',
                    'Entity' => 'Entity',
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

            if ($existingFiles['FormA1FileID']) {
                $clientContent[] = LiteralField::create("ExistingFormA1File_{$party}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> {$existingFiles['FormA1FileID']}
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $clientContent[] = FileField::create("FormA2File_{$party}", 'Form A2 — Customer Particulars Form (For Entity/Legal Arrangement)')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

            if ($existingFiles['FormA2FileID']) {
                $clientContent[] = LiteralField::create("ExistingFormA2File_{$party}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> {$existingFiles['FormA2FileID']}
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $clientContent[] = FileField::create("FormA3File_{$party}", 'Form A3 — Particulars of Individual your Client is acting on behalf of')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

            if ($existingFiles['FormA3FileID']) {
                $clientContent[] = LiteralField::create("ExistingFormA3File_{$party}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> {$existingFiles['FormA3FileID']}
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $clientContent[] = FileField::create("FormA4File_{$party}", 'Form A4 — Particulars of Legal Person your Client is acting on behalf of')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

            if ($existingFiles['FormA4FileID']) {
                $clientContent[] = LiteralField::create("ExistingFormA4File_{$party}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> {$existingFiles['FormA4FileID']}
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $clientContent[] = FileField::create("FormBFile_{$party}", 'Form B — Risk Determination and Screening Checklist')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

            if ($existingFiles['FormBFileID']) {
                $clientContent[] = LiteralField::create("ExistingFormBFile_{$party}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> {$existingFiles['FormBFileID']}
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $clientContent[] = FileField::create("OwnershipProof_{$party}", 'Ownership Proof')
                ->setDescription(in_array($party, ['Seller', 'Landlord'])
                    ? 'Required (excluding listed companies)'
                    : 'Optional - Allowed file types: pdf, jpg, jpeg, png, doc, docx')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

            if ($existingFiles['OwnershipProofID']) {
                $clientContent[] = LiteralField::create("ExistingFile_{$party}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> {$existingFiles['OwnershipProofID']}
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $clientContent[] = HiddenField::create("PartyType_{$party}", '', $party);
            $clientContent[] = HiddenField::create("ClientInfoID_{$party}", '', $clientInfo ? $clientInfo->ID : 0);

            $clientSection = CompositeField::create($clientContent);
            $clientSection->addExtraClass('client-section');
            $fields->push($clientSection);
        }

        // ── Section 3: AML Records (per party) ──────────────────────────────
        $amlIndex = 0;
        foreach ($representing as $party) {
            $amlRecord = $submission->AMLRecords()->filter(['PartyType' => $party])->first();

            // Existing AML file
            $existingAMLFileName = '';
            if ($amlRecord && $amlRecord->AMLFileID) {
                $f = File::get()->byID($amlRecord->AMLFileID);
                if ($f && $f->exists()) $existingAMLFileName = $f->Name;
            }

            // Existing FormQCID file
            $existingFormQCFileName = '';
            if ($amlRecord && $amlRecord->FormQCIDID) {
                $f = File::get()->byID($amlRecord->FormQCIDID);
                if ($f && $f->exists()) $existingFormQCFileName = $f->Name;
            }

            // Existing UCP form files
            $existingUCPNames = [];
            if ($amlRecord) {
                foreach ($amlRecord->UCPForms() as $ucpForm) {
                    if ($ucpForm->FormFile() && $ucpForm->FormFile()->exists()) {
                        $existingUCPNames[] = $ucpForm->FormFile()->Name;
                    }
                }
            }

            // Existing ECDD / FormQCIB files
            $existingECDDNames = [];
            $existingQCIBNames = [];
            if ($amlRecord) {
                foreach ($amlRecord->ECDDForms() as $ecddForm) {
                    if ($ecddForm->FormFile() && $ecddForm->FormFile()->exists()) {
                        if ($ecddForm->FormType === 'ECDDForm') {
                            $existingECDDNames[] = $ecddForm->FormFile()->Name;
                        } elseif ($ecddForm->FormType === 'FormQCIB') {
                            $existingQCIBNames[] = $ecddForm->FormFile()->Name;
                        }
                    }
                }
            }

            $amlContent = [
                LiteralField::create("AMLHeader{$amlIndex}",
                    "<h3 class='aml-section-title'>{$party} - AML Search &amp; Forms</h3>"),

                OptionsetField::create("AMLCompleted_{$amlIndex}", 'Have you conducted an AML search?', [
                    '1' => 'Yes',
                    '0' => 'No',
                ])->setValue($amlRecord ? (string)$amlRecord->AMLCompleted : null),

                FileField::create("AMLFile_{$amlIndex}", 'Upload AML PDF file')
                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'),
            ];

            if ($existingAMLFileName) {
                $amlContent[] = LiteralField::create("ExistingAMLFile_{$amlIndex}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded AML file:</strong> {$existingAMLFileName}
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $amlContent = array_merge($amlContent, [
                OptionsetField::create("FormBSection2_{$amlIndex}", 'Was any component under section 2 of Form B checked "Yes"?', [
                    '1' => 'Yes',
                    '0' => 'No',
                ])->setValue($amlRecord ? (string)$amlRecord->FormBSection2Checked : null),

                FileField::create("FormQCID_{$amlIndex}", 'Upload Form QCI-D')
                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'),
            ]);

            if ($existingFormQCFileName) {
                $amlContent[] = LiteralField::create("ExistingFormQCID_{$amlIndex}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded Form QCI-D:</strong> {$existingFormQCFileName}
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $amlContent = array_merge($amlContent, [
                OptionsetField::create("OtherPartyRepresented_{$amlIndex}", 'Is the other party Represented?', [
                    '1' => 'Yes',
                    '0' => 'No',
                ])->setValue($amlRecord ? (string)$amlRecord->OtherPartyRepresented : null),

                OptionsetField::create("UCPType_{$amlIndex}", 'Is the other party (UCP) an:', [
                    'Individual' => 'Individual',
                    'Entity' => 'Entity',
                ])->setValue($amlRecord ? $amlRecord->UCPType : null),

                DropdownField::create("UCPActingType_{$amlIndex}", 'Specific UCP type:', [
                    '' => '-- Select type --',
                    'UCP (Individual) acting for himself' => 'UCP (Individual) acting for himself',
                    'UCP (Individual) acting on behalf of another individual' => 'UCP (Individual) acting on behalf of another individual',
                    'UCP (Individual) acting on behalf of another (Entity/Legal Arrangement)' => 'UCP (Individual) acting on behalf of another (Entity/Legal Arrangement)',
                    'UCP (Entity/Legal Arrangement) acting for himself' => 'UCP (Entity/Legal Arrangement) acting for himself',
                    'UCP (Entity/Legal Arrangement) acting on behalf of another individual' => 'UCP (Entity/Legal Arrangement) acting on behalf of another individual',
                    'UCP (Entity/Legal Arrangement) acting on behalf of another (Entity/Legal Arrangement)' => 'UCP (Entity/Legal Arrangement) acting on behalf of another (Entity/Legal Arrangement)',
                ])->setValue($amlRecord ? $amlRecord->UCPActingType : null),

                FileField::create("UCPForms_{$amlIndex}", 'Upload UCP Forms')
                    ->setDescription('Multiple files allowed')
                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx')
                    ->setAttribute('multiple', 'multiple'),
            ]);

            if (!empty($existingUCPNames)) {
                $amlContent[] = LiteralField::create("ExistingUCPForms_{$amlIndex}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded UCP Forms:</strong> " . implode(', ', $existingUCPNames) . "
                        <br><small>Upload new files to add to or replace existing ones.</small>
                    </div>"
                );
            }

            $amlContent = array_merge($amlContent, [
                OptionsetField::create("ECDDRequired_{$amlIndex}", 'After checking the CDD and AML forms, is ECDD warranted?', [
                    '1' => 'Yes',
                    '0' => 'No',
                ])->setValue($amlRecord ? (string)$amlRecord->ECDDRequired : null),

                FileField::create("ECDDForm_{$amlIndex}", 'Upload ECDD Form')
                    ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx'),
            ]);

            if (!empty($existingECDDNames)) {
                $amlContent[] = LiteralField::create("ExistingECDDForm_{$amlIndex}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded ECDD Form:</strong> " . implode(', ', $existingECDDNames) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $amlContent[] = FileField::create("FormQCIB_{$amlIndex}", 'Upload Form QCI-B')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

            if (!empty($existingQCIBNames)) {
                $amlContent[] = LiteralField::create("ExistingFormQCIB_{$amlIndex}",
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded Form QCI-B:</strong> " . implode(', ', $existingQCIBNames) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }

            $amlContent = array_merge($amlContent, [
                OptionsetField::create("EAApproval_{$amlIndex}", "Has EA's approval been obtained?", [
                    '1' => 'Yes',
                    '0' => 'No',
                ])->setValue($amlRecord ? (string)$amlRecord->EAApprovalObtained : null),

                HiddenField::create("AMLPartyType_{$amlIndex}", '', $party),
                HiddenField::create("AMLRecordID_{$amlIndex}", '', $amlRecord ? $amlRecord->ID : 0),
            ]);

            $amlSection = CompositeField::create($amlContent);
            $amlSection->addExtraClass('aml-section');
            $fields->push($amlSection);
            $amlIndex++;
        }

        // ── Section 4: Documents ─────────────────────────────────────────────
        $existingDocs = $submission->Documents();
        $existingDocsByType = [];
        foreach ($existingDocs as $doc) {
            $existingDocsByType[$doc->DocumentType][] = $doc;
        }

        $transactionType = $submission->TransactionType;

        $docContent = [
            LiteralField::create('DocumentsHeader', "<h3 class='aml-section-title'>Documents</h3>"),
        ];

        // Transaction-specific document
        if ($transactionType === 'Sale') {
            $docContent[] = FileField::create('OptionToPurchase', 'Option To Purchase / Sales Agreement')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

            $existingOTP = $existingDocsByType['Option To Purchase / Sales Agreement'] ?? [];
            if (!empty($existingOTP)) {
                $names = array_filter(array_map(function($d) { return $d->DocumentFile() && $d->DocumentFile()->exists() ? $d->DocumentFile()->Name : null; }, $existingOTP));
                if ($names) {
                    $docContent[] = LiteralField::create('ExistingOptionToPurchase',
                        "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                            <strong>Currently uploaded file:</strong> " . implode(', ', $names) . "
                            <br><small>Upload a new file to replace the existing one.</small>
                        </div>"
                    );
                }
            }
        } else {
            $docContent[] = FileField::create('TenancyAgreement', 'Tenancy Agreement / Letter Of Intent / Letter Of Offer')
                ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');

            $existingTA = $existingDocsByType['Tenancy Agreement / Letter Of Intent / Letter Of Offer'] ?? [];
            if (!empty($existingTA)) {
                $names = array_filter(array_map(function($d) { return $d->DocumentFile() && $d->DocumentFile()->exists() ? $d->DocumentFile()->Name : null; }, $existingTA));
                if ($names) {
                    $docContent[] = LiteralField::create('ExistingTenancyAgreement',
                        "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                            <strong>Currently uploaded file:</strong> " . implode(', ', $names) . "
                            <br><small>Upload a new file to replace the existing one.</small>
                        </div>"
                    );
                }
            }
        }

        // CEA Agreement
        $existingCEA = $existingDocsByType['CEA Agreement'] ?? [];
        $hasCEA = !empty($existingCEA) ? '1' : '0';
        $docContent[] = OptionsetField::create('HasCEAAgreement', 'Is there a CEA Agreement?', ['1' => 'Yes', '0' => 'No'])->setValue($hasCEA);
        $docContent[] = FileField::create('CEAAgreement', 'Upload CEA Agreement')->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');
        if (!empty($existingCEA)) {
            $names = array_filter(array_map(function($d) { return $d->DocumentFile() && $d->DocumentFile()->exists() ? $d->DocumentFile()->Name : null; }, $existingCEA));
            if ($names) {
                $docContent[] = LiteralField::create('ExistingCEAAgreement',
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> " . implode(', ', $names) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }
        }

        // Co-broke Agreement
        $existingCobroke = $existingDocsByType['Co-broke Agreement'] ?? [];
        $hasCobroke = !empty($existingCobroke) ? '1' : '0';
        $docContent[] = OptionsetField::create('HasCobrokeAgreement', 'Is there a Co-broke Agreement?', ['1' => 'Yes', '0' => 'No'])->setValue($hasCobroke);
        $docContent[] = FileField::create('CobrokeAgreement', 'Upload Co-broke Agreement')->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');
        if (!empty($existingCobroke)) {
            $names = array_filter(array_map(function($d) { return $d->DocumentFile() && $d->DocumentFile()->exists() ? $d->DocumentFile()->Name : null; }, $existingCobroke));
            if ($names) {
                $docContent[] = LiteralField::create('ExistingCobrokeAgreement',
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> " . implode(', ', $names) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }
        }

        // Commission Agreement
        $existingCommission = $existingDocsByType['Commission Agreement'] ?? [];
        $hasCommission = !empty($existingCommission) ? '1' : '0';
        $docContent[] = OptionsetField::create('HasCommissionAgreement', 'Is there a Commission Agreement?', ['1' => 'Yes', '0' => 'No'])->setValue($hasCommission);
        $docContent[] = FileField::create('CommissionAgreement', 'Upload Commission Agreement')->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');
        if (!empty($existingCommission)) {
            $names = array_filter(array_map(function($d) { return $d->DocumentFile() && $d->DocumentFile()->exists() ? $d->DocumentFile()->Name : null; }, $existingCommission));
            if ($names) {
                $docContent[] = LiteralField::create('ExistingCommissionAgreement',
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded file:</strong> " . implode(', ', $names) . "
                        <br><small>Upload a new file to replace the existing one.</small>
                    </div>"
                );
            }
        }

        // HDB Approval (Lease only)
        if ($transactionType === 'Lease') {
            $existingHDB = $existingDocsByType['HDB Approval Letter'] ?? [];
            $hasHDB = !empty($existingHDB) ? '1' : '0';
            $docContent[] = OptionsetField::create('HasHDBApproval', 'Is there HDB Approval letter for subletting?', ['1' => 'Yes', '0' => 'No'])->setValue($hasHDB);
            $docContent[] = FileField::create('HDBApproval', 'Upload HDB Approval letter')->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx');
            if (!empty($existingHDB)) {
                $names = array_filter(array_map(function($d) { return $d->DocumentFile() && $d->DocumentFile()->exists() ? $d->DocumentFile()->Name : null; }, $existingHDB));
                if ($names) {
                    $docContent[] = LiteralField::create('ExistingHDBApproval',
                        "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                            <strong>Currently uploaded file:</strong> " . implode(', ', $names) . "
                            <br><small>Upload a new file to replace the existing one.</small>
                        </div>"
                    );
                }
            }
        }

        // Other Documents
        $existingOther = $existingDocsByType['Other Documents'] ?? [];
        $hasOther = !empty($existingOther) ? '1' : '0';
        $otherDesc = '';
        if (!empty($existingOther)) {
            $otherDesc = $existingOther[0]->Description ?? '';
        }
        $docContent[] = OptionsetField::create('HasOtherDocuments', 'Are there any other documents?', ['1' => 'Yes', '0' => 'No'])->setValue($hasOther);
        $docContent[] = TextField::create('OtherDocumentsDescription', 'Please specify')->setValue($otherDesc);
        $docContent[] = FileField::create('OtherDocuments', 'Upload other documents (Max 3 files)')
            ->setDescription('Multiple files allowed')
            ->setAttribute('accept', '.pdf,.jpg,.jpeg,.png,.doc,.docx')
            ->setAttribute('multiple', 'multiple');
        if (!empty($existingOther)) {
            $names = array_filter(array_map(function($d) { return $d->DocumentFile() && $d->DocumentFile()->exists() ? $d->DocumentFile()->Name : null; }, $existingOther));
            if ($names) {
                $docContent[] = LiteralField::create('ExistingOtherDocuments',
                    "<div class='existing-file-info alert alert-info' style='margin-top:10px;padding:10px;'>
                        <strong>Currently uploaded files:</strong> " . implode(', ', $names) . "
                        <br><small>Upload new files to add to or replace existing ones.</small>
                    </div>"
                );
            }
        }

        $docSection = CompositeField::create($docContent);
        $docSection->addExtraClass('aml-section');
        $fields->push($docSection);

        // ── Actions ──────────────────────────────────────────────────────────
        $actions = FieldList::create(
            FormAction::create('saveEditForm', 'Save Changes')
                ->addExtraClass('btn btn-primary')
                ->setUseButtonTag(true),
            LiteralField::create('CancelButton',
                '<a href="/dashboard" class="btn btn-secondary" style="margin-left:0.5rem;">Cancel</a>')
        );

        $validator = RequiredFields::create(['PropertyAddress', 'TransactionType', 'Representing']);

        $form = Form::create($this, 'EditForm', $fields, $actions, $validator);
        // multi-step-form makes styleMultiStepForm() + handleConditionalFieldsStep5() from main.js work on this page too
        $form->addExtraClass('edit-page-form multi-step-form');
        $form->setFormMethod('POST');
        $form->setEncType(Form::ENC_TYPE_MULTIPART);
        $form->setFormAction($this->Link('EditForm') . '?request=' . $submission->ID);

        return $form;
    }

    public function saveEditForm($data, $form)
    {
        $submissionID = $data['SubmissionID'] ?? null;
        if (!$submissionID) {
            $form->sessionMessage('Submission ID not found', 'bad');
            return $this->redirectBack();
        }

        $submission = FormSubmission::get()->byID($submissionID);
        if (!$submission || $submission->RESNumber !== $this->member->RESNumber) {
            $form->sessionMessage('Permission denied or submission not found', 'bad');
            return $this->redirectBack();
        }

        try {
            // Basic info
            $submission->PropertyAddress = $data['PropertyAddress'] ?? '';
            $submission->TransactionType = $data['TransactionType'] ?? '';
            if (isset($data['Representing'])) {
                $val = $data['Representing'];
                $submission->setRepresentingArray(is_array($val) ? $val : [$val]);
            }

            $representing = $submission->getRepresentingArray();
            $submissionFolder = 'submissions/' . $submission->SerialNumber . '/client-info';

            // Save Client Info per party
            foreach ($representing as $party) {
                $clientInfoID = $data["ClientInfoID_{$party}"] ?? 0;
                if ($clientInfoID) {
                    $clientInfo = ClientInfo::get()->byID($clientInfoID);
                }
                if (empty($clientInfo) || !$clientInfo) {
                    $clientInfo = ClientInfo::create();
                    $clientInfo->FormSubmissionID = $submission->ID;
                    $clientInfo->PartyType = $party;
                }

                $clientInfo->ClientType = $data["ClientType_{$party}"] ?? null;
                $clientInfo->ClientActingTypeSelection = $data["ClientActingType_{$party}"] ?? null;
                $clientInfo->ActingType = $data["ClientActingType_{$party}"] ?? null;
                $clientInfo->write();

                // Upload all 6 file fields for this party
                $fileUploads = [
                    "OwnershipProof_{$party}" => 'OwnershipProofID',
                    "FormA1File_{$party}"     => 'FormA1FileID',
                    "FormA2File_{$party}"     => 'FormA2FileID',
                    "FormA3File_{$party}"     => 'FormA3FileID',
                    "FormA4File_{$party}"     => 'FormA4FileID',
                    "FormBFile_{$party}"      => 'FormBFileID',
                ];
                foreach ($fileUploads as $fieldName => $relationField) {
                    $this->handleFileUpload($fieldName, $clientInfo, $relationField, $submissionFolder);
                }
            }

            // Save AML Records per party
            $amlFolder = 'submissions/' . $submission->SerialNumber . '/aml';
            $amlIndex = 0;
            foreach ($representing as $party) {
                $amlRecordID = $data["AMLRecordID_{$amlIndex}"] ?? 0;
                if ($amlRecordID) {
                    $amlRecord = AMLRecord::get()->byID($amlRecordID);
                }
                if (empty($amlRecord) || !$amlRecord) {
                    $amlRecord = AMLRecord::create();
                    $amlRecord->FormSubmissionID = $submission->ID;
                    $amlRecord->PartyType = $party;
                }

                $amlRecord->AMLCompleted = isset($data["AMLCompleted_{$amlIndex}"]) ? (bool)$data["AMLCompleted_{$amlIndex}"] : false;
                $amlRecord->FormBSection2Checked = isset($data["FormBSection2_{$amlIndex}"]) ? (bool)$data["FormBSection2_{$amlIndex}"] : false;
                $amlRecord->OtherPartyRepresented = isset($data["OtherPartyRepresented_{$amlIndex}"]) ? (bool)$data["OtherPartyRepresented_{$amlIndex}"] : false;
                $amlRecord->UCPType = $data["UCPType_{$amlIndex}"] ?? null;
                $amlRecord->UCPActingType = $data["UCPActingType_{$amlIndex}"] ?? null;
                $amlRecord->ECDDRequired = isset($data["ECDDRequired_{$amlIndex}"]) ? (bool)$data["ECDDRequired_{$amlIndex}"] : false;
                $amlRecord->EAApprovalObtained = isset($data["EAApproval_{$amlIndex}"]) ? (bool)$data["EAApproval_{$amlIndex}"] : false;
                $amlRecord->write();

                $this->handleFileUpload("AMLFile_{$amlIndex}", $amlRecord, 'AMLFileID', $amlFolder);
                $this->handleFileUpload("FormQCID_{$amlIndex}", $amlRecord, 'FormQCIDID', $amlFolder);
                $this->handleMultipleFileUpload("UCPForms_{$amlIndex}", $amlRecord, 'UCPForm', $amlFolder);
                $this->handleECDFileUpload("ECDDForm_{$amlIndex}", $amlRecord, 'ECDDForm', $amlFolder);
                $this->handleECDFileUpload("FormQCIB_{$amlIndex}", $amlRecord, 'FormQCIB', $amlFolder);

                $amlIndex++;
            }

            // Save Documents
            $this->handleDocumentUploads($data, $submission);

            $submission->write();

            $this->sendUpdateNotificationEmail($submission);

            $form->sessionMessage('Submission updated successfully! Administrators have been notified.', 'good');
            return $this->redirect($this->Link() . '&success=1');

        } catch (\Exception $e) {
            $form->sessionMessage('Error updating submission: ' . $e->getMessage(), 'bad');
            return $this->redirectBack();
        }
    }

    private function handleFileUpload($fieldName, $record, $relationField, $folder)
    {
        if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
            try {
                $upload = Upload::create();
                $file = File::create();
                $upload->loadIntoFile($_FILES[$fieldName], $file, $folder . '/');
                if ($file && $file->exists()) {
                    $file->publishSingle();
                    $record->$relationField = $file->ID;
                    $record->write();
                }
            } catch (\Exception $e) {
                error_log("EditForm file upload error [{$fieldName}]: " . $e->getMessage());
            }
        }
    }

    private function handleMultipleFileUpload($fieldName, $amlRecord, $modelClass, $folder)
    {
        if (!isset($_FILES[$fieldName])) return;

        $files = $_FILES[$fieldName];
        $isSingle = !is_array($files['tmp_name']);

        if ($isSingle) {
            if (empty($files['tmp_name'])) return;
            $this->saveMultipleFile($files, $amlRecord, $modelClass, $folder);
        } else {
            foreach ($files['tmp_name'] as $i => $tmp) {
                if (empty($tmp)) continue;
                $fileData = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $tmp,
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
                $this->saveMultipleFile($fileData, $amlRecord, $modelClass, $folder);
            }
        }
    }

    private function saveMultipleFile($fileData, $amlRecord, $modelClass, $folder)
    {
        try {
            $upload = Upload::create();
            $file = File::create();
            $upload->loadIntoFile($fileData, $file, $folder . '/');
            if ($file && $file->exists()) {
                $file->publishSingle();
                $className = "App\\Model\\{$modelClass}";
                $related = $className::create();
                $related->AMLRecordID = $amlRecord->ID;
                $related->FormFileID = $file->ID;
                $related->FormType = $modelClass;
                $related->write();
            }
        } catch (\Exception $e) {
            error_log("EditForm multiple file upload error: " . $e->getMessage());
        }
    }

    private function handleECDFileUpload($fieldName, $amlRecord, $formType, $folder)
    {
        if (isset($_FILES[$fieldName]) && !empty($_FILES[$fieldName]['tmp_name'])) {
            try {
                $upload = Upload::create();
                $file = File::create();
                $upload->loadIntoFile($_FILES[$fieldName], $file, $folder . '/');
                if ($file && $file->exists()) {
                    $file->publishSingle();
                    $ecddForm = ECDDForm::create();
                    $ecddForm->AMLRecordID = $amlRecord->ID;
                    $ecddForm->FormFileID = $file->ID;
                    $ecddForm->FormType = $formType;
                    $ecddForm->write();
                }
            } catch (\Exception $e) {
                error_log("EditForm ECD file upload error [{$fieldName}]: " . $e->getMessage());
            }
        }
    }

    private function handleDocumentUploads($data, $submission)
    {
        $documentsFolder = 'submissions/' . $submission->SerialNumber . '/documents';

        $documentTypes = [
            'OptionToPurchase'   => 'Option To Purchase / Sales Agreement',
            'TenancyAgreement'   => 'Tenancy Agreement / Letter Of Intent / Letter Of Offer',
            'CEAAgreement'       => 'CEA Agreement',
            'CobrokeAgreement'   => 'Co-broke Agreement',
            'CommissionAgreement'=> 'Commission Agreement',
            'HDBApproval'        => 'HDB Approval Letter',
            'OtherDocuments'     => 'Other Documents',
        ];

        foreach ($documentTypes as $fieldName => $documentType) {
            if (!isset($_FILES[$fieldName])) continue;

            $files = $_FILES[$fieldName];
            if (is_array($files['tmp_name'])) {
                foreach ($files['tmp_name'] as $i => $tmp) {
                    if (empty($tmp)) continue;
                    $fileData = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $tmp,
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    $this->saveDocument($fileData, $submission, $documentType, $data, $documentsFolder);
                }
            } elseif (!empty($files['tmp_name'])) {
                $this->saveDocument($files, $submission, $documentType, $data, $documentsFolder);
            }
        }
    }

    private function saveDocument($fileData, $submission, $documentType, $formData, $folder)
    {
        try {
            $upload = Upload::create();
            $file = File::create();
            $upload->loadIntoFile($fileData, $file, $folder . '/');
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
            }
        } catch (\Exception $e) {
            error_log("EditForm document save error [{$documentType}]: " . $e->getMessage());
        }
    }

    private function sendUpdateNotificationEmail($submission)
    {
        $to = [
            'felicia.teo@quinvest-chambers.com.sg',
            'Ian.loh@quinvest-chambers.com.sg',
            'wendy.low@quinvest-chambers.com.sg',
        ];

        $subject = "Form Submission Updated - #{$submission->SerialNumber}";
        $body = "<p>A form submission has been updated by <strong>{$submission->SalespersonName}</strong>.</p>
                <p><strong>Serial Number:</strong> {$submission->SerialNumber}</p>
                <p><strong>RES Number:</strong> {$submission->RESNumber}</p>
                <p><strong>Transaction Type:</strong> {$submission->TransactionType}</p>
                <p><strong>Property Address:</strong> {$submission->PropertyAddress}</p>
                <p><strong>Updated Date:</strong> " . date('Y-m-d H:i:s') . "</p>
                <p><strong>Status:</strong> {$submission->Status}</p>
                <p>Please review the updated submission in the <a href=\"https://billing.quinvest-chambers.com.sg/admin/form-submissions\">admin panel</a>.</p>";

        $email = Email::create()
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body);

        try {
            $email->send();
        } catch (\Exception $e) {
            error_log("Failed to send edit notification email: " . $e->getMessage());
        }
    }

    public function Link($action = null)
    {
        $baseLink = parent::Link($action);
        $id = $this->submission ? $this->submission->ID : $this->getRequest()->getVar('request');
        if ($id && !$action) {
            return $baseLink . '?request=' . $id;
        }
        return $baseLink;
    }
}
