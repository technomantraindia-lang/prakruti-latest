import React from 'react';
import './AboutSection.css';
import certificationBadges from '../../data/certificationBadges';

const pillars = [
  {
    step: '01',
    icon: '🌱',
    title: 'Certified Organic Farmlands',
    subtitle: 'Scope Certificate',
    desc: 'Cultivated in pesticide-free soils using non-GMO native seeds nurtured with natural bio-compost across 500+ farmer cooperatives.'
  },
  {
    step: '02',
    icon: '🔬',
    title: 'Laboratory Tested',
    subtitle: 'Zero Chemical Residues',
    desc: 'Every harvest batch is independently screened for 200+ chemical pesticide residues, heavy metals, and adulterants.'
  },
  {
    step: '03',
    icon: '🏺',
    title: 'Heritage Cold-Pressing',
    subtitle: 'Slow Wooden Ghani (<40°C)',
    desc: 'Extracted under 40°C without chemical solvents, retaining raw living enzymes, natural antioxidants, and authentic aromas.'
  },
  {
    step: '04',
    icon: '🛡️',
    title: 'Eco-Fresh Vacuum Packaging',
    subtitle: 'We Provide Vacuum Packaging',
    desc: 'We provide vacuum packaging in food-grade recyclable barrier pouches and glass jars to lock in farm-fresh flavor and eliminate oxygen exposure without preservatives.'
  }
];

const AboutSection = () => {
  return (
    <section className="about-section" id="about">
      <div className="container">
        
        {/* 1. Minimal Header */}
        <div className="trust-minimal-header">
          <span className="trust-eyebrow">TRUST &amp; ACCREDITED STANDARDS</span>
          <h2 className="trust-title">Purity Verified. <em>Trust Certified.</em></h2>
          <p className="trust-subtitle">
            From generational organic farmlands to rigorous laboratory tests, we protect the purity of your food at every step.
          </p>
        </div>

        {/* 2. Minimalist 4-Pillar Grid */}
        <div className="trust-minimal-grid">
          {pillars.map((item) => (
            <div key={item.step} className="trust-minimal-card">
              <div className="t-card-top-row">
                <span className="t-card-icon">{item.icon}</span>
                <span className="t-card-step">{item.step}</span>
              </div>
              <h3 className="t-card-heading">{item.title}</h3>
              <span className="t-card-highlight">{item.subtitle}</span>
              <p className="t-card-text">{item.desc}</p>
            </div>
          ))}
        </div>

        {/* 3. Official Quality Accreditations (Big & Prominent) */}
        <div className="trust-accreditations-wrap">
          <p className="trust-certification-note">
            Responsibly Sourced &amp; Packed at a Certified Organic Facility
          </p>
          <div className="trust-logos-grid">
            {certificationBadges.map((badge) => (
              <div className="trust-logo-card" key={badge.label}>
                <img src={badge.image} alt={badge.label} loading="lazy" />
                <span>{badge.label}</span>
              </div>
            ))}
          </div>

        </div>

      </div>
    </section>
  );
};

export default AboutSection;
