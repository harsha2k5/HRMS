/**
 * Apex Global HRMS — API Client & Toast Notification System
 */

const Toast = {
  container: null,

  init() {
    if (!this.container) {
      this.container = document.createElement('div');
      this.container.className = 'toast-container';
      document.body.appendChild(this.container);
    }
  },

  show(message, type = 'info', duration = 3500) {
    this.init();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✓';
    if (type === 'error') icon = '⚠️';
    if (type === 'warning') icon = '⚡';

    toast.innerHTML = `<span>${icon}</span><span style="flex:1;">${message}</span>`;
    this.container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }
};

const API = {
  baseUrl: '',

  async request(endpoint, options = {}) {
    // Ensure clean endpoint formatting
    let url = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    
    // Add default headers unless body is FormData
    const headers = options.headers || {};
    if (!(options.body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      headers['Accept'] = 'application/json';
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers
      });

      // Handle 401 Unauthenticated
      if (response.status === 401) {
        if (window.location.hash !== '#login') {
          Toast.show('Session expired. Please log in again.', 'warning');
          window.location.hash = '#login';
        }
        throw new Error('Unauthenticated');
      }

      const data = await response.json();

      if (!response.ok || !data.success) {
        const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Request failed');
        throw new Error(errorMsg);
      }

      return data;
    } catch (err) {
      if (err.message !== 'Unauthenticated') {
        Toast.show(err.message, 'error');
      }
      throw err;
    }
  },

  get(endpoint, params = {}) {
    const url = new URL(window.location.origin + (endpoint.startsWith('/') ? endpoint : `/${endpoint}`));
    Object.keys(params).forEach(key => {
      if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
        url.searchParams.append(key, params[key]);
      }
    });
    return this.request(url.pathname + url.search, { method: 'GET' });
  },

  post(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data)
    });
  },

  upload(endpoint, formData) {
    return this.request(endpoint, {
      method: 'POST',
      body: formData
    });
  }
};
