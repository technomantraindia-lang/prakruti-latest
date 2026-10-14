import React, { useEffect, useState } from 'react';
import { apiClient } from '../../api/client';
import './FarmGallery.css';

// Import assets
import ethicalImg from '../../assets/images/image copy.png';
import seedsImg from '../../assets/images/masala.png';
import bannerBg from '../../assets/images/gallery_hero_bg.jpg';
import farmVideo from '../../assets/images/farm gallery.mp4';
import cerealPulsesImg from '../../assets/images/cereal_pulses.png';
import oilsGheeImg from '../../assets/images/oil and ghe.png';
import speltWheatImg from '../../assets/images/farm_gallery_spelt_wheat.jpg';
import gheeImg from '../../assets/images/bestseller_gir_ghee.jpg';

// Import Ghee Process Videos
import gheeVideo2 from '../../assets/videos_ghee/ghee_video_2.mp4';
import gheeVideo3 from '../../assets/videos_ghee/ghee_video_3.mp4';

import award1Img from '../../assets/images/PRAKGALLERY/AWARD1.jpeg';
import award2Img from '../../assets/images/PRAKGALLERY/AWARD2.jpeg';
import award3Img from '../../assets/images/PRAKGALLERY/AWARD3.png';
import award4Img from '../../assets/images/PRAKGALLERY/AWARD4.png';
import award5Img from '../../assets/images/PRAKGALLERY/AWARD5.png';
import farmerImg from '../../assets/images/PRAKGALLERY/FAARMER.png';
import farmerCommunityVideo from '../../assets/images/PRAKGALLERY/WhatsApp Video 2026-10-06 at 1.13.16 PM.mp4';
import fieldVideo1 from '../../assets/images/PRAKGALLERY/WhatsApp Video 2026-10-06 at 1.13.28 PM.mp4';
import fieldVideo2 from '../../assets/images/PRAKGALLERY/WhatsApp Video 2026-10-06 at 1.04.13 PM.mp4';
import fieldVideo3 from '../../assets/images/PRAKGALLERY/FARMER CUTTIN2.mp4';
import fieldVideo4 from '../../assets/images/PRAKGALLERY/FARMER CUTTING 4.mp4';
import fieldVideo5 from '../../assets/images/PRAKGALLERY/FARMER CUTTING.mp4';
import fieldVideo6 from '../../assets/images/PRAKGALLERY/FARMERCUTTIN3.mp4';
import vegetablesImg from '../../assets/images/PRAKGALLERY/image.png';
import vegetablesImg2 from '../../assets/images/PRAKGALLERY/WhatsApp Image 2026-10-06 at 1.12.32 PM.jpeg';
import vegetablesImg3 from '../../assets/images/PRAKGALLERY/WhatsApp Image 2026-10-06 at 1.13.50 PM.jpeg';
import vegetablesImg4 from '../../assets/images/PRAKGALLERY/WhatsApp Image 2026-10-06 at 1.13.50 PM (1).jpeg';
import vegetablesImg5 from '../../assets/images/PRAKGALLERY/WhatsApp Image 2026-10-06 at 1.13.48 PM.jpeg';
import vegetablesVideo1 from '../../assets/images/PRAKGALLERY/PNT.mp4';
import vegetablesVideo2 from '../../assets/images/PRAKGALLERY/WhatsApp Video 2026-10-06 at 1.13.47 PM.mp4';

const GALLERY_CATEGORIES = [
  { slug: 'farmer-community', label: 'Farmer & Community', icon: '🧑‍🌾' },
  { slug: 'fields-cultivation', label: 'Fields, Soil & Cultivation', icon: '🌾' },
  { slug: 'crops-vegetables', label: 'Crops, Cows & Vegetables', icon: '🐄' },
  { slug: 'events', label: 'Events', icon: '🎪' },
];

const LEGACY_CATEGORY_MAP = {
  fields: 'fields-cultivation',
  harvesting: 'farmer-community',
  processing: 'crops-vegetables',
  'crops-livestock': 'crops-vegetables',
  'fresh-produce': 'crops-vegetables',
  'testing-purpose': 'crops-vegetables',
  'awards-events': 'events',
};

const normalizeGalleryItem = (item) => {
  const category = LEGACY_CATEGORY_MAP[item.category] || item.category;
  const categoryInfo = GALLERY_CATEGORIES.find((entry) => entry.slug === category);

  return {
    ...item,
    category,
    category_label: categoryInfo?.label || item.category_label,
  };
};

const fallbackGalleryItems = [
  {
    id: 1,
    category: 'fields-cultivation',
    title: 'Certified Organic Spelt & Wheat Farmlands',
    description: 'Our organic grains are grown in native, mineral-rich soils completely free of synthetic pesticides and fertilizers, yielding higher natural fiber, rich micronutrients, and authentic flavor.',
    image: speltWheatImg,
    tag: 'Sowing & Fields',
    location: 'Punjab & Madhya Pradesh',
    details: 'Native heirloom seed preservation with compost-enriched organic soil management.'
  },
  {
    id: 2,
    category: 'crops-vegetables',
    title: 'Traditional Vedic Bilona A2 Gir Cow Ghee',
    description: 'Handcrafted from fresh curd of free-grazing indigenous Gir cows, slowly hand-churned in wooden bilona clay pots and woodfire simmered into golden, aromatic, granular elixir.',
    image: gheeImg,
    videos: [
      { src: gheeVideo2, title: 'Traditional Curd Churning (Bilona)' },
      { src: gheeVideo3, title: 'Pouring Golden Granular Ghee' }
    ],
    tag: 'Vedic Bilona Ghee',
    location: 'Gir Somnath & Saurashtra Pastures',
    details: 'Cruelty-free grass-fed A2 Gir cows, bi-directional wooden bilona curd churning, low-heat brass vessel boiling.'
  },
  {
    id: 3,
    category: 'farmer-community',
    title: 'Ethical & Natural Manual Harvesting',
    description: 'Each crop is hand-harvested at peak solar ripeness by generational farming families to ensure that living enzymes, vital nutrients, and natural plant aromas are fully preserved.',
    image: ethicalImg,
    tag: 'Manual Harvest',
    location: 'Rural Farmer Cooperatives',
    details: 'Hand-picked selection and natural shade drying to preserve delicate nutrients.'
  },
  {
    id: 4,
    category: 'crops-vegetables',
    title: 'Traditional Wooden Ghani Cold-Pressing',
    description: 'Our cold-pressed cooking oils are gently extracted using slow wooden Ghana rotations at temperatures strictly kept below 40°C, keeping essential antioxidants and aroma intact.',
    image: oilsGheeImg,
    tag: 'Cold Pressing',
    location: 'Heritage Extraction Mills',
    details: 'Zero artificial heat, zero chemical solvents, 100% virgin unrefined oils.'
  },
  {
    id: 7,
    category: 'farmer-community',
    title: 'Handpicked Nutrient-Dense Seed Selection',
    description: 'Carefully sorted and triple-cleaned seeds ensuring uniform high-grade grains, zero grit, and optimal omega-3 fatty acid and dietary fiber retention.',
    image: seedsImg,
    tag: 'Seed Selection',
    location: 'Local Farmer Collectives',
    details: 'Triple gravity-cleaned sorting retaining whole-grain nutrient density.'
  }
];

const createLocalImageItem = ({ id, category, title, image, tag, description }) => ({
  id: `local-${id}`,
  category,
  title,
  description,
  image,
  tag,
  location: 'Prakruti Organic Community',
  details: 'A real Prakruti sourcing, farming, community, or recognition moment shared from our gallery.',
});

const createLocalVideoItem = ({ id, category, title, video, image, tag, description }) => ({
  id: `local-${id}`,
  category,
  title,
  description,
  image,
  tag,
  location: 'Prakruti Organic Community',
  details: 'Watch this real Prakruti farming and community moment from our gallery.',
  videos: [{ src: video, title: 'Play Gallery Video' }],
});

const LOCAL_GALLERY_ITEMS = [
  createLocalImageItem({ id: 'event-1', category: 'events', title: 'Prakruti Organic Meet & Gathering', image: award1Img, tag: 'Community Event', description: 'A proud celebration and community gathering from the Prakruti journey.' }),
  createLocalImageItem({ id: 'event-2', category: 'events', title: 'Prakruti Community Recognition Event', image: award2Img, tag: 'Community Event', description: 'Celebrating the people and partnerships behind our work.' }),
  createLocalImageItem({ id: 'event-3', category: 'events', title: 'Honouring Organic Farming Excellence', image: award3Img, tag: 'Special Event', description: 'A special event celebrating purity, quality, and responsible sourcing.' }),
  createLocalImageItem({ id: 'event-4', category: 'events', title: 'Farmer Community Gathering', image: award4Img, tag: 'Farmer Event', description: 'A memorable gathering with the farming community we work with.' }),
  createLocalImageItem({ id: 'event-5', category: 'events', title: 'Prakruti Annual Event Celebration', image: award5Img, tag: 'Special Event', description: 'A memorable Prakruti event shared with our community.' }),

  createLocalVideoItem({ id: 'farmer-community-video', category: 'farmer-community', title: 'Farmer Community in Action', video: farmerCommunityVideo, image: farmerImg, tag: 'Farmer Community', description: 'Meet the people and farming families behind every Prakruti staple.' }),
  createLocalImageItem({ id: 'farmer-community-photo', category: 'farmer-community', title: 'Our Farming Community', image: farmerImg, tag: 'Farmer Community', description: 'A closer look at the farmers who make our sourcing transparent and personal.' }),

  createLocalVideoItem({ id: 'field-video-1', category: 'fields-cultivation', title: 'Fields & Cultivation', video: fieldVideo1, image: speltWheatImg, tag: 'Fields & Soil', description: 'A visual story from our cultivated fields and organic soil.' }),
  createLocalVideoItem({ id: 'field-video-2', category: 'fields-cultivation', title: 'Organic Farming Practices', video: fieldVideo2, image: speltWheatImg, tag: 'Fields & Soil', description: 'Traditional cultivation practices supporting healthy soil and crops.' }),
  createLocalVideoItem({ id: 'field-video-3', category: 'fields-cultivation', title: 'Farmer Field Work', video: fieldVideo3, image: speltWheatImg, tag: 'Field Work', description: 'Farmers caring for crops through the growing season.' }),
  createLocalVideoItem({ id: 'field-video-4', category: 'fields-cultivation', title: 'Harvest Preparation', video: fieldVideo4, image: speltWheatImg, tag: 'Field Work', description: 'Hands-on work from our natural farming operations.' }),
  createLocalVideoItem({ id: 'field-video-5', category: 'fields-cultivation', title: 'Crop Care at the Farm', video: fieldVideo5, image: speltWheatImg, tag: 'Field Work', description: 'A behind-the-scenes look at crop care and cultivation.' }),
  createLocalVideoItem({ id: 'field-video-6', category: 'fields-cultivation', title: 'From Soil to Harvest', video: fieldVideo6, image: speltWheatImg, tag: 'Fields & Soil', description: 'The careful work that brings farm-fresh produce to your home.' }),

  createLocalImageItem({ id: 'vegetables-1', category: 'crops-vegetables', title: 'Fresh Vegetable Farming', image: vegetablesImg, tag: 'Crops & Vegetables', description: 'Fresh vegetables grown with care at the farm.' }),
  createLocalImageItem({ id: 'vegetables-2', category: 'crops-vegetables', title: 'Farm-Fresh Produce', image: vegetablesImg2, tag: 'Crops & Vegetables', description: 'A closer look at fresh crops and produce from our farming community.' }),
  createLocalImageItem({ id: 'vegetables-3', category: 'crops-vegetables', title: 'Harvested Organic Produce', image: vegetablesImg3, tag: 'Crops & Vegetables', description: 'Naturally grown produce prepared for the journey from farm to family.' }),
  createLocalImageItem({ id: 'vegetables-4', category: 'crops-vegetables', title: 'Crops, Cows & Vegetables', image: vegetablesImg4, tag: 'Crops & Vegetables', description: 'A glimpse of the diverse, living farm ecosystem.' }),
  createLocalImageItem({ id: 'vegetables-5', category: 'crops-vegetables', title: 'Natural Farm Harvest', image: vegetablesImg5, tag: 'Crops & Vegetables', description: 'Fresh harvest moments from the Prakruti farm story.' }),
  createLocalVideoItem({ id: 'vegetables-video-1', category: 'crops-vegetables', title: 'Vegetable Farm Story', video: vegetablesVideo1, image: vegetablesImg, tag: 'Crops & Vegetables', description: 'See the care and attention behind our farm-grown vegetables.' }),
  createLocalVideoItem({ id: 'vegetables-video-2', category: 'crops-vegetables', title: 'Fresh Produce in Motion', video: vegetablesVideo2, image: vegetablesImg2, tag: 'Crops & Vegetables', description: 'A moving look at crops, produce, and natural farming life.' }),
];

const FarmGallery = ({ onShopClick }) => {
  const [galleryItems, setGalleryItems] = useState([...fallbackGalleryItems, ...LOCAL_GALLERY_ITEMS]);
  const [activeFilter, setActiveFilter] = useState('all');
  const [selectedItem, setSelectedItem] = useState(null);
  const [activeSlide, setActiveSlide] = useState(0);

  useEffect(() => {
    let mounted = true;

    apiClient('/farm-gallery').then((res) => {
      if (!mounted || !res.success || !Array.isArray(res.data) || res.data.length === 0) {
        return;
      }

      setGalleryItems([...res.data.map(normalizeGalleryItem), ...LOCAL_GALLERY_ITEMS]);
    });

    return () => {
      mounted = false;
    };
  }, []);

  const filteredItems = activeFilter === 'all'
    ? galleryItems
    : galleryItems.filter(item => item.category === activeFilter);

  const handleOpenItem = (item) => {
    setSelectedItem(item);
    setActiveSlide(0);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handlePrevSlide = () => {
    if (!selectedItem?.videos?.length) return;
    setActiveSlide((prev) => (prev === 0 ? selectedItem.videos.length - 1 : prev - 1));
  };

  const handleNextSlide = () => {
    if (!selectedItem?.videos?.length) return;
    setActiveSlide((prev) => (prev === selectedItem.videos.length - 1 ? 0 : prev + 1));
  };

  if (selectedItem) {
    const mediaHasVideos = selectedItem.videos && selectedItem.videos.length > 0;
    const mediaVideo = mediaHasVideos ? selectedItem.videos[activeSlide] : null;

    return (
      <div
        className="gallery-media-lightbox"
        role="dialog"
        aria-modal="true"
        aria-label="Gallery media viewer"
        onClick={() => {
          setSelectedItem(null);
          setActiveSlide(0);
        }}
      >
        <button
          type="button"
          className="gallery-media-lightbox-close"
          onClick={() => {
            setSelectedItem(null);
            setActiveSlide(0);
          }}
          aria-label="Close media viewer"
        >
          ×
        </button>

        <div className="gallery-media-lightbox-content" onClick={(event) => event.stopPropagation()}>
          <div className="gallery-media-lightbox-media-wrapper">
            {mediaHasVideos ? (
              <video
                key={mediaVideo.src}
                className="gallery-media-lightbox-video"
                src={mediaVideo.src}
                controls
                autoPlay
                playsInline
              />
            ) : (
              <img className="gallery-media-lightbox-image" src={selectedItem.image} alt={selectedItem.title || 'Gallery media'} />
            )}

            {mediaHasVideos && selectedItem.videos.length > 1 && (
              <>
                <button type="button" className="gallery-media-lightbox-prev" onClick={handlePrevSlide} aria-label="Previous video">
                  ‹
                </button>
                <button type="button" className="gallery-media-lightbox-next" onClick={handleNextSlide} aria-label="Next video">
                  ›
                </button>
              </>
            )}
          </div>

          {(selectedItem.title || selectedItem.description || selectedItem.location) && (
            <div className="gallery-media-lightbox-info">
              <div className="gallery-media-lightbox-meta">
                {selectedItem.tag && <span className="gallery-media-lightbox-tag">{selectedItem.tag}</span>}
                {selectedItem.location && <span className="gallery-media-lightbox-loc">📍 {selectedItem.location}</span>}
              </div>
              {selectedItem.title && <h3 className="gallery-media-lightbox-title">{selectedItem.title}</h3>}
              {selectedItem.description && <p className="gallery-media-lightbox-desc">{selectedItem.description}</p>}
              {selectedItem.details && selectedItem.details !== selectedItem.description && (
                <p className="gallery-media-lightbox-details">{selectedItem.details}</p>
              )}
            </div>
          )}
        </div>
      </div>
    );

    const hasVideos = selectedItem.videos && selectedItem.videos.length > 0;
    const activeVideo = hasVideos ? selectedItem.videos[activeSlide] : null;

    return (
      <div className="farm-gallery farm-gallery-detail-page">
        <section className="gallery-detail-hero">
          <div className="container">
            <button
              type="button"
              className="gallery-detail-back"
              onClick={() => {
                setSelectedItem(null);
                setActiveSlide(0);
                window.scrollTo({ top: 0, behavior: 'smooth' });
              }}
            >
              Back to Farm Gallery
            </button>

            <div className="gallery-detail-hero-grid">
              <div className="gallery-detail-copy">
                <div className="gallery-detail-meta-row">
                  <span className="gallery-detail-tag">{selectedItem.tag}</span>
                  <span className="gallery-detail-location">{selectedItem.location}</span>
                </div>
                <h1 className="gallery-detail-title">{selectedItem.title}</h1>
                <p className="gallery-detail-desc">{selectedItem.description}</p>
              </div>

              <div className="gallery-detail-visual">
                <img src={selectedItem.image} alt={selectedItem.title} />
              </div>
            </div>
          </div>
        </section>

        <section className="gallery-detail-main">
          <div className="container">
            <div className="gallery-detail-content-grid">
              <div className="gallery-detail-story">
                <span className="section-eyebrow">PROCESS STORY</span>
                <h2 className="gallery-detail-section-title">Operation Specifications</h2>
                <p className="gallery-detail-details">{selectedItem.details}</p>

                <div className="gallery-detail-checklist">
                  <div className="meta-check">
                    <span className="check-icon">✓</span>
                    <span>Organic &amp; Pesticide Free</span>
                  </div>
                  <div className="meta-check">
                    <span className="check-icon">✓</span>
                    <span>Direct-Trade Cooperative Sourced</span>
                  </div>
                  <div className="meta-check">
                    <span className="check-icon">✓</span>
                    <span>NABL Accredited Lab Tested</span>
                  </div>
                </div>

                <button
                  type="button"
                  className="lightbox-shop-btn"
                  onClick={() => {
                    setSelectedItem(null);
                    if (onShopClick) onShopClick();
                  }}
                >
                  Shop Related Organic Products
                </button>
              </div>

              <div className="gallery-detail-media-panel">
                {hasVideos ? (
                  <div className="gallery-detail-video-block">
                    <video
                      key={activeVideo.src}
                      className="gallery-detail-video"
                      src={activeVideo.src}
                      autoPlay
                      loop
                      muted
                      playsInline
                      controls
                    />

                    <div className="gallery-detail-video-footer">
                      <div>
                        <span className="slider-badge-pill">Video {activeSlide + 1} of {selectedItem.videos.length}</span>
                        {activeVideo.title && <h3>{activeVideo.title}</h3>}
                      </div>
                      {selectedItem.videos.length > 1 && (
                        <div className="gallery-detail-video-actions">
                          <button type="button" onClick={handlePrevSlide} aria-label="Previous video">Prev</button>
                          <button type="button" onClick={handleNextSlide} aria-label="Next video">Next</button>
                        </div>
                      )}
                    </div>

                    {selectedItem.videos.length > 1 && (
                      <div className="gallery-detail-video-list">
                        {selectedItem.videos.map((video, idx) => (
                          <button
                            key={`${video.src}-${idx}`}
                            type="button"
                            className={idx === activeSlide ? 'active' : ''}
                            onClick={() => setActiveSlide(idx)}
                          >
                            {video.title || `Video ${idx + 1}`}
                          </button>
                        ))}
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="gallery-detail-image-card">
                    <img src={selectedItem.image} alt={selectedItem.title} />
                  </div>
                )}
              </div>
            </div>
          </div>
        </section>
      </div>
    );
  }

  return (
    <div className="farm-gallery">
      {/* 1. Header Hero Banner */}
      <section className="gallery-hero" style={{ backgroundImage: `linear-gradient(to right, rgba(19, 44, 21, 0.92) 0%, rgba(19, 44, 21, 0.6) 60%, rgba(19, 44, 21, 0.3) 100%), url(${bannerBg})` }}>
        <div className="container">
          <div className="gallery-hero-content">
            <span className="gallery-hero-badge">🌱 100% Farm Traceable</span>
            <h1 className="gallery-title">
              From Sacred Soils to <em>Your Kitchen Table</em>
            </h1>
            <p className="gallery-subtitle">
              Take a visual journey through our certified organic farmlands, generational harvesting cooperatives, 
              and time-honored slow-processing methods. Honest, pure, and completely traceable.
            </p>

            <div className="gallery-hero-stats">
              <div className="hero-stat-item">
                <span className="h-stat-num">500+</span>
                <span className="h-stat-lbl">Partner Farmers</span>
              </div>
              <div className="hero-stat-item">
                <span className="h-stat-num">100%</span>
                <span className="h-stat-lbl">Chemical Free</span>
              </div>
              <div className="hero-stat-item">
                <span className="h-stat-num">4</span>
                <span className="h-stat-lbl">Purity Stages</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 2. Filter Navigation & Grid */}
      <section className="gallery-main-section">
        <div className="container">
          <div className="gallery-section-header">
            <span className="section-eyebrow">OUR PROCESS</span>
            <h2 className="section-heading">Behind Every Pure Harvest</h2>
            <p className="section-desc">Explore our transparent farm operations from certified sowing to table-ready packaging</p>
            
            {/* Filter Pills */}
            <div className="gallery-filters-bar">
              <button
                type="button"
                className={`gallery-filter-btn ${activeFilter === 'all' ? 'active' : ''}`}
                onClick={() => setActiveFilter('all')}
              >
                🌿 All ({galleryItems.length})
              </button>
              {GALLERY_CATEGORIES.map((category) => (
                <button
                  key={category.slug}
                  type="button"
                  className={`gallery-filter-btn ${activeFilter === category.slug ? 'active' : ''}`}
                  onClick={() => setActiveFilter(category.slug)}
                >
                  {category.icon} {category.label} ({galleryItems.filter((item) => item.category === category.slug).length})
                </button>
              ))}
            </div>
          </div>

          {/* Gallery Cards Grid */}
          <div className="gallery-cards-grid">
            {filteredItems.map((item, idx) => {
              const hasText = item.display_mode !== 'simple_image' && item.display_mode !== 'simple_video' && Boolean(item.title || item.description);

              return (
                <div 
                  key={item.id} 
                  className={`farm-gallery-card ${!hasText ? 'card-simple-media' : 'card-with-text'}`}
                  onClick={() => handleOpenItem(item)}
                  style={{ '--animation-order': idx }}
                >
                  <div className="card-image-box">
                    {item.videos?.length ? (
                      <video
                        src={item.videos[0].src}
                        className="card-cover-video"
                        muted
                        loop
                        autoPlay
                        playsInline
                        preload="metadata"
                        aria-label={item.title || 'Farm video'}
                      />
                    ) : (
                      <img src={item.image} alt={item.title || 'Farm photo'} className="card-cover-img" loading="lazy" />
                    )}
                    {item.tag && <span className="card-operation-tag">{item.tag}</span>}
                    {item.videos && item.videos.length > 0 && (
                      <span className="card-video-count-badge">📹 {item.videos.length} Videos</span>
                    )}

                    <div className="card-hover-overlay">
                      <span className="zoom-btn-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                          <circle cx="11" cy="11" r="8"></circle>
                          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                          <line x1="11" y1="8" x2="11" y2="14"></line>
                          <line x1="8" y1="11" x2="14" y2="11"></line>
                        </svg>
                      </span>
                      <span className="hover-cta-text">{item.videos?.length ? 'Watch Process Videos' : 'View Process Story'}</span>
                    </div>
                  </div>

                  {hasText && (
                    <div className="card-body-box">
                      {item.title && <h3 className="card-title">{item.title}</h3>}
                      {item.description && <p className="card-description">{item.description}</p>}
                      
                      <div className="card-action-link">
                        <span>{item.videos?.length ? 'Watch Videos & Details' : 'Learn Story Details'}</span>
                        <span className="arrow-icon">→</span>
                      </div>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* 3. Farm Documentary Video Section */}
      <section className="gallery-video-section">
        <div className="container">
          <div className="video-editorial-grid">
            <div className="video-copy-col">
              <span className="video-pill-tag">FARM DOCUMENTARY</span>
              <h2 className="video-section-title">Experience the Harvest in Action</h2>
              <p className="video-desc">
                Watch our dedicated farmers hand-gather golden first-harvest grains and traditional spices across our rural cooperatives. 
                We partner with 500+ certified organic farmers, regenerating soil biology and ensuring unadulterated food reaches your home.
              </p>
              
              <div className="video-metrics-row">
                <div className="metric-box">
                  <span className="metric-number">500+</span>
                  <span className="metric-label">Organic Farmers</span>
                </div>
                <div className="metric-box">
                  <span className="metric-number">100%</span>
                  <span className="metric-label">Traceable Sourcing</span>
                </div>
                <div className="metric-box">
                  <span className="metric-number">Zero</span>
                  <span className="metric-label">Chemical Pesticides</span>
                </div>
              </div>
            </div>

            <div className="video-player-col">
              <div className="video-frame-container">
                <video id="farm-vid" className="farm-video-player" autoPlay loop muted playsInline preload="auto">
                  <source src={farmVideo} type="video/mp4" />
                  Your browser does not support the video tag.
                </video>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 4. Pillars of Purity Strip */}
      <section className="gallery-pillars-section">
        <div className="container">
          <div className="pillars-grid">
            <div className="pillar-item">
              <span className="pillar-icon">🌱</span>
              <div className="pillar-text">
                <h4>Chemical-Free Soil</h4>
                <p>Nourished exclusively with bio-compost and green manure.</p>
              </div>
            </div>
            <div className="pillar-item">
              <span className="pillar-icon">🌾</span>
              <div className="pillar-text">
                <h4>Non-GMO Native Seeds</h4>
                <p>Heirloom seed varieties conserved for maximum nutrition.</p>
              </div>
            </div>
            <div className="pillar-item">
              <span className="pillar-icon">🔬</span>
              <div className="pillar-text">
                <h4>Third-Party Lab Tested</h4>
                <p>Certified 100% free of heavy metals and pesticide residue.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 5. Lightbox Modal */}
      {selectedItem && (
        <div className="gallery-lightbox" onClick={() => setSelectedItem(null)}>
          <div className="lightbox-content" onClick={(e) => e.stopPropagation()}>
            <button 
              className="lightbox-close-btn" 
              onClick={() => setSelectedItem(null)}
              aria-label="Close modal"
            >
              ×
            </button>
            <div className="lightbox-inner-grid">
              <div className="lightbox-image-wrap">
                {selectedItem.videos && selectedItem.videos.length > 0 ? (
                  <div className="lightbox-video-slider">
                    <video 
                      key={selectedItem.videos[activeSlide].src}
                      className="lightbox-slider-video"
                      src={selectedItem.videos[activeSlide].src}
                      autoPlay
                      loop
                      muted
                      playsInline
                      controls
                    />
                    
                    <span className="lightbox-image-tag">{selectedItem.tag}</span>

                    {/* Top slide counter and title */}
                    <div className="slider-counter-badge">
                      <span className="slider-badge-pill">📹 Video {activeSlide + 1} of {selectedItem.videos.length}</span>
                      {selectedItem.videos[activeSlide].title && (
                        <span className="slider-video-title">{selectedItem.videos[activeSlide].title}</span>
                      )}
                    </div>

                    {/* Prev & Next slide buttons */}
                    <button 
                      type="button" 
                      className="slider-nav-btn slider-prev-btn" 
                      onClick={handlePrevSlide}
                      aria-label="Previous video"
                    >
                      ‹
                    </button>
                    <button 
                      type="button" 
                      className="slider-nav-btn slider-next-btn" 
                      onClick={handleNextSlide}
                      aria-label="Next video"
                    >
                      ›
                    </button>

                    {/* Dots pagination */}
                    <div className="slider-dots-row">
                      {selectedItem.videos.map((vid, idx) => (
                        <button
                          key={idx}
                          type="button"
                          className={`slider-dot-btn ${idx === activeSlide ? 'active' : ''}`}
                          onClick={(e) => { e.stopPropagation(); setActiveSlide(idx); }}
                          aria-label={`Go to video ${idx + 1}`}
                        />
                      ))}
                    </div>
                  </div>
                ) : (
                  <>
                    <img src={selectedItem.image} alt={selectedItem.title} />
                    <span className="lightbox-image-tag">{selectedItem.tag}</span>
                  </>
                )}
              </div>
              
              <div className="lightbox-info-col">
                <div className="lightbox-meta-top">
                  <span className="lightbox-category">{selectedItem.tag}</span>
                  <span className="lightbox-location">📍 {selectedItem.location}</span>
                </div>

                <h3 className="lightbox-title">{selectedItem.title}</h3>
                <p className="lightbox-desc">{selectedItem.description}</p>
                
                <div className="lightbox-specs-card">
                  <h4>Operation Specifications</h4>
                  <p>{selectedItem.details}</p>
                </div>

                <div className="lightbox-trust-checklist">
                  <div className="meta-check">
                    <span className="check-icon">✓</span>
                    <span>Organic &amp; Pesticide Free</span>
                  </div>
                  <div className="meta-check">
                    <span className="check-icon">✓</span>
                    <span>Direct-Trade Cooperative Sourced</span>
                  </div>
                  <div className="meta-check">
                    <span className="check-icon">✓</span>
                    <span>NABL Accredited Lab Tested</span>
                  </div>
                </div>

                <button 
                  type="button"
                  className="lightbox-shop-btn" 
                  onClick={() => { 
                    setSelectedItem(null); 
                    if (onShopClick) onShopClick(); 
                  }}
                >
                  Shop Related Organic Products →
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default FarmGallery;
