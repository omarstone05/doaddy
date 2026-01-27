import React, { useState, useEffect } from 'react';
import axios from 'axios';

/**
 * Digitax Settings Component for Addy Super Admin
 * 
 * Allows admins to configure and test Digitax API credentials
 * Location: Admin/Integration/DigitaxSettings
 */

export default function DigitaxSettings() {
  const [credentials, setCredentials] = useState(null);
  const [formData, setFormData] = useState({
    serialNumber: '',
    tpin: '',
    digitaxApiKey: '',
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
      const response = await axios.get('/api/digitax/credentials');
      
      if (response.data.data && response.data.data.length > 0) {
        setCredentials(response.data.data[0]);
      }
    } catch (err) {
      console.error('Failed to load credentials:', err);
      if (err.response?.status !== 401) {
        setError('Failed to load credentials');
      }
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

    if (!formData.serialNumber || !formData.tpin || !formData.digitaxApiKey || !formData.branchId) {
      setError('All fields are required');
      return;
    }

    try {
      setLoading(true);

      // Map form data to API format
      const payload = {
        api_key: formData.serialNumber,
        api_secret: formData.tpin,
        digitax_api_key: formData.digitaxApiKey,
        environment: formData.environment
      };

      const response = await axios.post('/api/digitax/credentials', payload);

      setCredentials(response.data.data);
      setSuccess('✅ Credentials saved successfully! Please test the connection.');
      setFormData({
        serialNumber: '',
        tpin: '',
        digitaxApiKey: '',
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
        `/api/digitax/credentials/${credentials.id}/test`
      );

      setTestResult(response.data);

      if (response.data.success) {
        setSuccess('✅ Connection test successful!');
        setCredentials(prev => ({
          ...prev,
          is_active: true
        }));
      } else {
        setError(`❌ Connection test failed: ${response.data.message}`);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Connection test failed');
      setTestResult({
        success: false,
        message: err.response?.data?.message || err.message
      });
    } finally {
      setTesting(false);
    }
  };

  const handleUpdateCredentials = () => {
    setCredentials(null);
    setFormData({
      serialNumber: credentials.api_key || '',
      tpin: '',
      digitaxApiKey: '',
      branchId: '',
      environment: credentials.environment || 'sandbox'
    });
  };

  const handleDeleteCredentials = async () => {
    if (!credentials?.id) return;

    if (!window.confirm('Are you sure you want to delete these credentials? This action cannot be undone.')) {
      return;
    }

    try {
      setLoading(true);
      await axios.delete(`/api/digitax/credentials/${credentials.id}`);

      setCredentials(null);
      setTestResult(null);
      setSuccess('✅ Credentials deleted successfully');
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
        <p className="subtitle">Manage your Digitax API credentials for tax calculations and compliance</p>

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
              type="button"
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
              type="button"
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
                <span className={`badge badge-${credentials.environment}`}>
                  {credentials.environment.toUpperCase()}
                </span>
              </div>
              <div className="info-row">
                <label>Status:</label>
                <span className={`badge badge-${credentials.is_active ? 'active' : 'inactive'}`}>
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
                type="button"
              >
                {testing ? '⏳ Testing...' : '🧪 Test Connection'}
              </button>
              <button
                className="btn btn-secondary"
                onClick={handleUpdateCredentials}
                type="button"
              >
                📝 Update Credentials
              </button>
              <button
                className="btn btn-danger"
                onClick={handleDeleteCredentials}
                type="button"
              >
                🗑️ Delete
              </button>
            </div>
          </div>
        )}

        {/* Test Results */}
        {testResult && (
          <div className={`test-results test-results-${testResult.success ? 'success' : 'failure'}`}>
            <h3>Test Results</h3>
            <div className="result-message">
              <p>{testResult.message || testResult.error || 'Test completed'}</p>
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

            {(testResult.error || testResult.test_error) && (
              <div className="error-details">
                <h4>Error Details:</h4>
                <code>{testResult.error || testResult.test_error}</code>
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
              <label htmlFor="digitaxApiKey">Digitax API Key *</label>
              <input
                type="password"
                id="digitaxApiKey"
                name="digitaxApiKey"
                value={formData.digitaxApiKey}
                onChange={handleInputChange}
                placeholder="e.g., api_key_KC4gxhqWqcYlgdpnJdVBoyE34fjAChOn"
                required
              />
              <small>Your unique Digitax API Key (will be encrypted)</small>
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
          max-width: 900px;
          margin: 0 auto;
          padding: 20px;
        }

        .settings-card {
          background: white;
          border-radius: 8px;
          box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
          padding: 30px;
        }

        .settings-card h2 {
          margin-top: 0;
          margin-bottom: 5px;
          color: #1a202c;
          font-size: 28px;
          border-bottom: 3px solid #4e4af9;
          padding-bottom: 12px;
        }

        .subtitle {
          color: #666;
          margin-bottom: 25px;
          font-size: 14px;
        }

        .alert {
          padding: 16px;
          border-radius: 6px;
          margin-bottom: 20px;
          display: flex;
          align-items: flex-start;
          gap: 15px;
          border-left: 4px solid;
        }

        .alert-error {
          background-color: #fef2f2;
          border-left-color: #dc2626;
          color: #991b1b;
        }

        .alert-success {
          background-color: #f0fdf4;
          border-left-color: #16a34a;
          color: #166534;
        }

        .alert-icon {
          font-size: 22px;
          flex-shrink: 0;
          margin-top: 2px;
        }

        .alert-content {
          flex: 1;
        }

        .alert-content h4 {
          margin: 0 0 4px 0;
          font-size: 14px;
          font-weight: 600;
        }

        .alert-content p {
          margin: 0;
          font-size: 13px;
          line-height: 1.5;
        }

        .alert-close {
          background: none;
          border: none;
          font-size: 24px;
          cursor: pointer;
          color: inherit;
          padding: 0;
          margin-left: auto;
          flex-shrink: 0;
          opacity: 0.6;
          transition: opacity 0.2s;
        }

        .alert-close:hover {
          opacity: 1;
        }

        .credentials-display {
          background: #f8fafc;
          padding: 25px;
          border-radius: 6px;
          margin-bottom: 20px;
          border: 1px solid #e2e8f0;
        }

        .credentials-display h3 {
          margin-top: 0;
          margin-bottom: 20px;
          font-size: 16px;
          color: #1a202c;
        }

        .credential-info {
          margin-bottom: 20px;
          background: white;
          padding: 15px;
          border-radius: 4px;
        }

        .info-row {
          display: flex;
          justify-content: space-between;
          padding: 12px 0;
          border-bottom: 1px solid #e2e8f0;
          align-items: center;
        }

        .info-row:last-child {
          border-bottom: none;
        }

        .info-row label {
          font-weight: 600;
          color: #374151;
          min-width: 140px;
        }

        .info-row code {
          font-family: 'Courier New', monospace;
          background: #f3f4f6;
          padding: 4px 8px;
          border-radius: 3px;
          font-size: 13px;
          word-break: break-all;
        }

        .badge {
          display: inline-block;
          padding: 5px 14px;
          border-radius: 20px;
          font-size: 12px;
          font-weight: 600;
          text-transform: uppercase;
        }

        .badge-sandbox {
          background: #fef3c7;
          color: #92400e;
        }

        .badge-production {
          background: #fee2e2;
          color: #991b1b;
        }

        .badge-active {
          background: #dcfce7;
          color: #166534;
        }

        .badge-inactive {
          background: #fee2e2;
          color: #991b1b;
        }

        .action-buttons {
          display: flex;
          gap: 10px;
          margin-top: 15px;
          flex-wrap: wrap;
        }

        .btn {
          padding: 10px 16px;
          border: none;
          border-radius: 6px;
          cursor: pointer;
          font-weight: 600;
          font-size: 13px;
          transition: all 0.3s ease;
          white-space: nowrap;
        }

        .btn-primary {
          background: #4e4af9;
          color: white;
        }

        .btn-primary:hover:not(:disabled) {
          background: #3d39d8;
          box-shadow: 0 4px 12px rgba(78, 74, 249, 0.3);
        }

        .btn-secondary {
          background: #e5e7eb;
          color: #374151;
        }

        .btn-secondary:hover:not(:disabled) {
          background: #d1d5db;
        }

        .btn-danger {
          background: #ef4444;
          color: white;
        }

        .btn-danger:hover:not(:disabled) {
          background: #dc2626;
          box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn:disabled {
          opacity: 0.6;
          cursor: not-allowed;
        }

        .test-results {
          background: #f3f4f6;
          padding: 20px;
          border-radius: 6px;
          margin: 20px 0;
          border-left: 4px solid #4e4af9;
        }

        .test-results-success {
          background: #f0fdf4;
          border-left-color: #16a34a;
        }

        .test-results-failure {
          background: #fef2f2;
          border-left-color: #dc2626;
        }

        .result-message {
          font-weight: 600;
          margin-bottom: 15px;
          color: #1a202c;
        }

        .test-details,
        .error-details {
          background: white;
          padding: 15px;
          border-radius: 4px;
          margin-top: 10px;
        }

        .test-details h4,
        .error-details h4 {
          margin-top: 0;
          margin-bottom: 10px;
          font-size: 13px;
          font-weight: 600;
          color: #374151;
        }

        .test-details ul {
          list-style: none;
          padding: 0;
          margin: 0;
        }

        .test-details li {
          padding: 10px 0;
          border-bottom: 1px solid #e5e7eb;
          font-size: 13px;
        }

        .test-details li:last-child {
          border-bottom: none;
        }

        .test-details strong {
          display: inline-block;
          min-width: 150px;
          color: #374151;
        }

        .error-details code {
          display: block;
          background: #f9fafb;
          padding: 12px;
          border-radius: 4px;
          overflow-x: auto;
          font-size: 12px;
          color: #991b1b;
          border: 1px solid #fee2e2;
        }

        .credential-form {
          background: #f9fafb;
          padding: 25px;
          border-radius: 6px;
          border: 1px solid #e5e7eb;
        }

        .credential-form h3 {
          margin-top: 0;
          margin-bottom: 20px;
          font-size: 16px;
          color: #1a202c;
        }

        .form-group {
          margin-bottom: 20px;
        }

        .form-group label {
          display: block;
          font-weight: 600;
          margin-bottom: 6px;
          color: #374151;
          font-size: 13px;
        }

        .form-group input,
        .form-group select {
          width: 100%;
          padding: 10px 12px;
          border: 1px solid #d1d5db;
          border-radius: 6px;
          font-size: 14px;
          font-family: inherit;
          transition: border-color 0.2s, box-shadow 0.2s;
          box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
          outline: none;
          border-color: #4e4af9;
          box-shadow: 0 0 0 3px rgba(78, 74, 249, 0.1);
        }

        .form-group small {
          display: block;
          color: #6b7280;
          margin-top: 5px;
          font-size: 12px;
        }

        .form-actions {
          margin-top: 25px;
        }

        .info-box {
          background: #eff6ff;
          border-left: 4px solid #3b82f6;
          padding: 16px;
          border-radius: 4px;
          margin-top: 20px;
        }

        .info-box h4 {
          margin-top: 0;
          margin-bottom: 10px;
          color: #1e40af;
          font-size: 13px;
          font-weight: 600;
        }

        .info-box ul {
          margin: 0;
          padding-left: 20px;
        }

        .info-box li {
          margin: 6px 0;
          color: #1e3a8a;
          font-size: 13px;
          line-height: 1.5;
        }

        @media (max-width: 768px) {
          .settings-card {
            padding: 20px;
          }

          .action-buttons {
            flex-direction: column;
          }

          .btn {
            width: 100%;
          }

          .info-row {
            flex-direction: column;
            align-items: flex-start;
          }

          .info-row label {
            margin-bottom: 5px;
          }
        }
      `}</style>
    </div>
  );
}
