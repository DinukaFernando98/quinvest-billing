// Edit Form Enhancements
document.addEventListener('DOMContentLoaded', () => {
    const editForm = document.querySelector('.edit-form');
    
    if (!editForm) {
        return; // Not on edit page
    }
    
    console.log('Initializing edit form enhancements');
    
    // Initialize edit form specific features
    initEditFormNavigation();
    styleEditFormFields();
    handleEditFormConditionals();
    updateEditProgressBar();
    setupEditProgressInteractions();
});

// ===== EDIT FORM NAVIGATION =====
function initEditFormNavigation() {
    const form = document.querySelector('.edit-form');
    if (!form) return;
    
    console.log('Setting up edit form navigation');
    
    // Handle form submission to detect which button was clicked
    form.addEventListener('submit', function(e) {
        const submitter = e.submitter;
        
        if (!submitter) return;
        
        console.log('Form submitted by:', submitter.name, submitter.textContent);
        
        // Don't prevent submission - let it go through naturally
        // The PHP controller will handle the action detection
    });
    
    // Add click handlers for debugging
    const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            console.log('Button clicked:', this.name, this.value, this.textContent);
        });
    });
}

// ===== STYLE EDIT FORM FIELDS =====
function styleEditFormFields() {
    const form = document.querySelector('.edit-form');
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
    
    // Style file upload fields
    styleEditFileFields();
}

// ===== STYLE EDIT FILE FIELDS =====
function styleEditFileFields() {
    const form = document.querySelector('.edit-form');
    if (!form) return;
    
    form.querySelectorAll('input[type="file"]').forEach(input => {
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
            <p class="upload-description">Upload new file to replace existing</p>
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
        const existingFileInfo = field.querySelector('.existing-file-info');
        
        if (existingFileInfo) {
            existingFileInfo.after(uploadArea);
        } else if (label) {
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
                    showEditAlert(`File ${file.name} exceeds 10MB limit`, 'error');
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

// ===== HANDLE EDIT FORM CONDITIONALS =====
function handleEditFormConditionals() {
    const currentStep = parseInt(document.querySelector('.form-step')?.getAttribute('data-step') || 2);
    
    console.log('Setting up conditionals for step:', currentStep);
    
    switch(currentStep) {
        case 3:
            handleEditStep3Conditionals();
            break;
        case 4:
            handleEditStep4Conditionals();
            break;
        case 5:
            handleEditStep5Conditionals();
            break;
    }
}

// ===== STEP 3 CONDITIONALS =====
function handleEditStep3Conditionals() {
    document.querySelectorAll('input[name^="ClientType_"]').forEach(field => {
        field.addEventListener('change', function() {
            const party = this.name.split('_')[1];
            const actingTypeField = document.querySelector(`select[name="ClientActingType_${party}"]`);
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

// ===== STEP 4 CONDITIONALS =====
function handleEditStep4Conditionals() {
    // Find all party types (could be multiple: Seller, Buyer, Landlord, Tenant)
    const partyTypes = new Set();
    document.querySelectorAll('input[name^="AMLCompleted_"]').forEach(input => {
        const match = input.name.match(/AMLCompleted_(\d+)/);
        if (match) {
            partyTypes.add(match[1]);
        }
    });
    
    partyTypes.forEach(index => {
        setupAMLConditionals(index);
        setupFormBConditionals(index);
        setupOtherPartyConditionals(index);
        setupECDDConditionals(index);
    });
}

function setupAMLConditionals(index) {
    const yesRadio = document.querySelector(`input[name="AMLCompleted_${index}"][value="1"]`);
    const noRadio = document.querySelector(`input[name="AMLCompleted_${index}"][value="0"]`);
    const fileField = document.querySelector(`input[name="AMLFile_${index}"]`)?.closest('.field');
    
    if (!yesRadio || !noRadio) return;
    
    function toggle() {
        if (fileField) {
            fileField.style.display = yesRadio.checked ? 'block' : 'none';
        }
    }
    
    toggle();
    yesRadio.addEventListener('change', toggle);
    noRadio.addEventListener('change', toggle);
}

function setupFormBConditionals(index) {
    const yesRadio = document.querySelector(`input[name="FormBSection2_${index}"][value="1"]`);
    const noRadio = document.querySelector(`input[name="FormBSection2_${index}"][value="0"]`);
    const fileField = document.querySelector(`input[name="FormQCID_${index}"]`)?.closest('.field');
    
    if (!yesRadio || !noRadio) return;
    
    function toggle() {
        if (fileField) {
            fileField.style.display = yesRadio.checked ? 'block' : 'none';
        }
    }
    
    toggle();
    yesRadio.addEventListener('change', toggle);
    noRadio.addEventListener('change', toggle);
}

function setupOtherPartyConditionals(index) {
    const yesRadio = document.querySelector(`input[name="OtherPartyRepresented_${index}"][value="1"]`);
    const noRadio = document.querySelector(`input[name="OtherPartyRepresented_${index}"][value="0"]`);
    const ucpTypeField = document.querySelector(`input[name="UCPType_${index}"]`)?.closest('.field');
    const ucpFormsField = document.querySelector(`input[name="UCPForms_${index}"]`)?.closest('.field');
    
    if (!yesRadio || !noRadio) return;
    
    function toggle() {
        const showUCP = noRadio.checked; // Show when NOT represented
        if (ucpTypeField) ucpTypeField.style.display = showUCP ? 'block' : 'none';
        if (ucpFormsField) ucpFormsField.style.display = showUCP ? 'block' : 'none';
    }
    
    toggle();
    yesRadio.addEventListener('change', toggle);
    noRadio.addEventListener('change', toggle);
}

function setupECDDConditionals(index) {
    const yesRadio = document.querySelector(`input[name="ECDDRequired_${index}"][value="1"]`);
    const noRadio = document.querySelector(`input[name="ECDDRequired_${index}"][value="0"]`);
    const ecddField = document.querySelector(`input[name="ECDDForm_${index}"]`)?.closest('.field');
    const qcibField = document.querySelector(`input[name="FormQCIB_${index}"]`)?.closest('.field');
    const eaField = document.querySelector(`input[name="EAApproval_${index}"]`)?.closest('.field');
    
    if (!yesRadio || !noRadio) return;
    
    function toggle() {
        const showECDD = yesRadio.checked;
        if (ecddField) ecddField.style.display = showECDD ? 'block' : 'none';
        if (qcibField) qcibField.style.display = showECDD ? 'block' : 'none';
        if (eaField) eaField.style.display = showECDD ? 'block' : 'none';
    }
    
    toggle();
    yesRadio.addEventListener('change', toggle);
    noRadio.addEventListener('change', toggle);
}

// ===== STEP 5 CONDITIONALS =====
function handleEditStep5Conditionals() {
    const form = document.querySelector('.edit-form');
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
            field.addEventListener('change', function() {
                targets.forEach(t => {
                    const fld = form.querySelector(`[name="${t}"], [id*="${t}"]`)?.closest('.field');
                    if (fld) fld.style.display = this.value === '1' ? 'block' : 'none';
                });
            });
            
            // Trigger initial state
            if (field.checked) {
                field.dispatchEvent(new Event('change'));
            }
        });
    });
}

// ===== UPDATE EDIT PROGRESS BAR =====
function updateEditProgressBar() {
    const progressFill = document.querySelector('.progress-fill');
    const currentStep = parseInt(document.querySelector('.form-step')?.getAttribute('data-step') || 2);
    const totalSteps = 5; // Steps 2-6 = 5 steps
    
    if (progressFill) {
        const percentage = ((currentStep - 1) / totalSteps) * 100;
        progressFill.style.width = `${percentage}%`;
    }
    
    // Update progress step indicators
    document.querySelectorAll('.progress-step').forEach((step) => {
        const stepNumber = parseInt(step.getAttribute('data-step'));
        
        if (stepNumber < currentStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else if (stepNumber === currentStep) {
            step.classList.add('active');
            step.classList.remove('completed');
        } else {
            step.classList.remove('active', 'completed');
        }
    });
}

// ===== SETUP EDIT PROGRESS INTERACTIONS =====
function setupEditProgressInteractions() {
    const progressSteps = document.querySelectorAll('.progress-step');
    
    progressSteps.forEach(step => {
        step.addEventListener('mouseenter', function() {
            if (this.classList.contains('completed') || this.classList.contains('active')) {
                this.style.cursor = 'pointer';
                this.style.transform = 'translateY(-2px)';
            }
        });
        
        step.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

// ===== ALERT SYSTEM FOR EDIT FORM =====
function showEditAlert(message, type = 'info') {
    document.querySelectorAll('.alert-floating').forEach(a => a.remove());
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-floating`;
    alert.style.cssText = `position:fixed;top:100px;right:20px;min-width:300px;z-index:3000;animation:slideInRight .3s ease-out;`;
    const icon = type === 'error' ? 
        '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>' : 
        '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>';
    alert.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${icon}</svg><span>${message}</span>`;
    document.body.appendChild(alert);
    setTimeout(() => { 
        alert.style.animation = 'slideOutRight .3s ease-out'; 
        setTimeout(() => alert.remove(), 300); 
    }, 5000);
}
