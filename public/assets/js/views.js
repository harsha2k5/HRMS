/**
 * Apex Global HRMS — Module Views & Interactive Components
 */

App.views = {};

/* ==========================================================================
   1. AUTHENTICATION VIEWS
   ========================================================================== */
App.renderAuthView = function(view = 'login') {
  const root = document.getElementById('appRoot');
  if (!root) return;

  if (view === 'login') {
    root.innerHTML = `
      <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, var(--slate-900), var(--slate-800)); padding:1.5rem;">
        <div style="max-width:440px; width:100%; background:var(--white); border-radius:var(--radius-xl); box-shadow:var(--shadow-xl); padding:2.5rem; border:1px solid var(--border-color);">
          <div style="text-align:center; margin-bottom:2rem;">
            <div class="brand-icon" style="margin:0 auto 1rem auto; width:48px; height:48px; font-size:1.5rem;">⚡</div>
            <h2 style="font-size:1.6rem; font-weight:800; color:var(--slate-900); letter-spacing:-0.03em;">Welcome to Apex HRMS</h2>
            <p style="color:var(--slate-500); font-size:0.92rem; margin-top:0.35rem;">Sign in to access your enterprise portal</p>
          </div>

          <!-- Quick 1-Click Demo Accounts -->
          <div style="background:var(--slate-50); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0.85rem; margin-bottom:1.5rem;">
            <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; color:var(--slate-400); margin-bottom:0.5rem; letter-spacing:0.05em;">Quick Demo One-Click Sign In:</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.4rem;">
              <button class="btn btn-secondary btn-sm" onclick="App.fillDemo('admin@company.com', 'password123')" style="font-size:0.75rem; padding:0.4rem;">👑 Super Admin</button>
              <button class="btn btn-secondary btn-sm" onclick="App.fillDemo('hr@company.com', 'password123')" style="font-size:0.75rem; padding:0.4rem;">👥 HR Admin</button>
              <button class="btn btn-secondary btn-sm" onclick="App.fillDemo('manager@company.com', 'password123')" style="font-size:0.75rem; padding:0.4rem;">💼 Manager</button>
              <button class="btn btn-secondary btn-sm" onclick="App.fillDemo('employee@company.com', 'password123')" style="font-size:0.75rem; padding:0.4rem;">👤 Employee</button>
            </div>
          </div>

          <form onsubmit="App.handleLogin(event)">
            <div class="form-group">
              <label class="form-label">Email or Username</label>
              <input type="text" class="form-control" id="loginUsername" required placeholder="admin@company.com" value="admin@company.com">
            </div>
            <div class="form-group">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
                <label class="form-label" style="margin:0;">Password</label>
                <a href="javascript:void(0)" onclick="App.renderAuthView('forgot')" style="font-size:0.8rem; color:var(--brand-accent);">Forgot?</a>
              </div>
              <input type="password" class="form-control" id="loginPassword" required placeholder="••••••••" value="password123">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem; font-size:0.95rem; margin-top:0.5rem;" id="loginSubmitBtn">
              Sign In to Portal
            </button>
          </form>

          <div style="text-align:center; margin-top:1.5rem; font-size:0.88rem; color:var(--slate-500);">
            Need a new account? <a href="javascript:void(0)" onclick="App.renderAuthView('register')" style="color:var(--slate-900); font-weight:600;">Register here</a>
          </div>
        </div>
      </div>
    `;
  } else if (view === 'register') {
    root.innerHTML = `
      <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, var(--slate-900), var(--slate-800)); padding:1.5rem;">
        <div style="max-width:480px; width:100%; background:var(--white); border-radius:var(--radius-xl); box-shadow:var(--shadow-xl); padding:2.5rem; border:1px solid var(--border-color);">
          <div style="text-align:center; margin-bottom:1.75rem;">
            <div class="brand-icon" style="margin:0 auto 1rem auto; width:48px; height:48px; font-size:1.5rem;">⚡</div>
            <h2 style="font-size:1.5rem; font-weight:800; color:var(--slate-900);">Create Employee Account</h2>
            <p style="color:var(--slate-500); font-size:0.9rem;">Join the company workspace</p>
          </div>
          <form onsubmit="App.handleRegister(event)">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" class="form-control" name="first_name" required placeholder="John">
              </div>
              <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" class="form-control" name="last_name" required placeholder="Doe">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" class="form-control" name="username" required minlength="3" placeholder="johndoe">
            </div>
            <div class="form-group">
              <label class="form-label">Work Email</label>
              <input type="email" class="form-control" name="email" required placeholder="john.doe@company.com">
            </div>
            <div class="form-group">
              <label class="form-label">Password (min 8 chars)</label>
              <input type="password" class="form-control" name="password" required minlength="8" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem;">Complete Registration</button>
          </form>
          <div style="text-align:center; margin-top:1.5rem; font-size:0.88rem; color:var(--slate-500);">
            Already registered? <a href="javascript:void(0)" onclick="App.renderAuthView('login')" style="color:var(--slate-900); font-weight:600;">Sign in</a>
          </div>
        </div>
      </div>
    `;
  } else if (view === 'forgot') {
    root.innerHTML = `
      <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg, var(--slate-900), var(--slate-800)); padding:1.5rem;">
        <div style="max-width:440px; width:100%; background:var(--white); border-radius:var(--radius-xl); box-shadow:var(--shadow-xl); padding:2.5rem; border:1px solid var(--border-color);">
          <div style="text-align:center; margin-bottom:1.75rem;">
            <div class="brand-icon" style="margin:0 auto 1rem auto; width:48px; height:48px; font-size:1.5rem;">🔑</div>
            <h2 style="font-size:1.5rem; font-weight:800; color:var(--slate-900);">Reset Password</h2>
            <p style="color:var(--slate-500); font-size:0.9rem;">Enter your email to receive recovery instructions</p>
          </div>
          <form onsubmit="App.handleForgotPassword(event)">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" name="email" required placeholder="user@company.com">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.75rem;">Send Reset Link</button>
          </form>
          <div style="text-align:center; margin-top:1.5rem; font-size:0.88rem;">
            <a href="javascript:void(0)" onclick="App.renderAuthView('login')" style="color:var(--slate-600);">← Back to sign in</a>
          </div>
        </div>
      </div>
    `;
  }
};

App.fillDemo = function(username, password) {
  document.getElementById('loginUsername').value = username;
  document.getElementById('loginPassword').value = password;
  Toast.show(`Credentials loaded for ${username}`, 'info', 2000);
};

App.handleLogin = async function(e) {
  e.preventDefault();
  const btn = document.getElementById('loginSubmitBtn');
  btn.disabled = true;
  btn.textContent = 'Authenticating...';

  try {
    const login = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;

    const res = await API.post('api/auth/login.php', { login, password });
    Toast.show(res.message, 'success');
    await App.checkAuth();
  } catch (err) {
    btn.disabled = false;
    btn.textContent = 'Sign In to Portal';
  }
};

App.handleRegister = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/auth/register.php', data);
    Toast.show(res.message, 'success');
    App.renderAuthView('login');
  } catch (err) {}
};

App.handleForgotPassword = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/auth/forgot_password.php', data);
    Toast.show(res.message, 'info');
    App.renderAuthView('login');
  } catch (err) {}
};

/* ==========================================================================
   2. DASHBOARD VIEW
   ========================================================================== */
App.views.dashboard = async function() {
  const container = document.getElementById('pageContainer');
  container.innerHTML = `<div class="skeleton" style="height: 300px; width:100%;"></div>`;

  try {
    const res = await API.get('api/dashboard/stats.php');
    const stats = res.data;
    const role = App.state.role;
    const isPrivileged = ['super_admin', 'hr_admin'].includes(role);
    const isManager = role === 'manager';

    let statsHtml = '';
    if (isPrivileged) {
      statsHtml = `
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Total Workforce</span>
              <div class="stat-icon" style="background:#eff6ff; color:#2563eb;">👥</div>
            </div>
            <div class="stat-value">${stats.total_employees}</div>
            <div class="stat-subtext"><span style="color:var(--status-success); font-weight:600;">${stats.active_employees}</span> active full-time</div>
          </div>
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Today's Present</span>
              <div class="stat-icon" style="background:#ecfdf5; color:#059669;">⏱️</div>
            </div>
            <div class="stat-value">${stats.attendance_today.present + stats.attendance_today.late}</div>
            <div class="stat-subtext">${stats.attendance_today.late} late arrival(s)</div>
          </div>
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Pending Leave</span>
              <div class="stat-icon" style="background:#fffbeb; color:#d97706;">🏖️</div>
            </div>
            <div class="stat-value">${stats.pending_leave_count}</div>
            <div class="stat-subtext">Awaiting administrative approval</div>
          </div>
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Open Positions</span>
              <div class="stat-icon" style="background:#f5f3ff; color:#7c3aed;">🎯</div>
            </div>
            <div class="stat-value">${stats.open_jobs_count}</div>
            <div class="stat-subtext">Active hiring pipelines</div>
          </div>
        </div>
      `;
    } else if (isManager) {
      statsHtml = `
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Team Members</span><div class="stat-icon" style="background:#eff6ff; color:#2563eb;">👥</div></div>
            <div class="stat-value">${stats.team_size}</div>
            <div class="stat-subtext">Direct & Department reports</div>
          </div>
          <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Team Present Today</span><div class="stat-icon" style="background:#ecfdf5; color:#059669;">⏱️</div></div>
            <div class="stat-value">${stats.team_present_today}</div>
            <div class="stat-subtext">Checked in today</div>
          </div>
          <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Pending Team Leave</span><div class="stat-icon" style="background:#fffbeb; color:#d97706;">🏖️</div></div>
            <div class="stat-value">${stats.pending_team_leave}</div>
            <div class="stat-subtext">Awaiting your review</div>
          </div>
          <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Active Team Goals</span><div class="stat-icon" style="background:#f5f3ff; color:#7c3aed;">⭐</div></div>
            <div class="stat-value">${stats.team_goals_active}</div>
            <div class="stat-subtext">Performance OKRs in progress</div>
          </div>
        </div>
      `;
    } else {
      // Employee Self-Service Dashboard
      const myAtt = stats.my_attendance_today;
      statsHtml = `
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Today's Attendance</span><div class="stat-icon" style="background:#ecfdf5; color:#059669;">⏱️</div></div>
            <div class="stat-value" style="font-size:1.35rem;">${myAtt ? (myAtt.check_out ? 'Completed Day' : `Checked in at ${myAtt.check_in}`) : 'Not Checked In'}</div>
            <div class="stat-subtext">${myAtt ? `Status: ${myAtt.status.toUpperCase()}` : 'Please punch in below'}</div>
          </div>
          <div class="stat-card">
            <div class="stat-header"><span class="stat-label">My Leave Requests</span><div class="stat-icon" style="background:#fffbeb; color:#d97706;">🏖️</div></div>
            <div class="stat-value">${stats.my_pending_leaves}</div>
            <div class="stat-subtext">Pending approval</div>
          </div>
          <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Active Goals</span><div class="stat-icon" style="background:#eff6ff; color:#2563eb;">⭐</div></div>
            <div class="stat-value">${stats.my_active_goals}</div>
            <div class="stat-subtext">Key milestones in progress</div>
          </div>
          <div class="stat-card">
            <div class="stat-header"><span class="stat-label">Latest Salary Slip</span><div class="stat-icon" style="background:#f5f3ff; color:#7c3aed;">💰</div></div>
            <div class="stat-value" style="font-size:1.5rem;">${stats.latest_payslip ? '$' + Number(stats.latest_payslip.net_salary).toLocaleString() : 'N/A'}</div>
            <div class="stat-subtext">${stats.latest_payslip ? `${stats.latest_payslip.payment_status.toUpperCase()}` : 'No slips issued yet'}</div>
          </div>
        </div>
      `;
    }

    container.innerHTML = `
      <div class="page-header">
        <div>
          <h1 class="page-title">Workforce Dashboard</h1>
          <p class="page-subtitle">Welcome back, ${App.state.employee ? App.state.employee.first_name : App.state.user.username}. Here is what's happening today.</p>
        </div>
        <div style="display:flex; gap:0.75rem;">
          <button class="btn btn-primary" onclick="App.openPunchModal()">
            ⏱️ Quick Punch In / Out
          </button>
        </div>
      </div>

      ${statsHtml}

      <!-- Interactive Middle Grid: Charts & Activity -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
        <!-- Left Box: Analytics / Attendance Overview -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Attendance & Workforce Overview</h3>
            <span class="badge badge-success">Live Status</span>
          </div>
          <div class="card-body">
            <div style="height: 210px; display:flex; align-items:flex-end; gap:1.25rem; padding-top:1rem; border-bottom:1px solid var(--border-color);" id="attendanceBars">
              ${(stats.attendance_trend || [
                {date: '09-11', present_count: 7},
                {date: '09-12', present_count: 6},
                {date: '09-15', present_count: 8},
                {date: '09-16', present_count: 7},
                {date: '09-17', present_count: 8}
              ]).map(item => `
                <div style="flex:1; display:flex; flex-direction:column; align-items:center; gap:0.5rem; height:100%; justify-content:flex-end;">
                  <span style="font-size:0.75rem; font-weight:700; color:var(--slate-800);">${item.present_count}</span>
                  <div style="width:100%; max-width:38px; height:${Math.min(100, item.present_count * 12)}%; background:linear-gradient(180deg, var(--slate-800), var(--slate-900)); border-radius:6px 6px 0 0; transition:height 0.3s ease;"></div>
                  <span style="font-size:0.72rem; color:var(--slate-500);">${item.date.slice(-5)}</span>
                </div>
              `).join('')}
            </div>
            <div style="display:flex; justify-content:space-between; margin-top:1rem; font-size:0.85rem; color:var(--slate-600);">
              <span>Daily present check-ins trend</span>
              <span style="font-weight:600; color:var(--slate-900);">Standard: 8.0 hrs/day</span>
            </div>
          </div>
        </div>

        <!-- Right Box: Department Distribution or Team Roster -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Department Distribution</h3>
            <span class="badge badge-neutral">${(stats.department_distribution || []).length} Depts</span>
          </div>
          <div class="card-body">
            <div style="display:flex; flex-direction:column; gap:0.85rem;">
              ${(stats.department_distribution || [
                {name: 'Engineering & Tech', employee_count: 3},
                {name: 'Human Resources', employee_count: 2},
                {name: 'Finance & Accounts', employee_count: 1},
                {name: 'Marketing & Growth', employee_count: 1},
                {name: 'Operations & Legal', employee_count: 1}
              ]).map(dept => `
                <div>
                  <div style="display:flex; justify-content:space-between; font-size:0.88rem; font-weight:600; margin-bottom:0.25rem;">
                    <span>${dept.name}</span>
                    <span style="color:var(--slate-500);">${dept.employee_count} members</span>
                  </div>
                  <div style="height:6px; background:var(--slate-100); border-radius:var(--radius-full); overflow:hidden;">
                    <div style="height:100%; width:${Math.min(100, dept.employee_count * 25)}%; background:var(--slate-800); border-radius:var(--radius-full);"></div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Grid: Announcements & Upcoming Holidays -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
        <!-- Announcements -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">📢 Company Announcements</h3>
            <a href="javascript:void(0)" onclick="App.navigate('announcements')" style="font-size:0.82rem; font-weight:600; color:var(--brand-accent);">View All →</a>
          </div>
          <div class="card-body" style="padding:0.75rem 1.25rem;">
            ${(stats.announcements || []).map(a => `
              <div style="padding:0.85rem 0; border-bottom:1px solid var(--border-color);">
                <div style="font-weight:600; font-size:0.92rem; color:var(--slate-900);">${a.title}</div>
                <div style="font-size:0.85rem; color:var(--slate-600); margin-top:0.25rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">${a.content}</div>
                <div style="font-size:0.75rem; color:var(--slate-400); margin-top:0.4rem;">Posted by ${a.author_name} • ${a.created_at.slice(0, 10)}</div>
              </div>
            `).join('')}
          </div>
        </div>

        <!-- Upcoming Holidays & Events -->
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">🎉 Upcoming Holidays & Events</h3>
            <a href="javascript:void(0)" onclick="App.navigate('calendar')" style="font-size:0.82rem; font-weight:600; color:var(--brand-accent);">Calendar →</a>
          </div>
          <div class="card-body" style="padding:0.75rem 1.25rem;">
            ${(stats.upcoming_holidays || []).map(h => `
              <div style="display:flex; align-items:center; justify-content:space-between; padding:0.75rem 0; border-bottom:1px solid var(--border-color);">
                <div>
                  <div style="font-weight:600; font-size:0.9rem; color:var(--slate-900);">${h.name}</div>
                  <div style="font-size:0.8rem; color:var(--slate-500);">${h.description || 'Public Holiday'}</div>
                </div>
                <span class="badge badge-neutral">${h.date}</span>
              </div>
            `).join('')}
          </div>
        </div>
      </div>
    `;
  } catch (err) {
    container.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><div class="empty-title">Error Loading Dashboard</div><p class="empty-text">${err.message}</p></div>`;
  }
};

/* Quick Punch In/Out Modal */
App.openPunchModal = async function() {
  try {
    const res = await API.get('api/attendance/today.php');
    const d = res.data;
    const isCheckedIn = d.is_checked_in;
    const isCheckedOut = d.is_checked_out;

    let statusText = 'Not Punched In Today';
    let actionBtn = `<button class="btn btn-success" onclick="App.executePunchIn()">✅ Punch In Now</button>`;

    if (isCheckedIn) {
      statusText = `Currently Working (Punched In at ${d.attendance.check_in})`;
      actionBtn = `<button class="btn btn-danger" onclick="App.executePunchOut()">🛑 Punch Out (End Shift)</button>`;
    } else if (isCheckedOut) {
      statusText = `Shift Completed (Punched Out at ${d.attendance.check_out})`;
      actionBtn = `<span class="badge badge-success" style="padding:0.5rem 1rem; font-size:0.85rem;">Completed for today</span>`;
    }

    App.showModal('⏱️ Attendance Punch Clock', `
      <div style="text-align:center; padding:1rem 0;">
        <div style="font-size:2.5rem; font-weight:800; color:var(--slate-900); font-variant-numeric:tabular-nums;" id="punchLiveClock">
          ${new Date().toLocaleTimeString()}
        </div>
        <div style="color:var(--slate-500); font-size:0.9rem; margin-top:0.25rem;">${new Date().toDateString()}</div>
        <div style="margin:1.5rem 0; padding:1rem; background:var(--slate-50); border-radius:var(--radius-md); border:1px solid var(--border-color);">
          <div style="font-size:0.8rem; text-transform:uppercase; color:var(--slate-400); font-weight:700;">Status</div>
          <div style="font-weight:700; font-size:1.05rem; color:var(--slate-800); margin-top:0.25rem;">${statusText}</div>
        </div>
        ${!isCheckedIn && !isCheckedOut ? `
          <div class="form-group" style="text-align:left;">
            <label class="form-label">Note (Optional)</label>
            <input type="text" class="form-control" id="punchNote" placeholder="e.g. Working from headquarters">
          </div>
        ` : ''}
      </div>
    `, `
      <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Close</button>
      ${actionBtn}
    `);
  } catch (e) {}
};

App.executePunchIn = async function() {
  const note = document.getElementById('punchNote')?.value || '';
  try {
    const res = await API.post('api/attendance/checkin.php', { notes: note });
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.dashboard();
  } catch (e) {}
};

App.executePunchOut = async function() {
  try {
    const res = await API.post('api/attendance/checkout.php', {});
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.dashboard();
  } catch (e) {}
};

/* ==========================================================================
   3. EMPLOYEES MANAGEMENT VIEW
   ========================================================================== */
App.views.employees = async function(page = 1) {
  const container = document.getElementById('pageContainer');
  const role = App.state.role;
  const isPrivileged = ['super_admin', 'hr_admin'].includes(role);

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Employee Directory</h1>
        <p class="page-subtitle">Manage organization staff, contracts, hierarchy, and roles.</p>
      </div>
      <div style="display:flex; gap:0.75rem;">
        ${isPrivileged ? `
          <button class="btn btn-primary" onclick="App.views.openAddEmployeeModal()">
            ➕ Add New Employee
          </button>
        ` : ''}
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-body" style="padding:1rem 1.5rem;">
        <div style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
          <div style="flex:1; min-width:220px;">
            <input type="text" class="form-control" id="empSearchInput" placeholder="Search by name, email, employee code..." onkeydown="if(event.key==='Enter') App.views.filterEmployees()">
          </div>
          <div style="min-width:180px;">
            <select class="form-control" id="empDeptFilter" onchange="App.views.filterEmployees()">
              <option value="">All Departments</option>
            </select>
          </div>
          <div style="min-width:160px;">
            <select class="form-control" id="empStatusFilter" onchange="App.views.filterEmployees()">
              <option value="">All Statuses</option>
              <option value="active" selected>Active</option>
              <option value="on_leave">On Leave</option>
              <option value="probation">Probation</option>
              <option value="terminated">Terminated</option>
            </select>
          </div>
          <button class="btn btn-secondary" onclick="App.views.filterEmployees()">Filter</button>
        </div>
      </div>
    </div>

    <!-- Table Card -->
    <div class="card">
      <div class="table-responsive" id="employeesTableWrapper">
        <div class="skeleton" style="height: 300px; width: 100%;"></div>
      </div>
    </div>
  `;

  // Populate department filter
  try {
    const deptRes = await API.get('api/departments/list.php');
    App.state.departments = deptRes.data;
    const select = document.getElementById('empDeptFilter');
    if (select) {
      deptRes.data.forEach(d => {
        select.innerHTML += `<option value="${d.id}">${d.name}</option>`;
      });
    }
  } catch (e) {}

  await App.views.loadEmployeesTable(page);
};

App.views.filterEmployees = function() {
  App.views.loadEmployeesTable(1);
};

App.views.loadEmployeesTable = async function(page = 1) {
  const wrapper = document.getElementById('employeesTableWrapper');
  if (!wrapper) return;

  const search = document.getElementById('empSearchInput')?.value || '';
  const deptId = document.getElementById('empDeptFilter')?.value || '';
  const status = document.getElementById('empStatusFilter')?.value || '';
  const isPrivileged = ['super_admin', 'hr_admin'].includes(App.state.role);

  try {
    const res = await API.get('api/employees/list.php', {
      page,
      limit: 15,
      q: search,
      department_id: deptId,
      status: status
    });

    const employees = res.data;
    const meta = res.meta;

    if (!employees.length) {
      wrapper.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">👥</div>
          <div class="empty-title">No employees found</div>
          <p class="empty-text">No employee records match the given criteria. Try resetting your search or filters.</p>
        </div>
      `;
      return;
    }

    wrapper.innerHTML = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>ID Code</th>
            <th>Department</th>
            <th>Designation</th>
            <th>Status</th>
            <th>Joined Date</th>
            ${isPrivileged ? '<th>Basic Salary</th>' : ''}
            <th style="text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          ${employees.map(e => `
            <tr>
              <td>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                  <div class="user-avatar" style="width:34px; height:34px; font-size:0.85rem;">
                    ${e.first_name[0]}${e.last_name[0]}
                  </div>
                  <div>
                    <div style="font-weight:600; color:var(--slate-900);">${e.first_name} ${e.last_name}</div>
                    <div style="font-size:0.78rem; color:var(--slate-500);">${e.email}</div>
                  </div>
                </div>
              </td>
              <td><span style="font-family:monospace; font-weight:600;">${e.employee_code}</span></td>
              <td>${e.department_name || '—'}</td>
              <td>${e.designation_title || '—'}</td>
              <td>
                <span class="badge ${e.employment_status === 'active' ? 'badge-success' : e.employment_status === 'on_leave' ? 'badge-warning' : 'badge-danger'}">
                  ${e.employment_status}
                </span>
              </td>
              <td>${e.joining_date}</td>
              ${isPrivileged ? `<td><strong>$${Number(e.basic_salary).toLocaleString()}</strong></td>` : ''}
              <td style="text-align:right;">
                <button class="btn btn-secondary btn-sm" onclick="App.views.openEmployeeDrawer(${e.id})">View Profile</button>
                ${isPrivileged ? `
                  <button class="btn btn-secondary btn-sm" onclick="App.views.openEditEmployeeModal(${e.id})" title="Edit">✏️</button>
                ` : ''}
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
      <div style="padding:1rem 1.5rem; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-color);">
        <span style="font-size:0.85rem; color:var(--slate-500);">Showing page ${meta.page} of ${meta.total_pages} (${meta.total} total)</span>
        <div style="display:flex; gap:0.5rem;">
          <button class="btn btn-secondary btn-sm" ${meta.page <= 1 ? 'disabled' : ''} onclick="App.views.loadEmployeesTable(${meta.page - 1})">Previous</button>
          <button class="btn btn-secondary btn-sm" ${meta.page >= meta.total_pages ? 'disabled' : ''} onclick="App.views.loadEmployeesTable(${meta.page + 1})">Next</button>
        </div>
      </div>
    `;
  } catch (err) {
    wrapper.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><p class="empty-text">${err.message}</p></div>`;
  }
};

/* Detailed Profile Drawer Modal with Sub-Tabs */
App.views.openEmployeeDrawer = async function(id) {
  try {
    const res = await API.get('api/employees/view.php', { id });
    const { employee, attendance, leave_balances, leave_requests, payroll, documents, goals, reviews } = res.data;

    const bodyHtml = `
      <div style="display:flex; gap:1.25rem; align-items:center; padding-bottom:1.5rem; border-bottom:1px solid var(--border-color); margin-bottom:1.25rem;">
        <div class="user-avatar" style="width:60px; height:60px; font-size:1.5rem;">
          ${employee.first_name[0]}${employee.last_name[0]}
        </div>
        <div>
          <h2 style="font-size:1.35rem; font-weight:800; color:var(--slate-900);">${employee.first_name} ${employee.last_name}</h2>
          <div style="font-size:0.88rem; color:var(--slate-500); margin-top:0.15rem;">
            ${employee.designation_title || 'Staff'} • ${employee.department_name || 'General'} • <span style="font-family:monospace; font-weight:600;">${employee.employee_code}</span>
          </div>
          <div style="display:flex; gap:0.5rem; margin-top:0.5rem;">
            <span class="badge ${employee.employment_status === 'active' ? 'badge-success' : 'badge-neutral'}">${employee.employment_status}</span>
            <span class="badge badge-info">${employee.employment_type}</span>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="tabs-nav" id="empProfileTabs">
        <button class="tab-btn active" onclick="App.views.switchProfileTab('overview', this)">Overview</button>
        <button class="tab-btn" onclick="App.views.switchProfileTab('attendance', this)">Attendance</button>
        <button class="tab-btn" onclick="App.views.switchProfileTab('leave', this)">Leave & Balances</button>
        ${payroll ? `<button class="tab-btn" onclick="App.views.switchProfileTab('payroll', this)">Payroll</button>` : ''}
        <button class="tab-btn" onclick="App.views.switchProfileTab('docs', this)">Documents</button>
        <button class="tab-btn" onclick="App.views.switchProfileTab('performance', this)">Performance</button>
      </div>

      <!-- Tab Content: Overview -->
      <div id="tab-overview" class="tab-pane">
        <div class="form-row" style="margin-bottom:1rem;">
          <div><span style="font-size:0.75rem; color:var(--slate-400); text-transform:uppercase; font-weight:700;">Email</span><div style="font-weight:600; color:var(--slate-900);">${employee.email}</div></div>
          <div><span style="font-size:0.75rem; color:var(--slate-400); text-transform:uppercase; font-weight:700;">Phone</span><div style="font-weight:600; color:var(--slate-900);">${employee.phone || '—'}</div></div>
          <div><span style="font-size:0.75rem; color:var(--slate-400); text-transform:uppercase; font-weight:700;">Manager</span><div style="font-weight:600; color:var(--slate-900);">${employee.manager_first_name ? `${employee.manager_first_name} ${employee.manager_last_name}` : 'Executive Lead'}</div></div>
        </div>
        <div class="form-row" style="margin-bottom:1rem;">
          <div><span style="font-size:0.75rem; color:var(--slate-400); text-transform:uppercase; font-weight:700;">Joining Date</span><div style="font-weight:600; color:var(--slate-900);">${employee.joining_date}</div></div>
          <div><span style="font-size:0.75rem; color:var(--slate-400); text-transform:uppercase; font-weight:700;">Date of Birth</span><div style="font-weight:600; color:var(--slate-900);">${employee.date_of_birth || '—'}</div></div>
          <div><span style="font-size:0.75rem; color:var(--slate-400); text-transform:uppercase; font-weight:700;">Gender</span><div style="font-weight:600; color:var(--slate-900); text-transform:capitalize;">${employee.gender}</div></div>
        </div>
        <div style="margin-bottom:1rem;"><span style="font-size:0.75rem; color:var(--slate-400); text-transform:uppercase; font-weight:700;">Address</span><div style="font-weight:500; color:var(--slate-800);">${employee.address || '—'}</div></div>
        <div><span style="font-size:0.75rem; color:var(--slate-400); text-transform:uppercase; font-weight:700;">Emergency Contact</span><div style="font-weight:500; color:var(--slate-800);">${employee.emergency_contact || '—'}</div></div>
      </div>

      <!-- Tab Content: Attendance -->
      <div id="tab-attendance" class="tab-pane" style="display:none;">
        <table class="data-table" style="font-size:0.85rem;">
          <thead>
            <tr><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Overtime</th><th>Status</th></tr>
          </thead>
          <tbody>
            ${attendance.map(a => `
              <tr>
                <td><strong>${a.date}</strong></td>
                <td>${a.check_in || '—'}</td>
                <td>${a.check_out || '—'}</td>
                <td>${a.working_hours} hrs</td>
                <td>${a.overtime_hours} hrs</td>
                <td><span class="badge ${a.status === 'present' ? 'badge-success' : 'badge-warning'}">${a.status}</span></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>

      <!-- Tab Content: Leave -->
      <div id="tab-leave" class="tab-pane" style="display:none;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:0.75rem; margin-bottom:1.5rem;">
          ${leave_balances.map(b => `
            <div style="background:var(--slate-50); border:1px solid var(--border-color); border-radius:8px; padding:0.85rem; text-align:center;">
              <div style="font-size:0.75rem; font-weight:700; color:var(--slate-500); text-transform:uppercase;">${b.leave_code}</div>
              <div style="font-size:1.4rem; font-weight:800; color:var(--slate-900); margin:0.25rem 0;">${b.total_days - b.used_days - b.pending_days}</div>
              <div style="font-size:0.72rem; color:var(--slate-400);">${b.used_days} used / ${b.total_days} total</div>
            </div>
          `).join('')}
        </div>
        <h4 style="font-size:0.9rem; font-weight:700; color:var(--slate-800); margin-bottom:0.75rem;">Recent Requests</h4>
        <table class="data-table" style="font-size:0.85rem;">
          <thead><tr><th>Period</th><th>Type</th><th>Days</th><th>Status</th></tr></thead>
          <tbody>
            ${leave_requests.map(r => `
              <tr>
                <td>${r.start_date} to ${r.end_date}</td>
                <td>${r.leave_name}</td>
                <td>${r.total_days}</td>
                <td><span class="badge ${r.status === 'approved' ? 'badge-success' : r.status === 'pending' ? 'badge-warning' : 'badge-danger'}">${r.status}</span></td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>

      <!-- Tab Content: Payroll -->
      ${payroll ? `
        <div id="tab-payroll" class="tab-pane" style="display:none;">
          <table class="data-table" style="font-size:0.85rem;">
            <thead><tr><th>Period</th><th>Basic</th><th>Allowances</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
              ${payroll.map(p => `
                <tr>
                  <td><strong>${p.month}/${p.year}</strong></td>
                  <td>$${Number(p.basic_salary).toLocaleString()}</td>
                  <td>+$${Number(p.total_allowances).toLocaleString()}</td>
                  <td>-$${Number(p.total_deductions).toLocaleString()}</td>
                  <td><strong>$${Number(p.net_salary).toLocaleString()}</strong></td>
                  <td><span class="badge badge-success">${p.payment_status}</span></td>
                  <td><button class="btn btn-secondary btn-sm" onclick="App.views.viewPayslip(${p.id})">Payslip</button></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      ` : ''}

      <!-- Tab Content: Documents -->
      <div id="tab-docs" class="tab-pane" style="display:none;">
        <div style="display:flex; flex-direction:column; gap:0.6rem;">
          ${documents.length ? documents.map(d => `
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem; background:var(--slate-50); border:1px solid var(--border-color); border-radius:8px;">
              <div>
                <div style="font-weight:600; font-size:0.88rem; color:var(--slate-900);">📄 ${d.title}</div>
                <div style="font-size:0.75rem; color:var(--slate-400);">${(d.file_size/1024).toFixed(1)} KB • ${d.category}</div>
              </div>
              <a href="api/documents/download.php?id=${d.id}" target="_blank" class="btn btn-secondary btn-sm">Preview / Download</a>
            </div>
          `).join('') : '<p style="color:var(--slate-400); font-size:0.85rem;">No documents uploaded yet.</p>'}
        </div>
      </div>

      <!-- Tab Content: Performance -->
      <div id="tab-performance" class="tab-pane" style="display:none;">
        <h4 style="font-size:0.9rem; font-weight:700; color:var(--slate-800); margin-bottom:0.75rem;">Active Goals</h4>
        <div style="display:flex; flex-direction:column; gap:0.75rem; margin-bottom:1.5rem;">
          ${goals.map(g => `
            <div style="padding:0.75rem; background:var(--slate-50); border-radius:8px; border:1px solid var(--border-color);">
              <div style="display:flex; justify-content:space-between; font-size:0.88rem; font-weight:600; color:var(--slate-900);">
                <span>${g.title}</span>
                <span>${g.progress}%</span>
              </div>
              <div style="height:6px; background:var(--slate-200); border-radius:999px; margin-top:0.4rem; overflow:hidden;">
                <div style="height:100%; width:${g.progress}%; background:var(--slate-800);"></div>
              </div>
            </div>
          `).join('')}
        </div>
        <h4 style="font-size:0.9rem; font-weight:700; color:var(--slate-800); margin-bottom:0.75rem;">Appraisals & Reviews</h4>
        ${reviews.map(r => `
          <div style="padding:0.85rem; border:1px solid var(--border-color); border-radius:8px; margin-bottom:0.75rem;">
            <div style="display:flex; justify-content:space-between; font-weight:600; font-size:0.9rem;">
              <span>${r.review_period}</span>
              <span class="badge badge-success">⭐ ${r.rating} / 5.0</span>
            </div>
            <p style="font-size:0.85rem; color:var(--slate-600); margin-top:0.35rem;"><strong>Manager Feedback:</strong> ${r.manager_feedback}</p>
          </div>
        `).join('')}
      </div>
    `;

    App.showModal(`Employee Profile — ${employee.employee_code}`, bodyHtml, `
      <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Close</button>
    `, 'lg');

  } catch (err) {
    Toast.show(err.message, 'error');
  }
};

App.views.switchProfileTab = function(tabName, btn) {
  document.querySelectorAll('#empProfileTabs .tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
  const target = document.getElementById(`tab-${tabName}`);
  if (target) target.style.display = 'block';
};

/* Add Employee Modal */
App.views.openAddEmployeeModal = async function() {
  const depts = App.state.departments;
  let deptOptions = depts.map(d => `<option value="${d.id}">${d.name}</option>`).join('');

  let desigOptions = '';
  try {
    const desigRes = await API.get('api/designations/list.php');
    desigOptions = desigRes.data.map(des => `<option value="${des.id}">${des.title} (${des.department_name})</option>`).join('');
  } catch (e) {}

  App.showModal('➕ Add New Employee', `
    <form id="addEmployeeForm" onsubmit="App.views.submitAddEmployee(event)">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input type="text" class="form-control" name="first_name" required placeholder="Alexander">
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input type="text" class="form-control" name="last_name" required placeholder="Pierce">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Corporate Email *</label>
          <input type="email" class="form-control" name="email" required placeholder="alex.p@company.com">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" class="form-control" name="phone" placeholder="+1 (555) 019-2831">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Department *</label>
          <select class="form-control" name="department_id" required>${deptOptions}</select>
        </div>
        <div class="form-group">
          <label class="form-label">Designation *</label>
          <select class="form-control" name="designation_id" required>${desigOptions}</select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Joining Date *</label>
          <input type="date" class="form-control" name="joining_date" required value="${new Date().toISOString().slice(0, 10)}">
        </div>
        <div class="form-group">
          <label class="form-label">Employment Type</label>
          <select class="form-control" name="employment_type">
            <option value="full_time">Full-Time</option>
            <option value="part_time">Part-Time</option>
            <option value="contract">Contract</option>
            <option value="intern">Intern</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Basic Monthly Salary ($) *</label>
          <input type="number" step="100" class="form-control" name="basic_salary" required placeholder="7500.00" value="6500">
        </div>
        <div class="form-group">
          <label class="form-label">System Role Access</label>
          <select class="form-control" name="role_id">
            <option value="4" selected>Standard Employee</option>
            <option value="3">Manager</option>
            <option value="2">HR Admin</option>
            <option value="1">Super Admin</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Bank Name</label>
          <input type="text" class="form-control" name="bank_name" placeholder="JPMorgan Chase">
        </div>
        <div class="form-group">
          <label class="form-label">Bank Account Number</label>
          <input type="text" class="form-control" name="bank_account_no" placeholder="CHAS9028192837">
        </div>
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="addEmployeeForm" type="submit">Create Employee</button>
  `, 'lg');
};

App.views.submitAddEmployee = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/employees/create.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.loadEmployeesTable();
  } catch (err) {}
};

/* Edit Employee Modal */
App.views.openEditEmployeeModal = async function(id) {
  try {
    const res = await API.get('api/employees/view.php', { id });
    const emp = res.data.employee;

    const depts = App.state.departments;
    const deptOptions = depts.map(d => `<option value="${d.id}" ${d.id === emp.department_id ? 'selected' : ''}>${d.name}</option>`).join('');

    App.showModal(`Edit Employee — ${emp.employee_code}`, `
      <form id="editEmployeeForm" onsubmit="App.views.submitEditEmployee(event, ${emp.id})">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">First Name</label>
            <input type="text" class="form-control" name="first_name" required value="${emp.first_name}">
          </div>
          <div class="form-group">
            <label class="form-label">Last Name</label>
            <input type="text" class="form-control" name="last_name" required value="${emp.last_name}">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" value="${emp.phone || ''}">
          </div>
          <div class="form-group">
            <label class="form-label">Department</label>
            <select class="form-control" name="department_id">${deptOptions}</select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Basic Salary ($)</label>
            <input type="number" step="50" class="form-control" name="basic_salary" value="${emp.basic_salary || 0}">
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select class="form-control" name="employment_status">
              <option value="active" ${emp.employment_status === 'active' ? 'selected' : ''}>Active</option>
              <option value="on_leave" ${emp.employment_status === 'on_leave' ? 'selected' : ''}>On Leave</option>
              <option value="probation" ${emp.employment_status === 'probation' ? 'selected' : ''}>Probation</option>
              <option value="terminated" ${emp.employment_status === 'terminated' ? 'selected' : ''}>Terminated</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Home Address</label>
          <input type="text" class="form-control" name="address" value="${emp.address || ''}">
        </div>
        <div class="form-group">
          <label class="form-label">Emergency Contact</label>
          <input type="text" class="form-control" name="emergency_contact" value="${emp.emergency_contact || ''}">
        </div>
      </form>
    `, `
      <button class="btn btn-danger btn-sm" onclick="App.views.confirmDeactivateEmployee(${emp.id}, '${emp.first_name} ${emp.last_name}')" style="margin-right:auto;">Deactivate</button>
      <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
      <button class="btn btn-primary btn-sm" form="editEmployeeForm" type="submit">Save Changes</button>
    `, 'lg');
  } catch (err) {}
};

App.views.submitEditEmployee = async function(e, id) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  data.id = id;
  try {
    const res = await API.post('api/employees/update.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.loadEmployeesTable();
  } catch (err) {}
};

App.views.confirmDeactivateEmployee = function(id, name) {
  if (confirm(`Are you sure you want to deactivate employee ${name}? This will suspend their user account and terminate their active status.`)) {
    API.post('api/employees/delete.php', { id }).then(res => {
      Toast.show(res.message, 'info');
      App.closeAllModals();
      App.views.loadEmployeesTable();
    });
  }
};
