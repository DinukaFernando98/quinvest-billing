<section class="bg-gray-50 min-h-screen">
    <% if $Form %>
    <section class="py-8">
        <div class="page-container edit-submission-page">

            <!-- Page header -->
            <div class="edit-page-header">
                <div class="edit-header-bg"></div>
                <div class="edit-header-content">
                    <div class="edit-header-left">
                        <h1 class="edit-page-title">$Title</h1>
                        <div class="edit-page-meta">
                            <span class="edit-meta-badge">$Submission.Status</span>
                            <span class="edit-meta-item">Serial: <strong>$Submission.SerialNumber</strong></span>
                            <span class="edit-meta-item">Submitted: <strong>$Submission.Created.Nice</strong></span>
                        </div>
                    </div>
                    <div class="edit-header-actions">
                        <a href="/dashboard" class="edit-btn-back">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <% if $SuccessMessage %>
            <div class="edit-alert edit-alert-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                $SuccessMessage
            </div>
            <% end_if %>

            <% if $ErrorMessage %>
            <div class="edit-alert edit-alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                $ErrorMessage
            </div>
            <% end_if %>

            <div class="edit-form-card">
                <form $Form.AttributesHTML>
                    $Form.Fields

                    <div class="edit-form-footer">
                        <p class="edit-form-note">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            All changes will be logged and administrators will be notified.
                        </p>
                        $Form.Actions
                    </div>
                </form>
            </div>

        </div>
    </section>
    <% else %>
    <section class="py-16">
        <div class="page-container">
            <div class="edit-error-card">
                <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="edit-error-icon"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <h2>Unable to Load Form</h2>
                <p>Please check your permissions or try again.</p>
                <a href="/dashboard" class="btn btn-primary">Return to Dashboard</a>
            </div>
        </div>
    </section>
    <% end_if %>
</section>
