import React, { useState } from 'react'
import { motion } from 'framer-motion'
import './HomeUspHighlights.css'
import doctorImage from '../../assets/images/docotr iamge.png'

export default function HomeUspHighlights({ onNavigateToFamilyPack, onNavigateToConsultation }) {
  const [hoveredCard, setHoveredCard] = useState(null)
  const [mobileExpanded, setMobileExpanded] = useState({ family: false, doctor: false })

  const toggleMobile = (key) => {
    setMobileExpanded((prev) => ({ ...prev, [key]: !prev[key] }))
  }

  return (
    <section className="home-usp-section" aria-label="Personalized Health and Nutrition">
      <div className="container">
        
        {/* Section Header with Meaningful Title based on user requirement */}
        <div className="home-usp-header">
          <div className="home-usp-tag">
            <span className="sparkle">✦</span>
            <span>TAILORED HEALTH &amp; NUTRITION</span>
            <span className="sparkle">✦</span>
          </div>
          <h2 className="home-usp-title">
            Products Tailored to Your Health, <span>Doctor Guidance for Your Diet</span>
          </h2>
        </div>

        {/* 2-Column Responsive Card Grid (Previous Clean UI without banner images) */}
        <div className="home-usp-grid">
          
          {/* Card 1: Family Pack Builder */}
          <motion.div
            className={`home-usp-card family-card ${hoveredCard === 'family' ? 'is-hovered' : ''} ${mobileExpanded.family ? 'is-expanded-mobile' : ''}`}
            onMouseEnter={() => setHoveredCard('family')}
            onMouseLeave={() => setHoveredCard(null)}
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5, ease: [0.25, 1, 0.5, 1] }}
          >
            <div className="usp-card-glass">
              {/* Top Accent Ribbon */}
              <div className="usp-card-top">
                <span className="usp-pill-badge family-pill">
                  <span className="pill-dot"></span>
                  Family Grocery Planning
                </span>
                <span className="usp-meta-label">01 • PURE STAPLES</span>
              </div>

              {/* Main Preview Row */}
              <div className="usp-card-main">
                <div className="usp-icon-thumb family-thumb">
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#2d5a27" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                    <line x1="16.5" y1="9.4" x2="7.55" y2="4.24" />
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                    <line x1="12" y1="22.08" x2="12" y2="12" />
                  </svg>
                </div>
                <div className="usp-main-info">
                  <h3 className="usp-card-title">Smart Family Grocery Planner</h3>
                  <p className="usp-card-tagline">
                    Exact monthly organic staples customized to your household size.
                  </p>
                </div>
              </div>

              {/* Quick Highlight Pills */}
              <div className="usp-chips-row">
                <span className="usp-chip">✓ ICMR-NIN Certified</span>
                <span className="usp-chip">✓ Zero Grocery Waste</span>
                <span className="usp-chip">✓ Doorstep Delivery</span>
              </div>

              {/* Mobile View Toggle Button */}
              <button 
                type="button" 
                className="usp-mobile-toggle-btn"
                onClick={() => toggleMobile('family')}
                aria-expanded={mobileExpanded.family}
              >
                {mobileExpanded.family ? 'Hide Details ▲' : 'View Feature Details ▼'}
              </button>

              {/* Hover / Expand Detail Section */}
              <div className="usp-expand-content">
                <div className="usp-divider-line"></div>
                <h4 className="usp-how-title">How This Feature Helps Your Family:</h4>
                <ul className="usp-steps-list">
                  <li>
                    <span className="step-num">1</span>
                    <div className="step-body">
                      <strong>Tell Us Your Family Size:</strong> Enter the number of adults, growing children, and seniors in your home.
                    </div>
                  </li>
                  <li>
                    <span className="step-num">2</span>
                    <div className="step-body">
                      <strong>Scientific Quantity Calculation:</strong> Uses Indian nutritional standards (ICMR-NIN) to calculate exact kg of unpolished dals, A2 ghee, stone-ground flours, and cold-pressed oils.
                    </div>
                  </li>
                  <li>
                    <span className="step-num">3</span>
                    <div className="step-body">
                      <strong>One-Click Monthly Delivery:</strong> Never run out mid-month. Receive your curated organic pantry bundle delivered fresh to your doorstep.
                    </div>
                  </li>
                </ul>

                <button
                  type="button"
                  className="usp-action-btn family-btn"
                  onClick={onNavigateToFamilyPack}
                >
                  <span>Build Your Monthly Family Pack</span>
                  <span className="btn-arrow">➔</span>
                </button>
              </div>

            </div>
          </motion.div>

          {/* Card 2: Doctor Consultation */}
          <motion.div
            className={`home-usp-card doctor-card ${hoveredCard === 'doctor' ? 'is-hovered' : ''} ${mobileExpanded.doctor ? 'is-expanded-mobile' : ''}`}
            onMouseEnter={() => setHoveredCard('doctor')}
            onMouseLeave={() => setHoveredCard(null)}
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5, delay: 0.1, ease: [0.25, 1, 0.5, 1] }}
          >
            <div className="usp-card-glass">
              {/* Top Accent Ribbon */}
              <div className="usp-card-top">
                <span className="usp-pill-badge doctor-pill">
                  <span className="pill-dot"></span>
                  1-on-1 Doctor &amp; Nutritionist
                </span>
                <span className="usp-meta-label">02 • CLINICAL CARE</span>
              </div>

              {/* Main Preview Row */}
              <div className="usp-card-main">
                <div
                  className="doctor-profile-trigger"
                  tabIndex="0"
                  aria-label="View Dr. Varsha Shah profile"
                >
                  <div className="usp-icon-thumb doctor-thumb">
                    <img src={doctorImage} alt="Dr. Varsha Shah" className="doctor-profile-image" />
                    <span className="doctor-profile-hint">View bio</span>
                  </div>
                  <div className="doctor-bio-panel">
                    <div className="doctor-bio-heading">
                      <img src={doctorImage} alt="" className="doctor-bio-avatar" />
                      <div>
                        <strong>Dr. Varsha Shah</strong>
                        <span>DCH, DNB (Pediatrics), MBBS</span>
                        <span>PGDLM (Lifestyle Medicine)</span>
                      </div>
                    </div>
                    <div className="doctor-bio-contact">
                      <span>Email: drvarshas@yahoo.com</span>
                      <span>Phone: +91 99090 26338</span>
                      <span>Vadodara, Gujarat, India</span>
                      <span>Date of Birth: 30 October 1977</span>
                    </div>
                    <p>
                      Ex-Professor at SBKS Medical Institute and Research Centre. Medical Consultant at SHARAN and PRAKRUTI HEAL, with a special interest in lifestyle medicine, nutrition, disease reversal, and holistic health.
                    </p>
                    <small>IAP · ISLM · PAN member</small>
                  </div>
                </div>
                <div className="usp-main-info">
                  <h3 className="usp-card-title">Lifestyle &amp; Doctor Consultation</h3>
                  <p className="usp-card-tagline">
                    Dedicated clinical nutritionist &amp; lifestyle doctor for metabolic health, gut restoration &amp; organic diet.
                  </p>
                </div>
              </div>

              {/* Quick Highlight Pills */}
              <div className="usp-chips-row">
                <span className="usp-chip">✓ Clinical Nutritionist</span>
                <span className="usp-chip">✓ Food as Medicine</span>
                <span className="usp-chip">✓ 12+ Yrs Experience</span>
              </div>

              {/* Mobile View Toggle Button */}
              <button 
                type="button" 
                className="usp-mobile-toggle-btn"
                onClick={() => toggleMobile('doctor')}
                aria-expanded={mobileExpanded.doctor}
              >
                {mobileExpanded.doctor ? 'Hide Details ▲' : 'View Feature Details ▼'}
              </button>

              {/* Hover / Expand Detail Section */}
              <div className="usp-expand-content">
                <div className="usp-divider-line"></div>
                <h4 className="usp-how-title">How This Feature Helps Your Health:</h4>
                <ul className="usp-steps-list">
                  <li>
                    <span className="step-num">1</span>
                    <div className="step-body">
                      <strong>Personal Health &amp; Metabolic Assessment:</strong> In-depth 1-on-1 consultation analyzing your gut health, metabolic goals, and daily dietary lifestyle.
                    </div>
                  </li>
                  <li>
                    <span className="step-num">2</span>
                    <div className="step-body">
                      <strong>Customized Organic Meal Blueprint:</strong> Tailored dietary plan focused on gut restoration, acidity relief, blood sugar, and natural weight balance.
                    </div>
                  </li>
                  <li>
                    <span className="step-num">3</span>
                    <div className="step-body">
                      <strong>Pantry Transition Guide:</strong> Step-by-step guidance to switch your kitchen from processed foods to wholesome, unadulterated organic staples.
                    </div>
                  </li>
                </ul>

                <button
                  type="button"
                  className="usp-action-btn doctor-btn"
                  onClick={onNavigateToConsultation}
                >
                  <span>Book Consultation Session</span>
                  <span className="btn-arrow">➔</span>
                </button>
              </div>

            </div>
          </motion.div>

        </div>

      </div>
    </section>
  )
}
