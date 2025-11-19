<!-- Animated Hero Banner -->
<div class="hero-banner">
    <div class="hero-background">
        <!-- 3D Floating Objects -->
        <div class="floating-objects">
            <div class="floating-cube cube-1"></div>
            <div class="floating-cube cube-2"></div>
            <div class="floating-cube cube-3"></div>
            <div class="floating-sphere sphere-1"></div>
            <div class="floating-sphere sphere-2"></div>
            <div class="floating-pyramid pyramid-1"></div>
            <div class="floating-cylinder cylinder-1"></div>
        </div>
        
        <!-- Animated Background Elements -->
        <div class="bg-grid"></div>
        <div class="bg-particles"></div>
    </div>
    
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title">
                <span class="title-word title-word-1">Quinvest Form</span>
                <span class="title-word title-word-2">Submissions</span>
            </h1>
            <p class="hero-subtitle">Complete your transaction documentation with our intuitive multistep form</p>
            
            <div class="hero-features">
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <span>Step-by-step guidance</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <span>Secure file uploads</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">✓</div>
                    <span>Real-time progress tracking</span>
                </div>
            </div>
            
            <div class="hero-cta">
                <a href="#form-start" class="cta-button primary">
                    <span>Start Form</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
                <a href="/guidelines" class="cta-button secondary">
                    <span>View Guidelines</span>
                </a>
            </div>
        </div>
        
        <div class="hero-visual">
            <div class="floating-form-preview">
                <div class="form-card card-1">
                    <div class="card-header"></div>
                    <div class="card-field"></div>
                    <div class="card-field"></div>
                    <div class="card-button"></div>
                </div>
                <div class="form-card card-2">
                    <div class="card-header"></div>
                    <div class="card-field"></div>
                    <div class="card-button"></div>
                </div>
                <div class="form-card card-3">
                    <div class="card-progress"></div>
                    <div class="card-checkmark"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="scroll-indicator">
        <div class="scroll-arrow" id="form-start"></div>
    </div>
</div>

<div class="form-submission-page page-container">    
    <!-- Background overlay for better readability -->
    <div style="position: relative; z-index: 2;">
        <div class="page-hero__form">
            <div class="hero-content__form">
                <h1 class="page-title">Form Submissions</h1>
                <p class="page-subtitle">Complete the multistep form below</p>
            </div>
        </div>
        
    <% if $RequestVar('success') %>
    <div class="modal active" id="successModal">
        <div class="modal-content success">
            <div class="modal-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#047a4d" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="8 12 11 15 16 9"/>
                </svg>
            </div>
            <h2>Form Submitted Successfully!</h2>
            <p>Your submission serial number is:</p>
            <div class="serial-number-container">
                <strong id="serialNumber">$RequestVar('serial')</strong>
                <button class="copy-serial-btn"
                    onclick="navigator.clipboard.writeText(document.getElementById('serialNumber').innerText); this.querySelector('.copy-text').innerText='Copied!'; setTimeout(()=>{this.querySelector('.copy-text').innerText='Copy';},1500);"
                    aria-label="Copy serial number">

                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    <span class="copy-text">Copy</span>
                </button>
            </div>
            <p class="modal-note">Please use this serial number when submitting your billing form.</p>
            <div class="modal-actions">
                <a href="$Link" class="btn btn-primary justify-center">Submit again</a>
                <a href="/billing-form" class="btn btn-primary">Submit billing form</a>
            </div>
        </div>
    </div>
        <% end_if %>
        
        <div class="form-container">
            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: {$getCurrentFormStepPercentage}%;"></div>
                </div>
                <div class="progress-steps">
                    <div class="progress-step {$getStepClass(1)}" data-step="1">
                        <div class="step-circle">1</div>
                        <div class="step-label">Login Info</div>
                    </div>
                    <div class="progress-step {$getStepClass(2)}" data-step="2">
                        <div class="step-circle">2</div>
                        <div class="step-label">Property Details</div>
                    </div>
                    <div class="progress-step {$getStepClass(3)}" data-step="3">
                        <div class="step-circle">3</div>
                        <div class="step-label">Client Info</div>
                    </div>
                    <div class="progress-step {$getStepClass(4)}" data-step="4">
                        <div class="step-circle">4</div>
                        <div class="step-label">AML & Forms</div>
                    </div>
                    <div class="progress-step {$getStepClass(5)}" data-step="5">
                        <div class="step-circle">5</div>
                        <div class="step-label">Documents</div>
                    </div>
                    <div class="progress-step {$getStepClass(6)}" data-step="6">
                        <div class="step-circle">6</div>
                        <div class="step-label">Review</div>
                    </div>
                </div>
            </div>
            
            <!-- Form Steps -->
            <div class="form-step active" data-step="$CurrentStep">
                <h2 class="step-title">$StepTitle</h2>
                
                $MultiStepForm
            </div>
        </div>
    </div>
</div>