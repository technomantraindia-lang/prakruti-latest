import React from 'react';
import './AboutSection.css';
import certificationBadges from '../../data/certificationBadges';
import laboratoryImage from '../../assets/images/Final packaging/image copy 5.png';
import woodenGhaniImage from '../../assets/images/Final packaging/image copy 6.png';
import vacuumPackagingImage from '../../assets/images/Final packaging/image copy 8.png';
import certifiedOrganicImage from '../../assets/images/Final packaging/image copy 9.png';

const pillars = [
  {
    image: certifiedOrganicImage,
    title: 'Certified Organic Farmlands',
    titleLines: ['Certified Organic', 'Farmlands'],
    subtitle: 'Scope Certificate',
    desc: 'Cultivated in pesticide-free soils using non-GMO native seeds nurtured with natural bio-compost across 500+ farmer cooperatives.'
  },
    {
    image: laboratoryImage,
    title: 'Laboratory Tested',
    titleLines: ['Laboratory', 'Tested'],
    subtitle: 'Zero Chemical Residues',
    desc: 'Every harvest batch is independently screened for 200+ chemical pesticide residues, heavy metals, and adulterants.'
  },
  {
    image: woodenGhaniImage,
    title: 'Heritage Wooden Ghani',
    titleLines: ['Heritage Wooden', 'Ghani'],
    subtitle: 'Slow Wooden Ghani (<40°C)',
    desc: 'Extracted under 40°C without chemical solvents, retaining raw living enzymes, natural antioxidants, and authentic aromas.'
  },
  {
    image: vacuumPackagingImage,
    title: 'Eco-Fresh Vacuum Packaging',
    titleLines: ['Eco-Fresh Vacuum', 'Packaging'],
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
            <div key={item.title} className="trust-minimal-card">
              <div className="t-card-top-row">
                <img className="t-card-image" src={item.image} alt="" width="50" height="50" loading="lazy" />
              </div>
              <h3 className="t-card-heading">
                {item.titleLines.map((line, index) => (
                  <React.Fragment key={line}>
                    {index > 0 ? ' ' : ''}{line}
                  </React.Fragment>
                ))}
              </h3>
              <span className="t-card-highlight">{item.subtitle}</span>
              <p className="t-card-text">{item.desc}</p>
            </div>
          ))}
        </div>

        {/* 3. Official Quality Accreditations (Big & Prominent) */}
        <div className="trust-accreditations-wrap">
          <div className="trust-logos-panel">
            <span className="accred-eyebrow">OUR QUALITY MARKS</span>
            <div className="trust-logos-grid">
              {certificationBadges
                .filter((badge) => badge.label !== 'HACCP Food Safety Certified')
                .map((badge) => (
                  <div className="trust-logo-card" key={badge.label}>
                    <img src={badge.image} alt={badge.label} loading="lazy" />
                    <span>{badge.label}</span>
                  </div>
                ))}
            </div>
          </div>
          <div className="trust-accreditations-copy">
            <span className="accred-eyebrow">A STANDARD WE LIVE BY</span>
            <h3>Responsibly Sourced &amp; Packed at a Certified Organic Facility</h3>
            <p>
              At Prakruti, we believe quality begins with responsible sourcing and careful handling. Our products are sourced with attention to quality, authenticity and traceability. They are packed at a certified organic facility, following appropriate hygiene and quality practices to maintain product integrity. Every step reflects our commitment to responsible practices and consumer trust.
            </p>
            <span className="trust-copy-rule" aria-hidden="true" />
          </div>
        </div>

      </div>
    </section>
  );
};

export default AboutSection;
