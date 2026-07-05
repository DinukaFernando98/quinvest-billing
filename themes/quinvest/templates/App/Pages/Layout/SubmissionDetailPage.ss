<div class="page-container">
    <!-- Main Content -->
    <main class="main-content">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="/dashboard">Dashboard</a>
            <span class="separator">/</span>
            <span>Submission Details</span>
        </div>
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>Submission Details</h1>
            <% if $Submission %>
                <div class="header-actions">
                    <% if not $IsEditMode %>
                        <% if $Submission.Status == 'Draft' || $Submission.Status == 'Submitted' %>
                            <a href="{$Link}edit/{$Submission.ID}/edit" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit Submission
                            </a>
                        <% end_if %>
                        <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
                    <% else %>
                        <a href="{$Link}view/{$Submission.ID}" class="btn btn-secondary">Cancel Edit</a>
                    <% end_if %>
                </div>
            <% end_if %>
        </div>
        
        <!-- Flash Messages -->
        <% if $FlashMessage %>
            <div class="alert alert-{$FlashMessage.Type}">
                $FlashMessage.Message
            </div>
        <% end_if %>
        
        <!-- Content -->
        <% if $Submission %>
            <!-- View Mode -->
            <% if not $IsEditMode %>
                <div class="submission-view">
                    <!-- Submission Header -->
                    <div class="submission-header">
                        <div class="header-left">
                            <div class="serial-number">$Submission.SerialNumber</div>
                            <div class="status-badge status-{$Submission.Status.LowerCase}">$Submission.Status</div>
                        </div>
                        <div class="header-right">
                            <div class="meta-info">
                                <span class="meta-item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    Submitted: <% if $Submission.SubmittedDate %>$Submission.SubmittedDate.Nice<% else %>Not submitted<% end_if %>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overview Cards -->
                    <div class="overview-cards">
                        <div class="overview-card">
                            <div class="card-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Salesperson</h3>
                                <p>$Submission.SalespersonName</p>
                                <small>RES: $Submission.RESNumber</small>
                            </div>
                        </div>
                        
                        <div class="overview-card">
                            <div class="card-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                    <circle cx="12" cy="10" r="3"/>
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Property Address</h3>
                                <p>$Submission.PropertyAddress</p>
                            </div>
                        </div>
                        
                        <div class="overview-card">
                            <div class="card-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="3" y1="9" x2="21" y2="9"/>
                                    <line x1="9" y1="21" x2="9" y2="9"/>
                                </svg>
                            </div>
                            <div class="card-content">
                                <h3>Transaction Type</h3>
                                <p>$Submission.TransactionType</p>
                                <small>Representing: $Submission.getRepresentingArray.join(', ')</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Client Information -->
                    <div class="detail-section">
                        <h2 class="section-title">Client Information</h2>
                        <% if $Submission.ClientInfo %>
                            <div class="client-grid">
                                <% loop $Submission.ClientInfo %>
                                    <div class="client-card">
                                        <h3 class="client-type">$PartyType</h3>
                                        <div class="client-details">
                                            <div class="detail-row">
                                                <span class="detail-label">Client Type:</span>
                                                <span class="detail-value">$ClientType</span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="detail-label">Acting Type:</span>
                                                <span class="detail-value">$ClientActingTypeSelection</span>
                                            </div>
                                            <% if $OwnershipProof %>
                                                <div class="detail-row">
                                                    <span class="detail-label">Ownership Proof:</span>
                                                    <a href="$OwnershipProof.URL" class="file-link" download>
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                            <polyline points="14 2 14 8 20 8"/>
                                                        </svg>
                                                        $OwnershipProof.Name
                                                    </a>
                                                </div>
                                            <% end_if %>
                                        </div>
                                    </div>
                                <% end_loop %>
                            </div>
                        <% else %>
                            <p class="text-muted">No client information available.</p>
                        <% end_if %>
                    </div>
                    
                    <!-- AML Records -->
                    <div class="detail-section">
                        <h2 class="section-title">AML Records</h2>
                        <% if $Submission.AMLRecords %>
                            <div class="aml-grid">
                                <% loop $Submission.AMLRecords %>
                                    <div class="aml-card">
                                        <h3 class="aml-type">$PartyType</h3>
                                        <div class="aml-details">
                                            <div class="detail-row">
                                                <span class="detail-label">AML Search:</span>
                                                <span class="detail-value"><% if $AMLCompleted %>Completed<% else %>Not Completed<% end_if %></span>
                                            </div>
                                            <% if $AMLFile %>
                                                <div class="detail-row">
                                                    <span class="detail-label">AML File:</span>
                                                    <a href="$AMLFile.URL" class="file-link" download>
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                            <polyline points="14 2 14 8 20 8"/>
                                                        </svg>
                                                        $AMLFile.Name
                                                    </a>
                                                </div>
                                            <% end_if %>
                                            <div class="detail-row">
                                                <span class="detail-label">Form QCI-D:</span>
                                                <% if $FormQCID %>
                                                    <a href="$FormQCID.URL" class="file-link" download>$FormQCID.Name</a>
                                                <% else %>
                                                    <span class="text-muted">Not uploaded</span>
                                                <% end_if %>
                                            </div>
                                        </div>
                                    </div>
                                <% end_loop %>
                            </div>
                        <% else %>
                            <p class="text-muted">No AML records available.</p>
                        <% end_if %>
                    </div>
                    
                    <!-- Documents -->
                    <div class="detail-section">
                        <h2 class="section-title">Documents</h2>
                        <% if $Submission.Documents %>
                            <div class="documents-list">
                                <% loop $Submission.Documents %>
                                    <div class="document-item">
                                        <div class="document-icon">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                            </svg>
                                        </div>
                                        <div class="document-info">
                                            <h4>$DocumentType</h4>
                                            <% if $Description %>
                                                <p class="document-description">$Description</p>
                                            <% end_if %>
                                            <% if $DocumentFile %>
                                                <a href="$DocumentFile.URL" class="btn btn-sm" download>
                                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                        <polyline points="7 10 12 15 17 10"/>
                                                        <line x1="12" y1="15" x2="12" y2="3"/>
                                                    </svg>
                                                    Download
                                                </a>
                                            <% end_if %>
                                        </div>
                                    </div>
                                <% end_loop %>
                            </div>
                        <% else %>
                            <p class="text-muted">No documents uploaded.</p>
                        <% end_if %>
                    </div>
                </div>
                
            <!-- Edit Mode -->
            <% else %>
                <div class="submission-edit">
                    $EditForm
                </div>
            <% end_if %>
            
        <% else %>
            <div class="alert alert-error">
                <h3>Submission Not Found</h3>
                <p>The requested submission could not be found or you don't have permission to view it.</p>
                <a href="/dashboard" class="btn btn-primary">Return to Dashboard</a>
            </div>
        <% end_if %>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <p>&copy; $Now.Year Quinvest Chambers. All rights reserved.</p>
    </footer>
</div>

<style>
    /* Basic Styles */
    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: #f5f5f5;
        color: #333;
    }
    
    .page-container {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    /* Navigation */
    .main-nav {
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 1rem 0;
    }
    
    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .logo {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary-color, #2563eb);
        text-decoration: none;
    }
    
    .nav-links {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .nav-links a {
        color: #666;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 4px;
    }
    
    .nav-links a:hover {
        background: #f3f4f6;
    }
    
    .btn-logout {
        background: #ef4444;
        color: white !important;
    }
    
    .btn-logout:hover {
        background: #dc2626;
    }
    
    /* Main Content */
    .main-content {
        flex: 1;
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        width: 100%;
    }
    
    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 2rem;
        color: #6b7280;
        font-size: 0.875rem;
    }
    
    .breadcrumb a {
        color: var(--primary-color, #2563eb);
        text-decoration: none;
    }
    
    .breadcrumb a:hover {
        text-decoration: underline;
    }
    
    .separator {
        color: #d1d5db;
    }
    
    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .page-header h1 {
        margin: 0;
        font-size: 2rem;
        font-weight: 700;
        color: #111827;
    }
    
    .header-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.875rem;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: var(--primary-color, #2563eb);
        color: white;
    }
    
    .btn-primary:hover {
        background: #1d4ed8;
    }
    
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border-color: #d1d5db;
    }
    
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Alerts */
    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }
    
    .alert-good {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }
    
    .alert-warning {
        background: #fef3c7;
        border: 1px solid #fde68a;
        color: #92400e;
    }
    
    .alert-error {
        background: #fee2e2;
        border: 1px solid #fca5a5;
        color: #991b1b;
    }
    
    /* Submission View Styles */
    .submission-view {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    
    .submission-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 1.5rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .serial-number {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--primary-color, #2563eb);
    }
    
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .status-draft {
        background: #f3f4f6;
        color: #374151;
    }
    
    .status-submitted {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-approved {
        background: #d1fae5;
        color: #065f46;
    }
    
    .status-rejected {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .header-right .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #6b7280;
        font-size: 0.875rem;
    }
    
    /* Overview Cards */
    .overview-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .overview-card {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--primary-color, #2563eb), #1d4ed8);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .card-icon svg {
        width: 24px;
        height: 24px;
        stroke: white;
    }
    
    .card-content h3 {
        margin: 0 0 0.5rem 0;
        font-size: 0.875rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .card-content p {
        margin: 0 0 0.25rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
    }
    
    .card-content small {
        color: #6b7280;
        font-size: 0.75rem;
    }
    
    /* Detail Sections */
    .detail-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .section-title {
        margin: 0 0 1.5rem 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    /* Grid Layouts */
    .client-grid,
    .aml-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
    }
    
    .client-card,
    .aml-card {
        background: #f9fafb;
        border-radius: 6px;
        padding: 1rem;
        border: 1px solid #e5e7eb;
    }
    
    .client-type,
    .aml-type {
        margin: 0 0 1rem 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--primary-color, #2563eb);
    }
    
    .detail-row {
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
    }
    
    .detail-label {
        font-weight: 500;
        color: #6b7280;
        font-size: 0.875rem;
    }
    
    .detail-value {
        text-align: right;
        color: #111827;
        font-weight: 500;
        font-size: 0.875rem;
    }
    
    .file-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--primary-color, #2563eb);
        text-decoration: none;
        font-size: 0.875rem;
    }
    
    .file-link:hover {
        text-decoration: underline;
    }
    
    /* Documents List */
    .documents-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .document-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
    }
    
    .document-icon {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        background: linear-gradient(135deg, var(--primary-color, #2563eb), #1d4ed8);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .document-icon svg {
        width: 20px;
        height: 20px;
        stroke: white;
    }
    
    .document-info {
        flex: 1;
    }
    
    .document-info h4 {
        margin: 0 0 0.25rem 0;
        font-size: 0.875rem;
        font-weight: 600;
        color: #111827;
    }
    
    .document-description {
        margin: 0 0 0.5rem 0;
        color: #6b7280;
        font-size: 0.75rem;
    }
    
    .text-muted {
        color: #6b7280;
        font-style: italic;
    }
    
    /* Form Styles */
    .submission-edit {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #374151;
    }
    
    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 1rem;
    }
    
    .form-input:focus {
        outline: none;
        border-color: var(--primary-color, #2563eb);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    textarea.form-input {
        min-height: 100px;
        resize: vertical;
    }
    
    .radio-group,
    .checkbox-group {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .radio-group label,
    .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: normal;
    }
    
    .form-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }
    
    /* Footer */
    .footer {
        background: white;
        padding: 1.5rem;
        text-align: center;
        color: #6b7280;
        font-size: 0.875rem;
        border-top: 1px solid #e5e7eb;
        margin-top: 2rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
        }
        
        .header-actions {
            width: 100%;
            justify-content: flex-start;
        }
        
        .submission-header {
            flex-direction: column;
            gap: 1rem;
        }
        
        .header-left {
            flex-wrap: wrap;
        }
        
        .overview-cards {
            grid-template-columns: 1fr;
        }
        
        .client-grid,
        .aml-grid {
            grid-template-columns: 1fr;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>