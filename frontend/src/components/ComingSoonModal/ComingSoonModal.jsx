import React, { useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import './ComingSoonModal.css'
import prakrutiLogo from '../../assets/images/Prakruti Logo_cc.png'

const ComingSoonModal = ({ isOpen, onClose, onContinueBrowsing }) => {
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

            {/* WhatsApp Direct Contact Section */}
            <div className="prakruti-cs-whatsapp-box">
              <div className="prakruti-cs-whatsapp-header">
                <span className="prakruti-cs-wa-badge">
                  <svg className="prakruti-cs-wa-svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91C2.13 13.66 2.59 15.36 3.45 16.86L2.05 22L7.3 20.62C8.75 21.41 10.38 21.83 12.04 21.83C17.5 21.83 21.95 17.38 21.95 11.92C21.95 9.27 20.92 6.78 19.05 4.91C17.18 3.03 14.69 2 12.04 2ZM12.05 3.67C14.25 3.67 16.31 4.53 17.87 6.09C19.42 7.65 20.28 9.72 20.28 11.92C20.28 16.46 16.58 20.15 12.04 20.15C10.56 20.15 9.11 19.76 7.85 19.01L7.55 18.83L4.43 19.65L5.26 16.61L5.06 16.29C4.24 14.99 3.81 13.47 3.81 11.91C3.81 7.37 7.5 3.67 12.05 3.67ZM8.83 7.35C8.63 7.35 8.33 7.42 8.08 7.69C7.83 7.96 7.12 8.63 7.12 9.99C7.12 11.35 8.11 12.66 8.25 12.85C8.39 13.04 10.18 15.81 12.94 17C15.23 17.99 15.7 17.79 16.19 17.74C16.68 17.69 17.79 17.08 18.02 16.44C18.25 15.8 18.25 15.25 18.18 15.13C18.11 15.01 17.94 14.94 17.69 14.82C17.44 14.7 16.21 14.09 15.98 14C15.75 13.91 15.58 13.87 15.41 14.12C15.24 14.37 14.75 14.94 14.6 15.11C14.45 15.28 14.3 15.3 14.05 15.18C13.8 15.06 12.75 14.71 11.51 13.6C10.54 12.74 9.89 11.67 9.77 11.46C9.65 11.25 9.76 11.14 9.88 11.02C9.99 10.91 10.13 10.73 10.27 10.57C10.41 10.41 10.46 10.29 10.55 10.11C10.64 9.93 10.59 9.78 10.53 9.66C10.47 9.54 9.98 8.35 9.77 7.86C9.57 7.38 9.37 7.44 9.21 7.43C9.06 7.42 8.88 7.35 8.83 7.35Z"/>
                  </svg>
                  <span>WhatsApp Orders &amp; Support</span>
                </span>
              </div>
              <p className="prakruti-cs-whatsapp-note">
                "Pls connect with this number via WhatsApp till we come live"
              </p>
              <div className="prakruti-cs-whatsapp-actions">
                <a
                  href="https://wa.me/919898714271?text=Hi%20Prakruti%2C%20I%20want%20to%20place%20an%20order"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="prakruti-cs-wa-btn"
                >
                  <span className="prakruti-cs-wa-num">9898714271</span>
                  <span className="prakruti-cs-wa-sub">Chat on WhatsApp ➔</span>
                </a>
                <a
                  href="https://wa.me/919274683460?text=Hi%20Prakruti%2C%20I%20want%20to%20place%20an%20order"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="prakruti-cs-wa-btn"
                >
                  <span className="prakruti-cs-wa-num">+91 92746 83460</span>
                  <span className="prakruti-cs-wa-sub">Chat on WhatsApp ➔</span>
                </a>
              </div>
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
