<header class="site-header">
    <div class="header-container">
        <div class="logo">
            <a href="/">
                <img 
                    src="$resourceURL('/_resources/themes/startup-theme/images/quinvest.svg')" 
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
                    <a href="/" class="nav-link <% if $IsFormPage %>active<% end_if %>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Form Submissions
                    </a>
                </li>
                <li>
                    <a href="/billing-form" class="nav-link <% if $IsFormPage %>active<% end_if %>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        Billing Form
                    </a>
                </li>
                <li>
                    <a href="/guidelines" class="nav-link <% if $IsGuidelinesPage %>active<% end_if %>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01"/>
                        </svg>
                        Guidelines
                    </a>
                </li>
                <%-- <li class="user-menu">
                    <button class="user-button">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>{$CurrentMember.FirstName}</span>
                    </button>
                    <div class="user-dropdown">
                        <a href="/logout" class="logout-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </li> --%>
            </ul>
        </nav>

    </div>
</header>