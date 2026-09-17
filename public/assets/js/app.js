/**
 * Apex Global HRMS — Core Application Controller & Single-Page Router
 */

const App = {
  state: {
    user: null,
    employee: null,
    role: null,
    unreadNotifications: 0,
    activeRoute: 'dashboard',
    departments: [],
    designations: [],
    clockInterval: null
  },

  async init() {
    this.setupGlobalListeners();
    await this.checkAuth();
  },

  setupGlobalListeners() {
    window.addEventListener('hashchange', () => this.handleRoute());
    
    // Keyboard shortcut for Global Search: Ctrl+K or Cmd+K
    window.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        this.openGlobalSearch();
      }
      if (e.key === 'Escape') {
        this.closeAllModals();
      }
    });

    // Mobile sidebar toggle
    const toggleBtn = document.getElementById('mobileMenuToggle');
    if (toggleBtn) {
      toggleBtn.addEventListener('click', () => {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('mobile-open');
      });
    }
  },

  async checkAuth() {
    try {
      const res = await API.get('api/auth/me.php');
      this.state.user = res.data.user;
      this.state.employee = res.data.employee;
      this.state.role = res.data.user.role_name;
      this.state.unreadNotifications = res.data.unread_notifications || 0;
      
      this.renderAppShell();
      this.startClock();
      this.handleRoute();
    } catch (err) {
      this.state.user = null;
      this.state.employee = null;
      this.state.role = null;
      this.renderAuthView();
    }
  },

  renderAppShell() {
    document.getElementById('appRoot').innerHTML = `
      <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
          <div class="sidebar-header">
            <div class="brand-logo">
              <div class="brand-icon">⚡</div>
              <span>Apex HRMS</span>
            </div>
          </div>
          <nav class="sidebar-nav" id="sidebarNav"></nav>
          <div class="sidebar-footer">
            <div class="user-mini-card">
              <div class="user-avatar">
                ${this.state.employee ? `${this.state.employee.first_name[0]}${this.state.employee.last_name[0]}` : '👤'}
              </div>
              <div class="user-meta">
                <div class="user-name">${this.state.employee ? `${this.state.employee.first_name} ${this.state.employee.last_name}` : this.state.user.username}</div>
                <span class="user-role-badge">${this.state.user.role_display_name || this.state.role}</span>
              </div>
              <button class="icon-btn" onclick="App.logout()" title="Sign Out" style="width:32px; height:32px; border:none; background:transparent;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
              </button>
            </div>
          </div>
        </aside>

        <!-- Main Content Area -->
        <div class="main-wrapper">
          <!-- Topbar -->
          <header class="topbar">
            <div class="topbar-left">
              <button class="mobile-menu-toggle" id="mobileMenuToggle">☰</button>
              <div class="global-search-trigger" onclick="App.openGlobalSearch()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Search employees, docs, jobs...</span>
                <span class="search-shortcut">⌘K</span>
              </div>
            </div>
            <div class="topbar-right">
              <div class="quick-clock-widget" id="headerClockWidget">
                <span class="pulse-dot"></span>
                <span id="liveClockDisplay">--:--:--</span>
              </div>
              <button class="icon-btn" onclick="App.openNotificationsModal()" title="Notifications">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                ${this.state.unreadNotifications > 0 ? '<span class="badge-dot"></span>' : ''}
              </button>
              <button class="btn btn-secondary btn-sm" onclick="App.openChangePasswordModal()">
                🔑 Security
              </button>
            </div>
          </header>

          <!-- Main Dynamic Page Body -->
          <main class="page-container" id="pageContainer">
            <div class="skeleton" style="height: 280px; width: 100%;"></div>
          </main>
        </div>
      </div>

      <!-- Modal Container -->
      <div id="modalRoot"></div>
    `;

    this.renderSidebarNav();
  },

  renderSidebarNav() {
    const role = this.state.role;
    const isSuper = role === 'super_admin';
    const isHR = role === 'hr_admin';
    const isManager = role === 'manager';
    const isPrivileged = isSuper || isHR;

    const nav = [
      { id: 'dashboard', label: 'Dashboard', icon: '📊', show: true },
      { id: 'employees', label: 'Employees', icon: '👥', show: true },
      { id: 'attendance', label: 'Attendance', icon: '⏱️', show: true },
      { id: 'leave', label: 'Leave', icon: '🏖️', show: true },
      { id: 'payroll', label: 'Payroll', icon: '💰', show: isPrivileged || true },
      { id: 'departments', label: 'Departments', icon: '🏢', show: isPrivileged || isManager },
      { id: 'recruitment', label: 'Recruitment', icon: '🎯', show: isPrivileged || isManager },
      { id: 'performance', label: 'Performance', icon: '⭐', show: true },
      { id: 'documents', label: 'Documents', icon: '📁', show: true },
      { id: 'announcements', label: 'Announcements', icon: '📢', show: true },
      { id: 'calendar', label: 'Calendar', icon: '📅', show: true },
      { id: 'reports', label: 'Reports', icon: '📈', show: isPrivileged || isManager },
      { id: 'settings', label: 'Settings', icon: '⚙️', show: isSuper }
    ];

    const container = document.getElementById('sidebarNav');
    if (!container) return;

    container.innerHTML = nav.filter(item => item.show).map(item => `
      <div class="nav-item ${this.state.activeRoute === item.id ? 'active' : ''}" onclick="App.navigate('${item.id}')">
        <div class="nav-item-content">
          <span style="font-size:1.15rem;">${item.icon}</span>
          <span>${item.label}</span>
        </div>
      </div>
    `).join('');
  },

  navigate(route) {
    window.location.hash = `#${route}`;
  },

  handleRoute() {
    if (!this.state.user) {
      this.renderAuthView();
      return;
    }

    const hash = window.location.hash.replace('#', '') || 'dashboard';
    this.state.activeRoute = hash;
    this.renderSidebarNav();

    // Close mobile drawer if open
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) sidebar.classList.remove('mobile-open');

    switch (hash) {
      case 'dashboard':
        this.views.dashboard();
        break;
      case 'employees':
        this.views.employees();
        break;
      case 'attendance':
        this.views.attendance();
        break;
      case 'leave':
        this.views.leave();
        break;
      case 'payroll':
        this.views.payroll();
        break;
      case 'departments':
        this.views.departments();
        break;
      case 'recruitment':
        this.views.recruitment();
        break;
      case 'performance':
        this.views.performance();
        break;
      case 'documents':
        this.views.documents();
        break;
      case 'announcements':
        this.views.announcements();
        break;
      case 'calendar':
        this.views.calendar();
        break;
      case 'reports':
        this.views.reports();
        break;
      case 'settings':
        this.views.settings();
        break;
      default:
        this.views.dashboard();
        break;
    }
  },

  startClock() {
    if (this.state.clockInterval) clearInterval(this.state.clockInterval);
    const updateTime = () => {
      const el = document.getElementById('liveClockDisplay');
      if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString();
      }
    };
    updateTime();
    this.state.clockInterval = setInterval(updateTime, 1000);
  },

  async logout() {
    try {
      await API.post('api/auth/logout.php');
      Toast.show('You have been signed out.', 'info');
    } catch (e) {}
    this.state.user = null;
    this.state.employee = null;
    this.renderAuthView();
  },

  /* ==========================================================================
     MODALS & UTILITIES
     ========================================================================== */
  showModal(title, bodyHtml, footerHtml = '', size = '') {
    const modalRoot = document.getElementById('modalRoot');
    modalRoot.innerHTML = `
      <div class="modal-backdrop active" id="currentModalBackdrop" onclick="if(event.target === this) App.closeAllModals()">
        <div class="modal-dialog ${size === 'lg' ? 'modal-dialog-lg' : ''}">
          <div class="modal-header">
            <h3 class="modal-title">${title}</h3>
            <button class="modal-close" onclick="App.closeAllModals()">&times;</button>
          </div>
          <div class="modal-body">${bodyHtml}</div>
          ${footerHtml ? `<div class="modal-footer">${footerHtml}</div>` : ''}
        </div>
      </div>
    `;
  },

  closeAllModals() {
    const modalRoot = document.getElementById('modalRoot');
    if (modalRoot) modalRoot.innerHTML = '';
  },

  openGlobalSearch() {
    this.showModal('🔍 Global Search', `
      <div class="form-group">
        <input type="text" class="form-control" id="globalSearchInput" placeholder="Type to search employees, departments, announcements, jobs..." autofocus oninput="App.executeGlobalSearch(this.value)">
      </div>
      <div id="searchResultsContainer" style="max-height: 360px; overflow-y: auto;">
        <div class="empty-state" style="padding: 2rem 1rem;">
          <p class="empty-text">Start typing at least 2 characters to search across system...</p>
        </div>
      </div>
    `, '', 'lg');
    setTimeout(() => document.getElementById('globalSearchInput')?.focus(), 100);
  },

  async executeGlobalSearch(query) {
    const container = document.getElementById('searchResultsContainer');
    if (!container) return;
    if (query.trim().length < 2) {
      container.innerHTML = `<div class="empty-state" style="padding: 2rem;"><p class="empty-text">Type at least 2 characters...</p></div>`;
      return;
    }

    try {
      const res = await API.get('api/search.php', { q: query });
      const { employees, departments, announcements, jobs } = res.data;

      let html = '';
      if (employees.length) {
        html += `<h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--slate-400); margin:0.5rem 0;">Employees</h4>`;
        employees.forEach(e => {
          html += `
            <div style="padding:0.6rem; border-radius:6px; background:var(--slate-50); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="App.views.openEmployeeDrawer(${e.id}); App.closeAllModals();">
              <div>
                <strong>${e.first_name} ${e.last_name}</strong> <span style="color:var(--slate-400); font-size:0.8rem;">(${e.employee_code})</span>
                <div style="font-size:0.8rem; color:var(--slate-500);">${e.designation_title || 'Employee'} • ${e.department_name || ''}</div>
              </div>
              <span class="badge badge-neutral">View Profile</span>
            </div>
          `;
        });
      }

      if (departments.length) {
        html += `<h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--slate-400); margin:1rem 0 0.5rem 0;">Departments</h4>`;
        departments.forEach(d => {
          html += `
            <div style="padding:0.6rem; border-radius:6px; background:var(--slate-50); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="App.navigate('departments'); App.closeAllModals();">
              <div><strong>${d.name}</strong> <span style="color:var(--slate-400); font-size:0.8rem;">(${d.code})</span></div>
              <span class="badge badge-neutral">View Dept</span>
            </div>
          `;
        });
      }

      if (jobs.length) {
        html += `<h4 style="font-size:0.8rem; text-transform:uppercase; color:var(--slate-400); margin:1rem 0 0.5rem 0;">Job Openings</h4>`;
        jobs.forEach(j => {
          html += `
            <div style="padding:0.6rem; border-radius:6px; background:var(--slate-50); margin-bottom:0.4rem; display:flex; justify-content:space-between; align-items:center; cursor:pointer;" onclick="App.navigate('recruitment'); App.closeAllModals();">
              <div><strong>${j.title}</strong> <span style="font-size:0.8rem; color:var(--slate-500);">(${j.job_type})</span></div>
              <span class="badge badge-info">Recruitment</span>
            </div>
          `;
        });
      }

      if (!employees.length && !departments.length && !jobs.length) {
        html = `<div class="empty-state" style="padding:2rem;"><div class="empty-title">No results found</div><p class="empty-text">No matches found for "${query}".</p></div>`;
      }

      container.innerHTML = html;
    } catch (e) {}
  },

  async openNotificationsModal() {
    try {
      const res = await API.get('api/notifications/list.php');
      const notifs = res.data.notifications;

      let html = '';
      if (!notifs.length) {
        html = `<div class="empty-state" style="padding: 2rem;"><p class="empty-text">You have no notifications right now.</p></div>`;
      } else {
        html = notifs.map(n => `
          <div style="padding:0.85rem; border-bottom:1px solid var(--border-color); background:${n.is_read ? 'transparent' : 'var(--slate-50)'}; display:flex; gap:0.75rem; align-items:flex-start;">
            <span style="font-size:1.2rem;">${n.type === 'success' ? '✅' : n.type === 'warning' ? '⚠️' : '📢'}</span>
            <div style="flex:1;">
              <div style="font-weight:600; font-size:0.9rem; color:var(--slate-900);">${n.title}</div>
              <div style="font-size:0.85rem; color:var(--slate-600); margin-top:0.2rem;">${n.message}</div>
              <div style="font-size:0.75rem; color:var(--slate-400); margin-top:0.35rem;">${n.created_at}</div>
            </div>
          </div>
        `).join('');
      }

      this.showModal('Notifications', `
        <div style="max-height: 400px; overflow-y: auto;">${html}</div>
      `, `
        <button class="btn btn-secondary btn-sm" onclick="App.markAllNotificationsRead()">Mark All as Read</button>
        <button class="btn btn-primary btn-sm" onclick="App.closeAllModals()">Close</button>
      `);
    } catch (e) {}
  },

  async markAllNotificationsRead() {
    await API.post('api/notifications/mark_read.php', {});
    this.state.unreadNotifications = 0;
    const dot = document.querySelector('.badge-dot');
    if (dot) dot.remove();
    this.closeAllModals();
    Toast.show('All notifications marked as read.', 'success');
  },

  openChangePasswordModal() {
    this.showModal('Security — Change Password', `
      <form id="changePasswordForm" onsubmit="App.submitChangePassword(event)">
        <div class="form-group">
          <label class="form-label">Current Password</label>
          <input type="password" class="form-control" name="current_password" required placeholder="Enter current password">
        </div>
        <div class="form-group">
          <label class="form-label">New Password (min 8 chars)</label>
          <input type="password" class="form-control" name="new_password" required minlength="8" placeholder="Enter new strong password">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password</label>
          <input type="password" class="form-control" name="confirm_password" required placeholder="Re-type new password">
        </div>
      </form>
    `, `
      <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
      <button class="btn btn-primary btn-sm" form="changePasswordForm" type="submit">Update Password</button>
    `);
  },

  async submitChangePassword(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form).entries());
    try {
      await API.post('api/auth/change_password.php', data);
      this.closeAllModals();
      Toast.show('Password changed successfully.', 'success');
    } catch (err) {}
  }
};
