<div class="registration-page">
    <div class="page-hero__form">
        <div class="hero-content__form">
            <h1 class="page-title">Create Account</h1>
            <p class="page-subtitle">Register to submit forms</p>
        </div>
    </div>

    <div class="form-container">
        <% if $RequestVar('registered') %>
        <div class="register-success-card">
            <div class="register-success-icon">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#047a4d" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="8 12 11 15 16 9"/>
                </svg>
            </div>
            <h2 class="register-success-title">Account Created Successfully!</h2>
            <p class="register-success-msg">Your account has been created and you are now logged in. You can start filling in your form.</p>
            <a href="/form-submissions" class="btn btn-primary register-start-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                Start Form
            </a>
        </div>
        <% else %>
        $RegistrationForm

        <div class="form-footer" style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
            <p>Already have an account? <a href="/login" class="login-link" style="color: var(--primary-color); font-weight: 600;">Login here</a></p>
        </div>
        <% end_if %>
    </div>
</div>
