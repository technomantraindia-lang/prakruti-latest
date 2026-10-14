import React, { useEffect, useRef, useState } from 'react';
import './AboutPage.css';

// Import Assets
import aboutPurityImg from '../../assets/images/NEWST ABOT.png';
import seedsImg from '../../assets/images/seeds.png';
import gheeImg from '../../assets/images/bestseller_gir_ghee.jpg';
import packagingImg from '../../assets/images/packge.png';
import spicesImg from '../../assets/images/spices.png';
import aboutUsBanner from '../../assets/images/packaging/about us aiamge .png';
import sectionBg from '../../assets/images/SECTION .png';

// Certification Badges
import certificationBadges from '../../data/certificationBadges';

const FAQ_ITEMS = [
  {
    q: "How does Prakruti ensure 100% chemical-free purity?",
    a: "Every single crop is cultivated in certified pesticide-free organic soils without synthetic fertilizers or chemical sprays. Each harvested batch is sent to certified accredited laboratories to test for over 200+ chemical pesticide residues, heavy metals, and adulterants before it is packaged."
  },
  {
    q: "Where do you source your organic staples from?",
    a: "We source directly from generational farmer cooperatives across India's most fertile agricultural regions. Our A2 Gir Cow Ghee comes from Saurashtra & Gir pastures in Gujarat, our cold-pressed mustard and sesame seeds from Rajasthan, high-fiber Spelt & Emmer wheat from Madhya Pradesh, and Salem turmeric from Tamil Nadu."
  },
  {
    q: "What makes your traditional processing different from commercial brands?",
    a: "Commercial brands use chemical solvents, high temperatures exceeding 180°C, and synthetic bleaching agents. Prakruti uses time-honored slow wooden Ghani cold-pressing (kept strictly below 40°C) and traditional stone-milling. This preserves living enzymes, natural dietary fibers, antioxidants, and original aromas intact."
  },
  {
    q: "What certifications do Prakruti products hold?",
    a: "Our products are certified by Jaivik Bharat (FSSAI), USDA Organic, and packaged in ISO 22000 certified clean-room facilities."
  },
  {
    q: "How does your eco-friendly packaging protect freshness?",
    a: "We provide vacuum packaging that helps protect freshness by eliminating oxygen exposure, preventing moisture ingress, and preserving the natural taste, aroma, and quality of every staple."
  }
];

const JOURNEY_STEPS = [
  {
    step: "01",
    title: "Native Heirloom Sowing",
    desc: "We preserve non-GMO native seed varieties sown in nutrient-rich organic soils nourished with bio-compost and green manure.",
    image: seedsImg,
    proof: "Non-GMO native seeds",
    icon: "🌱"
  },
  {
    step: "02",
    title: "Generational Harvest",
    desc: "Crops are harvested at peak solar ripeness by experienced farming families to preserve vital micronutrients and flavor.",
    image: spicesImg,
    proof: "Peak-ripeness harvesting",
    icon: "🧑‍🌾"
  },
  {
    step: "03",
    title: "Traditional Slow Processing",
    desc: "Extracted via slow wooden Ghani cold-pressing and slow stone-grinding without high heat, chemical solvents, or synthetic polish.",
    image: gheeImg,
    proof: "SLOW PROCESSING BELOW 45°C",
    icon: "🪵"
  },
  {
    step: "04",
    title: "Purity Testing",
    desc: "Every harvest batch is screened for pesticide residues, heavy metals, and adulterants before it can reach our shelves.",
    image: spicesImg,
    proof: "200+ residue checks",
    icon: "🔬"
  },
  {
    step: "05",
    title: "Eco-Fresh Sealed Delivery",
    desc: "Triple gravity-cleaned, batch-tested and vacuum-sealed in food-grade recyclable packaging for freshness at your doorstep.",
    image: packagingImg,
    proof: "We provide vacuum packaging",
    icon: "📦"
  }
];

const AboutPage = ({ onShopClick }) => {
  const [openFaqIdx, setOpenFaqIdx] = useState(null);
  const [processStarted, setProcessStarted] = useState(false);
  const [processProgress, setProcessProgress] = useState(0);
  const [processVisibleStep, setProcessVisibleStep] = useState(-1);
  const [activeJourneyStep, setActiveJourneyStep] = useState(0);
  const [hoveredStep, setHoveredStep] = useState(null);
  const processSectionRef = useRef(null);

  useEffect(() => {
    const section = processSectionRef.current;
    if (!section) return undefined;
    if (typeof IntersectionObserver === 'undefined') {
      setProcessStarted(true);
      return undefined;
    }

    const observer = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) {
        setProcessStarted(true);
        observer.disconnect();
      }
    }, { threshold: 0.24 });

    observer.observe(section);

    return () => observer.disconnect();
  }, []);

  useEffect(() => {
    if (!processStarted) return undefined;

    const timers = [];
    JOURNEY_STEPS.forEach((_, index) => {
      const delay = index * 850;
      timers.push(window.setTimeout(() => setProcessProgress(index + 1), delay));
      timers.push(window.setTimeout(() => setProcessVisibleStep(index), delay + 350));
    });

    return () => timers.forEach((timer) => window.clearTimeout(timer));
  }, [processStarted]);

  const activeLevel = hoveredStep !== null ? hoveredStep + 1 : processProgress;

  const toggleFaq = (idx) => {
    setOpenFaqIdx(prev => prev === idx ? null : idx);
  };

  return (
    <div className="about-page">
      
      {/* 1. Header Hero Banner */}
      <section className="about-hero" style={{ backgroundImage: `url(${aboutUsBanner})` }}>
        {/* Clean Graphic Banner */}
      </section>

      {/* 2. Our Story Section */}
      <section className="about-story-section">
        <div className="container">
          <div className="story-grid">
            
            {/* Left Image Frame */}
            <div className="story-image-col">
              <div className="story-image-wrap">
                <img src={aboutPurityImg} alt="Organic farming, traditional processing, trusted sourcing, and laboratory testing" className="story-main-img" />
                <div className="story-image-floating-badge">
                  <span className="badge-icon">🌾</span>
                  <div className="badge-text">
                    <strong>Organic Origins</strong>
                    <span>Direct Farm Traceable</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Right Story Narrative */}
            <div className="story-text-col">
              <span className="section-eyebrow">OUR ORIGINS &amp; PHILOSOPHY</span>
              <h2 className="story-heading">Pure by Nature. Honest by Choice.</h2>
              
              <p className="story-paragraph">
                In a world crowded with ultra-processed foods, artificial preservatives, and chemically polished grains, 
                <strong> Prakruti</strong> was born from a fundamental return to basics: food should heal and energize, never harm.
              </p>
              
              <p className="story-paragraph">
                What began as a collaborative mission with traditional farmer cooperatives in Gujarat and Madhya Pradesh 
                has evolved into India's trusted daily organic staple brand. We work closely with generational farming families, 
                championing soil regeneration, biodiversity, and clean heritage processing techniques.
              </p>

              <div className="story-highlights-grid">
                <div className="story-hl-card">
                  <span className="hl-icon">🌱</span>
                  <div>
                    <h4>Chemical-Free Soil</h4>
                    <p>Zero synthetic pesticides, non-GMO heirloom seeds.</p>
                  </div>
                </div>
                <div className="story-hl-card">
                  <span className="hl-icon">🏺</span>
                  <div>
                    <h4>Traditional Slow Cold-Press</h4>
                    <p>Wooden Ghani extraction below 40°C to keep enzymes intact.</p>
                  </div>
                </div>
                <div className="story-hl-card">
                  <span className="hl-icon">🤝</span>
                  <div>
                    <h4>Direct Farmer Trade</h4>
                    <p>Fair compensation empowering 500+ rural farm families.</p>
                  </div>
                </div>
                <div className="story-hl-card">
                  <span className="hl-icon">🔬</span>
                  <div>
                    <h4>NABL Lab Verified</h4>
                    <p>Every single batch certified for purity and safety.</p>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* 3. Mission & Vision */}
      <section className="about-mission-section">
        <div className="container">
          <div className="mission-grid">
            <div className="mission-card mission-card-primary">
              <div className="mission-icon-box">🎯</div>
              <span className="mission-tag">PURPOSE &amp; DRIVE</span>
              <h3 className="mission-title">Our Mission</h3>
              <p className="mission-text">
                To make unpolished, nutrient-dense, certified organic daily staples accessible to every household across India, 
                while empowering rural smallholder farming communities with fair, sustainable livelihoods and preserving ecological balance.
              </p>
            </div>
            
            <div className="mission-card mission-card-secondary">
              <div className="mission-icon-box">👁️</div>
              <span className="mission-tag">LONG-TERM ASPIRATION</span>
              <h3 className="mission-title">Our Vision</h3>
              <p className="mission-text">
                To be India's most trusted natural nutrition brand, leading a national transformation toward chemical-free living, 
                transparent farm traceability, and ancient Vedic wisdom backed by rigorous modern food science.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* 4. Our 5 Core Values */}
      <section className="about-values-section">
        <div className="container">
          <div className="section-header-centered">
            <span className="section-eyebrow">OUR GUIDING PRINCIPLES</span>
            <h2 className="section-heading">The 5 Pillars of Prakruti</h2>
            <p className="section-desc">The core convictions that guide our farming partnerships, processing methods, and customer commitment</p>
          </div>

          <div className="values-grid">
            <div className="value-card">
              <div className="val-icon-wrap">🌱</div>
              <h4 className="val-title">Pure &amp; Natural</h4>
              <p className="val-desc">Zero chemical pesticides, synthetic polish, artificial colors, or chemical preservatives.</p>
            </div>
            
            <div className="value-card">
              <div className="val-icon-wrap">🚜</div>
              <h4 className="val-title">Direct Farm Sourcing</h4>
              <p className="val-desc">Fair, ethical partnerships directly with verified organic farming cooperatives.</p>
            </div>
            
            <div className="value-card">
              <div className="val-icon-wrap">🏺</div>
              <h4 className="val-title">Time-Honored Wisdom</h4>
              <p className="val-desc">Wooden Ghani cold-pressing, Bilona ghee churning, and slow stone grinding.</p>
            </div>
            
            <div className="value-card">
              <div className="val-icon-wrap">🔬</div>
              <h4 className="val-title">Scientific Purity</h4>
              <p className="val-desc">Triple-stage certified lab testing for heavy metals, moisture, and zero adulteration.</p>
            </div>
            
            <div className="value-card">
              <div className="val-icon-wrap">👪</div>
              <h4 className="val-title">Family Health First</h4>
              <p className="val-desc">We only harvest and pack what we are completely confident serving our own children.</p>
            </div>
          </div>
        </div>
      </section>

      {/* 5. From Source to Shelf Journey Timeline */}
      <section className="about-journey-section">
        <div className="container">
          <div className="section-header-centered">
            <span className="section-eyebrow">OUR PROCESS</span>
            <h2 className="section-heading">From Sacred Soil to Kitchen Shelf</h2>
            <p className="section-desc">A transparent five-stage journey ensuring uncompromised nutritional integrity</p>
          </div>

          <div className={`journey-path-experience ${processStarted ? 'process-started' : ''}`} ref={processSectionRef}>
            <svg className="journey-path-svg" viewBox="0 0 1000 720" preserveAspectRatio="none" aria-hidden="true">
              <defs>
                <filter id="journey-path-glow" x="-30%" y="-30%" width="160%" height="160%">
                  <feGaussianBlur stdDeviation="3.5" result="blur" />
                  <feMerge>
                    <feMergeNode in="blur" />
                    <feMergeNode in="SourceGraphic" />
                  </feMerge>
                </filter>
              </defs>

              {/* Static background guide track */}
              <path
                className="journey-path-base"
                d="M 250 115 H 750 V 355 H 250 V 470 C 250 595, 360 595, 500 595"
              />

              {/* Dynamic animated glow segments connecting stages 01 -> 02 -> 03 -> 04 -> 05 */}
              {[
                { d: 'M 250 115 H 750', key: 'seg-1' },
                { d: 'M 750 115 V 355', key: 'seg-2' },
                { d: 'M 750 355 H 250', key: 'seg-3' },
                { d: 'M 250 355 V 470 C 250 595, 360 595, 500 595', key: 'seg-4' }
              ].map((seg, index) => (
                <path
                  key={seg.key}
                  className={`journey-path-segment ${activeLevel > index + 1 ? 'revealed' : ''} ${hoveredStep === index + 1 ? 'segment-hovered' : ''}`}
                  d={seg.d}
                />
              ))}

              {/* Stage Node Milestones */}
              {[
                { cx: 250, cy: 115 },
                { cx: 750, cy: 115 },
                { cx: 750, cy: 355 },
                { cx: 250, cy: 355 },
                { cx: 500, cy: 595 }
              ].map((pt, index) => (
                <g key={`node-${index}`}>
                  <circle
                    className={`journey-path-node-svg ${activeLevel > index ? 'complete' : ''} ${activeLevel === index + 1 ? 'current' : ''}`}
                    cx={pt.cx}
                    cy={pt.cy}
                    r="7"
                  />
                  {(activeLevel === index + 1 || hoveredStep === index) && (
                    <circle
                      className="journey-path-node-pulse"
                      cx={pt.cx}
                      cy={pt.cy}
                      r="14"
                    />
                  )}
                </g>
              ))}
            </svg>

            {JOURNEY_STEPS.map((stepItem, idx) => (
              <article
                key={stepItem.step}
                className={`journey-path-card journey-path-card-${idx + 1} ${(processVisibleStep >= idx || hoveredStep !== null) ? 'revealed' : ''} ${hoveredStep === idx ? 'hovered' : ''} ${activeJourneyStep === idx ? 'active' : ''}`}
                style={{ '--card-delay': `${idx * 110}ms` }}
                onMouseEnter={() => setHoveredStep(idx)}
                onMouseLeave={() => setHoveredStep(null)}
                onClick={() => { setActiveJourneyStep(idx); setHoveredStep(idx); }}
                tabIndex={0}
                role="button"
                aria-label={`Stage ${stepItem.step}: ${stepItem.title}`}
              >
                <span className="journey-path-card-connector" aria-hidden="true" />
                <span className="journey-path-node" aria-hidden="true" />
                <div className="journey-path-card-topline">
                  <span className="journey-path-index">{stepItem.step}</span>
                  <span className="journey-path-icon">{stepItem.icon}</span>
                </div>
                <h3 className="journey-path-title">{stepItem.title}</h3>
                <p className="journey-path-desc">{stepItem.desc}</p>
                <span className="journey-path-proof">{stepItem.proof}</span>
              </article>
            ))}
          </div>

          <div className="journey-experience">
            <div className="journey-route" aria-label="Four stages from soil to shelf">
              <div className="journey-route-line" aria-hidden="true">
                <span style={{ width: `${(activeJourneyStep / (JOURNEY_STEPS.length - 1)) * 100}%` }} />
              </div>
              <div className="journey-route-steps">
                {JOURNEY_STEPS.map((stepItem, idx) => (
                  <button
                    key={stepItem.step}
                    type="button"
                    className={`journey-route-step ${activeJourneyStep === idx ? 'active' : ''} ${activeJourneyStep > idx ? 'complete' : ''}`}
                    onClick={() => setActiveJourneyStep(idx)}
                    aria-label={`Show stage ${stepItem.step}: ${stepItem.title}`}
                    aria-current={activeJourneyStep === idx ? 'step' : undefined}
                  >
                    <span className="journey-route-node"><span>{stepItem.icon}</span></span>
                    <span className="journey-route-label">
                      <small>STAGE {stepItem.step}</small>
                      <strong>{stepItem.title}</strong>
                    </span>
                  </button>
                ))}
              </div>
            </div>

            <div className="journey-stage-layout">
              <div className="journey-stage-visual">
                <div className="journey-stage-image-shell">
                  <img
                    key={JOURNEY_STEPS[activeJourneyStep].image}
                    src={JOURNEY_STEPS[activeJourneyStep].image}
                    alt={JOURNEY_STEPS[activeJourneyStep].title}
                    className="journey-stage-image"
                  />
                  <div className="journey-stage-image-shade" />
                  <div className="journey-stage-number">
                    <span>STAGE</span>
                    <strong>{JOURNEY_STEPS[activeJourneyStep].step}</strong>
                  </div>
                  <div className="journey-stage-image-caption">Prakruti, from the ground up</div>
                </div>
              </div>

              <div className="journey-stage-copy" key={activeJourneyStep}>
                <span className="journey-stage-kicker">THE PRAKRUTI METHOD · {JOURNEY_STEPS[activeJourneyStep].step} / 04</span>
                <div className="journey-stage-icon">{JOURNEY_STEPS[activeJourneyStep].icon}</div>
                <h3>{JOURNEY_STEPS[activeJourneyStep].title}</h3>
                <p>{JOURNEY_STEPS[activeJourneyStep].desc}</p>
                <div className="journey-stage-proof">
                  <span className="journey-proof-dot" />
                  <strong>{JOURNEY_STEPS[activeJourneyStep].proof}</strong>
                </div>
                <div className="journey-stage-controls" aria-label="Process navigation">
                  <button
                    type="button"
                    onClick={() => setActiveJourneyStep((activeJourneyStep + JOURNEY_STEPS.length - 1) % JOURNEY_STEPS.length)}
                    aria-label="Previous process stage"
                  >
                    &larr;
                  </button>
                  <span>Explore the journey</span>
                  <button
                    type="button"
                    onClick={() => setActiveJourneyStep((activeJourneyStep + 1) % JOURNEY_STEPS.length)}
                    aria-label="Next process stage"
                  >
                    &rarr;
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div className="journey-grid">
            {JOURNEY_STEPS.map((stepItem, idx) => (
              <div key={idx} className="journey-card">
                <div className="journey-step-badge">{stepItem.step}</div>
                <div className="journey-icon">{stepItem.icon}</div>
                <h3 className="journey-title">{stepItem.title}</h3>
                <p className="journey-desc">{stepItem.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 6. Purity Certifications Strip */}
      <section className="about-certifications-section">
        <div className="container">
          <div className="certifications-shell">
            <div className="cert-header">
              <span className="section-eyebrow">ACCREDITED STANDARDS</span>
              <h3 className="cert-title">Certified for Purity &amp; Quality</h3>
              <p className="certification-note">
                Responsibly Sourced &amp; Packed at a Certified Organic Facility
              </p>
            </div>

            <div className="cert-badges-grid">
              {certificationBadges.map((badge) => (
                <div className="cert-badge-box" key={badge.label}>
                  <img src={badge.image} alt={badge.label} />
                  <span>{badge.label}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* 7. Why Families Choose Prakruti Feature CTA */}
      <section className="about-promo-section">
        <div className="container">
          <div className="about-promo-grid">
            
            <div className="about-promo-copy">
              <span className="promo-badge">THE PRAKRUTI PROMISE</span>
              <h2 className="promo-heading">Every Product.<br />Every Promise.</h2>
              
              <ul className="promo-checklist">
                <li>
                  <span className="check-box">✓</span>
                  <div>
                    <strong>Unpolished &amp; Chemical Free</strong>
                    <p>No synthetic polish, toxic fumigants, or artificial colors.</p>
                  </div>
                </li>
                <li>
                  <span className="check-box">✓</span>
                  <div>
                    <strong>We Provide Vacuum Packaging</strong>
                    <p>Tamper-evident food-grade pouches with vacuum packaging to preserve peak freshness and natural aroma.</p>
                  </div>
                </li>
                <li>
                  <span className="check-box">✓</span>
                  <div>
                    <strong>Trusted by 10,000+ Indian Families</strong>
                    <p>Consistent 4.9★ rating for taste, freshness, and purity.</p>
                  </div>
                </li>
              </ul>

              <button type="button" className="promo-cta-btn" onClick={onShopClick}>
                Explore Our Pure Organic Staples →
              </button>
            </div>

            <div className="about-promo-visual">
              <div className="promo-img-container">
                <img src={seedsImg} alt="Organic Seeds and Natural Spices" className="promo-main-img" />
                <div className="promo-floating-stat">
                  <span className="p-stat-num">Pure</span>
                  <span className="p-stat-text">Chemical Free Guarantee</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* 8. FAQs Accordion */}
      <section className="about-faq-section">
        <div className="container">
          <div className="section-header-centered">
            <span className="section-eyebrow">TRANSPARENCY &amp; TRUST</span>
            <h2 className="section-heading">Frequently Asked Questions</h2>
            <p className="section-desc">Clear answers about our organic sourcing, purity guarantees, and processing standards</p>
          </div>

          <div className="faq-accordion-list">
            {FAQ_ITEMS.map((item, idx) => {
              const isOpen = openFaqIdx === idx;
              return (
                <div key={idx} className={`faq-card ${isOpen ? 'open' : ''}`}>
                  <button 
                    type="button" 
                    className="faq-question-btn"
                    onClick={() => toggleFaq(idx)}
                    aria-expanded={isOpen}
                  >
                    <span className="faq-q-text">{item.q}</span>
                    <span className="faq-toggle-icon">{isOpen ? '−' : '+'}</span>
                  </button>
                  {isOpen && (
                    <div className="faq-answer-pane">
                      <p>{item.a}</p>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* 9. Closing CTA Banner */}
      <section className="about-closing-section" style={{ backgroundImage: `linear-gradient(rgba(19, 44, 21, 0.9), rgba(19, 44, 21, 0.95)), url(${sectionBg})` }}>
        <div className="container">
          <div className="closing-box">
            <span className="closing-badge">🌱 JOIN THE MOVEMENT</span>
            <h2 className="closing-title">Bring Home Purity.<br />Nourish Your Loved Ones.</h2>
            <p className="closing-desc">
              Experience the unmatched vitality and authentic flavors of certified organic daily staples 
              delivered straight from the farmlands to your doorstep.
            </p>
            <div className="closing-actions">
              <button type="button" className="btn-closing-primary" onClick={onShopClick}>
                Shop All Categories
              </button>
              <button 
                type="button" 
                className="btn-closing-outline" 
                onClick={() => {
                  onShopClick();
                  setTimeout(() => {
                    const el = document.getElementById('bestsellers') || document.querySelector('.best-sellers');
                    if (el) el.scrollIntoView({ behavior: 'smooth' });
                  }, 150);
                }}
              >
                View Bestsellers
              </button>
            </div>
          </div>
        </div>
      </section>

    </div>
  );
};

export default AboutPage;
