import React, { useEffect, useState } from 'react';
import './AuthPage.css';
import { motion } from 'framer-motion';
import { api } from '../../services/api';

// Import background images/assets
import authBgImg from '../../assets/images/first banner.png';
import prakrutiLogo from '../../assets/images/Prakruti Logo_cc.png';

const REMEMBERED_EMAIL_KEY = 'prakruti_remembered_customer_email';

const AuthPage = ({ mode, setMode, onSuccess, resetToken = '', resetEmail = '' }) => {
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);
  const [email, setEmail] = useState(() => resetEmail || localStorage.getItem(REMEMBERED_EMAIL_KEY) || '');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [profession, setProfession] = useState('');
  const [gender, setGender] = useState('');
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [showPolicyModal, setShowPolicyModal] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');
  const [successMsg, setSuccessMsg] = useState('');

  const isRegister = mode === 'register';
  const isForgot = mode === 'forgot-password';
  const isReset = mode === 'reset-password';

  useEffect(() => {
    if (resetEmail) {
      setEmail(resetEmail);
    }
  }, [resetEmail]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrorMsg('');
    setSuccessMsg('');

    try {
      let res;
      if (isForgot) {
        res = await api.forgotPassword(email);
      } else if (isReset) {
        res = await api.resetPassword({
          email,
          token: resetToken,
          password,
          password_confirmation: passwordConfirmation,
        });
      } else if (isRegister) {
        if (!termsAccepted) {
          setIsSubmitting(false);
          setErrorMsg('Please agree to the Terms & Conditions and Return/Breakage/Open Goods Policy to register.');
          return;
        }
        res = await api.register({
          name: fullName,
          email,
          password,
          phone,
          profession,
          gender,
          terms_accepted: termsAccepted,
        });
      } else {
        res = await api.login(email, password, rememberMe);
      }

      setIsSubmitting(false);

      if (res.success) {
        if (!isRegister && !isForgot && !isReset) {
          if (rememberMe) {
            localStorage.setItem(REMEMBERED_EMAIL_KEY, email);
          } else {
            localStorage.removeItem(REMEMBERED_EMAIL_KEY);
          }
        }

        setSuccessMsg(res.message || (isRegister ? 'Account created successfully! Welcome to Prakruti.' : isForgot ? 'Password reset link sent if this email exists.' : isReset ? 'Password reset successfully.' : 'Logged in successfully!'));
        setTimeout(() => {
          setSuccessMsg('');
          if (isForgot) {
            setMode('login');
          } else if (isReset) {
            setMode('login');
          } else if (onSuccess) {
            onSuccess(res.user || res.data?.user);
          }
        }, isForgot || isReset ? 1800 : 1200);
      } else {
        setErrorMsg(res.message || 'Authentication failed. Please check your details.');
      }
    } catch (err) {
      setIsSubmitting(false);
      setErrorMsg('An unexpected error occurred. Please try again.');
    }
  };

  return (
    <div className="auth-page-wrapper">
      <div className="auth-page-container">
        
        {/* Left Side: Editorial botanical panel */}
        <div 
          className="auth-editorial-panel" 
          style={{ backgroundImage: `linear-gradient(to bottom, rgba(19, 44, 21, 0.93), rgba(36, 91, 42, 0.8)), url(${authBgImg})` }}
        >
          <div className="editorial-inner">
            <div className="editorial-top-row">
              <span className="editorial-badge">Pure & Trusted</span>
              <div className="editorial-logo-badge" title="Prakruti - Crafted Traditionally. Tested Scientifically.">
                <img src={prakrutiLogo} alt="Prakruti Logo" className="editorial-header-logo" />
              </div>
            </div>
            <h2 className="editorial-title">Prakruti</h2>
            <p className="editorial-text">
              Crafted Traditionally. Tested Scientifically. Join our community for a healthier, chemical-free lifestyle.
            </p>

            <div className="editorial-bullets">
              <div className="bullet-point">
                <span className="bullet-icon">📦</span>
                <div className="bullet-info">
                  <h4>Seamless Ordering</h4>
                  <p>Track your orders, reorder items with one click, and manage addresses.</p>
                </div>
              </div>
              <div className="bullet-point">
                <span className="bullet-icon">🎁</span>
                <div className="bullet-info">
                  <h4>Exclusive Rewards</h4>
                  <p>Earn points on every purchase and get early access to new seasonal harvests.</p>
                </div>
              </div>
            </div>
            
            <div className="editorial-footer">
              <p>Need support? <a href="#contact">Contact Support</a></p>
            </div>
          </div>
        </div>

        {/* Right Side: Form panel */}
        <div className="auth-form-panel">
          <div className="form-inner">
            
            {/* Header/Switch tabs */}
            <div className="form-header-box">
              <div className="form-brand-header">
                <img src={prakrutiLogo} alt="Prakruti Logo" className="form-brand-logo" />
              </div>
              <h1 className="form-main-title">
                {isForgot ? 'Forgot Password' : isReset ? 'Reset Password' : isRegister ? 'Create Account' : 'Welcome Back'}
              </h1>
              <p className="form-sub-title">
                {isForgot
                  ? 'Enter your customer email and we will send a reset link.'
                  : isReset
                    ? 'Create a new password for your customer account.'
                    : isRegister ? 'Join us and start eating clean today' : 'Sign in to access your wellness dashboard'}
              </p>
              {!isRegister && !isForgot && !isReset && (
                <p className="form-sub-title" style={{ marginTop: 8, fontSize: '0.82rem' }}>
                  Demo customer: customer@example.com / password
                </p>
              )}
              
              {!isForgot && !isReset && (
                <div className="form-tabs-switch">
                <button 
                  className={`tab-btn ${!isRegister ? 'active' : ''}`}
                  onClick={() => setMode('login')}
                  type="button"
                >
                  Login
                </button>
                <button 
                  className={`tab-btn ${isRegister ? 'active' : ''}`}
                  onClick={() => setMode('register')}
                  type="button"
                >
                  Register
                </button>
              </div>
              )}
            </div>

            {/* Success / Error Prompts */}
            {errorMsg && (
              <div className="auth-success-toast" style={{ backgroundColor: '#fff0f0', borderColor: '#ffcdd2', color: '#c62828' }}>
                <span className="toast-icon">⚠️</span>
                <span className="toast-msg">{errorMsg}</span>
              </div>
            )}

            {successMsg && (
              <div className="auth-success-toast">
                <span className="toast-icon">✨</span>
                <span className="toast-msg">{successMsg}</span>
              </div>
            )}

            {/* Auth Form */}
            <form onSubmit={handleSubmit} className="auth-form-body">
              
              {isRegister && (
                <div className="input-group">
                  <label htmlFor="fullName">Full Name</label>
                  <div className="input-wrapper">
                    <span className="input-icon">👤</span>
                    <input 
                      type="text" 
                      id="fullName" 
                      placeholder="Arjun Sharma" 
                      value={fullName}
                      onChange={(e) => setFullName(e.target.value)}
                      required 
                    />
                  </div>
                </div>
              )}

              <div className="input-group">
                <label htmlFor="email">Email Address</label>
                <div className="input-wrapper">
                  <span className="input-icon">✉️</span>
                  <input 
                    type="email" 
                    id="email" 
                    placeholder="you@example.com" 
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required 
                  />
                </div>
              </div>

              {isRegister && (
                <>
                  <div className="input-group">
                    <label htmlFor="phone">Mobile Number *</label>
                    <div className="input-wrapper">
                      <span className="input-icon">📞</span>
                      <input 
                        type="tel" 
                        id="phone" 
                        placeholder="10-digit mobile number" 
                        value={phone}
                        onChange={(e) => setPhone(e.target.value)}
                        required 
                      />
                    </div>
                  </div>

                  <div className="input-group">
                    <label htmlFor="profession">Profession *</label>
                    <div className="input-wrapper">
                      <span className="input-icon">💼</span>
                      <select
                        id="profession"
                        value={profession}
                        onChange={(e) => setProfession(e.target.value)}
                        required
                      >
                        <option value="">-- Select Profession --</option>
                        <option value="Doctor / Healthcare">Doctor / Healthcare Professional</option>
                        <option value="IT / Software / Tech">IT / Software / Technology</option>
                        <option value="Business / Entrepreneur">Business / Entrepreneur</option>
                        <option value="Homemaker">Homemaker / Home Manager</option>
                        <option value="Teacher / Education">Teacher / Professor / Education</option>
                        <option value="Corporate / Private Sector">Corporate / Private Sector</option>
                        <option value="Government / Public Sector">Government / Public Sector</option>
                        <option value="Finance / Banking">Finance / Banking / Accounting</option>
                        <option value="Student">Student</option>
                        <option value="Retired">Retired</option>
                        <option value="Other">Other Profession</option>
                      </select>
                    </div>
                  </div>

                  <div className="input-group">
                    <label htmlFor="gender">Gender *</label>
                    <div className="input-wrapper">
                      <span className="input-icon">⚧️</span>
                      <select
                        id="gender"
                        value={gender}
                        onChange={(e) => setGender(e.target.value)}
                        required
                      >
                        <option value="">-- Select Gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other / Prefer not to say</option>
                      </select>
                    </div>
                  </div>
                </>
              )}

              {!isForgot && (
                <div className="input-group">
                <div className="label-row">
                  <label htmlFor="password">Password</label>
                  {!isRegister && !isReset && (
                    <button type="button" className="forgot-link" onClick={() => setMode('forgot-password')}>
                      Forgot Password?
                    </button>
                  )}
                </div>
                <div className="input-wrapper">
                  <span className="input-icon">🔒</span>
                  <input 
                    type={showPassword ? "text" : "password"} 
                    id="password" 
                    placeholder="••••••••" 
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    required 
                  />
                  <button 
                    type="button" 
                    className="password-toggle"
                    onClick={() => setShowPassword(!showPassword)}
                    aria-label={showPassword ? 'Hide password' : 'Show password'}
                    title={showPassword ? 'Hide password' : 'Show password'}
                  >
                    {showPassword ? '👁️' : '👁️‍🗨️'}
                  </button>
                </div>
                {isRegister && (
                  <small style={{ color: '#4b5563', fontSize: '0.8rem', display: 'block', marginTop: '6px', fontStyle: 'italic' }}>
                    * Password must be at least 8 characters long.
                  </small>
                )}
              </div>
              )}

              {isReset && (
                <div className="input-group">
                  <label htmlFor="password_confirmation">Confirm Password</label>
                  <div className="input-wrapper">
                    <span className="input-icon">🔒</span>
                    <input
                      type={showPasswordConfirmation ? "text" : "password"}
                      id="password_confirmation"
                      placeholder="Confirm new password"
                      value={passwordConfirmation}
                      onChange={(e) => setPasswordConfirmation(e.target.value)}
                      required
                    />
                    <button
                      type="button"
                      className="password-toggle"
                      onClick={() => setShowPasswordConfirmation(!showPasswordConfirmation)}
                      aria-label={showPasswordConfirmation ? 'Hide password' : 'Show password'}
                      title={showPasswordConfirmation ? 'Hide password' : 'Show password'}
                    >
                      {showPasswordConfirmation ? 'Hide' : 'Show'}
                    </button>
                  </div>
                </div>
              )}

              {/* Extra terms or checkbox */}
              {isRegister ? (
                <div style={{ margin: '14px 0' }}>
                  <label className="checkbox-container auth-terms" style={{ display: 'flex', alignItems: 'flex-start', gap: 8, cursor: 'pointer' }}>
                    <input 
                      type="checkbox" 
                      checked={termsAccepted} 
                      onChange={(e) => setTermsAccepted(e.target.checked)}
                      required
                    />
                    <span className="checkbox-checkmark"></span>
                    <span style={{ fontSize: '0.85rem', lineHeight: 1.4 }}>
                      I agree to the{' '}
                      <button 
                        type="button" 
                        onClick={() => setShowPolicyModal(true)}
                        style={{ background: 'none', border: 'none', color: '#2d5a27', fontWeight: 600, textDecoration: 'underline', padding: 0, cursor: 'pointer' }}
                      >
                        Terms &amp; Conditions, Return, Breakage &amp; Opened Goods Policy
                      </button>{' '}
                      as per standard norms. ✅
                    </span>
                  </label>
                  {!termsAccepted && (
                    <small style={{ color: '#d32f2f', fontSize: '0.78rem', display: 'block', marginTop: 4 }}>
                      * You must agree via tick ✅ to register your account.
                    </small>
                  )}
                </div>
              ) : !isForgot && !isReset ? (
                <label className="checkbox-container auth-terms">
                  <input
                    type="checkbox"
                    checked={rememberMe}
                    onChange={() => setRememberMe((prev) => !prev)}
                  />
                  <span className="checkbox-checkmark"></span>
                  Keep me logged in for 30 days
                </label>
              ) : null}

              {/* Submit Button */}
              <button 
                type="submit" 
                className="btn auth-btn-primary" 
                disabled={isSubmitting}
              >
                {isSubmitting ? (
                  <span className="loader-dots">Processing...</span>
                ) : (
                  isForgot ? 'Send Reset Link' : isReset ? 'Reset Password' : isRegister ? 'Create Account Securely' : 'Login Securely'
                )}
              </button>

              {/* Toggle switch text */}
              <p className="auth-switch-prompt">
                {isForgot || isReset ? 'Remembered your password?' : isRegister ? 'Already have an account?' : 'New to Prakruti?'}
                <button 
                  type="button" 
                  className="switch-btn"
                  onClick={() => setMode(isRegister || isForgot || isReset ? 'login' : 'register')}
                >
                  {isRegister || isForgot || isReset ? 'Login here' : 'Register here'}
                </button>
              </p>

            </form>
          </div>
        </div>

      </div>

      {/* Policy Terms & Conditions Modal */}
      {showPolicyModal && (
        <div style={{ position: 'fixed', inset: 0, backgroundColor: 'rgba(0,0,0,0.65)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 9999, padding: 16 }}>
          <div style={{ background: '#fff', maxWidth: 640, width: '100%', maxHeight: '85vh', borderRadius: 14, overflow: 'hidden', display: 'flex', flexDirection: 'column', boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)' }}>
            <div style={{ padding: '18px 24px', borderBottom: '1px solid #e5e7eb', display: 'flex', justifyContent: 'space-between', alignItems: 'center', background: '#f8fafc' }}>
              <h3 style={{ margin: 0, fontSize: '1.15rem', color: '#166534', fontWeight: 700 }}>
                📜 Terms, Return, Breakage &amp; Opened Goods Policy
              </h3>
              <button type="button" onClick={() => setShowPolicyModal(false)} style={{ background: 'none', border: 'none', fontSize: '1.6rem', cursor: 'pointer', color: '#64748b', lineHeight: 1 }}>×</button>
            </div>
            <div style={{ padding: '22px 24px', overflowY: 'auto', fontSize: '0.92rem', lineHeight: 1.6, color: '#334155' }}>
              <div style={{ marginBottom: 18 }}>
                <h4 style={{ fontSize: '1rem', color: '#14532d', marginBottom: 6, fontWeight: 700 }}>1. Return Policy (Standard Consumable Food Norms)</h4>
                <p style={{ margin: 0 }}>
                  Perishable and edible organic grocery products (including cold-pressed oils, bilona ghee, natural flours, pulses, and seeds) are consumable food items under FSSAI consumer protection norms. Any request for return or exchange must be submitted within <strong>48 hours of delivery</strong>.
                </p>
              </div>

              <div style={{ marginBottom: 18 }}>
                <h4 style={{ fontSize: '1rem', color: '#14532d', marginBottom: 6, fontWeight: 700 }}>2. Transit Breakage &amp; Damage Policy</h4>
                <p style={{ margin: 0 }}>
                  Glass jars, bottles, and tin containers are secured with multi-layer bubble wrap and heavy corrugated packing. If any product is broken, leaking, or damaged during transit, please notify customer support within <strong>24 hours of package receipt</strong> with clear photos or unboxing video. We will issue a <strong>100% free immediate replacement or full refund</strong>.
                </p>
              </div>

              <div style={{ marginBottom: 18 }}>
                <h4 style={{ fontSize: '1rem', color: '#14532d', marginBottom: 6, fontWeight: 700 }}>3. Opened Goods &amp; Seal Broken Policy</h4>
                <p style={{ margin: 0 }}>
                  For customer safety, hygiene, and contamination prevention, products once unsealed, opened, or partially consumed cannot be returned or refunded, except in verified cases of manufacturing spoilage or packaging defect verified at delivery.
                </p>
              </div>

              <div style={{ marginBottom: 18 }}>
                <h4 style={{ fontSize: '1rem', color: '#14532d', marginBottom: 6, fontWeight: 700 }}>4. Order Cancellation Policy</h4>
                <p style={{ margin: 0 }}>
                  Orders may be cancelled before warehouse dispatch with instant refund processing. Once dispatched with courier tracking generated, orders cannot be cancelled mid-transit.
                </p>
              </div>

              <div style={{ background: '#f0fdf4', padding: '14px 16px', borderRadius: 8, border: '1px solid #bbf7d0', color: '#166534', fontSize: '0.88rem', fontWeight: 500 }}>
                ✓ By checking the agreement box during registration, you confirm that you have read, understood, and accept these standard customer terms.
              </div>
            </div>
            <div style={{ padding: '14px 24px', borderTop: '1px solid #e5e7eb', background: '#f8fafc', display: 'flex', justifyContent: 'flex-end', gap: 12 }}>
              <button 
                type="button" 
                onClick={() => { setTermsAccepted(true); setShowPolicyModal(false); }} 
                style={{ backgroundColor: '#15803d', borderColor: '#15803d', padding: '10px 22px', borderRadius: 8, color: '#fff', fontWeight: 600, cursor: 'pointer', border: 'none', fontSize: '0.95rem' }}
              >
                I Agree &amp; Accept Policy ✅
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default AuthPage;
