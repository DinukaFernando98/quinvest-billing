// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
    initializeHeader();
    styleUploadFields();
    styleFileFields();
    styleMultiStepForm();
    updateProgressBar();
    handleConditionalFieldsStep3();
    handleConditionalFieldsStep4();
    handleConditionalFieldsStep5();
    handleFileUploads();
    handleModal();
    trackFormStep();
    setupProgressBarInteractions();
    addProgressStepStyles();
});

// ===== STEP NAVIGATION =====
function navigateToStep(targetStep) {
    const currentStep = parseInt(document.querySelector('.form-step')?.getAttribute('data-step') || 1);
    
    console.log(`Navigating from step ${currentStep} to step ${targetStep}`);
    
    // If going to a previous step, navigate directly (no validation needed)
    if (targetStep < currentStep) {
        redirectToStep(targetStep);
        return;
    }
    
    // If going to the same step, do nothing
    if (targetStep === currentStep) {
        return;
    }
    
    // If going to next step (current + 1), validate current form first
    if (targetStep === currentStep + 1) {
        if (validateCurrentStep()) {
            redirectToStep(targetStep);
        }
        return;
    }
    
    // If jumping ahead more than one step, check if we're past step 1
    // Once step 1 is completed, allow navigation to any step
    if (targetStep > currentStep + 1) {
        // Check if we have at least completed step 1
        const step1Completed = checkStep1Completion();
        if (step1Completed) {
            // Allow jumping to any step if step 1 is completed
            redirectToStep(targetStep);
        } else {
            showAlert('Please complete Step 1 first before jumping ahead', 'error');
        }
        return;
    }
}

function checkStep1Completion() {
    // Check if step 1 required fields are filled
    const salespersonName = document.querySelector('input[name="SalespersonName"]')?.value;
    const resNumber = document.querySelector('input[name="RESNumber"]')?.value;
    
    return salespersonName && salespersonName.trim() && resNumber && resNumber.trim();
}

function redirectToStep(step) {
    const currentUrl = window.location.href.split('?')[0];
    const newUrl = `${currentUrl}?step=${step}`;
    console.log('Redirecting to:', newUrl);
    window.location.href = newUrl;
}

function validateCurrentStep() {
    const form = document.querySelector('.multi-step-form');
    if (!form) return true;
    
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim() && field.type !== 'checkbox' && field.type !== 'radio') {
            isValid = false;
            field.style.borderColor = 'var(--error-color)';
            field.addEventListener('input', () => field.style.borderColor = '', { once: true });
        } else if (field.type === 'checkbox' || field.type === 'radio') {
            const name = field.name;
            const checked = form.querySelector(`input[name="${name}"]:checked`);
            if (!checked) {
                isValid = false;
                const container = field.closest('.field');
                if (container) {
                    container.style.borderColor = 'var(--error-color)';
                    field.addEventListener('change', () => container.style.borderColor = '', { once: true });
                }
            }
        }
    });
    
    if (!isValid) {
        showAlert('Please fill in all required fields before proceeding', 'error');
        const firstError = form.querySelector('[required][style*="border-color"]');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
    
    return true;
}

// ===== PROGRESS BAR INTERACTION =====
function setupProgressBarInteractions() {
    // Add click event listeners to progress steps
    document.addEventListener('click', function(e) {
        const progressStep = e.target.closest('.progress-step');
        if (progressStep && (progressStep.classList.contains('completed') || progressStep.classList.contains('active'))) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetStep = parseInt(progressStep.getAttribute('data-step'));
            console.log('Progress step clicked:', targetStep);
            navigateToStep(targetStep);
        }
    });
    
    const progressSteps = document.querySelectorAll('.progress-step');
    
    progressSteps.forEach(step => {
        // Add hover effects
        step.addEventListener('mouseenter', function() {
            if (this.classList.contains('completed') || this.classList.contains('active')) {
                this.style.cursor = 'pointer';
                this.style.transform = 'translateY(-2px)';
            } else {
                // this.style.cursor = 'not-allowed';
            }
        });
        
        step.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// ===== ADD PROGRESS STEP STYLES =====
function addProgressStepStyles() {
    const style = document.createElement('style');
    style.textContent = `
        .progress-step {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .progress-step.completed,
        .progress-step.active {
            cursor: pointer;
        }
        
        .progress-step.completed:hover,
        .progress-step.active:hover {
            transform: translateY(-2px);
        }
        
        .progress-step.completed:hover .step-circle {
            background-color: var(--primary-dark);
            transform: scale(1.1);
        }
        
        .progress-step.active:hover .step-circle {
            background-color: var(--primary-dark);
            transform: scale(1.1);
        }
        
        .step-circle {
            transition: all 0.3s ease;
        }
        
        .progress-step:not(.completed):not(.active) {
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        /* Make completed steps more visually distinct */
        .progress-step.completed .step-circle {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
    `;
    document.head.appendChild(style);
}

// ===== HEADER FUNCTIONALITY =====
function initializeHeader() {
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            mobileToggle.classList.toggle('active');
        });
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.main-nav')) {
                navLinks.classList.remove('active');
                mobileToggle.classList.remove('active');
            }
        });
    }
}

// ===== SMOOTH SCROLL =====
function initializeSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

// ===== STYLE UPLOAD FIELDS =====
function styleUploadFields() {
    document.querySelectorAll('.uploadfield').forEach(field => {
        const holder = field.querySelector('.uploadfield-holder');
        if (holder) {
            holder.classList.add('file-upload-area');
            if (!holder.querySelector('.upload-icon')) {
                const icon = document.createElement('div');
                icon.className = 'upload-icon';
                icon.innerHTML = `
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>`;
                holder.insertBefore(icon, holder.firstChild);
            }
        }
    });
}

// ===== STYLE FILE FIELDS (like UploadField) =====
function styleFileFields() {
    document.querySelectorAll('input[type="file"]').forEach(input => {
        const field = input.closest('.field');
        if (!field || field.classList.contains('styled-file-field')) return;
        
        field.classList.add('styled-file-field');
        
        // Create upload area wrapper
        const uploadArea = document.createElement('div');
        uploadArea.className = 'file-upload-area uploadfield-holder';
        
        // Add upload icon
        const icon = document.createElement('div');
        icon.className = 'upload-icon';
        icon.innerHTML = `
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
        `;
        
        // Add text
        const text = document.createElement('div');
        text.className = 'upload-text';
        text.innerHTML = `
            <p class="upload-title">Drop files here or click to upload</p>
            <p class="upload-description">Allowed file types: pdf, jpg, jpeg, png, doc, docx</p>
        `;
        
        // File name display
        const fileName = document.createElement('div');
        fileName.className = 'file-name-display';
        fileName.style.display = 'none';
        
        // Hide original input
        input.style.display = 'none';
        
        // Build structure
        uploadArea.appendChild(icon);
        uploadArea.appendChild(text);
        uploadArea.appendChild(fileName);
        uploadArea.appendChild(input);
        
        // Insert after label or at beginning of field
        const label = field.querySelector('label');
        if (label) {
            label.after(uploadArea);
        } else {
            field.insertBefore(uploadArea, field.firstChild);
        }
        
        // Click to upload
        uploadArea.addEventListener('click', (e) => {
            if (e.target !== input) {
                input.click();
            }
        });
        
        // File change handler
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                fileName.innerHTML = `
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                        <polyline points="13 2 13 9 20 9"/>
                    </svg>
                    <span>${file.name}</span>
                    <span class="file-size">(${(file.size / 1024).toFixed(1)} KB)</span>
                `;
                fileName.style.display = 'flex';
                icon.style.display = 'none';
                text.style.display = 'none';
                
                // Validate file size
                const maxSize = 10 * 1024 * 1024;
                if (file.size > maxSize) {
                    showAlert(`File ${file.name} exceeds 10MB limit`, 'error');
                    this.value = '';
                    fileName.style.display = 'none';
                    icon.style.display = 'block';
                    text.style.display = 'block';
                }
            } else {
                fileName.style.display = 'none';
                icon.style.display = 'block';
                text.style.display = 'block';
            }
        });
        
        // Drag and drop handlers
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('drag-over');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            
            if (e.dataTransfer.files.length > 0) {
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
}

// ===== MULTI-STEP FORM STYLING =====
function styleMultiStepForm() {
    const form = document.querySelector('.multi-step-form');
    if (!form) return;

    // Style fields
    form.querySelectorAll('.field').forEach(field => {
        field.classList.add('form-group');
        const input = field.querySelector('input:not([type="radio"]):not([type="checkbox"]), textarea, select');
        if (input) input.classList.add('form-input');
        const select = field.querySelector('select');
        if (select) select.classList.add('form-select');
    });

    // Style radio/checkbox groups
    form.querySelectorAll('.optionset').forEach(optionset => {
        optionset.classList.add('radio-group');
        optionset.querySelectorAll('li').forEach(li => li.classList.add('radio-label'));
    });
    form.querySelectorAll('.checkboxset').forEach(checkboxset => {
        checkboxset.classList.add('checkbox-group');
        checkboxset.querySelectorAll('li').forEach(li => li.classList.add('checkbox-label'));
    });

    // Style actions
    form.querySelectorAll('.Actions').forEach(actions => actions.classList.add('form-navigation'));

    // Add required asterisks
    form.querySelectorAll('.field.required label').forEach(label => {
        if (!label.querySelector('.required')) {
            const asterisk = document.createElement('span');
            asterisk.className = 'required';
            asterisk.textContent = ' *';
            label.appendChild(asterisk);
        }
    });

    // Transaction type update
    const transactionTypeFields = form.querySelectorAll('input[name="TransactionType"]');
    transactionTypeFields.forEach(field => {
        field.addEventListener('change', function() {
            updateRepresentingOptions(this.value);
        });
    });

    // Smooth scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===== UPDATE REPRESENTING OPTIONS STEP 2 =====
function updateRepresentingOptions(transactionType) {
    const checkboxSet = document.querySelector('.checkboxset');
    if (!checkboxSet) return;

    checkboxSet.querySelectorAll('li').forEach(li => {
        const input = li.querySelector('input');
        const value = input.value;
        if (transactionType === 'Sale') {
            li.style.display = (value === 'Seller' || value === 'Buyer') ? 'flex' : 'none';
        } else if (transactionType === 'Lease') {
            li.style.display = (value === 'Landlord' || value === 'Tenant') ? 'flex' : 'none';
        }
        if (li.style.display === 'none') input.checked = false;
    });
}

// ===== CONDITIONAL FIELDS STEP 3 =====
function handleConditionalFieldsStep3() {
    document.querySelectorAll('input[name^="ClientType_"]').forEach(field => {
        field.addEventListener('change', function() {
            const index = this.name.split('_')[1];
            const actingTypeField = document.querySelector(`select[name="ClientActingType_${index}"]`);
            if (!actingTypeField) return;

            const individualOptions = [
                '', 'Individual acting for himself', 
                'Individual acting on behalf of another individual', 
                'Individual acting on behalf of another (Entity/Legal arrangement)'
            ];
            const entityOptions = [
                '', 'Entity acting for himself', 
                'Entity acting on behalf of another individual', 
                'Entity acting on behalf of another (Entity/Legal arrangement)'
            ];

            actingTypeField.innerHTML = '';
            const options = this.value === 'Individual' ? individualOptions : entityOptions;
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.textContent = opt || '-- Select --';
                actingTypeField.appendChild(option);
            });
        });
    });
}

// ===== CONDITIONAL FIELDS STEP 4 =====
function handleConditionalFieldsStep4() {
    // AML Search toggle
    const amlCompletedYes = document.getElementById('Form_MultiStepForm_AMLCompleted_Seller_1');
    const amlCompletedNo = document.getElementById('Form_MultiStepForm_AMLCompleted_Seller_0');
    const amlFileHolder = document.getElementById('Form_MultiStepForm_AMLFile_Seller_Holder');
    const existingAMLNotice = document.querySelector('#Form_MultiStepForm_AMLFile_Seller_Holder + .existing-file-info');
    
    if (amlCompletedYes && amlCompletedNo) {
        function updateAMLFileVisibility() {
            const showAML = amlCompletedYes.checked;
            if (amlFileHolder) amlFileHolder.style.display = showAML ? 'block' : 'none';
            if (existingAMLNotice) existingAMLNotice.style.display = showAML ? 'block' : 'none';
        }
        updateAMLFileVisibility();
        amlCompletedYes.addEventListener('change', updateAMLFileVisibility);
        amlCompletedNo.addEventListener('change', updateAMLFileVisibility);
    }

    // Form B Section 2 toggle
    const formBSection2Yes = document.getElementById('Form_MultiStepForm_FormBSection2_Seller_1');
    const formBSection2No = document.getElementById('Form_MultiStepForm_FormBSection2_Seller_0');
    const formQCIDHolder = document.getElementById('Form_MultiStepForm_FormQCID_Seller_Holder');
    const existingFormQCIDNotice = document.querySelector('#Form_MultiStepForm_FormQCID_Seller_Holder + .existing-file-info');
    
    if (formBSection2Yes && formBSection2No) {
        function updateFormQCIDVisibility() {
            const showFormQCID = formBSection2Yes.checked;
            if (formQCIDHolder) formQCIDHolder.style.display = showFormQCID ? 'block' : 'none';
            if (existingFormQCIDNotice) existingFormQCIDNotice.style.display = showFormQCID ? 'block' : 'none';
        }
        updateFormQCIDVisibility();
        formBSection2Yes.addEventListener('change', updateFormQCIDVisibility);
        formBSection2No.addEventListener('change', updateFormQCIDVisibility);
    }

    // Other Party Represented toggle
    const otherPartyYes = document.getElementById('Form_MultiStepForm_OtherPartyRepresented_Seller_1');
    const otherPartyNo = document.getElementById('Form_MultiStepForm_OtherPartyRepresented_Seller_0');
    const ucpTypeHolder = document.getElementById('Form_MultiStepForm_UCPType_Seller_Holder');
    const ucpFormsHolder = document.getElementById('Form_MultiStepForm_UCPForms_Seller_Holder');
    const existingUCPNotice = document.querySelector('#Form_MultiStepForm_UCPForms_Seller_Holder + .existing-file-info');
    
    if (otherPartyYes && otherPartyNo) {
        function updateUCPVisibility() {
            const showUCP = otherPartyYes.checked;
            if (ucpTypeHolder) ucpTypeHolder.style.display = showUCP ? 'block' : 'none';
            if (ucpFormsHolder) ucpFormsHolder.style.display = showUCP ? 'block' : 'none';
            if (existingUCPNotice) existingUCPNotice.style.display = showUCP ? 'block' : 'none';
        }
        updateUCPVisibility();
        otherPartyYes.addEventListener('change', updateUCPVisibility);
        otherPartyNo.addEventListener('change', updateUCPVisibility);
    }

    // ECDD Required toggle
    const ecddRequiredYes = document.getElementById('Form_MultiStepForm_ECDDRequired_Seller_1');
    const ecddRequiredNo = document.getElementById('Form_MultiStepForm_ECDDRequired_Seller_0');
    const ecddFormHolder = document.getElementById('Form_MultiStepForm_ECDDForm_Seller_Holder');
    const formQCIBHolder = document.getElementById('Form_MultiStepForm_FormQCIB_Seller_Holder');
    const eaApprovalHolder = document.getElementById('Form_MultiStepForm_EAApproval_Seller_Holder');
    const existingECDDNotice = document.querySelector('#Form_MultiStepForm_ECDDForm_Seller_Holder + .existing-file-info');
    const existingFormQCIBNotice = document.querySelector('#Form_MultiStepForm_FormQCIB_Seller_Holder + .existing-file-info');
    
    if (ecddRequiredYes && ecddRequiredNo) {
        function updateECDDVisibility() {
            const showECDD = ecddRequiredYes.checked;
            if (ecddFormHolder) ecddFormHolder.style.display = showECDD ? 'block' : 'none';
            if (formQCIBHolder) formQCIBHolder.style.display = showECDD ? 'block' : 'none';
            if (eaApprovalHolder) eaApprovalHolder.style.display = showECDD ? 'block' : 'none';
            if (existingECDDNotice) existingECDDNotice.style.display = showECDD ? 'block' : 'none';
            if (existingFormQCIBNotice) existingFormQCIBNotice.style.display = showECDD ? 'block' : 'none';
        }
        updateECDDVisibility();
        ecddRequiredYes.addEventListener('change', updateECDDVisibility);
        ecddRequiredNo.addEventListener('change', updateECDDVisibility);
    }
}

// ===== CONDITIONAL FIELDS STEP 5 =====
function handleConditionalFieldsStep5() {
    const form = document.querySelector('.multi-step-form');
    if (!form) return;

    const toggleFields = [
        ['HasCEAAgreement','CEAAgreement'],
        ['HasCobrokeAgreement','CobrokeAgreement'],
        ['HasCommissionAgreement','CommissionAgreement'],
        ['HasHDBApproval','HDBApproval'],
        ['HasOtherDocuments','OtherDocumentsDescription','OtherDocuments']
    ];

    toggleFields.forEach(([trigger, ...targets]) => {
        form.querySelectorAll(`input[name="${trigger}"]`).forEach(field => {
            field.addEventListener('change', function() {
                targets.forEach(t => {
                    const fld = form.querySelector(`[name="${t}"], [id*="${t}"]`)?.closest('.field');
                    if (fld) fld.style.display = this.value === '1' ? 'block' : 'none';
                });
            });
        });
    });

    // Hide by default
    toggleFields.flat().forEach(f => {
        const fld = form.querySelector(`[name="${f}"], [id*="${f}"]`)?.closest('.field');
        if (fld) fld.style.display = 'none';
    });
}

// ===== FILE UPLOAD ENHANCEMENTS =====
function handleFileUploads() {
    document.querySelectorAll('.uploadfield').forEach(field => {
        const input = field.querySelector('input[type="file"]');
        const holder = field.querySelector('.uploadfield-holder');
        if (!input) return;

        input.addEventListener('change', function() {
            const maxSize = 10 * 1024 * 1024;
            Array.from(this.files).forEach(file => {
                if (file.size > maxSize) {
                    showAlert(`File ${file.name} exceeds 10MB limit`, 'error');
                    this.value = '';
                }
            });
        });

        if (holder) {
            holder.addEventListener('dragover', e => { e.preventDefault(); holder.classList.add('drag-over'); });
            holder.addEventListener('dragleave', () => holder.classList.remove('drag-over'));
            holder.addEventListener('drop', e => { e.preventDefault(); holder.classList.remove('drag-over'); });
        }
    });
}

// ===== MODAL HANDLING =====
function handleModal() {
    const modal = document.getElementById('successModal');
    if (!modal) return;
    modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('active'); });
}

// ===== FORM STEP TRACKING =====
function trackFormStep() {
    const currentStep = parseInt(document.querySelector('.form-step')?.getAttribute('data-step') || 1);
    document.querySelectorAll('.progress-step').forEach((step, index) => {
        if (index + 1 < currentStep) step.classList.add('completed');
        else if (index + 1 === currentStep) step.classList.add('active');
        else step.classList.remove('active', 'completed');
    });
}

// ===== PROGRESS BAR =====
function updateProgressBar() {
    const progressFill = document.getElementById('progressFill');
    const currentStep = parseInt(document.querySelector('.form-step')?.getAttribute('data-step') || 1);
    const totalSteps = 6;
    if (progressFill) progressFill.style.width = `${(currentStep / totalSteps) * 100}%`;
}

// ===== ALERT SYSTEM =====
function showAlert(message, type='info') {
    document.querySelectorAll('.alert-floating').forEach(a => a.remove());
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-floating`;
    alert.style.cssText = `position:fixed;top:100px;right:20px;min-width:300px;z-index:3000;animation:slideInRight .3s ease-out;`;
    const icon = type === 'error' ? '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>';
    alert.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${icon}</svg><span>${message}</span>`;
    document.body.appendChild(alert);
    setTimeout(() => { alert.style.animation='slideOutRight .3s ease-out'; setTimeout(()=>alert.remove(),300); }, 5000);
}

// ===== ANIMATION STYLES =====
const styleEl = document.createElement('style');
styleEl.textContent = `
@keyframes slideInRight {from {transform: translateX(100%); opacity:0;} to {transform:translateX(0); opacity:1;}}
@keyframes slideOutRight {from {transform: translateX(0); opacity:1;} to {transform:translateX(100%); opacity:0;}}
.drag-over {border-color: var(--primary-color)!important; background: rgba(4,122,77,0.1)!important;}
`;
document.head.appendChild(styleEl);

// ===== STYLE BILLING FORM =====
function styleBillingForm() {
    const billingForm = document.querySelector('.billing-form');
    if (!billingForm) return;

    // Style fields
    billingForm.querySelectorAll('.field').forEach(field => {
        field.classList.add('form-group');
        const input = field.querySelector('input:not([type="radio"]):not([type="checkbox"]), textarea, select');
        if (input) input.classList.add('form-input');
    });

    // Add required asterisks
    billingForm.querySelectorAll('.field label').forEach(label => {
        const fieldName = label.getAttribute('for');
        if (fieldName && (fieldName.includes('SerialNumber') || fieldName.includes('BillingFile'))) {
            if (!label.querySelector('.required')) {
                const asterisk = document.createElement('span');
                asterisk.className = 'required';
                asterisk.textContent = ' *';
                label.appendChild(asterisk);
            }
        }
    });

    // Style the submit button if it's not already styled
    const submitBtn = billingForm.querySelector('.btn-success');
    if (submitBtn) {
        submitBtn.classList.add('btn', 'btn-success');
    }

    // Add custom validation for billing form
    addBillingFormValidation();
}

// ===== BILLING FORM VALIDATION =====
function addBillingFormValidation() {
    const billingForm = document.querySelector('.billing-form');
    if (!billingForm) return;

    billingForm.addEventListener('submit', function(e) {
        let isValid = true;
        const serialNumber = document.getElementById('Form_BillingForm_SerialNumber');
        const billingFile = document.getElementById('Form_BillingForm_BillingFile');
        
        // Validate serial number
        if (!serialNumber.value.trim()) {
            isValid = false;
            showFieldError(serialNumber, 'Serial number is required');
        } else {
            clearFieldError(serialNumber);
        }

        // Validate file upload
        if (!billingFile.files || billingFile.files.length === 0) {
            isValid = false;
            const fileUploadArea = billingFile.closest('.file-upload-area');
            if (fileUploadArea) {
                fileUploadArea.style.borderColor = 'var(--error-color)';
                fileUploadArea.style.background = 'rgba(220, 38, 38, 0.05)';
            }
            showAlert('Please upload a billing file', 'error');
        } else {
            const fileUploadArea = billingFile.closest('.file-upload-area');
            if (fileUploadArea) {
                fileUploadArea.style.borderColor = '';
                fileUploadArea.style.background = '';
            }
        }

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = billingForm.querySelector('.error-field, [style*="border-color: var(--error-color)"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
}

function showFieldError(field, message) {
    field.classList.add('error-field');
    field.style.borderColor = 'var(--error-color)';
    
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Add error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = 'var(--error-color)';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.5rem';
    errorDiv.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ${message}`;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error-field');
    field.style.borderColor = '';
    
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}