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

// Enhanced smooth scrolling
// (function () {
//     let isScrolling = false;
//     let scrollTarget = 0;
//     let initialized = false;
//     const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);

//     function smoothScroll() {
//         const currentScroll = window.pageYOffset;
//         const distance = scrollTarget - currentScroll;

//         if (Math.abs(distance) > 1) {
//             window.scrollTo(0, currentScroll + distance * 0.1);
//             requestAnimationFrame(smoothScroll);
//         } else {
//             isScrolling = false;
//         }
//     }

//     // Initialize scroll target after page load and anchor jump
//     window.addEventListener('load', function () {
//         setTimeout(function () {
//             scrollTarget = window.pageYOffset;
//             initialized = true;
//         }, 100);
//     });

//     // Handle anchor clicks with smooth scroll (desktop only)
//     if (!isMobile) {
//         document.addEventListener('click', function (e) {
//             const link = e.target.closest('a[href*="#"]');
//             if (link) {
//                 const href = link.getAttribute('href');
//                 const hashIndex = href.indexOf('#');
//                 if (hashIndex !== -1) {
//                     const hash = href.substring(hashIndex);
//                     const targetElement = document.querySelector(hash);
//                     if (targetElement) {
//                         e.preventDefault();

//                         // Get target position
//                         const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
//                         scrollTarget = targetPosition;

//                         // Start smooth scroll
//                         if (!isScrolling) {
//                             isScrolling = true;
//                             requestAnimationFrame(smoothScroll);
//                         }

//                         // Update URL hash
//                         if (href.indexOf('?') === -1) {
//                             history.pushState(null, null, hash);
//                         } else {
//                             window.location.href = href;
//                         }
//                     }
//                 }
//             }
//         });
//     } else {
//         // Mobile: update scrollTarget after native anchor navigation
//         document.addEventListener('click', function (e) {
//             const link = e.target.closest('a[href*="#"]');
//             if (link) {
//                 const href = link.getAttribute('href');
//                 const hashIndex = href.indexOf('#');
//                 if (hashIndex !== -1) {
//                     const hash = href.substring(hashIndex);
//                     const targetElement = document.querySelector(hash);
//                     if (targetElement) {
//                         setTimeout(function () {
//                             scrollTarget = window.pageYOffset;
//                         }, 100);
//                     }
//                 }
//             }
//         });
//     }

//     window.addEventListener('wheel', function (e) {
//         if (!initialized) return;

//         e.preventDefault();
//         scrollTarget += e.deltaY;
//         scrollTarget = Math.max(0, Math.min(scrollTarget, document.documentElement.scrollHeight - window.innerHeight));

//         if (!isScrolling) {
//             isScrolling = true;
//             requestAnimationFrame(smoothScroll);
//         }
//     }, { passive: false });
// })();

// ===== STEP NAVIGATION =====
function navigateToStep(targetStep) {
    const currentStep = parseInt(document.querySelector('.form-step')?.getAttribute('data-step') || 1);

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
}

function redirectToStep(step) {
    const currentUrl = window.location.href.split('?')[0];
    const newUrl = `${currentUrl}?step=${step}`;
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
    document.addEventListener('click', function (e) {
        const progressStep = e.target.closest('.progress-step');
        if (progressStep && (progressStep.classList.contains('completed') || progressStep.classList.contains('active'))) {
            e.preventDefault();
            e.stopPropagation();

            const targetStep = parseInt(progressStep.getAttribute('data-step'));
            navigateToStep(targetStep);
        }
    });

    const progressSteps = document.querySelectorAll('.progress-step');

    progressSteps.forEach(step => {
        // Add hover effects
        step.addEventListener('mouseenter', function () {
            if (this.classList.contains('completed') || this.classList.contains('active')) {
                this.style.cursor = 'pointer';
                this.style.transform = 'translateY(-2px)';
            } else {
                // this.style.cursor = 'not-allowed';
            }
        });

        step.addEventListener('mouseleave', function () {
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
        input.addEventListener('change', function () {
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
        field.addEventListener('change', function () {
            updateRepresentingOptions(this.value);
        });
    });

    // Apply initial state if TransactionType already has a value (e.g. returning to step 2)
    const checkedTransactionType = form.querySelector('input[name="TransactionType"]:checked');
    if (checkedTransactionType) {
        updateRepresentingOptions(checkedTransactionType.value);
    }

    // Smooth scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===== UPDATE REPRESENTING OPTIONS STEP 2 =====
function updateRepresentingOptions(transactionType) {
    // Target the Representing field's optionset specifically, not the first .optionset on the page
    const firstRepresentingInput = document.querySelector('input[name="Representing"]');
    if (!firstRepresentingInput) return;
    const optionSet = firstRepresentingInput.closest('ul');
    if (!optionSet) return;

    optionSet.querySelectorAll('li').forEach(li => {
        const input = li.querySelector('input');
        if (!input) return;
        const value = input.value;
        if (transactionType === 'Sale') {
            li.style.display = (value === 'Seller' || value === 'Buyer') ? '' : 'none';
        } else if (transactionType === 'Lease') {
            li.style.display = (value === 'Landlord' || value === 'Tenant') ? '' : 'none';
        } else {
            li.style.display = '';
        }
        if (li.style.display === 'none') input.checked = false;
    });
}

// ===== CONDITIONAL FIELDS STEP 3 =====
function handleConditionalFieldsStep3() {
    document.querySelectorAll('input[name^="ClientType_"]').forEach(field => {
        field.addEventListener('change', function () {
            const party = this.name.replace('ClientType_', '');
            const actingTypeSelect = document.querySelector(`select[name="ClientActingType_${party}"]`);
            if (!actingTypeSelect) return;

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

            actingTypeSelect.innerHTML = '';
            const options = this.value === 'Individual' ? individualOptions : entityOptions;
            options.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                option.textContent = opt || '-- Select --';
                actingTypeSelect.appendChild(option);
            });

            updateFormFilesForParty(party, '');
        });
    });

    document.querySelectorAll('select[name^="ClientActingType_"]').forEach(select => {
        const party = select.name.replace('ClientActingType_', '');
        select.addEventListener('change', function () {
            updateFormFilesForParty(party, this.value);
        });
        updateFormFilesForParty(party, select.value);
    });
}

function updateFormFilesForParty(party, actingType) {
    function fieldOf(name) {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? el.closest('.field') : null;
    }

    const a1 = fieldOf(`FormA1File_${party}`);
    const a2 = fieldOf(`FormA2File_${party}`);
    const a3 = fieldOf(`FormA3File_${party}`);
    const a4 = fieldOf(`FormA4File_${party}`);
    const b  = fieldOf(`FormBFile_${party}`);

    const showA1 = actingType.startsWith('Individual');
    const showA2 = actingType.startsWith('Entity');
    const showA3 = actingType.includes('behalf of another individual');
    const showA4 = actingType.includes('behalf of another (Entity');

    if (a1) a1.style.display = showA1 ? '' : 'none';
    if (a2) a2.style.display = showA2 ? '' : 'none';
    if (a3) a3.style.display = showA3 ? '' : 'none';
    if (a4) a4.style.display = showA4 ? '' : 'none';
    if (b)  b.style.display  = actingType ? '' : 'none';
}

// ===== CONDITIONAL FIELDS STEP 4 =====
function handleConditionalFieldsStep4() {
    const seen = new Set();
    document.querySelectorAll('input[name^="AMLCompleted_"]').forEach(function (input) {
        const i = input.name.replace('AMLCompleted_', '');
        if (!seen.has(i)) {
            seen.add(i);
            setupAMLSection(i);
        }
    });
}

function setupAMLSection(i) {
    function fieldOf(name) {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? el.closest('.field') : null;
    }
    function show(el) { if (el) el.style.display = ''; }
    function hide(el) { if (el) el.style.display = 'none'; }

    const amlYes = document.querySelector(`input[name="AMLCompleted_${i}"][value="1"]`);
    const amlNo  = document.querySelector(`input[name="AMLCompleted_${i}"][value="0"]`);

    const amlFileField       = fieldOf(`AMLFile_${i}`);
    const formBSection2Field = fieldOf(`FormBSection2_${i}`);
    const formQCIDField      = fieldOf(`FormQCID_${i}`);
    const otherPartyField    = fieldOf(`OtherPartyRepresented_${i}`);
    const ucpTypeField       = fieldOf(`UCPType_${i}`);
    const ucpActingTypeField = fieldOf(`UCPActingType_${i}`);
    const ucpFormsField      = fieldOf(`UCPForms_${i}`);
    const ecddRequiredField  = fieldOf(`ECDDRequired_${i}`);
    const ecddFormField      = fieldOf(`ECDDForm_${i}`);
    const formQCIBField      = fieldOf(`FormQCIB_${i}`);
    const eaApprovalField    = fieldOf(`EAApproval_${i}`);

    let amlNotice = document.getElementById(`aml-notice-${i}`);
    if (!amlNotice) {
        amlNotice = document.createElement('div');
        amlNotice.id = `aml-notice-${i}`;
        amlNotice.className = 'alert alert-warning';
        amlNotice.style.marginTop = '8px';
        amlNotice.textContent = 'Please complete an AML search before proceeding.';
        const amlCompletedField = fieldOf(`AMLCompleted_${i}`);
        if (amlCompletedField) amlCompletedField.after(amlNotice);
    }

    let strNotice = document.getElementById(`str-notice-${i}`);
    if (!strNotice) {
        strNotice = document.createElement('div');
        strNotice.id = `str-notice-${i}`;
        strNotice.className = 'alert alert-info';
        strNotice.style.marginTop = '8px';
        strNotice.textContent = 'Please file a Suspicious Transaction Report (STR) separately.';
        if (formBSection2Field) formBSection2Field.after(strNotice);
    }

    function updateAll() {
        const amlDone = amlYes && amlYes.checked;

        if (!amlDone) {
            hide(amlFileField); hide(formBSection2Field); hide(formQCIDField);
            hide(otherPartyField); hide(ucpTypeField); hide(ucpActingTypeField);
            hide(ucpFormsField); hide(ecddRequiredField); hide(ecddFormField);
            hide(formQCIBField); hide(eaApprovalField); hide(strNotice);
            show(amlNotice);
            return;
        }

        hide(amlNotice);
        show(amlFileField);
        show(formBSection2Field);

        const formBYes = document.querySelector(`input[name="FormBSection2_${i}"][value="1"]`);
        if (formBYes && formBYes.checked) {
            show(formQCIDField);
            show(strNotice);
        } else {
            hide(formQCIDField);
            hide(strNotice);
        }

        show(otherPartyField);

        const otherPartyYes = document.querySelector(`input[name="OtherPartyRepresented_${i}"][value="1"]`);
        if (otherPartyYes && otherPartyYes.checked) {
            hide(ucpTypeField); hide(ucpActingTypeField); hide(ucpFormsField);
        } else {
            show(ucpTypeField); show(ucpActingTypeField); show(ucpFormsField);
            updateUCPActingTypeOptions(i);
        }

        show(ecddRequiredField);

        const ecddYes = document.querySelector(`input[name="ECDDRequired_${i}"][value="1"]`);
        if (ecddYes && ecddYes.checked) {
            show(ecddFormField); show(formQCIBField); show(eaApprovalField);
        } else {
            hide(ecddFormField); hide(formQCIBField); hide(eaApprovalField);
        }
    }

    [amlYes, amlNo].forEach(el => { if (el) el.addEventListener('change', updateAll); });

    ['FormBSection2', 'OtherPartyRepresented', 'ECDDRequired'].forEach(field => {
        document.querySelectorAll(`input[name="${field}_${i}"]`).forEach(el => {
            el.addEventListener('change', updateAll);
        });
    });

    document.querySelectorAll(`input[name="UCPType_${i}"]`).forEach(el => {
        el.addEventListener('change', function () { updateUCPActingTypeOptions(i); });
    });

    updateAll();
}

function updateUCPActingTypeOptions(i) {
    const checked = document.querySelector(`input[name="UCPType_${i}"]:checked`);
    const select  = document.querySelector(`select[name="UCPActingType_${i}"]`);
    if (!select) return;

    const currentVal = select.value;

    const individualOpts = [
        ['', '-- Select type --'],
        ['UCP (Individual) acting for himself', 'UCP (Individual) acting for himself'],
        ['UCP (Individual) acting on behalf of another individual', 'UCP (Individual) acting on behalf of another individual'],
        ['UCP (Individual) acting on behalf of another (Entity/Legal Arrangement)', 'UCP (Individual) acting on behalf of another (Entity/Legal Arrangement)'],
    ];

    const entityOpts = [
        ['', '-- Select type --'],
        ['UCP (Entity/Legal Arrangement) acting for himself', 'UCP (Entity/Legal Arrangement) acting for himself'],
        ['UCP (Entity/Legal Arrangement) acting on behalf of another individual', 'UCP (Entity/Legal Arrangement) acting on behalf of another individual'],
        ['UCP (Entity/Legal Arrangement) acting on behalf of another (Entity/Legal Arrangement)', 'UCP (Entity/Legal Arrangement) acting on behalf of another (Entity/Legal Arrangement)'],
    ];

    const opts = !checked ? [individualOpts[0], ...individualOpts.slice(1), ...entityOpts.slice(1)] :
        checked.value === 'Individual' ? individualOpts : entityOpts;

    select.innerHTML = '';
    opts.forEach(([val, text]) => {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = text;
        if (val === currentVal) opt.selected = true;
        select.appendChild(opt);
    });
}

// ===== CONDITIONAL FIELDS STEP 5 =====
function handleConditionalFieldsStep5() {
    const form = document.querySelector('.multi-step-form');
    if (!form) return;

    const toggleFields = [
        ['HasCEAAgreement', 'CEAAgreement'],
        ['HasCobrokeAgreement', 'CobrokeAgreement'],
        ['HasCommissionAgreement', 'CommissionAgreement'],
        ['HasHDBApproval', 'HDBApproval'],
        ['HasOtherDocuments', 'OtherDocumentsDescription', 'OtherDocuments']
    ];

    toggleFields.forEach(([trigger, ...targets]) => {
        form.querySelectorAll(`input[name="${trigger}"]`).forEach(field => {
            field.addEventListener('change', function () {
                targets.forEach(t => {
                    // Use name= only (not id*=) to avoid substring matches like HasCEA matching CEA
                    const fld = form.querySelector(`[name="${t}"]`)?.closest('.field');
                    if (fld) fld.style.display = this.value === '1' ? '' : 'none';
                });
            });
        });
    });

    // Hide only target fields by default (not the trigger questions themselves)
    toggleFields.forEach(([trigger, ...targets]) => {
        targets.forEach(t => {
            const fld = form.querySelector(`[name="${t}"]`)?.closest('.field');
            if (fld) fld.style.display = 'none';
        });
    });

    // Restore state if a value is already checked (e.g. page reload with saved data)
    toggleFields.forEach(([trigger, ...targets]) => {
        const checked = form.querySelector(`input[name="${trigger}"]:checked`);
        if (checked) {
            targets.forEach(t => {
                const fld = form.querySelector(`[name="${t}"]`)?.closest('.field');
                if (fld) fld.style.display = checked.value === '1' ? '' : 'none';
            });
        }
    });
}

// ===== FILE UPLOAD ENHANCEMENTS =====
function handleFileUploads() {
    document.querySelectorAll('.uploadfield').forEach(field => {
        const input = field.querySelector('input[type="file"]');
        const holder = field.querySelector('.uploadfield-holder');
        if (!input) return;

        input.addEventListener('change', function () {
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
    const totalSteps = 5;
    if (progressFill) progressFill.style.width = `${(currentStep / totalSteps) * 100}%`;
}

// ===== ALERT SYSTEM =====
function showAlert(message, type = 'info') {
    document.querySelectorAll('.alert-floating').forEach(a => a.remove());
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-floating`;
    alert.style.cssText = `position:fixed;top:100px;right:20px;min-width:300px;z-index:3000;animation:slideInRight .3s ease-out;`;
    const icon = type === 'error' ? '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>' : '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>';
    alert.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${icon}</svg><span>${message}</span>`;
    document.body.appendChild(alert);
    setTimeout(() => { alert.style.animation = 'slideOutRight .3s ease-out'; setTimeout(() => alert.remove(), 300); }, 5000);
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

    billingForm.addEventListener('submit', function (e) {
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