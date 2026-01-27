import React, { useState, useEffect } from 'react';
import axios from 'axios';

/**
 * Digitax Settings Component for Addy Super Admin
 * 
 * Allows admins to configure and test Digitax API credentials
 * Location: Add to Super Admin Settings Panel
 */

export default function DigitaxSettings() {
  const [credentials, setCredentials] = useState(null);
  const [formData, setFormData] = useState({
    serialNumber: '',
    tpin: '',
    branchId: '',
    environment: 'sandbox'
  });
  const [loading, setLoading] = useState(false);
  const [testing, setTesting] = useState(false);
  const [testResult, setTestResult] = useState(null);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  // Load existing credentials on mount
  useEffect(() => {
    loadCredentials();
  }, []);

  const loadCredentials = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/api/digitax/credentials', {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });
      
      if (response.data.data && response.data.data.length > 0) {
        setCredentials(response.data.data[0]);
      }
    } catch (err) {
      console.error('Failed to load credentials:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSaveCredentials = async (e) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);

    if (!formData.serialNumber || !formData.tpin || !formData.branchId) {
      setError('All fields are required');
      return;
    }

    try {
      setLoading(true);

      // Map form data to API format
      const payload = {
        api_key: formData.serialNumber,
        api_secret: formData.tpin,
        environment: formData.environment
      };

      const response = await axios.post('/api/digitax/credentials', payload, {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });

      setCredentials(response.data.data);
      setSuccess('Credentials saved. Please test the connection.');
      setFormData({
        serialNumber: '',
        tpin: '',
        branchId: '',
        environment: 'sandbox'
      });
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to save credentials');
    } finally {
      setLoading(false);
    }
  };

  const handleTestConnection = async () => {
    if (!credentials?.id) {
      setError('No credentials to test');
      return;
    }

    try {
      setTesting(true);
      setTestResult(null);

      const response = await axios.post(
        `/api/digitax/credentials/${credentials.id}/test`,
        {},
        {
          headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
        }
      );

      setTestResult(response.data);

      if (response.data.success) {
        setSuccess('✅ Connection test successful!');
        setCredentials(prev => ({
          ...prev,
          is_active: true
        }));
      } else {
        setError(`❌ Connection test failed: ${response.data.error}`);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Connection test failed');
    } finally {
      setTesting(false);
    }
  };

  const handleDeleteCredentials = async () => {
    if (!credentials?.id) return;

    if (!window.confirm('Are you sure you want to delete these credentials?')) {
      return;
    }

    try {
      setLoading(true);
      await axios.delete(`/api/digitax/credentials/${credentials.id}`, {
        headers: { Authorization: `Bearer ${localStorage.getItem('token')}` }
      });

      setCredentials(null);
      setSuccess('Credentials deleted');
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to delete credentials');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="digitax-settings-container">
      <div className="settings-card">
        <h2>🔐 Digitax Configuration</h2>
        <p className="subtitle">Manage your Digitax API credentials for tax calculations</p>

        {/* Error Alert */}
        {error && (
          <div className="alert alert-error">
            <span className="alert-icon">⚠️</span>
            <div className="alert-content">
              <h4>Error</h4>
              <p>{error}</p>
            </div>
            <button 
              className="alert-close"
              onClick={() => setError(null)}
            >
              ×
            </button>
          </div>
        )}

        {/* Success Alert */}
        {success && (
          <div className="alert alert-success">
            <span className="alert-icon">✅</span>
            <div className="alert-content">
              <h4>Success</h4>
              <p>{success}</p>
            </div>
            <button 
              className="alert-close"
              onClick={() => setSuccess(null)}
            >
              ×
            </button>
          </div>
        )}

        {/* Existing Credentials Display */}
        {credentials && (
          <div className="credentials-display">
            <h3>Current Configuration</h3>
            <div className="credential-info">
              <div className="info-row">
                <label>Serial Number:</label>
                <code>{credentials.api_key}</code>
              </div>
              <div className="info-row">
                <label>Environment:</label>
                <span className={`badge ${credentials.environment}`}>
                  {credentials.environment.toUpperCase()}
                </span>
              </div>
              <div className="info-row">
                <label>Status:</label>
                <span className={`badge ${credentials.is_active ? 'active' : 'inactive'}`}>
                  {credentials.is_active ? '🟢 Active' : '🔴 Inactive'}
                </span>
              </div>
              {credentials.last_tested_at && (
                <div className="info-row">
                  <label>Last Tested:</label>
                  <span>{new Date(credentials.last_tested_at).toLocaleString()}</span>
                </div>
              )}
            </div>

            <div className="action-buttons">
              <button
                className="btn btn-primary"
                onClick={handleTestConnection}
                disabled={testing}
              >
                {testing ? '⏳ Testing...' : '🧪 Test Connection'}
              </button>
              <button
                className="btn btn-secondary"
                onClick={() => setCredentials(null)}
              >
                📝 Update Credentials
              </button>
              <button
                className="btn btn-danger"
                onClick={handleDeleteCredentials}
              >
                🗑️ Delete
              </button>
            </div>
          </div>
        )}

        {/* Test Results */}
        {testResult && (
          <div className={`test-results ${testResult.success ? 'success' : 'failure'}`}>
            <h3>Test Results</h3>
            <div className="result-message">
              <p>{testResult.message}</p>
            </div>

            {testResult.data?.test_details && (
              <div className="test-details">
                <h4>Details:</h4>
                <ul>
                  <li>
                    <strong>API URL:</strong> {testResult.data.test_details.api_url}
                  </li>
                  <li>
                    <strong>Environment:</strong> {testResult.data.test_details.environment}
                  </li>
                  <li>
                    <strong>Authentication:</strong>{' '}
                    {testResult.data.test_details.auth_verified ? '✅ Verified' : '❌ Failed'}
                  </li>
                  <li>
                    <strong>Tax Calculation:</strong>{' '}
                    {testResult.data.test_details.calculation_available ? '✅ Available' : '❌ Unavailable'}
                  </li>
                  <li>
                    <strong>Tested At:</strong> {new Date(testResult.data.test_details.tested_at).toLocaleString()}
                  </li>
                </ul>
              </div>
            )}

            {testResult.error && (
              <div className="error-details">
                <h4>Error Details:</h4>
                <code>{testResult.error}</code>
              </div>
            )}
          </div>
        )}

        {/* Credential Input Form */}
        {!credentials && (
          <form onSubmit={handleSaveCredentials} className="credential-form">
            <h3>Add Digitax Credentials</h3>

            <div className="form-group">
              <label htmlFor="serialNumber">Serial Number *</label>
              <input
                type="text"
                id="serialNumber"
                name="serialNumber"
                value={formData.serialNumber}
                onChange={handleInputChange}
                placeholder="e.g., NAMI26012180421379KB7DAE"
                required
              />
              <small>Your Digitax account serial number</small>
            </div>

            <div className="form-group">
              <label htmlFor="tpin">TPIN *</label>
              <input
                type="password"
                id="tpin"
                name="tpin"
                value={formData.tpin}
                onChange={handleInputChange}
                placeholder="Your TPIN"
                required
              />
              <small>Your Digitax TPIN (will be encrypted)</small>
            </div>

            <div className="form-group">
              <label htmlFor="branchId">Branch ID *</label>
              <input
                type="text"
                id="branchId"
                name="branchId"
                value={formData.branchId}
                onChange={handleInputChange}
                placeholder="e.g., 002"
                required
              />
              <small>Your branch ID from Digitax</small>
            </div>

            <div className="form-group">
              <label htmlFor="environment">Environment *</label>
              <select
                id="environment"
                name="environment"
                value={formData.environment}
                onChange={handleInputChange}
                required
              >
                <option value="sandbox">Sandbox (Testing)</option>
                <option value="production">Production</option>
              </select>
              <small>Choose sandbox for testing or production for live</small>
            </div>

            <div className="form-actions">
              <button
                type="submit"
                className="btn btn-primary"
                disabled={loading}
              >
                {loading ? '💾 Saving...' : '💾 Save Credentials'}
              </button>
            </div>

            <div className="info-box">
              <h4>ℹ️ Important</h4>
              <ul>
                <li>All credentials are encrypted in the database</li>
                <li>You must test the connection after adding credentials</li>
                <li>Only administrators can manage these settings</li>
                <li>Credentials are required for tax calculations</li>
              </ul>
            </div>
          </form>
        )}
      </div>

      <style jsx>{`
        .digitax-settings-container {
          max-width: 800px;
          margin: 0 auto;
          padding: 20px;
        }

        .settings-card {
          background: white;
          border-radius: 8px;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
          padding: 30px;
        }

        .settings-card h2 {
          margin-top: 0;
          color: #333;
          border-bottom: 2px solid #4e4af9;
          padding-bottom: 10px;
        }

        .subtitle {
          color: #666;
          margin-bottom: 20px;
        }

        .alert {
          padding: 15px;
          border-radius: 6px;
          margin-bottom: 20px;
          display: flex;
          align-items: flex-start;
          gap: 15px;
        }

        .alert-error {
          background-color: #fee;
          border: 1px solid #fcc;
          color: #c33;
        }

        .alert-success {
          background-color: #efe;
          border: 1px solid #cfc;
          color: #3c3;
        }

        .alert-icon {
          font-size: 20px;
        }

        .alert-content h4 {
          margin: 0 0 5px 0;
        }

        .alert-content p {
          margin: 0;
        }

        .alert-close {
          background: none;
          border: none;
          font-size: 20px;
          cursor: pointer;
          color: inherit;
          padding: 0;
          margin-left: auto;
        }

        .credentials-display {
          background: #f5f5f5;
          padding: 20px;
          border-radius: 6px;
          margin-bottom: 20px;
        }

        .credentials-display h3 {
          margin-top: 0;
        }

        .credential-info {
          margin-bottom: 15px;
        }

        .info-row {
          display: flex;
          justify-content: space-between;
          padding: 8px 0;
          border-bottom: 1px solid #ddd;
        }

        .info-row label {
          font-weight: bold;
          color: #333;
        }

        .info-row code {
          font-family: monospace;
          background: white;
          padding: 2px 6px;
          border-radius: 3px;
        }

        .badge {
          display: inline-block;
          padding: 4px 12px;
          border-radius: 20px;
          font-size: 12px;
          font-weight: bold;
        }

        .badge.sandbox {
          background: #ffeaa7;
          color: #d63031;
        }

        .badge.production {
          background: #ff7675;
          color: white;
        }

        .badge.active {
          background: #00b894;
          color: white;
        }

        .badge.inactive {
          background: #d63031;
          color: white;
        }

        .action-buttons {
          display: flex;
          gap: 10px;
          margin-top: 15px;
        }

        .btn {
          padding: 10px 15px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-weight: bold;
          transition: all 0.3s ease;
        }

        .btn-primary {
          background: #4e4af9;
          color: white;
        }

        .btn-primary:hover:not(:disabled) {
          background: #3d39d8;
        }

        .btn-secondary {
          background: #95afc0;
          color: white;
        }

        .btn-secondary:hover:not(:disabled) {
          background: #7f8f9f;
        }

        .btn-danger {
          background: #d63031;
          color: white;
        }

        .btn-danger:hover:not(:disabled) {
          background: #b22222;
        }

        .btn:disabled {
          opacity: 0.6;
          cursor: not-allowed;
        }

        .test-results {
          background: #f0f0f0;
          padding: 20px;
          border-radius: 6px;
          margin: 20px 0;
          border-left: 4px solid #4e4af9;
        }

        .test-results.success {
          background: #e8f5e9;
          border-left-color: #00b894;
        }

        .test-results.failure {
          background: #ffebee;
          border-left-color: #d63031;
        }

        .result-message {
          font-weight: bold;
          margin-bottom: 15px;
        }

        .test-details,
        .error-details {
          background: white;
          padding: 15px;
          border-radius: 4px;
          margin-top: 10px;
        }

        .test-details ul {
          list-style: none;
          padding: 0;
        }

        .test-details li {
          padding: 8px 0;
          border-bottom: 1px solid #eee;
        }

        .test-details strong {
          display: inline-block;
          min-width: 150px;
        }

        .error-details code {
          display: block;
          background: #f5f5f5;
          padding: 10px;
          border-radius: 4px;
          overflow-x: auto;
          font-size: 12px;
        }

        .credential-form {
          background: #f9f9f9;
          padding: 20px;
          border-radius: 6px;
        }

        .credential-form h3 {
          margin-top: 0;
        }

        .form-group {
          margin-bottom: 20px;
        }

        .form-group label {
          display: block;
          font-weight: bold;
          margin-bottom: 5px;
          color: #333;
        }

        .form-group input,
        .form-group select {
          width: 100%;
          padding: 10px;
          border: 1px solid #ddd;
          border-radius: 4px;
          font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
          outline: none;
          border-color: #4e4af9;
          box-shadow: 0 0 0 3px rgba(78, 74, 249, 0.1);
        }

        .form-group small {
          display: block;
          color: #999;
          margin-top: 5px;
          font-size: 12px;
        }

        .form-actions {
          margin-top: 20px;
        }

        .info-box {
          background: #e3f2fd;
          border-left: 4px solid #4e4af9;
          padding: 15px;
          border-radius: 4px;
          margin-top: 20px;
        }

        .info-box h4 {
          margin-top: 0;
          color: #1976d2;
        }

        .info-box ul {
          margin: 10px 0 0 0;
          padding-left: 20px;
        }

        .info-box li {
          margin: 5px 0;
          color: #333;
        }
      `}</style>
    </div>
  );
}
