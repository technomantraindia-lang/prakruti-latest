import React, { useEffect, useState } from 'react'
import './DoctorConsultation.css'
import { api } from '../../services/api'
import doctorImg from '../../assets/images/docotr iamge.png'
import { FaClipboardCheck } from 'react-icons/fa'

const HEALTH_GOALS = [
  'Gut Health & Acidity',
  'Weight Management',
  'Diabetes & Blood Sugar',
  'Cholesterol & Heart Care',
  'Thyroid & Hormonal Health',
  'Organic Pantry Switch',
  'Immunity & Detox'
]

const createInitialForm = (user = null) => ({
  name: user?.name || '',
  phone: user?.phone || '',
  email: user?.email || '',
  contact_method: 'whatsapp',
  age_range: '26-35',
  dietary_preference: 'vegetarian',
  preferred_time: 'Evening (4 PM - 8 PM)',
  concern: '',
  consent: true
})

export default function DoctorConsultation({ onShopClick, currentUser, onLoginClick }) {
  const [selectedGoals, setSelectedGoals] = useState(['Gut Health & Acidity'])
  const [currentStep, setCurrentStep] = useState('details') // 'details' | 'payment' | 'confirmed'
  const [sessionType, setSessionType] = useState('new_consultation')
  const [paymentMethod, setPaymentMethod] = useState('upi') // 'upi' | 'card' | 'netbanking'
  const [dummyUpiId, setDummyUpiId] = useState('')
  const [dummyCardNumber, setDummyCardNumber] = useState('4532 •••• •••• 8821')
  const [dummyCardExpiry, setDummyCardExpiry] = useState('12/28')
  const [dummyCardCvv, setDummyCardCvv] = useState('789')
  const [dummyBank, setDummyBank] = useState('HDFC Bank')
  const [paymentReceipt, setPaymentReceipt] = useState(null)
  
  const [form, setForm] = useState(() => createInitialForm(currentUser))

  const [isSubmitting, setIsSubmitting] = useState(false)
  const [status, setStatus] = useState({ type: '', message: '', referenceNo: '' })

  useEffect(() => {
    if (!currentUser) return
    setForm((prev) => ({
      ...prev,
      name: currentUser.name || prev.name,
      phone: currentUser.phone || prev.phone,
      email: currentUser.email || prev.email,
    }))
  }, [currentUser])

  const toggleGoal = (goal) => {
    setSelectedGoals((prev) =>
      prev.includes(goal) ? prev.filter((g) => g !== goal) : [...prev, goal]
    )
  }

  const handleChange = (field, value) => {
    setForm((prev) => ({ ...prev, [field]: value }))
    if (status.type === 'error') {
      setStatus({ type: '', message: '', referenceNo: '' })
    }
  }

  // Step 1: Validate patient details & proceed to payment
  const handleProceedToPayment = (e) => {
    e.preventDefault()
    setStatus({ type: '', message: '', referenceNo: '' })

    if (!currentUser) {
      setStatus({ type: 'error', message: 'Please log in before booking a consultation.' })
      onLoginClick?.()
      return
    }

    if (!form.name.trim()) {
      setStatus({ type: 'error', message: 'Please enter your full name.' })
      return
    }

    if (!form.phone.trim() && !form.email.trim()) {
      setStatus({ type: 'error', message: 'Please provide your mobile/WhatsApp number.' })
      return
    }

    if (!form.concern.trim()) {
      setStatus({ type: 'error', message: 'Please describe your dietary concern or wellness goal.' })
      return
    }

    if (!form.consent) {
      setStatus({ type: 'error', message: 'Please check the consent box to proceed.' })
      return
    }

    setCurrentStep('payment')
    const panel = document.querySelector('.doc-form-panel')
    if (panel) {
      panel.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }

  // Step 2: Simulate dummy ₹1500 payment & submit booking
  const handleProcessDummyPayment = async () => {
    setIsSubmitting(true)
    setStatus({ type: '', message: '', referenceNo: '' })

    // Simulate 1.2s payment gateway verification
    await new Promise((resolve) => setTimeout(resolve, 1200))

    const simulatedTxnId = 'TXN_PRK_' + Math.random().toString(36).substring(2, 8).toUpperCase() + Date.now().toString().slice(-4)

    const payload = {
      name: form.name.trim(),
      phone: form.phone.trim(),
      email: form.email.trim() || undefined,
      contact_method: form.contact_method,
      specialist_type: 'Life style Medicine Expert',
      session_type: sessionType,
      payment_status: 'paid_demo',
      payment_gateway: 'demo',
      payment_method: paymentMethod,
      payment_transaction_id: simulatedTxnId,
      payment_amount: 1500,
      consultation_type: 'Life style Medicine Consultation (₹1,500 Pre-Paid)',
      health_goals: selectedGoals.length > 0 ? selectedGoals : undefined,
      age_range: form.age_range,
      dietary_preference: form.dietary_preference,
      preferred_time: form.preferred_time,
      concern: form.concern.trim(),
      consent: true
    }

    try {
      const res = await api.submitConsultation(payload)
      setIsSubmitting(false)

      const refNo = res.data?.reference_no || ('CONS-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-' + Math.random().toString(36).substring(2, 7).toUpperCase())

      setPaymentReceipt({
        amount: '₹1,500',
        txnId: simulatedTxnId,
        date: new Date().toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }),
        paymentMethod: paymentMethod.toUpperCase(),
        referenceNo: refNo,
        patientName: form.name,
        slot: form.preferred_time,
        contact: form.phone || form.email,
        method: form.contact_method,
        sessionType: res.data?.session_type || sessionType,
        sessionNumber: res.data?.session_number || null
      })

      setStatus({
        type: 'success',
        message: `${sessionType === 'follow_up' ? 'Follow-up session' : 'New consultation'} payment of ₹1,500 received and consultation confirmed! Our Life style Medicine Expert will reach out at your chosen time window.`,
        referenceNo: refNo
      })

      setCurrentStep('confirmed')
      setForm(createInitialForm(currentUser))
    } catch (err) {
      setIsSubmitting(false)
      setStatus({
        type: 'error',
        message: 'A network error occurred while submitting. Please try again.'
      })
    }
  }

  const handleReset = () => {
    setStatus({ type: '', message: '', referenceNo: '' })
    setCurrentStep('details')
    setPaymentReceipt(null)
  }

  return (
    <section className="doctor-consultation-section" id="doctor-consultation">
      <div className="doc-bg-glow"></div>
      
      <div className="container doc-container">
        
        {/* Section Header */}
        <div className="doc-header">
          <div className="doc-badge">
            <span className="doc-badge-pulse"></span>
            <span>LIFESTYLE MEDICINE &amp; WELLNESS CARE</span>
          </div>
          <h2 className="doc-main-title">
            Consult Our Life style Medicine Expert
          </h2>
          <p className="doc-main-desc">
            Get a personalized therapeutic plan and expert guidance on transforming your pantry with wholesome, organic staples.
            One dedicated health expert to guide you every step of the way.
          </p>
        </div>

        {/* 2-Column Split: Doctor Profile & Form */}
        <div className="doc-split-layout">
          
          {/* Left Column: Dedicated Doctor Profile & Trust Highlights */}
          <div className="doc-profile-panel">
            
            {/* Doctor Spotlight Card */}
            <div className="doctor-spotlight-card">
              <div
                className="doctor-img-container"
                tabIndex="0"
                aria-label="View Dr. Varsha Shah biography"
              >
                <img 
                  src={doctorImg} 
                  alt="Dr. Varsha Shah"
                  className="doctor-photo"
                />
                <div className="doctor-verified-badge">
                  <span className="verified-check">✓</span> Verified Expert
                </div>
                <div className="doctor-bio-overlay">
                  <strong>Dr. Varsha Shah</strong>
                  <span>DCH, DNB (Pediatrics), MBBS</span>
                  <span>PGDLM (Lifestyle Medicine)</span>
                  <hr />
                  <span>Email: drvarshas@yahoo.com</span>
                  <span>Phone: +91 99090 26338</span>
                  <span>Vadodara, Gujarat, India</span>
                  <span>Date of Birth: 30 October 1977</span>
                  <p>Ex-Professor at SBKS Medical Institute and Research Centre. Consultant at SHARAN and PRAKRUTI HEAL, with a special interest in nutrition, disease reversal, and holistic health.</p>
                  <small>IAP · ISLM · PAN member</small>
                </div>
              </div>

              <div className="doctor-details">
                <div className="doc-role-row">
                  <span className="doc-role-chip">Lifestyle Medicine</span>
                  <span className="doc-exp-badge">12+ Yrs Exp</span>
                </div>
                <h3 className="doctor-name">Dr. Varsha Shah</h3>
                <p className="doctor-qualifications">DCH, DNB (Pediatrics), MBBS · PGDLM (Lifestyle Medicine)</p>
                <p className="doctor-bio">
                  Lifestyle medicine consultant passionate about nutrition, disease reversal, and helping families build healthier everyday routines.
                </p>

                {/* Upfront Consultation Fee Highlight */}
                <div className="doctor-fee-box">
                  <div className="fee-badge-pill">
                    <span className="fee-check">✓</span> Pre-Paid Booking
                  </div>
                  <div className="fee-pricing-row">
                    <div className="fee-amount-group">
                      <span className="fee-amount">₹1,500</span>
                      <span className="fee-cadence">/ session</span>
                    </div>
                    <span className="fee-tag">1-on-1 Consultation</span>
                  </div>
                  <p className="fee-benefit-text">
                    Includes personal health assessment, customized organic meal blueprint & pantry transition guide.
                  </p>
                </div>
              </div>
            </div>

            {/* 3 Core Trust Pillars */}
            <div className="trust-pillars-stack">
              
              <div className="trust-pillar-card">
                <div className="pillar-icon-box icon-confidential">
                  <span className="pillar-icon">🩺</span>
                </div>
                <div className="pillar-content">
                  <h4 className="pillar-title">1-on-1 Confidential</h4>
                  <p className="pillar-desc">Direct WhatsApp / Phone consultation tailored exclusively to your personal health concerns.</p>
                </div>
              </div>

              <div className="trust-pillar-card">
                <div className="pillar-icon-box icon-blueprint">
                  <span className="pillar-icon">📋</span>
                </div>
                <div className="pillar-content">
                  <h4 className="pillar-title">Custom Diet Blueprint</h4>
                  <p className="pillar-desc">Tailored organic daily meal chart incorporating unpolished dals, A2 bilona ghee, and traditional staples.</p>
                </div>
              </div>

              <div className="trust-pillar-card featured-pillar">
                <div className="pillar-icon-box icon-assessment">
                  <span className="pillar-icon"><FaClipboardCheck aria-hidden="true" /></span>
                </div>
                <div className="pillar-content">
                  <div className="pillar-title-row">
                    <h4 className="pillar-title">Personalized Assessment</h4>
                    <span className="complimentary-tag">₹1,500 All-Inclusive</span>
                  </div>
                  <p className="pillar-desc">Comprehensive consultation tailored to your daily wellness, dietary habits, and organic staples.</p>
                </div>
              </div>

            </div>

          </div>

          {/* Right Column: High-End Booking Form / Payment / Confirmation */}
          <div className="doc-form-panel">
            
            {!currentUser ? (
              <div className="doc-login-required-card">
                <div className="doc-login-icon" aria-hidden="true"><FaClipboardCheck /></div>
                <span className="doc-login-eyebrow">PRIVATE WELLNESS CONSULTATION</span>
                <h3 className="doc-login-title">Sign in to book your session</h3>
                <p className="doc-login-text">
                  Your account keeps your consultation history together, pre-fills your contact details, and lets you book every follow-up session securely.
                </p>
                <button type="button" className="doc-primary-btn doc-login-btn" onClick={onLoginClick}>
                  Sign In to Continue <span className="btn-arrow-icon">→</span>
                </button>
                <p className="doc-login-note">A ₹1,500 demo payment is recorded for each confirmed session until the live gateway is connected.</p>
              </div>
            ) : currentStep === 'confirmed' && status.type === 'success' ? (
              /* Step 3: Success / Confirmed Screen */
              <div className="doc-success-screen">
                <div className="doc-success-icon-wrap">
                  <svg className="doc-success-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <span className="doc-success-badge">PAYMENT RECEIVED &amp; CONSULTATION CONFIRMED</span>
                <h3 className="doc-success-title">Your Appointment Is Confirmed!</h3>
                <p className="doc-success-text">
                  Thank you for prioritizing wholesome, clean nutrition. Your consultation fee of <strong>₹1,500</strong> has been successfully received. Our Life style Medicine Expert will connect with you at your chosen time window.
                </p>

                {/* Receipt Box */}
                <div className="payment-receipt-card">
                  <div className="receipt-header">
                    <span className="receipt-title">Consultation Receipt</span>
                    <span className="receipt-paid-badge">₹1,500 PAID</span>
                  </div>
                  <div className="receipt-row">
                    <span className="r-label">Booking Reference:</span>
                    <strong className="r-val highlight">{status.referenceNo}</strong>
                  </div>
                  {paymentReceipt && (
                    <div className="receipt-row">
                      <span className="r-label">Session:</span>
                      <span className="r-val">{paymentReceipt.sessionType === 'follow_up' ? 'Follow-up' : 'New Consultation'}{paymentReceipt.sessionNumber ? ` #${paymentReceipt.sessionNumber}` : ''}</span>
                    </div>
                  )}
                  {paymentReceipt && (
                    <>
                      <div className="receipt-row">
                        <span className="r-label">Transaction ID:</span>
                        <span className="r-val mono">{paymentReceipt.txnId}</span>
                      </div>
                      <div className="receipt-row">
                        <span className="r-label">Payment Method:</span>
                        <span className="r-val">{paymentReceipt.paymentMethod} (Demo Mode)</span>
                      </div>
                      <div className="receipt-row">
                        <span className="r-label">Scheduled Slot:</span>
                        <span className="r-val">{paymentReceipt.slot}</span>
                      </div>
                    </>
                  )}
                </div>

                <div className="doc-next-steps">
                  <h5>What Happens Next?</h5>
                  <div className="step-chain">
                    <div className="step-point">
                      <span className="step-num">1</span>
                      <p><strong>Health History Review:</strong> Our Life style Medicine Expert reviews your wellness goals and dietary profile.</p>
                    </div>
                    <div className="step-point">
                      <span className="step-num">2</span>
                      <p><strong>1-on-1 Session:</strong> We will reach out via WhatsApp / phone to coordinate and conduct your consultation.</p>
                    </div>
                    <div className="step-point">
                      <span className="step-num">3</span>
                      <p><strong>Therapeutic Blueprint:</strong> Receive your customized daily meal chart and organic pantry transition blueprint.</p>
                    </div>
                  </div>
                </div>

                <div className="doc-success-actions">
                  <button type="button" className="btn-book-another" onClick={handleReset}>
                    Book Another Consultation
                  </button>
                  {onShopClick && (
                    <button type="button" className="btn-explore-organic" onClick={onShopClick}>
                      Explore Organic Staples
                    </button>
                  )}
                </div>
              </div>
            ) : currentStep === 'payment' ? (
              /* Step 2: Dummy Payment Checkout (₹1,500) */
              <div className="doc-form-card payment-checkout-card">
                
                <div className="form-card-header">
                  <div className="step-indicator-row">
                    <span className="step-tag active">Step 2 of 2</span>
                    <span className="step-title-hint">Secure Payment Checkout</span>
                  </div>
                  <h3 className="form-card-title">Pay Consultation Charge</h3>
                  <p className="form-card-subtitle">
                    Complete your ₹1,500 consultation fee to finalize and confirm your appointment slot.
                  </p>
                </div>

                {/* Appointment Summary Box */}
                <div className="checkout-summary-card">
                  <div className="summary-header">
                    <span className="summary-title">Appointment Summary</span>
                    <span className="summary-badge">Total: ₹1,500</span>
                  </div>
                  <div className="summary-row">
                    <span className="s-label">Expert:</span>
                    <span className="s-val font-semibold">Life style Medicine Expert</span>
                  </div>
                  <div className="summary-row">
                    <span className="s-label">Booking Type:</span>
                    <span className="s-val">{sessionType === 'follow_up' ? 'Follow-up Session' : 'New Consultation'}</span>
                  </div>
                  <div className="summary-row">
                    <span className="s-label">Patient Name:</span>
                    <span className="s-val">{form.name}</span>
                  </div>
                  <div className="summary-row">
                    <span className="s-label">Contact:</span>
                    <span className="s-val">{form.phone} ({form.contact_method.toUpperCase()})</span>
                  </div>
                  <div className="summary-row">
                    <span className="s-label">Time Window:</span>
                    <span className="s-val">{form.preferred_time}</span>
                  </div>
                  <div className="summary-divider"></div>
                  <div className="summary-total-row">
                    <span>Consultation Charge Due:</span>
                    <span className="summary-total-amount">₹1,500</span>
                  </div>
                </div>

                {/* Dummy Gateway Notice */}
                <div className="dummy-payment-notice">
                  <div className="notice-icon">ℹ️</div>
                  <div className="notice-text">
                    <strong>Demo Payment Mode:</strong> Live payment gateway is simulated for testing. No real money will be debited. Click <em>"Pay ₹1,500 &amp; Confirm Booking"</em> below to simulate a successful payment transaction.
                  </div>
                </div>

                {/* Payment Method Selector */}
                <div className="payment-method-selector">
                  <label className="group-label">
                    <span>Select Payment Method:</span>
                  </label>
                  <div className="payment-tabs-grid">
                    <button
                      type="button"
                      className={`pay-tab-btn ${paymentMethod === 'upi' ? 'active' : ''}`}
                      onClick={() => setPaymentMethod('upi')}
                    >
                      <span className="tab-icon">⚡</span>
                      <span className="tab-name">UPI / QR Code</span>
                    </button>
                    <button
                      type="button"
                      className={`pay-tab-btn ${paymentMethod === 'card' ? 'active' : ''}`}
                      onClick={() => setPaymentMethod('card')}
                    >
                      <span className="tab-icon">💳</span>
                      <span className="tab-name">Card (Debit/Credit)</span>
                    </button>
                    <button
                      type="button"
                      className={`pay-tab-btn ${paymentMethod === 'netbanking' ? 'active' : ''}`}
                      onClick={() => setPaymentMethod('netbanking')}
                    >
                      <span className="tab-icon">🏛️</span>
                      <span className="tab-name">Net Banking</span>
                    </button>
                  </div>
                </div>

                {/* Method Panel 1: UPI */}
                {paymentMethod === 'upi' && (
                  <div className="pay-method-panel upi-panel">
                    <div className="upi-qr-box">
                      <div className="dummy-qr-code">
                        <div className="qr-box-inner">
                          <span className="qr-corner tl"></span>
                          <span className="qr-corner tr"></span>
                          <span className="qr-corner bl"></span>
                          <span className="qr-center-icon">🌿</span>
                        </div>
                        <span className="qr-scan-text">Scan with Google Pay, PhonePe, Paytm, or BHIM</span>
                      </div>
                      <div className="upi-input-wrap">
                        <label htmlFor="upi-vpa">Or enter UPI ID:</label>
                        <input
                          id="upi-vpa"
                          type="text"
                          placeholder="e.g. yourname@okhdfcbank"
                          value={dummyUpiId}
                          onChange={(e) => setDummyUpiId(e.target.value)}
                        />
                      </div>
                    </div>
                  </div>
                )}

                {/* Method Panel 2: Card */}
                {paymentMethod === 'card' && (
                  <div className="pay-method-panel card-panel">
                    <div className="card-mock-row">
                      <label>Card Number</label>
                      <input
                        type="text"
                        value={dummyCardNumber}
                        onChange={(e) => setDummyCardNumber(e.target.value)}
                        placeholder="4532 •••• •••• 8821"
                      />
                    </div>
                    <div className="card-sub-grid">
                      <div>
                        <label>Expiry (MM/YY)</label>
                        <input
                          type="text"
                          value={dummyCardExpiry}
                          onChange={(e) => setDummyCardExpiry(e.target.value)}
                          placeholder="MM/YY"
                        />
                      </div>
                      <div>
                        <label>CVV</label>
                        <input
                          type="password"
                          value={dummyCardCvv}
                          onChange={(e) => setDummyCardCvv(e.target.value)}
                          maxLength="4"
                          placeholder="•••"
                        />
                      </div>
                    </div>
                  </div>
                )}

                {/* Method Panel 3: Net Banking */}
                {paymentMethod === 'netbanking' && (
                  <div className="pay-method-panel netbanking-panel">
                    <label className="bank-select-label">Choose Bank</label>
                    <div className="bank-chips-grid">
                      {['HDFC Bank', 'State Bank of India', 'ICICI Bank', 'Axis Bank', 'Kotak Bank'].map((bank) => (
                        <button
                          key={bank}
                          type="button"
                          className={`bank-chip ${dummyBank === bank ? 'active' : ''}`}
                          onClick={() => setDummyBank(bank)}
                        >
                          {bank}
                        </button>
                      ))}
                    </div>
                  </div>
                )}

                {status.type === 'error' && (
                  <div className="form-error-alert" role="alert">
                    <span className="error-icon">⚠️</span>
                    <span>{status.message}</span>
                  </div>
                )}

                {/* Checkout Action Buttons */}
                <div className="checkout-actions-row">
                  <button
                    type="button"
                    className="btn-back-details"
                    onClick={() => setCurrentStep('details')}
                    disabled={isSubmitting}
                  >
                    ← Modify Details
                  </button>
                  <button
                    type="button"
                    className="btn-pay-confirm"
                    onClick={handleProcessDummyPayment}
                    disabled={isSubmitting}
                  >
                    {isSubmitting ? (
                      <span className="btn-loading">
                        <span className="spinner-dot"></span>
                        Processing ₹1,500 Payment...
                      </span>
                    ) : (
                      <>
                        <span>Pay ₹1,500 &amp; Confirm Booking</span>
                        <span className="btn-arrow-icon">✓</span>
                      </>
                    )}
                  </button>
                </div>

                <div className="form-security-footer">
                  <span>🔒 256-Bit SSL Encrypted Simulated Payment</span>
                  <span className="footer-bullet">•</span>
                  <span>Instant Slot Confirmation</span>
                </div>

              </div>
            ) : (
              /* Step 1: Patient Information & Slot Booking Form */
              <form className="doc-form-card" onSubmit={handleProceedToPayment} noValidate>
                
                <div className="form-card-header">
                  <div className="step-indicator-row">
                    <span className="step-tag">Step 1 of 2</span>
                    <span className="step-title-hint">Patient Details</span>
                  </div>
                  <h3 className="form-card-title">Book Your Consultation</h3>
                  <p className="form-card-subtitle">
                    Share your dietary requirements below to proceed to the ₹1,500 consultation payment &amp; slot booking.
                  </p>
                </div>

                <div className="booking-type-block">
                  <span className="booking-type-label">What are you booking today?</span>
                  <div className="booking-type-options">
                    <button
                      type="button"
                      className={`booking-type-option ${sessionType === 'new_consultation' ? 'active' : ''}`}
                      onClick={() => setSessionType('new_consultation')}
                    >
                      <span className="booking-type-icon" aria-hidden="true"><FaClipboardCheck /></span>
                      <span>
                        <strong>New consultation</strong>
                        <small>Start a fresh wellness plan</small>
                      </span>
                    </button>
                    <button
                      type="button"
                      className={`booking-type-option ${sessionType === 'follow_up' ? 'active' : ''}`}
                      onClick={() => setSessionType('follow_up')}
                    >
                      <span className="booking-type-icon" aria-hidden="true"><FaClipboardCheck /></span>
                      <span>
                        <strong>Follow-up session</strong>
                        <small>Continue an existing plan</small>
                      </span>
                    </button>
                  </div>
                </div>

                <details className="consultation-form-section">
                  <summary>
                    <span><strong>1</strong> Wellness goals</span>
                    <small>Select every area you want to discuss</small>
                  </summary>
                  <div className="form-group-block">
                    <label className="group-label">
                      <span>What is your primary wellness goal?</span>
                      <span className="label-hint">(Select all that apply)</span>
                    </label>
                    <div className="goals-chips-cloud">
                      {HEALTH_GOALS.map((goal) => {
                        const isChecked = selectedGoals.includes(goal)
                        return (
                          <button
                            type="button"
                            key={goal}
                            className={`goal-chip ${isChecked ? 'active' : ''}`}
                            onClick={() => toggleGoal(goal)}
                          >
                            <span className="chip-icon">{isChecked ? '✓' : '+'}</span>
                            <span>{goal}</span>
                          </button>
                        )
                      })}
                    </div>
                  </div>
                </details>

                {/* Input Fields Grid */}
                <details className="consultation-form-section" open>
                  <summary>
                    <span><strong>2</strong> Personal &amp; contact details</span>
                    <small>Your account details are filled automatically</small>
                  </summary>
                <div className="form-fields-grid">
                  
                  <div className="input-group">
                    <label htmlFor="patient-name">
                      Full Name <span className="req">*</span>
                    </label>
                    <input
                      id="patient-name"
                      type="text"
                      placeholder="e.g. Ramesh Patel"
                      value={form.name}
                      onChange={(e) => handleChange('name', e.target.value)}
                      required
                    />
                  </div>

                  <div className="input-group">
                    <label htmlFor="patient-phone">
                      WhatsApp / Mobile <span className="req">*</span>
                    </label>
                    <input
                      id="patient-phone"
                      type="tel"
                      placeholder="e.g. 98765 43210"
                      value={form.phone}
                      onChange={(e) => handleChange('phone', e.target.value)}
                      required
                    />
                  </div>

                  <div className="input-group">
                    <label htmlFor="patient-email">
                      Email Address <span className="opt-tag">(Optional)</span>
                    </label>
                    <input
                      id="patient-email"
                      type="email"
                      placeholder="name@example.com"
                      value={form.email}
                      onChange={(e) => handleChange('email', e.target.value)}
                    />
                  </div>

                  <div className="input-group">
                    <label htmlFor="patient-mode">
                      Consultation Mode <span className="req">*</span>
                    </label>
                    <select
                      id="patient-mode"
                      value={form.contact_method}
                      onChange={(e) => handleChange('contact_method', e.target.value)}
                    >
                      <option value="whatsapp">WhatsApp (Video / Call)</option>
                      <option value="phone">Direct Phone Call</option>
                      <option value="email">Email Detailed Report</option>
                    </select>
                  </div>
                </div>
                </details>

                <details className="consultation-form-section">
                  <summary>
                    <span><strong>3</strong> Wellness profile &amp; timing</span>
                    <small>Help your expert prepare the right plan</small>
                  </summary>
                  <div className="form-fields-grid">
                  <div className="input-group">
                    <label htmlFor="patient-age">Age Range</label>
                    <select
                      id="patient-age"
                      value={form.age_range}
                      onChange={(e) => handleChange('age_range', e.target.value)}
                    >
                      <option value="18-25">18 - 25 Years</option>
                      <option value="26-35">26 - 35 Years</option>
                      <option value="36-50">36 - 50 Years</option>
                      <option value="51-65">51 - 65 Years</option>
                      <option value="65+">65+ Years</option>
                    </select>
                  </div>

                  <div className="input-group">
                    <label htmlFor="patient-diet">Current Diet</label>
                    <select
                      id="patient-diet"
                      value={form.dietary_preference}
                      onChange={(e) => handleChange('dietary_preference', e.target.value)}
                    >
                      <option value="vegetarian">Vegetarian</option>
                      <option value="vegan">Vegan (Plant-based)</option>
                      <option value="jain">Jain Diet</option>
                      <option value="mixed">Mixed Diet</option>
                    </select>
                  </div>

                  <div className="input-group input-group-full">
                    <label htmlFor="patient-time">Convenient Time Window</label>
                    <select
                      id="patient-time"
                      value={form.preferred_time}
                      onChange={(e) => handleChange('preferred_time', e.target.value)}
                    >
                      <option value="Morning (9 AM - 12 PM)">Morning (9:00 AM - 12:00 PM)</option>
                      <option value="Afternoon (12 PM - 4 PM)">Afternoon (12:00 PM - 4:00 PM)</option>
                      <option value="Evening (4 PM - 8 PM)">Evening (4:00 PM - 8:00 PM)</option>
                      <option value="Weekend Slot">Weekend Slot (Saturday / Sunday)</option>
                    </select>
                  </div>
                  </div>
                </details>

                <details className="consultation-form-section">
                  <summary>
                    <span><strong>4</strong> Health concern &amp; meal goals</span>
                    <small>Share the context behind your consultation</small>
                  </summary>
                  <div className="form-fields-grid">
                  <div className="input-group input-group-full">
                    <label htmlFor="patient-concern">
                      Describe Your Health Concern or Meal Goals <span className="req">*</span>
                    </label>
                    <textarea
                      id="patient-concern"
                      rows="3"
                      placeholder="e.g. Facing acidity/bloating after meals, need meal chart for diabetes control, or want help switching to unpolished grains and cold-pressed oils."
                      value={form.concern}
                      onChange={(e) => handleChange('concern', e.target.value)}
                      required
                    ></textarea>
                  </div>
                  </div>
                </details>

                {/* Consent Checkbox */}
                <div className="form-consent-row">
                  <label className="consent-label">
                    <input
                      type="checkbox"
                      checked={form.consent}
                      onChange={(e) => handleChange('consent', e.target.checked)}
                    />
                    <span>
                      I consent to share these details with Prakruti's certified Life style Medicine Expert for the purpose of receiving dietary and lifestyle consultation.
                    </span>
                  </label>
                </div>

                {/* Upfront Consultation Fee Callout */}
                <div className="fee-summary-callout">
                  <div className="fee-callout-left">
                    <span className="fee-callout-label">Consultation Fee</span>
                    <span className="fee-callout-sub">Pre-payment of ₹1,500 required to book appointment</span>
                  </div>
                  <div className="fee-callout-price">₹1,500</div>
                </div>

                {status.type === 'error' && (
                  <div className="form-error-alert" role="alert">
                    <span className="error-icon">⚠️</span>
                    <span>{status.message}</span>
                  </div>
                )}

                {/* Submit Action to Go to Payment Step */}
                <div className="form-submit-row">
                  <button
                    type="submit"
                    className="doc-primary-btn"
                  >
                    <span>Proceed to Pay ₹1,500 &amp; Book Slot</span>
                    <span className="btn-arrow-icon">→</span>
                  </button>

                  <div className="form-security-footer">
                    <span>🔒 100% Confidential &amp; Secure</span>
                    <span className="footer-bullet">•</span>
                    <span>No Spam</span>
                    <span className="footer-bullet">•</span>
                    <span>Life style Medicine Expert</span>
                  </div>
                </div>

              </form>
            )}
          </div>

        </div>

      </div>
    </section>
  )
}
