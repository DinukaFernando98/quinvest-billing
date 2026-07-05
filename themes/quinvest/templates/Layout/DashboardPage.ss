<div class="dashboard-page page-container">
    <!-- User Profile Banner -->
    <div class="user-profile-banner">
        <div class="banner-background">
            <div class="bg-pattern"></div>
        </div>
        <div class="banner-content">
            <div class="user-avatar">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <div class="user-info">
                <h1 class="user-name">$CurrentMember.FirstName $CurrentMember.Surname</h1>
                <div class="user-meta">
                    <span class="meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        RES Number: <strong>$CurrentMember.RESNumber</strong>
                    </span>
                    <span class="meta-item">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                        Member Since: <strong>$CurrentMember.Created.Format('d M Y')</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-number">$getUserSubmissions.Count</h3>
                <p class="stat-label">Form Submissions</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-number">$getUserBillingSubmissions.Count</h3>
                <p class="stat-label">Billing Submissions</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-number">
                    <% loop $getUserSubmissions %>
                        <% if $Status == 'Approved' %>$Pos<% end_if %>
                    <% end_loop %>
                </h3>
                <p class="stat-label">Approved</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3 class="stat-number">
                    <% loop $getUserSubmissions %>
                        <% if $Status == 'Declined' %>$Pos<% end_if %>
                    <% end_loop %>
                </h3>
                <p class="stat-label">Declined</p>
            </div>
        </div>
    </div>

    <!-- Form Submissions Section -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2 class="section-title">My Form Submissions</h2>
            <a href="/?step=2#form-start" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                New Submission
            </a>
        </div>
        
        <div class="submissions-table">
            <div class="table-header">
                <div class="table-cell">Serial Number</div>
                <div class="table-cell">Property Address</div>
                <div class="table-cell">Transaction Type</div>
                <div class="table-cell">Status</div>
                <div class="table-cell">Submitted Date</div>
                <div class="table-cell">Actions</div>
            </div>
            
            <% if $getUserSubmissions %>
                <% loop $getUserSubmissions %>
                <div class="table-row">
                    <div class="table-cell">
                        <span class="serial-number">$SerialNumber</span>
                    </div>
                    <div class="table-cell">
                        <span class="truncate-text">$PropertyAddress.LimitCharacters(50)</span>
                    </div>
                    <div class="table-cell">
                        <span class="status-badge type-{$TransactionType.LowerCase}">$TransactionType</span>
                    </div>
                    <div class="table-cell">
                        <span class="status-badge status-{$Status.LowerCase}">$Status</span>
                    </div>
                    <div class="table-cell">
                        <span class="date">$SubmittedDate.Nice</span>
                    </div>
                    <div class="table-cell">
                        <div class="action-buttons">
                            <!-- Show Edit button only for Submitted status -->
                            <% if $Status == 'Submitted' %>
                                <a href="/edit-submission?request=$ID" class="btn btn-sm btn-edit" title="Edit Submission">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </a>
                                
                                <button class="btn btn-sm btn-copy" onclick="copyToClipboard('$SerialNumber')" title="Copy Serial Number">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                    </svg>
                                </button>
                            <% end_if %>
                            
                            <!-- Show info badge for Approved/Declined status -->
                            <% if $Status == 'Approved' || $Status == 'Declined' %>
                                <span class="status-info" title="This submission cannot be edited">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <line x1="12" y1="16" x2="12" y2="12"/>
                                        <line x1="12" y1="8" x2="12.01" y2="8"/>
                                    </svg>
                                </span>
                            <% end_if %>
                        </div>
                    </div>
                </div>
                <% end_loop %>
            <% else %>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                    </svg>
                    <h3>No submissions yet</h3>
                    <p>Start your first form submission to see it here.</p>
                    <a href="/?step=2#form-start" class="btn btn-primary">Create First Submission</a>
                </div>
            <% end_if %>
        </div>
    </div>

    <!-- Billing Submissions Section -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2 class="section-title">My Billing Submissions</h2>
            <a href="/billing-form" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Submit Billing
            </a>
        </div>
        
        <div class="submissions-table">
            <div class="table-header">
                <div class="table-cell">Serial Number</div>
                <div class="table-cell">Form Submission</div>
                <div class="table-cell">File</div>
                <div class="table-cell">Submitted Date</div>
                <div class="table-cell">Actions</div>
            </div>
            
            <% if $getUserBillingSubmissions %>
                <% loop $getUserBillingSubmissions %>
                <div class="table-row">
                    <div class="table-cell">
                        <span class="serial-number">$SerialNumber</span>
                    </div>
                    <div class="table-cell">
                        <% if $FormSubmission %>
                            <button class="link" onclick="showSubmissionDetail($FormSubmission.ID)">
                                $FormSubmission.SalespersonName
                            </button>
                        <% else %>
                            <span class="text-muted">Not linked</span>
                        <% end_if %>
                    </div>
                    <div class="table-cell">
                        <% if $BillingFile %>
                            <span class="file-info">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                $BillingFile.Name
                            </span>
                        <% else %>
                            <span class="text-muted">No file</span>
                        <% end_if %>
                    </div>
                    <div class="table-cell">
                        <span class="date">$Created.Nice</span>
                    </div>
                    <div class="table-cell">
                        <div class="action-buttons">
                            <% if $BillingFile %>
                            <a href="{$BillingFile.URL}" class="btn btn-sm btn-download" title="Download File" download>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                            </a>
                            <% end_if %>

                            <!-- Show Edit button only while the linked submission is still pending approval -->
                            <% if $FormSubmission && $FormSubmission.Status == 'Submitted' %>
                                <a href="/billing-form?edit=$ID" class="btn btn-sm btn-edit" title="Edit Billing Submission">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </a>
                            <% end_if %>
                        </div>
                    </div>
                </div>
                <% end_loop %>
            <% else %>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <h3>No billing submissions yet</h3>
                    <p>Submit a billing form to see it here.</p>
                    <a href="/billing-form" class="btn btn-primary">Submit Billing Form</a>
                </div>
            <% end_if %>
        </div>
    </div>

    <!-- Submission Detail Modal -->
    <div class="modal" id="submissionDetailModal">
        <div class="modal-overlay" onclick="hideSubmissionDetail()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Submission Details</h2>
                <button class="modal-close" onclick="hideSubmissionDetail()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="submissionDetailContent">
                <!-- Content will be loaded here via AJAX -->
                <div class="loading-spinner">
                    <div class="spinner"></div>
                    <p>Loading submission details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'toast-message';
            toast.textContent = 'Serial number copied to clipboard!';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
        });
    }

    function showSubmissionDetail(submissionId) {
        const modal = document.getElementById('submissionDetailModal');
        const content = document.getElementById('submissionDetailContent');
        
        // Show loading state
        content.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading submission details...</p>
            </div>
        `;
        
        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Load submission details via AJAX
        fetch(`/dashboard/getSubmissionDetail/${submissionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = data.html;
                } else {
                    content.innerHTML = `
                        <div class="alert alert-error">
                            <h3>Error loading submission</h3>
                            <p>${data.message || 'Unable to load submission details.'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                content.innerHTML = `
                    <div class="alert alert-error">
                        <h3>Error loading submission</h3>
                        <p>Unable to load submission details. Please try again.</p>
                    </div>
                `;
            });
    }

    function hideSubmissionDetail() {
        const modal = document.getElementById('submissionDetailModal');
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideSubmissionDetail();
        }
    });
</script>

<style>
    .dashboard-page {
        padding: 2rem 0;
    }

    /* User Profile Banner */
    .user-profile-banner {
        background: linear-gradient(135deg, var(--primary-color), #1a56db);
        border-radius: 16px;
        position: relative;
        overflow: hidden;
        margin-bottom: 2rem;
        color: white;
    }

    .banner-background {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }

    .bg-pattern {
        position: absolute;
        width: 100%;
        height: 100%;
        background-image: radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 40px 40px;
    }

    .banner-content {
        position: relative;
        z-index: 2;
        padding: 3rem;
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .user-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid rgba(255,255,255,0.3);
    }

    .user-avatar svg {
        width: 50px;
        height: 50px;
        stroke: white;
    }

    .user-info {
        flex: 1;
    }

    .user-name {
        margin: 0 0 0.5rem 0;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .user-meta {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.95rem;
        opacity: 0.9;
    }

    .meta-item svg {
        width: 16px;
        height: 16px;
    }

    /* Dashboard Stats */
    .dashboard-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #e5e7eb;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon svg {
        width: 28px;
        height: 28px;
        stroke: white;
    }

    .stat-content {
        flex: 1;
    }

    .stat-number {
        margin: 0 0 0.25rem 0;
        font-size: 2rem;
        font-weight: 700;
        color: #111827;
    }

    .stat-label {
        margin: 0;
        color: #6b7280;
        font-size: 0.875rem;
    }

    /* Dashboard Sections */
    .dashboard-section {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        border: 1px solid #e5e7eb;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .section-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        color: #111827;
    }

    /* Tables */
    .submissions-table {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }

    .table-header {
        display: grid;
        grid-template-columns: 1.5fr 2fr 1fr 1fr 1fr 0.8fr;
        background: #f9fafb;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .table-row {
        display: grid;
        grid-template-columns: 1.5fr 2fr 1fr 1fr 1fr 0.8fr;
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        align-items: center;
        transition: background-color 0.2s ease;
    }

    .table-row:hover {
        background-color: #f9fafb;
    }

    .table-row:last-child {
        border-bottom: none;
    }

    .table-cell {
        padding: 0.5rem;
    }

    .serial-number {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.875rem;
        color: var(--primary-color);
        font-weight: 500;
    }

    .truncate-text {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
    }

    /* Status Badges */
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-submitted {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-approved {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-rejected {
        background-color: #fee2e2;
        color: #991b1b;
    }

    .status-draft {
        background-color: #e5e7eb;
        color: #374151;
    }

    .type-sale {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .type-lease {
        background-color: #f3e8ff;
        color: #7c3aed;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
        line-height: 1.5;
    }

    .btn-view {
        background-color: #e0f2fe;
        color: #0369a1;
    }

    .btn-view:hover {
        background-color: #bae6fd;
    }

    .btn-copy {
        background-color: #f1f5f9;
        color: #475569;
    }

    .btn-copy:hover {
        background-color: #e2e8f0;
    }

    .btn-download {
        background-color: #d1fae5;
        color: #065f46;
    }

    .btn-download:hover {
        background-color: #a7f3d0;
    }

    /* Empty State */
    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
        color: #6b7280;
    }

    .empty-state svg {
        margin-bottom: 1.5rem;
        stroke: #d1d5db;
    }

    .empty-state h3 {
        margin: 0 0 0.5rem 0;
        color: #374151;
        font-weight: 600;
    }

    .empty-state p {
        margin: 0 0 1.5rem 0;
    }

    /* File Info */
    .file-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .file-info svg {
        stroke: #6b7280;
    }

    .link {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        background: none;
        border: none;
        cursor: pointer;
        font-size: inherit;
        padding: 0;
    }

    .link:hover {
        text-decoration: underline;
    }

    .date {
        font-size: 0.875rem;
        color: #6b7280;
    }

    /* Toast Message */
    .toast-message {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background-color: #10b981;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        opacity: 0;
        transform: translateY(1rem);
        transition: opacity 0.3s ease, transform 0.3s ease;
        z-index: 1000;
    }

    .toast-message.show {
        opacity: 1;
        transform: translateY(0);
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1001;
    }

    .modal.active {
        display: block;
    }

    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }

    .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        color: #111827;
    }

    .modal-close {
        background: none;
        border: none;
        padding: 0.5rem;
        cursor: pointer;
        color: #6b7280;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-close:hover {
        background-color: #f3f4f6;
        color: #111827;
    }

    .modal-body {
        padding: 2rem;
        overflow-y: auto;
        flex: 1;
    }

    /* Loading Spinner */
    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        color: #6b7280;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #e5e7eb;
        border-top-color: var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 1rem;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Alert Styles */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 8px;
        margin: 1rem 0;
    }

    .alert-error {
        background-color: #fee2e2;
        border: 1px solid #fca5a5;
        color: #991b1b;
    }

    .alert h3 {
        margin-top: 0;
        margin-bottom: 0.5rem;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .table-header,
        .table-row {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .table-header .table-cell:nth-child(4),
        .table-header .table-cell:nth-child(5),
        .table-row .table-cell:nth-child(4),
        .table-row .table-cell:nth-child(5) {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .banner-content {
            flex-direction: column;
            text-align: center;
            padding: 2rem;
        }
        
        .user-meta {
            justify-content: center;
        }
        
        .section-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .dashboard-stats {
            grid-template-columns: 1fr;
        }
        
        .table-header,
        .table-row {
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        
        .table-header .table-cell:nth-child(3),
        .table-row .table-cell:nth-child(3) {
            display: none;
        }
        
        .modal-content {
            width: 95%;
            max-height: 95vh;
        }
        
        .modal-header {
            padding: 1rem 1.5rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
    }
</style>