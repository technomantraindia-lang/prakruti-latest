import React, { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import './ComingSoonModal.css'
import prakrutiLogo from '../../assets/images/Prakruti Logo_cc.png'

const ComingSoonModal = ({ isOpen, onClose, onContinueBrowsing }) => {
  const [email, setEmail] = useState('')
  const [subscribed, setSubscribed] = useState(false)

  // Listen for Escape key and lock body scroll when open
  useEffect(() => {
    if (!isOpen) return

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        onClose?.()
      }
    }

    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', handleKeyDown)

    return () => {
      document.body.style.overflow = previousOverflow
      window.removeEventListener('keydown', handleKeyDown)
    }
  }, [isOpen, onClose])

  const handleNotifySubmit = (e) => {
    e.preventDefault()
    if (!email || !email.includes('@')) return
    try {
      const existing = JSON.parse(localStorage.getItem('prakruti_launch_subscribers') || '[]')
      if (!existing.includes(email)) {
        existing.push(email)
        localStorage.setItem('prakruti_launch_subscribers', JSON.stringify(existing))
      }
    } catch {
      // Ignore storage errors
    }
    setSubscribed(true)
    setTimeout(() => {
      setEmail('')
    }, 2000)
  }

  const handleBackdropClick = (e) => {
    if (e.target === e.currentTarget) {
      onClose?.()
    }
  }

  return (
    <AnimatePresence>
      {isOpen && (
        <div
          className="prakruti-cs-backdrop"
          onClick={handleBackdropClick}
          role="dialog"
          aria-modal="true"
          aria-labelledby="prakruti-cs-title"
        >
          <motion.div
            className="prakruti-cs-card"
            initial={{ opacity: 0, scale: 0.92, y: 20 }}
            animate={{ opacity: 1, scale: 1, y: 0 }}
            exit={{ opacity: 0, scale: 0.92, y: 20 }}
            transition={{ duration: 0.32, ease: [0.16, 1, 0.3, 1] }}
          >
            {/* Top decorative glow */}
            <div className="prakruti-cs-glow" aria-hidden="true" />

            {/* Close Button */}
            <button
              type="button"
              className="prakruti-cs-close"
              onClick={onClose}
              aria-label="Close message"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>

            {/* Logo & Punchline Badge */}
            <div className="prakruti-cs-logo-wrap">
              <img 
                src={prakrutiLogo} 
                alt="Prakruti" 
                className="prakruti-cs-logo-img" 
              />
              <p className="prakruti-cs-punchline">
                Crafted Traditionally. Tested Scientifically.
              </p>
              <span className="prakruti-cs-pill">Preview Mode • Store Launching Soon</span>
            </div>

            {/* Main Heading */}
            <h2 id="prakruti-cs-title" className="prakruti-cs-heading">
              Something Pure is Coming Soon!
            </h2>

            {/* Divider with central leaf motif */}
            <div className="prakruti-cs-divider" aria-hidden="true">
              <span className="cs-div-line" />
              <svg className="cs-div-leaf" viewBox="0 0 20 20" fill="currentColor">
                <path d="M10 2C6 6 4 10 4 14a6 6 0 0012 0c0-4-2-8-6-12zm0 14c-1.5 0-3-1.2-3-3 0-2 1.5-4 3-6 1.5 2 3 4 3 6 0 1.8-1.5 3-3 3z" />
              </svg>
              <span className="cs-div-line" />
            </div>

            {/* Core Message requested by user */}
            <div className="prakruti-cs-body">
              <p className="prakruti-cs-intro">
                At Prakruti, we believe in bringing tradition, authenticity and nature closer to your everyday life.
              </p>

              <div className="prakruti-cs-callout">
                <p className="prakruti-cs-launch">
                  We will be launching soon!
                </p>
                <p className="prakruti-cs-trust">
                  Thank you for your interest and trust.
                </p>
              </div>

              <div className="prakruti-cs-signoff">
                <p className="prakruti-cs-motto">
                  Stay Connected. Stay Natural.
                </p>
                <p className="prakruti-cs-team">
                  Team Prakruti
                </p>
              </div>
            </div>

            {/* Interactive Section: Get Notified at Launch */}
            <div className="prakruti-cs-notify-box">
              {subscribed ? (
                <div className="prakruti-cs-notify-success">
                  <span className="prakruti-cs-success-icon">✓</span>
                  <span>Thank you! We will notify you the moment orders open.</span>
                </div>
              ) : (
                <form onSubmit={handleNotifySubmit} className="prakruti-cs-notify-form">
                  <label htmlFor="prakruti-notify-email" className="prakruti-cs-notify-label">
                    Be the first to know when we start accepting orders:
                  </label>
                  <div className="prakruti-cs-input-group">
                    <input
                      id="prakruti-notify-email"
                      type="email"
                      placeholder="Enter your email address"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      required
                    />
                    <button type="submit" className="prakruti-cs-notify-btn">
                      Notify Me
                    </button>
                  </div>
                </form>
              )}
            </div>

            {/* Actions */}
            <div className="prakruti-cs-actions">
              <button
                type="button"
                className="prakruti-cs-continue-btn"
                onClick={() => {
                  onClose?.()
                  onContinueBrowsing?.()
                }}
              >
                <span>Continue Exploring Products</span>
                <span className="cs-arrow">➔</span>
              </button>
            </div>
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  )
}

export default ComingSoonModal
