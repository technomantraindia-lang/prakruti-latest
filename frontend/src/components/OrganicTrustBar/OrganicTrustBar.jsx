import React from 'react'
import './OrganicTrustBar.css'
import jaivikBharatLogo from '../../assets/images/jaivik-bharat-logo.png'
import indocertLogo from '../../assets/images/indocert-logo.png'

export default function OrganicTrustBar() {
  return (
    <section id="organic-trust-bar" className="organic-trust-bar-section" aria-label="Official Organic Certifications">
      <div className="container">
        <div className="organic-trust-bar-card">

          {/* Left: The Two Official USP Seals */}
          <div className="otb-badges-column">
            <span className="otb-badge-heading">OFFICIAL CERTIFICATION MARKS</span>
            <div className="otb-logos-pair">
              {/* Jaivik Bharat (India Organic) Seal */}
              <div className="otb-seal-item" title="Jaivik Bharat / India Organic (NPOP Certified)">
                <div className="otb-seal-avatar">
                  <img
                    src={jaivikBharatLogo}
                    alt="Jaivik Bharat India Organic Official Certification"
                    className="otb-seal-img"
                    loading="lazy"
                  />
                </div>
                <div className="otb-seal-caption">
                  <span className="otb-seal-name">Jaivik Bharat</span>
                  <span className="otb-seal-sub">India Organic (NPOP)</span>
                </div>
              </div>

              <div className="otb-seal-divider" aria-hidden="true">✦</div>

              {/* INDOCERT Seal */}
              <div className="otb-seal-item" title="INDOCERT Organic Certification Body">
                <div className="otb-seal-avatar">
                  <img
                    src={indocertLogo}
                    alt="INDOCERT Certified Organic"
                    className="otb-seal-img"
                    loading="lazy"
                  />
                </div>
                <div className="otb-seal-caption">
                  <span className="otb-seal-name">INDOCERT</span>
                  <span className="otb-seal-sub">Accredited Organic Body</span>
                </div>
              </div>
            </div>
          </div>

          {/* Middle / Right: USP Headline & Description */}
          <div className="otb-info-column">
            <div className="otb-tag-pill">
              <span className="otb-sparkle">✦</span>
              <span>OUR CORE USP • 100% CERTIFIED ORGANIC</span>
            </div>
            <h2 className="otb-title">
              Certified Organic Farmlands &amp; Verified Purity
            </h2>
            <p className="otb-description">
              Prakruti is officially certified by <strong>INDOCERT</strong> under <strong>Jaivik Bharat (NPOP)</strong> national organic standards. Cultivated on pesticide-free generational soils with zero chemicals, zero GMOs, and complete batch traceability.
            </p>
          </div>

        </div>
      </div>
    </section>
  )
}
