import React, { useState } from 'react'
import './DoctorConsultation.css'
import { api } from '../../services/api'
import doctorImg from '../../assets/images/nutritionist_doctor.jpg'

const HEALTH_GOALS = [
  'Gut Health & Acidity',
  'Weight Management',
  'Diabetes & Blood Sugar',
  'Cholesterol & Heart Care',
  'Thyroid & Hormonal Health',
  'Organic Pantry Switch',
  'Immunity & Detox'
]

export default function DoctorConsultation({ onShopClick }) {
  const [selectedGoals, setSelectedGoals] = useState(['Gut Health & Acidity'])
  
  const [form, setForm] = useState({
    name: '',
    phone: '',
    email: '',
    contact_method: 'whatsapp',
    age_range: '26-35',
    dietary_preference: 'vegetarian',
    preferred_time: 'Evening (4 PM - 8 PM)',
    concern: '',
    consent: true
  })

  const [isSubmitting, setIsSubmitting] = useState(false)
  const [status, setStatus] = useState({ type: '', message: '', referenceNo: '' })

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

  const handleSubmit = async (e) => {
    e.preventDefault()
    setStatus({ type: '', message: '', referenceNo: '' })

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

    setIsSubmitting(true)

    const payload = {
      name: form.name.trim(),
      phone: form.phone.trim(),
      email: form.email.trim() || undefined,
      contact_method: form.contact_method,
      specialist_type: 'Clinical Nutritionist & Dietitian (M.Sc, CDE)',
      consultation_type: 'Clinical Nutritionist & Dietitian',
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

      if (res.success) {
        setStatus({
          type: 'success',
          message: 'Your consultation request has been confirmed! Our nutritionist will reach out to you as requested.',
          referenceNo: res.data?.reference_no || 'CONS-CONFIRMED'
        })
        setForm({
          name: '',
          phone: '',
          email: '',
          contact_method: 'whatsapp',
          age_range: '26-35',
          dietary_preference: 'vegetarian',
          preferred_time: 'Evening (4 PM - 8 PM)',
          concern: '',
          consent: true
        })
      } else {
        setStatus({
          type: 'error',
          message: res.message || 'Could not submit your request. Please check details and try again.'
        })
      }
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
  }

  return (
    <section className="doctor-consultation-section" id="doctor-consultation">
      <div className="doc-bg-glow"></div>
      
      <div className="container doc-container">
        
        {/* Section Header */}
        <div className="doc-header">
          <div className="doc-badge">
            <span className="doc-badge-pulse"></span>
            <span>CERTIFIED NUTRITION CARE</span>
          </div>
          <h2 className="doc-main-title">
            Talk to Our Clinical Expert
          </h2>
          <p className="doc-main-desc">
            Get personalized dietary plan and expert guidance on transforming your pantry with wholesome, organic staples.
            One dedicated health expert to guide you every step of the way.
          </p>
        </div>

        {/* 2-Column Split: Doctor Profile & Form */}
        <div className="doc-split-layout">
          
          {/* Left Column: Dedicated Doctor Profile & Trust Highlights */}
          <div className="doc-profile-panel">
            
            {/* Doctor / Nutritionist Spotlight Card */}
            <div className="doctor-spotlight-card">
              <div className="doctor-img-container">
                <img 
                  src={doctorImg} 
                  alt="Lifestyle Therapy Expert" 
                  className="doctor-photo"
                />
                <div className="doctor-verified-badge">
                  <span className="verified-check">✓</span> Verified Expert
                </div>
                <div className="doctor-status-pill">
                  <span className="status-live-dot"></span> Available This Week
                </div>
              </div>

              <div className="doctor-details">
                <div className="doc-role-row">
                  <span className="doc-role-chip">Lifestyle Therapy</span>
                  <span className="doc-exp-badge">12+ Yrs Exp</span>
                </div>
                <h3 className="doctor-name">Lifestyle Therapy Expert</h3>
                <p className="doctor-qualifications">M.Sc. Clinical Nutrition & Dietetics • Certified Diabetes Educator</p>
                <p className="doctor-bio">
                  Dedicated clinical nutritionist specializing in metabolic health, gut restoration, therapeutic meal plans, and transitioning family kitchens to pure, unadulterated organic staples.
                </p>
              </div>
            </div>

            {/* 3 Core Trust Pillars Highlighted as requested */}
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
                  <span className="pillar-icon">🌾</span>
                </div>
                <div className="pillar-content">
                  <div className="pillar-title-row">
                    <h4 className="pillar-title">Personalized Assessment</h4>
                    <span className="complimentary-tag">Expert Guided</span>
                  </div>
                  <p className="pillar-desc">Comprehensive consultation tailored to your daily wellness, dietary habits, and organic staples.</p>
                </div>
              </div>

            </div>

          </div>

          {/* Right Column: High-End Booking Form */}
          <div className="doc-form-panel">
            {status.type === 'success' ? (
              <div className="doc-success-screen">
                <div className="doc-success-icon-wrap">
                  <svg className="doc-success-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <span className="doc-success-badge">CONSULTATION CONFIRMED</span>
                <h3 className="doc-success-title">Your Appointment Request Is Confirmed!</h3>
                <p className="doc-success-text">
                  Thank you for prioritizing wholesome, clean nutrition. Our clinical nutritionist will review your details and connect with you at your chosen time window.
                </p>

                <div className="doc-ref-box">
                  <span className="ref-label">Booking Reference Number:</span>
                  <span className="ref-code">{status.referenceNo}</span>
                </div>

                <div className="doc-next-steps">
                  <h5>What Happens Next?</h5>
                  <div className="step-chain">
                    <div className="step-point">
                      <span className="step-num">1</span>
                      <p><strong>Diet History Review:</strong> Our nutritionist evaluates your focus areas, lifestyle, and dietary habits.</p>
                    </div>
                    <div className="step-point">
                      <span className="step-num">2</span>
                      <p><strong>WhatsApp / Call Confirmation:</strong> We'll reach out to coordinate your slot.</p>
                    </div>
                    <div className="step-point">
                      <span className="step-num">3</span>
                      <p><strong>Diet Blueprint Delivery:</strong> Receive your customized daily meal chart and organic pantry guide.</p>
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
            ) : (
              <form className="doc-form-card" onSubmit={handleSubmit} noValidate>
                
                <div className="form-card-header">
                  <div className="form-header-badge">APPOINTMENT</div>
                  <h3 className="form-card-title">Book Your Session</h3>
                  <p className="form-card-subtitle">
                    Share your dietary requirements below to get your personalized diet blueprint.
                  </p>
                </div>

                {/* Focus Areas Multi-Select Chips */}
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

                {/* Input Fields Grid */}
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

                {/* Consent Checkbox */}
                <div className="form-consent-row">
                  <label className="consent-label">
                    <input
                      type="checkbox"
                      checked={form.consent}
                      onChange={(e) => handleChange('consent', e.target.checked)}
                    />
                    <span>
                      I consent to share these details with Prakruti's certified clinical nutritionist for the purpose of receiving dietary and lifestyle consultation.
                    </span>
                  </label>
                </div>

                {status.type === 'error' && (
                  <div className="form-error-alert" role="alert">
                    <span className="error-icon">⚠️</span>
                    <span>{status.message}</span>
                  </div>
                )}

                {/* Submit Action */}
                <div className="form-submit-row">
                  <button
                    type="submit"
                    className="doc-primary-btn"
                    disabled={isSubmitting}
                  >
                    {isSubmitting ? (
                      <span className="btn-loading">
                        <span className="spinner-dot"></span>
                        Confirming Your Slot...
                      </span>
                    ) : (
                      <>
                        <span>Book Session Now</span>
                        <span className="btn-arrow-icon">→</span>
                      </>
                    )}
                  </button>

                  <div className="form-security-footer">
                    <span>🔒 100% Confidential & Secure</span>
                    <span className="footer-bullet">•</span>
                    <span>No Spam</span>
                    <span className="footer-bullet">•</span>
                    <span>Certified Dietitian</span>
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
