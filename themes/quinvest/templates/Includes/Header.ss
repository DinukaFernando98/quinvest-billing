<header class="site-header">
    <div class="header-container">
        <div class="logo">
            <a href="/">
                <img
                    src="resources/themes/quinvest/images/quinvest.svg"
                    alt="QuInvest Logo"
                    width="200"
                />
            </a>
        </div>

        <nav class="main-nav">
            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <ul class="nav-links">
                <li>
                    <a href="/form-submissions" class="nav-link <% if $IsFormPage %>active<% end_if %>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Form Submissions |
                    </a>
                </li>
                <li>
                    <a href="/billing-form" class="nav-link <% if $IsBillingPage %>active<% end_if %>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Billing Form |
                    </a>
                </li>
                <li>
                    <a href="/guidelines" class="nav-link <% if $IsGuidelinesPage %>active<% end_if %>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        Guidelines |
                    </a>
                </li>
                <% if $CurrentMember %>
                    <li>
                        <a href="/dashboard" class="nav-link <% if $IsDashboardPage %>active<% end_if %>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                            Past Submissions |
                        </a>
                    </li>
                    <li class="user-menu">
                        <a href="$LogoutURL" class="action btn btn-primary btn-block">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            <span>Logout</span>
                        </a>
                    </li>
                <% else %>
                    <li class="user-menu">
                        <a href="/login" class="action btn btn-primary btn-block <% if $IsLoginPage %>active<% end_if %>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                <polyline points="10 17 15 12 10 7"/>
                                <line x1="15" y1="12" x2="3" y2="12"/>
                            </svg>
                            <span>Login</span>
                        </a>
                    </li>
                <% end_if %>
            </ul>
        </nav>

    </div>
</header>
