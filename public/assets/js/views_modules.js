/**
 * Apex Global HRMS — Module Views (Attendance, Leave, Payroll, Recruitment, etc.)
 */

/* ==========================================================================
   4. ATTENDANCE VIEW
   ========================================================================== */
App.views.attendance = async function() {
  const container = document.getElementById('pageContainer');
  const month = new Date().getMonth() + 1;
  const year = new Date().getFullYear();

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Attendance Tracking</h1>
        <p class="page-subtitle">Monitor punch clocks, work hours, overtime, and shift compliance.</p>
      </div>
      <div>
        <button class="btn btn-primary" onclick="App.openPunchModal()">
          ⏱️ Punch In / Out
        </button>
      </div>
    </div>

    <!-- Attendance Filters -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-body" style="padding:1rem 1.5rem;">
        <div style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
          <div>
            <label class="form-label" style="margin-bottom:0.2rem; font-size:0.75rem;">Month</label>
            <select class="form-control" id="attMonthSelect" onchange="App.views.loadAttendanceTable()">
              ${[1,2,3,4,5,6,7,8,9,10,11,12].map(m => `
                <option value="${m}" ${m === month ? 'selected' : ''}>${new Date(2000, m-1).toLocaleString('default', {month: 'long'})}</option>
              `).join('')}
            </select>
          </div>
          <div>
            <label class="form-label" style="margin-bottom:0.2rem; font-size:0.75rem;">Year</label>
            <select class="form-control" id="attYearSelect" onchange="App.views.loadAttendanceTable()">
              <option value="${year}" selected>${year}</option>
              <option value="${year-1}">${year-1}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="card">
      <div class="table-responsive" id="attendanceTableWrapper">
        <div class="skeleton" style="height:300px; width:100%;"></div>
      </div>
    </div>
  `;

  await App.views.loadAttendanceTable();
};

App.views.loadAttendanceTable = async function() {
  const wrapper = document.getElementById('attendanceTableWrapper');
  if (!wrapper) return;

  const month = document.getElementById('attMonthSelect')?.value || (new Date().getMonth() + 1);
  const year = document.getElementById('attYearSelect')?.value || new Date().getFullYear();

  try {
    const res = await API.get('api/attendance/list.php', { month, year });
    const records = res.data;

    if (!records.length) {
      wrapper.innerHTML = `<div class="empty-state"><div class="empty-icon">⏱️</div><div class="empty-title">No attendance records</div><p class="empty-text">No attendance records found for this period.</p></div>`;
      return;
    }

    wrapper.innerHTML = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Employee</th>
            <th>Department</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Working Hours</th>
            <th>Overtime</th>
            <th>Status</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          ${records.map(a => `
            <tr>
              <td><strong>${a.date}</strong></td>
              <td>
                <div style="font-weight:600; color:var(--slate-900);">${a.first_name} ${a.last_name}</div>
                <div style="font-size:0.78rem; color:var(--slate-400);">${a.employee_code}</div>
              </td>
              <td>${a.department_name || '—'}</td>
              <td>${a.check_in || '—'}</td>
              <td>${a.check_out || '—'}</td>
              <td><strong>${a.working_hours} hrs</strong></td>
              <td>${a.overtime_hours > 0 ? `<span style="color:var(--status-success); font-weight:600;">+${a.overtime_hours} hrs</span>` : '—'}</td>
              <td>
                <span class="badge ${a.status === 'present' ? 'badge-success' : a.status === 'late' ? 'badge-warning' : 'badge-danger'}">
                  ${a.status}
                </span>
              </td>
              <td style="font-size:0.82rem; color:var(--slate-500);">${a.notes || '—'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (err) {
    wrapper.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><p class="empty-text">${err.message}</p></div>`;
  }
};

/* ==========================================================================
   5. LEAVE MANAGEMENT VIEW
   ========================================================================== */
App.views.leave = async function() {
  const container = document.getElementById('pageContainer');
  const role = App.state.role;
  const isManagerOrHR = ['super_admin', 'hr_admin', 'manager'].includes(role);

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Leave Management</h1>
        <p class="page-subtitle">Apply for time off, view balances, and approve team leave requests.</p>
      </div>
      <div>
        <button class="btn btn-primary" onclick="App.views.openApplyLeaveModal()">
          🏖️ Apply for Leave
        </button>
      </div>
    </div>

    <!-- Balances Cards Container -->
    <div id="leaveBalancesContainer" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">
      <div class="skeleton" style="height:110px;"></div>
    </div>

    <!-- Requests Table -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">${isManagerOrHR ? 'Leave Applications & Approvals' : 'My Leave History'}</h3>
        <select class="form-control" id="leaveStatusFilter" style="width:160px;" onchange="App.views.loadLeaveTable()">
          <option value="">All Statuses</option>
          <option value="pending" selected>Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
      <div class="table-responsive" id="leaveTableWrapper">
        <div class="skeleton" style="height:250px;"></div>
      </div>
    </div>
  `;

  // Load balances
  try {
    const bRes = await API.get('api/leave/balances.php');
    const { balances, leave_types } = bRes.data;
    App.state.leaveTypes = leave_types;

    const bCont = document.getElementById('leaveBalancesContainer');
    if (balances.length) {
      bCont.innerHTML = balances.map(b => {
        const available = b.total_days - b.used_days - b.pending_days;
        return `
          <div class="stat-card" style="padding:1.25rem;">
            <div style="font-size:0.75rem; font-weight:700; color:var(--slate-500); text-transform:uppercase;">${b.leave_type_name}</div>
            <div style="font-size:1.8rem; font-weight:800; color:var(--slate-900); margin:0.35rem 0;">${available} <span style="font-size:0.85rem; font-weight:500; color:var(--slate-400);">days left</span></div>
            <div style="font-size:0.78rem; color:var(--slate-500);">${b.used_days} taken • ${b.pending_days} pending</div>
          </div>
        `;
      }).join('');
    } else {
      bCont.innerHTML = `<div class="card" style="padding:1rem;"><p style="font-size:0.85rem; color:var(--slate-500);">Standard company leave balances active.</p></div>`;
    }
  } catch (e) {}

  await App.views.loadLeaveTable();
};

App.views.loadLeaveTable = async function() {
  const wrapper = document.getElementById('leaveTableWrapper');
  if (!wrapper) return;

  const status = document.getElementById('leaveStatusFilter')?.value || '';
  const role = App.state.role;
  const isApprover = ['super_admin', 'hr_admin', 'manager'].includes(role);

  try {
    const res = await API.get('api/leave/list.php', { status });
    const requests = res.data;

    if (!requests.length) {
      wrapper.innerHTML = `<div class="empty-state"><div class="empty-icon">🏖️</div><div class="empty-title">No leave requests</div><p class="empty-text">No leave requests match the selected criteria.</p></div>`;
      return;
    }

    wrapper.innerHTML = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Leave Type</th>
            <th>Dates</th>
            <th>Days</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Reviewed By</th>
            ${isApprover ? '<th style="text-align:right;">Action</th>' : ''}
          </tr>
        </thead>
        <tbody>
          ${requests.map(r => `
            <tr>
              <td>
                <div style="font-weight:600; color:var(--slate-900);">${r.first_name} ${r.last_name}</div>
                <div style="font-size:0.78rem; color:var(--slate-400);">${r.employee_code} • ${r.department_name || ''}</div>
              </td>
              <td><strong>${r.leave_type_name}</strong></td>
              <td>${r.start_date} <span style="color:var(--slate-400);">→</span> ${r.end_date}</td>
              <td><strong>${r.total_days}</strong></td>
              <td style="font-size:0.85rem; max-width:240px; color:var(--slate-600);">${r.reason}</td>
              <td>
                <span class="badge ${r.status === 'approved' ? 'badge-success' : r.status === 'pending' ? 'badge-warning' : 'badge-danger'}">
                  ${r.status}
                </span>
              </td>
              <td style="font-size:0.82rem; color:var(--slate-500);">
                ${r.reviewer_name ? `${r.reviewer_name}` : '—'}
                ${r.manager_comment ? `<div style="font-size:0.75rem; color:var(--slate-400); font-style:italic;">"${r.manager_comment}"</div>` : ''}
              </td>
              ${isApprover ? `
                <td style="text-align:right;">
                  ${r.status === 'pending' ? `
                    <button class="btn btn-success btn-sm" onclick="App.views.approveLeaveModal(${r.id})">Approve</button>
                    <button class="btn btn-danger btn-sm" onclick="App.views.rejectLeaveModal(${r.id})">Reject</button>
                  ` : '—'}
                </td>
              ` : ''}
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (err) {
    wrapper.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><p class="empty-text">${err.message}</p></div>`;
  }
};

App.views.openApplyLeaveModal = function() {
  const types = App.state.leaveTypes || [];
  const options = types.map(t => `<option value="${t.id}">${t.name} (Max ${t.max_days_per_year} days/yr)</option>`).join('');

  App.showModal('🏖️ Apply for Leave', `
    <form id="applyLeaveForm" onsubmit="App.views.submitApplyLeave(event)">
      <div class="form-group">
        <label class="form-label">Leave Category *</label>
        <select class="form-control" name="leave_type_id" required>${options}</select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Start Date *</label>
          <input type="date" class="form-control" name="start_date" id="leaveStartDate" required value="${new Date().toISOString().slice(0, 10)}">
        </div>
        <div class="form-group">
          <label class="form-label">End Date *</label>
          <input type="date" class="form-control" name="end_date" id="leaveEndDate" required value="${new Date().toISOString().slice(0, 10)}">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Reason for Absence *</label>
        <textarea class="form-control" name="reason" rows="3" required placeholder="Please provide clear context for your manager..."></textarea>
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="applyLeaveForm" type="submit">Submit Request</button>
  `);
};

App.views.submitApplyLeave = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/leave/apply.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.leave();
  } catch (err) {}
};

App.views.approveLeaveModal = function(id) {
  App.showModal('Approve Leave Request', `
    <p style="margin-bottom:1rem; font-size:0.9rem; color:var(--slate-600);">Are you sure you want to approve leave request #${id}?</p>
    <div class="form-group">
      <label class="form-label">Optional Reviewer Note</label>
      <input type="text" class="form-control" id="approveComment" placeholder="Approved. Enjoy your time off!">
    </div>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-success btn-sm" onclick="App.views.submitApproveLeave(${id})">Confirm Approval</button>
  `);
};

App.views.submitApproveLeave = async function(id) {
  const comment = document.getElementById('approveComment')?.value || 'Approved';
  try {
    const res = await API.post('api/leave/approve.php', { request_id: id, comment });
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.loadLeaveTable();
  } catch (e) {}
};

App.views.rejectLeaveModal = function(id) {
  App.showModal('Reject Leave Request', `
    <p style="margin-bottom:1rem; font-size:0.9rem; color:var(--slate-600);">Please state the reason for rejecting leave request #${id}:</p>
    <div class="form-group">
      <label class="form-label">Rejection Reason *</label>
      <input type="text" class="form-control" id="rejectComment" required placeholder="e.g. Critical release sprint scheduled on those dates">
    </div>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-danger btn-sm" onclick="App.views.submitRejectLeave(${id})">Confirm Rejection</button>
  `);
};

App.views.submitRejectLeave = async function(id) {
  const comment = document.getElementById('rejectComment')?.value;
  if (!comment) {
    Toast.show('Please provide a rejection reason.', 'warning');
    return;
  }
  try {
    const res = await API.post('api/leave/reject.php', { request_id: id, comment });
    Toast.show(res.message, 'info');
    App.closeAllModals();
    App.views.loadLeaveTable();
  } catch (e) {}
};

/* ==========================================================================
   6. PAYROLL VIEW
   ========================================================================== */
App.views.payroll = async function() {
  const container = document.getElementById('pageContainer');
  const role = App.state.role;
  const isPrivileged = ['super_admin', 'hr_admin'].includes(role);
  const currentMonth = new Date().getMonth() + 1;
  const currentYear = new Date().getFullYear();

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Compensation & Payroll</h1>
        <p class="page-subtitle">Salary structures, monthly disbursement processing, and payslips.</p>
      </div>
      <div>
        ${isPrivileged ? `
          <button class="btn btn-primary" onclick="App.views.openProcessPayrollModal()">
            ⚡ Process Monthly Payroll
          </button>
        ` : ''}
      </div>
    </div>

    <!-- Payroll Filter -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-body" style="padding:1rem 1.5rem;">
        <div style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
          <div>
            <label class="form-label" style="font-size:0.75rem; margin-bottom:0.2rem;">Month</label>
            <select class="form-control" id="payMonthSelect" onchange="App.views.loadPayrollTable()">
              ${[1,2,3,4,5,6,7,8,9,10,11,12].map(m => `
                <option value="${m}" ${m === currentMonth ? 'selected' : ''}>${new Date(2000, m-1).toLocaleString('default', {month: 'long'})}</option>
              `).join('')}
            </select>
          </div>
          <div>
            <label class="form-label" style="font-size:0.75rem; margin-bottom:0.2rem;">Year</label>
            <select class="form-control" id="payYearSelect" onchange="App.views.loadPayrollTable()">
              <option value="${currentYear}" selected>${currentYear}</option>
              <option value="${currentYear-1}">${currentYear-1}</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Payroll Table Card -->
    <div class="card">
      <div class="table-responsive" id="payrollTableWrapper">
        <div class="skeleton" style="height:300px;"></div>
      </div>
    </div>
  `;

  await App.views.loadPayrollTable();
};

App.views.loadPayrollTable = async function() {
  const wrapper = document.getElementById('payrollTableWrapper');
  if (!wrapper) return;

  const month = document.getElementById('payMonthSelect')?.value || (new Date().getMonth() + 1);
  const year = document.getElementById('payYearSelect')?.value || new Date().getFullYear();

  try {
    const res = await API.get('api/payroll/list.php', { month, year });
    const records = res.data;

    if (!records.length) {
      wrapper.innerHTML = `<div class="empty-state"><div class="empty-icon">💰</div><div class="empty-title">No payroll records</div><p class="empty-text">No payroll records have been processed for this cycle yet.</p></div>`;
      return;
    }

    wrapper.innerHTML = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Basic Salary</th>
            <th>Allowances</th>
            <th>Deductions</th>
            <th>Net Salary</th>
            <th>Status</th>
            <th>Payment Date</th>
            <th style="text-align:right;">Payslip</th>
          </tr>
        </thead>
        <tbody>
          ${records.map(p => `
            <tr>
              <td>
                <div style="font-weight:600; color:var(--slate-900);">${p.first_name} ${p.last_name}</div>
                <div style="font-size:0.78rem; color:var(--slate-400);">${p.employee_code}</div>
              </td>
              <td>${p.department_name || '—'}</td>
              <td>$${Number(p.basic_salary).toLocaleString()}</td>
              <td><span style="color:var(--status-success); font-weight:600;">+$${Number(p.total_allowances).toLocaleString()}</span></td>
              <td><span style="color:var(--status-danger); font-weight:600;">-$${Number(p.total_deductions).toLocaleString()}</span></td>
              <td><strong style="font-size:0.95rem; color:var(--slate-900);">$${Number(p.net_salary).toLocaleString()}</strong></td>
              <td><span class="badge badge-success">${p.payment_status}</span></td>
              <td>${p.payment_date || '—'}</td>
              <td style="text-align:right;">
                <button class="btn btn-secondary btn-sm" onclick="App.views.viewPayslip(${p.id})">📄 View Slip</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (err) {
    wrapper.innerHTML = `<div class="empty-state"><div class="empty-icon">⚠️</div><p class="empty-text">${err.message}</p></div>`;
  }
};

App.views.openProcessPayrollModal = function() {
  const currentMonth = new Date().getMonth() + 1;
  const currentYear = new Date().getFullYear();

  App.showModal('⚡ Process Monthly Payroll', `
    <div style="background:var(--slate-50); border:1px solid var(--border-color); border-radius:8px; padding:1rem; margin-bottom:1.5rem;">
      <p style="font-size:0.88rem; color:var(--slate-700);">
        This operation calculates all statutory earnings (Basic, HRA, Medical, Stipends) and deductions (Withholding Tax, Health Insurance, 401(k) / PF) for all active personnel.
      </p>
    </div>
    <form id="processPayrollForm" onsubmit="App.views.submitProcessPayroll(event)">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Disbursement Month *</label>
          <select class="form-control" name="month" required>
            ${[1,2,3,4,5,6,7,8,9,10,11,12].map(m => `
              <option value="${m}" ${m === currentMonth ? 'selected' : ''}>${new Date(2000, m-1).toLocaleString('default', {month: 'long'})}</option>
            `).join('')}
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Fiscal Year *</label>
          <input type="number" class="form-control" name="year" required value="${currentYear}">
        </div>
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="processPayrollForm" type="submit">Run Payroll Calculation</button>
  `);
};

App.views.submitProcessPayroll = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/payroll/process.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.loadPayrollTable();
  } catch (err) {}
};

/* Printable Payslip Modal */
App.views.viewPayslip = async function(id) {
  try {
    const res = await API.get('api/payroll/payslip.php', { id });
    const { payroll, period, allowances, deductions, company } = res.data;

    const html = `
      <div class="payslip-container" id="printablePayslipArea">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid var(--slate-900); padding-bottom:1.5rem; margin-bottom:1.5rem;">
          <div>
            <div style="font-size:1.4rem; font-weight:800; color:var(--slate-900); letter-spacing:-0.03em;">${company.company_name || 'Apex Global Technologies Inc.'}</div>
            <div style="font-size:0.82rem; color:var(--slate-500); margin-top:0.2rem;">${company.company_address || '100 Executive Way, Suite 400, New York, NY'}</div>
            <div style="font-size:0.82rem; color:var(--slate-500);">${company.company_email || 'payroll@apexglobal.tech'} • ${company.company_phone || '+1 (800) 555-0199'}</div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:1.1rem; font-weight:700; text-transform:uppercase; color:var(--slate-700); letter-spacing:0.05em;">Payslip / Salary Receipt</div>
            <div style="font-size:0.95rem; font-weight:700; color:var(--slate-900); margin-top:0.25rem;">${period}</div>
            <span class="badge badge-success" style="margin-top:0.4rem;">${payroll.payment_status.toUpperCase()}</span>
          </div>
        </div>

        <!-- Employee Info Summary -->
        <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:1.25rem; background:var(--slate-50); padding:1rem 1.25rem; border-radius:8px; margin-bottom:1.5rem;">
          <div>
            <div style="font-size:0.75rem; font-weight:700; color:var(--slate-400); text-transform:uppercase;">Employee Information</div>
            <div style="font-weight:700; font-size:1rem; color:var(--slate-900); margin-top:0.2rem;">${payroll.first_name} ${payroll.last_name}</div>
            <div style="font-size:0.85rem; color:var(--slate-600);">${payroll.designation_title} • ${payroll.department_name}</div>
            <div style="font-size:0.82rem; color:var(--slate-500); font-family:monospace;">ID: ${payroll.employee_code}</div>
          </div>
          <div>
            <div style="font-size:0.75rem; font-weight:700; color:var(--slate-400); text-transform:uppercase;">Disbursement Channel</div>
            <div style="font-weight:600; font-size:0.9rem; color:var(--slate-900); margin-top:0.2rem;">Direct Deposit (${payroll.bank_name || 'Corporate Banking'})</div>
            <div style="font-size:0.82rem; color:var(--slate-500); font-family:monospace;">Account: ${payroll.bank_account_no || '••••••••'}</div>
            <div style="font-size:0.82rem; color:var(--slate-500);">Value Date: ${payroll.payment_date || 'End of Month'}</div>
          </div>
        </div>

        <!-- Earnings & Deductions Tables -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
          <!-- Allowances -->
          <div>
            <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; color:var(--slate-700); margin-bottom:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.3rem;">Earnings & Allowances</h4>
            <div style="display:flex; justify-content:space-between; padding:0.4rem 0; font-size:0.88rem;">
              <span>Basic Salary</span>
              <strong>$${Number(payroll.basic_salary).toFixed(2)}</strong>
            </div>
            ${allowances.map(a => `
              <div style="display:flex; justify-content:space-between; padding:0.4rem 0; font-size:0.88rem; border-top:1px dashed var(--slate-200);">
                <span>${a.name}</span>
                <span>+$${Number(a.amount).toFixed(2)}</span>
              </div>
            `).join('')}
            <div style="display:flex; justify-content:space-between; padding:0.6rem 0; font-size:0.9rem; border-top:1px solid var(--slate-900); margin-top:0.5rem; font-weight:700;">
              <span>Gross Earnings</span>
              <span>$${(Number(payroll.basic_salary) + Number(payroll.total_allowances)).toFixed(2)}</span>
            </div>
          </div>

          <!-- Deductions -->
          <div>
            <h4 style="font-size:0.85rem; font-weight:700; text-transform:uppercase; color:var(--slate-700); margin-bottom:0.5rem; border-bottom:1px solid var(--border-color); padding-bottom:0.3rem;">Statutory Deductions</h4>
            ${deductions.map(d => `
              <div style="display:flex; justify-content:space-between; padding:0.4rem 0; font-size:0.88rem; border-bottom:1px dashed var(--slate-200);">
                <span>${d.name}</span>
                <span style="color:var(--status-danger);">-$${Number(d.amount).toFixed(2)}</span>
              </div>
            `).join('')}
            <div style="display:flex; justify-content:space-between; padding:0.6rem 0; font-size:0.9rem; border-top:1px solid var(--slate-900); margin-top:0.5rem; font-weight:700;">
              <span>Total Deductions</span>
              <span style="color:var(--status-danger);">-$${Number(payroll.total_deductions).toFixed(2)}</span>
            </div>
          </div>
        </div>

        <!-- Net Total Highlight Banner -->
        <div style="background:var(--slate-900); color:var(--white); padding:1.25rem 1.5rem; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <div style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; opacity:0.8;">Net Salary Disbursed</div>
            <div style="font-size:0.85rem; opacity:0.6;">Direct credit confirmed</div>
          </div>
          <div style="font-size:1.85rem; font-weight:800;">$${Number(payroll.net_salary).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
        </div>
      </div>
    `;

    App.showModal(`Payslip — ${payroll.first_name} ${payroll.last_name} (${period})`, html, `
      <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Close</button>
      <button class="btn btn-primary btn-sm" onclick="window.print()">🖨️ Print / Download PDF</button>
    `, 'lg');
  } catch (e) {}
};

/* ==========================================================================
   7. DEPARTMENTS & DESIGNATIONS VIEW
   ========================================================================== */
App.views.departments = async function() {
  const container = document.getElementById('pageContainer');
  const role = App.state.role;
  const isPrivileged = ['super_admin', 'hr_admin'].includes(role);

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Departments & Designations</h1>
        <p class="page-subtitle">Organizational structure, business units, and designation bands.</p>
      </div>
      <div>
        ${isPrivileged ? `
          <button class="btn btn-primary" onclick="App.views.openAddDepartmentModal()">
            ➕ Add Department
          </button>
        ` : ''}
      </div>
    </div>

    <!-- Departments Grid -->
    <h2 style="font-size:1.15rem; font-weight:700; color:var(--slate-900); margin-bottom:1rem;">Operational Departments</h2>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem; margin-bottom:2.5rem;" id="deptGridContainer">
      <div class="skeleton" style="height:160px;"></div>
    </div>

    <!-- Designations Table Section -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Role Designations & Salary Bands</h3>
        ${isPrivileged ? `<button class="btn btn-secondary btn-sm" onclick="App.views.openAddDesignationModal()">➕ Add Designation</button>` : ''}
      </div>
      <div class="table-responsive" id="designationsTableWrapper">
        <div class="skeleton" style="height:250px;"></div>
      </div>
    </div>
  `;

  // Load Departments
  try {
    const res = await API.get('api/departments/list.php');
    const depts = res.data;
    App.state.departments = depts;

    const grid = document.getElementById('deptGridContainer');
    grid.innerHTML = depts.map(d => `
      <div class="card" style="padding:1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
          <div>
            <h3 style="font-size:1.1rem; font-weight:700; color:var(--slate-900);">${d.name}</h3>
            <span class="badge badge-neutral" style="margin-top:0.25rem;">Code: ${d.code}</span>
          </div>
          <div class="stat-icon" style="background:var(--slate-100);">🏢</div>
        </div>
        <p style="font-size:0.85rem; color:var(--slate-500); margin:0.85rem 0;">${d.description || 'No department summary provided.'}</p>
        <div style="border-top:1px solid var(--border-color); padding-top:0.75rem; display:flex; justify-content:space-between; align-items:center; font-size:0.85rem;">
          <span style="color:var(--slate-600);">${d.head_first_name ? `Lead: <strong>${d.head_first_name} ${d.head_last_name}</strong>` : 'Lead: Not Assigned'}</span>
          <strong style="color:var(--slate-900);">${d.employee_count} Members</strong>
        </div>
      </div>
    `).join('');
  } catch (e) {}

  // Load Designations
  try {
    const dRes = await API.get('api/designations/list.php');
    const desigs = dRes.data;
    const dWrapper = document.getElementById('designationsTableWrapper');
    dWrapper.innerHTML = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Department</th>
            <th>Salary Range Band</th>
            <th>Active Headcount</th>
            <th>Description</th>
          </tr>
        </thead>
        <tbody>
          ${desigs.map(d => `
            <tr>
              <td><strong>${d.title}</strong></td>
              <td>${d.department_name}</td>
              <td><strong>$${Number(d.min_salary).toLocaleString()}</strong> – <strong>$${Number(d.max_salary).toLocaleString()}</strong></td>
              <td><span class="badge badge-neutral">${d.employee_count} employees</span></td>
              <td style="font-size:0.82rem; color:var(--slate-500);">${d.description || '—'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (e) {}
};

App.views.openAddDepartmentModal = function() {
  App.showModal('➕ Add New Department', `
    <form id="addDeptForm" onsubmit="App.views.submitAddDept(event)">
      <div class="form-group">
        <label class="form-label">Department Name *</label>
        <input type="text" class="form-control" name="name" required placeholder="e.g. Artificial Intelligence & Robotics">
      </div>
      <div class="form-group">
        <label class="form-label">Department Code *</label>
        <input type="text" class="form-control" name="code" required placeholder="e.g. AIR" style="text-transform:uppercase;">
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea class="form-control" name="description" rows="3" placeholder="Strategic scope of this department..."></textarea>
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="addDeptForm" type="submit">Create Department</button>
  `);
};

App.views.submitAddDept = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/departments/create.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.departments();
  } catch (err) {}
};

App.views.openAddDesignationModal = function() {
  const depts = App.state.departments || [];
  const options = depts.map(d => `<option value="${d.id}">${d.name}</option>`).join('');

  App.showModal('➕ Add Role Designation', `
    <form id="addDesigForm" onsubmit="App.views.submitAddDesig(event)">
      <div class="form-group">
        <label class="form-label">Department *</label>
        <select class="form-control" name="department_id" required>${options}</select>
      </div>
      <div class="form-group">
        <label class="form-label">Designation Title *</label>
        <input type="text" class="form-control" name="title" required placeholder="e.g. Staff Backend Engineer">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Minimum Salary ($)</label>
          <input type="number" class="form-control" name="min_salary" value="6000">
        </div>
        <div class="form-group">
          <label class="form-label">Maximum Salary ($)</label>
          <input type="number" class="form-control" name="max_salary" value="12000">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Role Description</label>
        <textarea class="form-control" name="description" rows="2" placeholder="Key responsibilities..."></textarea>
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="addDesigForm" type="submit">Save Designation</button>
  `);
};

App.views.submitAddDesig = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/designations/create.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.departments();
  } catch (err) {}
};

/* ==========================================================================
   8. RECRUITMENT & KANBAN PIPELINE VIEW
   ========================================================================== */
App.views.recruitment = async function() {
  const container = document.getElementById('pageContainer');
  const role = App.state.role;
  const isPrivileged = ['super_admin', 'hr_admin'].includes(role);

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Recruitment & Hiring Pipeline</h1>
        <p class="page-subtitle">Track job openings, candidate stages, and technical interview evaluations.</p>
      </div>
      <div style="display:flex; gap:0.75rem;">
        <button class="btn btn-secondary" onclick="App.views.openAddCandidateModal()">
          ➕ Add Candidate
        </button>
        ${isPrivileged ? `
          <button class="btn btn-primary" onclick="App.views.openAddJobModal()">
            🎯 Post Job Opening
          </button>
        ` : ''}
      </div>
    </div>

    <!-- Job Openings Horizontal Scroll -->
    <div style="margin-bottom:2rem;">
      <h3 style="font-size:1rem; font-weight:700; color:var(--slate-800); margin-bottom:0.75rem;">Active Positions</h3>
      <div style="display:flex; gap:1rem; overflow-x:auto; padding-bottom:0.5rem;" id="activeJobsContainer">
        <div class="skeleton" style="width:240px; height:100px;"></div>
      </div>
    </div>

    <!-- Kanban Board -->
    <div>
      <h3 style="font-size:1.05rem; font-weight:700; color:var(--slate-900); margin-bottom:1rem;">Candidate Pipeline Board</h3>
      <div class="kanban-board" id="recruitmentKanban">
        <div class="skeleton" style="width:100%; height:400px;"></div>
      </div>
    </div>
  `;

  // Load Job Openings
  try {
    const jRes = await API.get('api/recruitment/jobs.php');
    const jobs = jRes.data;
    App.state.jobs = jobs;

    const jCont = document.getElementById('activeJobsContainer');
    jCont.innerHTML = jobs.map(j => `
      <div class="card" style="flex:0 0 260px; padding:1rem;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
          <h4 style="font-size:0.95rem; font-weight:700; color:var(--slate-900);">${j.title}</h4>
          <span class="badge badge-success">${j.status}</span>
        </div>
        <div style="font-size:0.8rem; color:var(--slate-500); margin-top:0.35rem;">${j.department_name} • ${j.vacancies} open</div>
        <div style="margin-top:0.75rem; font-size:0.82rem; font-weight:600; color:var(--slate-700);">${j.candidate_count} Candidates Applied</div>
      </div>
    `).join('');
  } catch (e) {}

  await App.views.loadKanbanBoard();
};

App.views.loadKanbanBoard = async function() {
  const container = document.getElementById('recruitmentKanban');
  if (!container) return;

  try {
    const res = await API.get('api/recruitment/pipeline.php');
    const { pipeline } = res.data;

    const columns = [
      { id: 'applied', label: 'Applied', color: '#64748b' },
      { id: 'screening', label: 'Screening', color: '#2563eb' },
      { id: 'interview', label: 'Interviewing', color: '#7c3aed' },
      { id: 'selected', label: 'Selected', color: '#059669' },
      { id: 'hired', label: 'Hired 🎉', color: '#10b981' },
      { id: 'rejected', label: 'Rejected', color: '#ef4444' }
    ];

    container.innerHTML = columns.map(col => {
      const cards = pipeline[col.id] || [];
      return `
        <div class="kanban-column">
          <div class="kanban-column-header">
            <span class="kanban-column-title" style="color:${col.color};">${col.label}</span>
            <span class="badge badge-neutral">${cards.length}</span>
          </div>
          <div class="kanban-cards-container">
            ${cards.map(c => `
              <div class="kanban-card">
                <div style="font-weight:700; font-size:0.95rem; color:var(--slate-900);">${c.first_name} ${c.last_name}</div>
                <div style="font-size:0.8rem; color:var(--slate-500); margin-top:0.15rem;">${c.job_title}</div>
                <div style="font-size:0.78rem; color:var(--slate-400); margin-top:0.25rem;">${c.email}</div>
                <div style="margin-top:0.75rem; display:flex; justify-content:space-between; align-items:center;">
                  <span style="font-size:0.75rem; color:var(--slate-500);">⭐ ${c.rating || 3}/5</span>
                  <select class="form-control" style="font-size:0.75rem; padding:0.2rem 0.4rem; width:105px;" onchange="App.views.moveCandidateStage(${c.application_id}, this.value)">
                    <option value="">Move to...</option>
                    <option value="applied" ${c.stage === 'applied' ? 'disabled' : ''}>Applied</option>
                    <option value="screening" ${c.stage === 'screening' ? 'disabled' : ''}>Screening</option>
                    <option value="interview" ${c.stage === 'interview' ? 'disabled' : ''}>Interview</option>
                    <option value="selected" ${c.stage === 'selected' ? 'disabled' : ''}>Selected</option>
                    <option value="hired" ${c.stage === 'hired' ? 'disabled' : ''}>Hired</option>
                    <option value="rejected" ${c.stage === 'rejected' ? 'disabled' : ''}>Rejected</option>
                  </select>
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      `;
    }).join('');
  } catch (err) {
    container.innerHTML = `<div class="empty-state"><p class="empty-text">${err.message}</p></div>`;
  }
};

App.views.moveCandidateStage = async function(appId, newStage) {
  if (!newStage) return;
  try {
    const res = await API.post('api/recruitment/pipeline.php', {
      action: 'update_stage',
      application_id: appId,
      stage: newStage
    });
    Toast.show(res.message, 'success');
    App.views.loadKanbanBoard();
  } catch (e) {}
};

App.views.openAddJobModal = function() {
  const depts = App.state.departments || [];
  const deptOptions = depts.map(d => `<option value="${d.id}">${d.name}</option>`).join('');

  App.showModal('🎯 Post New Job Opening', `
    <form id="addJobForm" onsubmit="App.views.submitAddJob(event)">
      <div class="form-group">
        <label class="form-label">Job Title *</label>
        <input type="text" class="form-control" name="title" required placeholder="e.g. Senior Distributed Systems Engineer">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Department *</label>
          <select class="form-control" name="department_id" required>${deptOptions}</select>
        </div>
        <div class="form-group">
          <label class="form-label">Job Type</label>
          <select class="form-control" name="job_type">
            <option value="full_time">Full-Time</option>
            <option value="part_time">Part-Time</option>
            <option value="contract">Contract</option>
            <option value="remote">Remote</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Experience Level</label>
          <input type="text" class="form-control" name="experience_level" placeholder="Senior (5+ years)" value="Mid-Senior (4+ years)">
        </div>
        <div class="form-group">
          <label class="form-label">Open Vacancies</label>
          <input type="number" class="form-control" name="vacancies" value="1">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Job Description</label>
        <textarea class="form-control" name="description" rows="3" placeholder="Overview of role objectives..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Key Requirements</label>
        <textarea class="form-control" name="requirements" rows="2" placeholder="Skills, qualifications..."></textarea>
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="addJobForm" type="submit">Publish Job</button>
  `, 'lg');
};

App.views.submitAddJob = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/recruitment/jobs.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.recruitment();
  } catch (err) {}
};

App.views.openAddCandidateModal = function() {
  const jobs = App.state.jobs || [];
  const jobOptions = jobs.map(j => `<option value="${j.id}">${j.title} (${j.department_name})</option>`).join('');

  App.showModal('➕ Add Candidate to Pipeline', `
    <form id="addCandidateForm" onsubmit="App.views.submitAddCandidate(event)">
      <div class="form-group">
        <label class="form-label">Target Job Opening *</label>
        <select class="form-control" name="job_opening_id" required>${jobOptions}</select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input type="text" class="form-control" name="first_name" required placeholder="Jane">
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input type="text" class="form-control" name="last_name" required placeholder="Doe">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" class="form-control" name="email" required placeholder="jane.doe@example.com">
        </div>
        <div class="form-group">
          <label class="form-label">Phone Number</label>
          <input type="text" class="form-control" name="phone" placeholder="+1 (555) 234-5678">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Initial Recruiter Notes</label>
        <textarea class="form-control" name="notes" rows="2" placeholder="Candidate profile summary..."></textarea>
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="addCandidateForm" type="submit">Add Candidate</button>
  `);
};

App.views.submitAddCandidate = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  data.action = 'add_candidate';
  try {
    const res = await API.post('api/recruitment/pipeline.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.loadKanbanBoard();
  } catch (err) {}
};

/* ==========================================================================
   9. PERFORMANCE MANAGEMENT VIEW
   ========================================================================== */
App.views.performance = async function() {
  const container = document.getElementById('pageContainer');
  const role = App.state.role;
  const isManagerOrHR = ['super_admin', 'hr_admin', 'manager'].includes(role);

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Performance & Growth</h1>
        <p class="page-subtitle">Track quarterly OKRs, key goals, and annual appraisal evaluations.</p>
      </div>
      <div>
        ${isManagerOrHR ? `
          <button class="btn btn-primary" onclick="App.views.openAddGoalModal()">
            ➕ New Goal / OKR
          </button>
        ` : ''}
      </div>
    </div>

    <!-- Active Goals Grid -->
    <div class="card" style="margin-bottom:2rem;">
      <div class="card-header">
        <h3 class="card-title">Active Goals & Key Results</h3>
        <span class="badge badge-info">Q3/Q4 Milestone Cycle</span>
      </div>
      <div class="card-body" id="goalsListContainer">
        <div class="skeleton" style="height:150px;"></div>
      </div>
    </div>

    <!-- Appraisals & Reviews -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Performance Appraisals & Reviews</h3>
        ${isManagerOrHR ? `<button class="btn btn-secondary btn-sm" onclick="App.views.openAddAppraisalModal()">➕ Create Appraisal</button>` : ''}
      </div>
      <div class="table-responsive" id="reviewsTableWrapper">
        <div class="skeleton" style="height:200px;"></div>
      </div>
    </div>
  `;

  // Load Goals
  try {
    const gRes = await API.get('api/performance/goals.php');
    const goals = gRes.data;
    const gCont = document.getElementById('goalsListContainer');

    if (!goals.length) {
      gCont.innerHTML = `<div class="empty-state"><p class="empty-text">No active goals registered.</p></div>`;
    } else {
      gCont.innerHTML = `
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.25rem;">
          ${goals.map(g => `
            <div style="background:var(--slate-50); border:1px solid var(--border-color); border-radius:8px; padding:1.25rem;">
              <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                  <h4 style="font-weight:700; font-size:1rem; color:var(--slate-900);">${g.title}</h4>
                  <div style="font-size:0.8rem; color:var(--slate-500); margin-top:0.2rem;">${g.first_name} ${g.last_name} • Due ${g.target_date}</div>
                </div>
                <span class="badge ${g.progress >= 100 ? 'badge-success' : 'badge-info'}">${g.progress}%</span>
              </div>
              <p style="font-size:0.85rem; color:var(--slate-600); margin:0.75rem 0;">${g.description || 'Deliver target milestones'}</p>
              <div style="height:8px; background:var(--slate-200); border-radius:999px; overflow:hidden; margin-bottom:0.75rem;">
                <div style="height:100%; width:${g.progress}%; background:var(--slate-800);"></div>
              </div>
              <div style="text-align:right;">
                <button class="btn btn-secondary btn-sm" onclick="App.views.updateGoalProgressModal(${g.id}, ${g.progress})">Update Progress</button>
              </div>
            </div>
          `).join('')}
        </div>
      `;
    }
  } catch (e) {}

  // Load Reviews
  try {
    const rRes = await API.get('api/performance/reviews.php');
    const reviews = rRes.data;
    const rWrap = document.getElementById('reviewsTableWrapper');

    if (!reviews.length) {
      rWrap.innerHTML = `<div class="empty-state"><p class="empty-text">No performance appraisals completed yet.</p></div>`;
    } else {
      rWrap.innerHTML = `
        <table class="data-table">
          <thead>
            <tr><th>Employee</th><th>Review Period</th><th>Rating</th><th>Manager Feedback</th><th>Self Review</th></tr>
          </thead>
          <tbody>
            ${reviews.map(r => `
              <tr>
                <td>
                  <div style="font-weight:600; color:var(--slate-900);">${r.first_name} ${r.last_name}</div>
                  <div style="font-size:0.78rem; color:var(--slate-400);">${r.employee_code} • ${r.department_name || ''}</div>
                </td>
                <td><strong>${r.review_period}</strong></td>
                <td><span class="badge badge-success">⭐ ${r.rating} / 5.0</span></td>
                <td style="font-size:0.85rem; color:var(--slate-700); max-width:280px;">${r.manager_feedback || '—'}</td>
                <td style="font-size:0.85rem; color:var(--slate-500); max-width:200px;">
                  ${r.employee_self_review ? r.employee_self_review : (App.state.employee && App.state.employee.id === r.employee_id ? `<button class="btn btn-secondary btn-sm" onclick="App.views.submitSelfReviewModal(${r.id})">Add Self Review</button>` : '—')}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      `;
    }
  } catch (e) {}
};

App.views.updateGoalProgressModal = function(id, currentProg) {
  App.showModal('Update Goal Progress', `
    <div style="text-align:center; padding:1rem 0;">
      <div style="font-size:2rem; font-weight:800; color:var(--slate-900);" id="sliderValDisplay">${currentProg}%</div>
      <input type="range" min="0" max="100" value="${currentProg}" style="width:100%; margin:1.5rem 0;" oninput="document.getElementById('sliderValDisplay').textContent = this.value + '%'" id="progRangeInput">
    </div>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" onclick="App.views.saveGoalProgress(${id})">Save Progress</button>
  `);
};

App.views.saveGoalProgress = async function(id) {
  const val = document.getElementById('progRangeInput')?.value;
  try {
    const res = await API.post('api/performance/goals.php', { id, progress: val });
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.performance();
  } catch (e) {}
};

/* ==========================================================================
   10. DOCUMENTS VIEW
   ========================================================================== */
App.views.documents = async function() {
  const container = document.getElementById('pageContainer');

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Enterprise Document Vault</h1>
        <p class="page-subtitle">Secure cloud storage for corporate contracts, IDs, handbook policies, and files.</p>
      </div>
      <div>
        <button class="btn btn-primary" onclick="App.views.openUploadDocModal()">
          📤 Upload Document
        </button>
      </div>
    </div>

    <!-- Category Tabs -->
    <div class="tabs-nav" id="docCategoryNav">
      <button class="tab-btn active" onclick="App.views.filterDocs('', this)">All Vault Files</button>
      <button class="tab-btn" onclick="App.views.filterDocs('policies', this)">Company Policies</button>
      <button class="tab-btn" onclick="App.views.filterDocs('contracts', this)">Contracts & NDAs</button>
      <button class="tab-btn" onclick="App.views.filterDocs('identity', this)">Identity & Passports</button>
      <button class="tab-btn" onclick="App.views.filterDocs('certificates', this)">Certificates</button>
    </div>

    <!-- Documents Grid -->
    <div id="docsListContainer">
      <div class="skeleton" style="height:250px;"></div>
    </div>
  `;

  await App.views.loadDocumentsList('');
};

App.views.filterDocs = function(category, btn) {
  document.querySelectorAll('#docCategoryNav .tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  App.views.loadDocumentsList(category);
};

App.views.loadDocumentsList = async function(category = '') {
  const container = document.getElementById('docsListContainer');
  if (!container) return;

  try {
    const res = await API.get('api/documents/list.php', { category });
    const docs = res.data;

    if (!docs.length) {
      container.innerHTML = `<div class="empty-state"><div class="empty-icon">📁</div><div class="empty-title">No documents found</div><p class="empty-text">No documents match this category folder.</p></div>`;
      return;
    }

    container.innerHTML = `
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.25rem;">
        ${docs.map(d => `
          <div class="card" style="padding:1.25rem; display:flex; flex-direction:column; justify-content:space-between;">
            <div>
              <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <span class="badge badge-neutral" style="text-transform:uppercase;">${d.category}</span>
                <span style="font-size:0.75rem; color:var(--slate-400);">${(d.file_size/1024).toFixed(1)} KB</span>
              </div>
              <h3 style="font-size:1.05rem; font-weight:700; color:var(--slate-900); margin:0.75rem 0 0.25rem 0;">📄 ${d.title}</h3>
              <div style="font-size:0.8rem; color:var(--slate-500);">Uploaded by ${d.uploader_username} • ${d.created_at.slice(0, 10)}</div>
            </div>
            <div style="border-top:1px solid var(--border-color); margin-top:1rem; padding-top:0.85rem; display:flex; justify-content:space-between; align-items:center;">
              <a href="api/documents/download.php?id=${d.id}" target="_blank" class="btn btn-secondary btn-sm">👁️ Preview / Download</a>
              <button class="btn btn-secondary btn-sm" onclick="App.views.deleteDocument(${d.id}, '${d.title}')" style="color:var(--status-danger);">🗑️ Delete</button>
            </div>
          </div>
        `).join('')}
      </div>
    `;
  } catch (err) {
    container.innerHTML = `<div class="empty-state"><p class="empty-text">${err.message}</p></div>`;
  }
};

App.views.openUploadDocModal = function() {
  const currentEmpId = App.state.employee ? App.state.employee.id : 1;

  App.showModal('📤 Upload Document', `
    <form id="uploadDocForm" onsubmit="App.views.submitUploadDoc(event)">
      <div class="form-group">
        <label class="form-label">Document Title *</label>
        <input type="text" class="form-control" name="title" required placeholder="e.g. Updated Health Benefits Tier 2026.pdf">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Category *</label>
          <select class="form-control" name="category" required>
            <option value="policies">Company Policies</option>
            <option value="contracts">Contracts & NDAs</option>
            <option value="certificates">Certificates</option>
            <option value="identity">Identity & Documents</option>
            <option value="other">General Other</option>
          </select>
        </div>
        <input type="hidden" name="employee_id" value="${currentEmpId}">
      </div>
      <div class="form-group">
        <label class="form-label">Select File (PDF, Word, Excel, Images - Max 10MB) *</label>
        <input type="file" class="form-control" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="uploadDocForm" type="submit">Upload Document</button>
  `);
};

App.views.submitUploadDoc = async function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  try {
    const res = await API.upload('api/documents/upload.php', formData);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.documents();
  } catch (err) {}
};

App.views.deleteDocument = function(id, title) {
  if (confirm(`Are you sure you want to remove "${title}"?`)) {
    API.post('api/documents/delete.php', { id }).then(res => {
      Toast.show(res.message, 'info');
      App.views.documents();
    });
  }
};

/* ==========================================================================
   11. ANNOUNCEMENTS VIEW
   ========================================================================== */
App.views.announcements = async function() {
  const container = document.getElementById('pageContainer');
  const isPrivileged = ['super_admin', 'hr_admin'].includes(App.state.role);

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Company Announcements</h1>
        <p class="page-subtitle">Official broadcast updates, executive notices, and corporate news.</p>
      </div>
      <div>
        ${isPrivileged ? `
          <button class="btn btn-primary" onclick="App.views.openAddAnnouncementModal()">
            📢 Publish Announcement
          </button>
        ` : ''}
      </div>
    </div>

    <div id="announcementsList" style="display:flex; flex-direction:column; gap:1.25rem;">
      <div class="skeleton" style="height:200px;"></div>
    </div>
  `;

  try {
    const res = await API.get('api/announcements/list.php');
    const announcements = res.data;
    const aCont = document.getElementById('announcementsList');

    if (!announcements.length) {
      aCont.innerHTML = `<div class="empty-state"><div class="empty-icon">📢</div><p class="empty-text">No announcements posted yet.</p></div>`;
      return;
    }

    aCont.innerHTML = announcements.map(a => `
      <div class="card" style="padding:1.5rem; ${a.is_pinned ? 'border-left: 4px solid var(--slate-900);' : ''}">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
          <div>
            <div style="display:flex; align-items:center; gap:0.5rem;">
              ${a.is_pinned ? '<span class="badge badge-neutral">📌 Pinned</span>' : ''}
              <span class="badge badge-info">Audience: ${a.target_role.toUpperCase()}</span>
            </div>
            <h2 style="font-size:1.25rem; font-weight:800; color:var(--slate-900); margin:0.6rem 0 0.35rem 0;">${a.title}</h2>
          </div>
          ${isPrivileged ? `
            <button class="btn btn-secondary btn-sm" onclick="App.views.deleteAnnouncement(${a.id})" style="color:var(--status-danger);">Delete</button>
          ` : ''}
        </div>
        <p style="color:var(--slate-700); font-size:0.95rem; line-height:1.6; margin:0.5rem 0 1rem 0; white-space:pre-line;">${a.content}</p>
        <div style="font-size:0.8rem; color:var(--slate-400); border-top:1px solid var(--border-color); padding-top:0.75rem;">
          Published by <strong>${a.author_name}</strong> on ${a.created_at}
        </div>
      </div>
    `).join('');
  } catch (err) {}
};

App.views.openAddAnnouncementModal = function() {
  App.showModal('📢 Publish Company Announcement', `
    <form id="addAnnouncementForm" onsubmit="App.views.submitAddAnnouncement(event)">
      <div class="form-group">
        <label class="form-label">Headline Title *</label>
        <input type="text" class="form-control" name="title" required placeholder="e.g. Q4 Town Hall Meeting & Product Strategy">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Target Audience</label>
          <select class="form-control" name="target_role">
            <option value="all">Entire Company</option>
            <option value="manager">Managers Only</option>
            <option value="employee">Employees</option>
          </select>
        </div>
        <div class="form-group" style="display:flex; align-items:center; gap:0.5rem; margin-top:1.8rem;">
          <input type="checkbox" name="is_pinned" id="pinnedCheck" value="1">
          <label for="pinnedCheck" style="font-size:0.9rem; font-weight:600; cursor:pointer;">Pin to Top of Dashboard</label>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Announcement Content *</label>
        <textarea class="form-control" name="content" rows="5" required placeholder="Details of the announcement..."></textarea>
      </div>
    </form>
  `, `
    <button class="btn btn-secondary btn-sm" onclick="App.closeAllModals()">Cancel</button>
    <button class="btn btn-primary btn-sm" form="addAnnouncementForm" type="submit">Broadcast Announcement</button>
  `, 'lg');
};

App.views.submitAddAnnouncement = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/announcements/create.php', data);
    Toast.show(res.message, 'success');
    App.closeAllModals();
    App.views.announcements();
  } catch (err) {}
};

App.views.deleteAnnouncement = function(id) {
  if (confirm('Delete this announcement?')) {
    API.post('api/announcements/delete.php', { id }).then(res => {
      Toast.show(res.message, 'info');
      App.views.announcements();
    });
  }
};

/* ==========================================================================
   12. COMPANY CALENDAR VIEW
   ========================================================================== */
App.views.calendar = async function() {
  const container = document.getElementById('pageContainer');
  const now = new Date();
  const currentMonth = now.getMonth() + 1;
  const currentYear = now.getFullYear();

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Company Calendar</h1>
        <p class="page-subtitle">Track holidays, scheduled team leaves, and corporate gatherings.</p>
      </div>
    </div>

    <!-- Calendar Month Navigation & Events Card -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">${now.toLocaleString('default', { month: 'long' })} ${currentYear}</h3>
        <span class="badge badge-info">Unified Company Schedule</span>
      </div>
      <div class="card-body" id="calendarEventsContainer">
        <div class="skeleton" style="height:250px;"></div>
      </div>
    </div>
  `;

  try {
    const res = await API.get('api/calendar/events.php', { month: currentMonth, year: currentYear });
    const { events } = res.data;
    const cCont = document.getElementById('calendarEventsContainer');

    if (!events.length) {
      cCont.innerHTML = `<div class="empty-state"><p class="empty-text">No events or leaves scheduled for this month.</p></div>`;
      return;
    }

    cCont.innerHTML = `
      <div style="display:flex; flex-direction:column; gap:0.85rem;">
        ${events.map(ev => `
          <div style="display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; background:var(--slate-50); border:1px solid var(--border-color); border-radius:8px;">
            <div>
              <div style="font-weight:700; font-size:1rem; color:var(--slate-900);">${ev.title}</div>
              <div style="font-size:0.85rem; color:var(--slate-500); margin-top:0.2rem;">${ev.description || ''} ${ev.location ? `• 📍 ${ev.location}` : ''}</div>
            </div>
            <div style="text-align:right;">
              <span class="badge ${ev.type === 'holiday' ? 'badge-success' : ev.type === 'leave' ? 'badge-warning' : 'badge-info'}">${ev.badge}</span>
              <div style="font-size:0.8rem; font-weight:700; color:var(--slate-700); margin-top:0.35rem;">${ev.date}</div>
            </div>
          </div>
        `).join('')}
      </div>
    `;
  } catch (e) {}
};

/* ==========================================================================
   13. REPORTS VIEW (with CSV Export)
   ========================================================================== */
App.views.reports = async function() {
  const container = document.getElementById('pageContainer');
  const role = App.state.role;
  const isPrivileged = ['super_admin', 'hr_admin'].includes(role);

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Analytics & Custom Reports</h1>
        <p class="page-subtitle">Generate exportable audit datasets for compliance and management reviews.</p>
      </div>
      <div>
        <button class="btn btn-primary" onclick="App.views.exportReportCSV()">
          📥 Export to CSV
        </button>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-body" style="padding:1.25rem 1.5rem;">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; align-items:flex-end;">
          <div>
            <label class="form-label">Report Category</label>
            <select class="form-control" id="reportTypeSelect" onchange="App.views.loadReportData()">
              <option value="employees" selected>Employees Directory Report</option>
              <option value="attendance">Daily Attendance Log Report</option>
              <option value="leave">Leave History & Requests Report</option>
              ${isPrivileged ? '<option value="payroll">Payroll Disbursement Report</option>' : ''}
            </select>
          </div>
          <div>
            <label class="form-label">From Date</label>
            <input type="date" class="form-control" id="reportStartDate" value="2026-09-01" onchange="App.views.loadReportData()">
          </div>
          <div>
            <label class="form-label">To Date</label>
            <input type="date" class="form-control" id="reportEndDate" value="${new Date().toISOString().slice(0, 10)}" onchange="App.views.loadReportData()">
          </div>
          <div>
            <button class="btn btn-secondary" style="width:100%;" onclick="App.views.loadReportData()">Apply Filters</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Output Table -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title" id="reportHeading">Report Preview</h3>
        <span class="badge badge-neutral" id="reportCounter">Loading...</span>
      </div>
      <div class="table-responsive" id="reportTableWrapper">
        <div class="skeleton" style="height:300px;"></div>
      </div>
    </div>
  `;

  await App.views.loadReportData();
};

App.views.loadReportData = async function() {
  const wrapper = document.getElementById('reportTableWrapper');
  const type = document.getElementById('reportTypeSelect')?.value || 'employees';
  const startDate = document.getElementById('reportStartDate')?.value || '';
  const endDate = document.getElementById('reportEndDate')?.value || '';

  try {
    const res = await API.get('api/reports/generate.php', {
      type,
      start_date: startDate,
      end_date: endDate
    });

    const records = res.data.records;
    document.getElementById('reportCounter').textContent = `${records.length} records generated`;

    if (!records.length) {
      wrapper.innerHTML = `<div class="empty-state"><div class="empty-icon">📈</div><div class="empty-title">No report data found</div><p class="empty-text">No records meet the selected report criteria.</p></div>`;
      return;
    }

    const headers = Object.keys(records[0]);
    wrapper.innerHTML = `
      <table class="data-table">
        <thead>
          <tr>
            ${headers.map(h => `<th>${h.replace(/_/g, ' ').toUpperCase()}</th>`).join('')}
          </tr>
        </thead>
        <tbody>
          ${records.map(row => `
            <tr>
              ${headers.map(h => `<td>${row[h] !== null ? row[h] : '—'}</td>`).join('')}
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (err) {
    wrapper.innerHTML = `<div class="empty-state"><p class="empty-text">${err.message}</p></div>`;
  }
};

App.views.exportReportCSV = function() {
  const type = document.getElementById('reportTypeSelect')?.value || 'employees';
  const startDate = document.getElementById('reportStartDate')?.value || '';
  const endDate = document.getElementById('reportEndDate')?.value || '';
  window.open(`api/reports/generate.php?type=${type}&start_date=${startDate}&end_date=${endDate}&format=csv`, '_blank');
};

/* ==========================================================================
   14. SETTINGS & AUDIT VIEW (Super Admin)
   ========================================================================== */
App.views.settings = async function() {
  const container = document.getElementById('pageContainer');

  container.innerHTML = `
    <div class="page-header">
      <div>
        <h1 class="page-title">Enterprise System Settings</h1>
        <p class="page-subtitle">Configure organizational branding, manage user access roles, and inspect security audit logs.</p>
      </div>
    </div>

    <!-- Sub-tabs -->
    <div class="tabs-nav" id="settingsTabs">
      <button class="tab-btn active" onclick="App.views.switchSettingsTab('company', this)">Company Profile</button>
      <button class="tab-btn" onclick="App.views.switchSettingsTab('users', this)">User Accounts & Roles</button>
      <button class="tab-btn" onclick="App.views.switchSettingsTab('audit', this)">Security Audit Logs</button>
    </div>

    <!-- Pane 1: Company Profile -->
    <div id="settings-company" class="settings-pane">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Company Information & Operational Policies</h3></div>
        <div class="card-body">
          <form id="companySettingsForm" onsubmit="App.views.submitCompanySettings(event)">
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Legal Corporate Name</label>
                <input type="text" class="form-control" name="company_name" id="set_comp_name" required>
              </div>
              <div class="form-group">
                <label class="form-label">Tagline / Mission</label>
                <input type="text" class="form-control" name="company_tagline" id="set_comp_tag">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Official Support Email</label>
                <input type="email" class="form-control" name="company_email" id="set_comp_email" required>
              </div>
              <div class="form-group">
                <label class="form-label">Corporate Phone</label>
                <input type="text" class="form-control" name="company_phone" id="set_comp_phone">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Headquarters Address</label>
              <input type="text" class="form-control" name="company_address" id="set_comp_addr">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Work Day Start Time</label>
                <input type="time" class="form-control" name="working_hours_start" id="set_comp_start" value="09:00">
              </div>
              <div class="form-group">
                <label class="form-label">Work Day End Time</label>
                <input type="time" class="form-control" name="working_hours_end" id="set_comp_end" value="18:00">
              </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Save Corporate Settings</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Pane 2: Users Management -->
    <div id="settings-users" class="settings-pane" style="display:none;">
      <div class="card">
        <div class="card-header"><h3 class="card-title">System User Accounts</h3></div>
        <div class="table-responsive" id="usersTableWrapper">
          <div class="skeleton" style="height:250px;"></div>
        </div>
      </div>
    </div>

    <!-- Pane 3: Audit Logs -->
    <div id="settings-audit" class="settings-pane" style="display:none;">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Immutable Audit Trail</h3></div>
        <div class="table-responsive" id="auditTableWrapper">
          <div class="skeleton" style="height:250px;"></div>
        </div>
      </div>
    </div>
  `;

  // Load company settings
  try {
    const res = await API.get('api/settings/company.php');
    const s = res.data;
    document.getElementById('set_comp_name').value = s.company_name || '';
    document.getElementById('set_comp_tag').value = s.company_tagline || '';
    document.getElementById('set_comp_email').value = s.company_email || '';
    document.getElementById('set_comp_phone').value = s.company_phone || '';
    document.getElementById('set_comp_addr').value = s.company_address || '';
    if (s.working_hours_start) document.getElementById('set_comp_start').value = s.working_hours_start;
    if (s.working_hours_end) document.getElementById('set_comp_end').value = s.working_hours_end;
  } catch (e) {}

  // Load users & audit
  App.views.loadSettingsUsers();
  App.views.loadSettingsAudit();
};

App.views.switchSettingsTab = function(tabName, btn) {
  document.querySelectorAll('#settingsTabs .tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.settings-pane').forEach(p => p.style.display = 'none');
  const target = document.getElementById(`settings-${tabName}`);
  if (target) target.style.display = 'block';
};

App.views.submitCompanySettings = async function(e) {
  e.preventDefault();
  const form = e.target;
  const data = Object.fromEntries(new FormData(form).entries());
  try {
    const res = await API.post('api/settings/company.php', data);
    Toast.show(res.message, 'success');
  } catch (err) {}
};

App.views.loadSettingsUsers = async function() {
  const wrapper = document.getElementById('usersTableWrapper');
  if (!wrapper) return;

  try {
    const res = await API.get('api/settings/users.php');
    const { users, roles } = res.data;

    wrapper.innerHTML = `
      <table class="data-table">
        <thead>
          <tr>
            <th>User Account</th>
            <th>Email</th>
            <th>Role Level</th>
            <th>Status</th>
            <th>Last Active</th>
            <th style="text-align:right;">Modify</th>
          </tr>
        </thead>
        <tbody>
          ${users.map(u => `
            <tr>
              <td>
                <div style="font-weight:600; color:var(--slate-900);">${u.username}</div>
                <div style="font-size:0.78rem; color:var(--slate-400);">${u.first_name ? `${u.first_name} ${u.last_name}` : 'Administrative'}</div>
              </td>
              <td>${u.email}</td>
              <td>
                <select class="form-control" style="font-size:0.82rem; padding:0.25rem 0.5rem; width:150px;" onchange="App.views.updateUserRole(${u.id}, this.value)">
                  ${roles.map(r => `
                    <option value="${r.id}" ${r.id === u.role_id ? 'selected' : ''}>${r.display_name}</option>
                  `).join('')}
                </select>
              </td>
              <td>
                <select class="form-control" style="font-size:0.82rem; padding:0.25rem 0.5rem; width:110px;" onchange="App.views.updateUserStatus(${u.id}, this.value)">
                  <option value="active" ${u.status === 'active' ? 'selected' : ''}>Active</option>
                  <option value="inactive" ${u.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                  <option value="suspended" ${u.status === 'suspended' ? 'selected' : ''}>Suspended</option>
                </select>
              </td>
              <td style="font-size:0.8rem; color:var(--slate-500);">${u.last_login_at || 'Never logged in'}</td>
              <td style="text-align:right;">
                <span class="badge badge-neutral">ID #${u.id}</span>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (e) {}
};

App.views.updateUserRole = async function(id, roleId) {
  try {
    const res = await API.post('api/settings/users.php', { id, role_id: roleId });
    Toast.show(res.message, 'success');
  } catch (e) {}
};

App.views.updateUserStatus = async function(id, status) {
  try {
    const res = await API.post('api/settings/users.php', { id, status });
    Toast.show(res.message, 'success');
  } catch (e) {}
};

App.views.loadSettingsAudit = async function() {
  const wrapper = document.getElementById('auditTableWrapper');
  if (!wrapper) return;

  try {
    const res = await API.get('api/settings/audit_logs.php');
    const logs = res.data;

    wrapper.innerHTML = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>Action Event</th>
            <th>Entity Type</th>
            <th>Initiator</th>
            <th>IP Address</th>
            <th>Event Details</th>
          </tr>
        </thead>
        <tbody>
          ${logs.map(l => `
            <tr>
              <td style="font-family:monospace; font-size:0.8rem;">${l.created_at}</td>
              <td><span class="badge badge-neutral">${l.action}</span></td>
              <td style="text-transform:capitalize; font-weight:600;">${l.entity_type}</td>
              <td>${l.username ? l.username : 'System / Guest'}</td>
              <td style="font-family:monospace; font-size:0.8rem;">${l.ip_address}</td>
              <td style="font-size:0.82rem; color:var(--slate-600); max-width:320px;">${l.details || '—'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;
  } catch (e) {}
};
