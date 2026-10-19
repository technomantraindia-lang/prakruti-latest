import React from 'react'
import './InstagramFeed.css'

// Import official Prakruti product & packaging media assets
import packingVideo from '../../assets/videos_ghee/ghee_video_2.mp4'
import newPackingImg from '../../assets/images/new packing .png'
import girGheeImg from '../../assets/images/gir_ghee.png'
import spicesImg from '../../assets/images/indian spices.png'
import packageEcoImg from '../../assets/images/packge.png'
import masalaImg from '../../assets/images/masala.png'

const instagramPosts = [
  {
    id: 1,
    type: 'video',
    video: packingVideo,
    alt: 'Prakruti fresh organic packaging process video'
  },
  {
    id: 2,
    type: 'image',
    image: newPackingImg,
    alt: 'Prakruti Vacuum Sealed Freshness Packaging'
  },
  {
    id: 3,
    type: 'image',
    image: girGheeImg,
    alt: 'Traditional Vedic Bilona A2 Gir Cow Ghee'
  },
  {
    id: 4,
    type: 'image',
    image: spicesImg,
    alt: 'Authentic 100% Pure Organic Spices'
  },
  {
    id: 5,
    type: 'image',
    image: packageEcoImg,
    alt: 'Eco-Friendly Certified Organic Food Packaging'
  },
  {
    id: 6,
    type: 'image',
    image: masalaImg,
    alt: 'Nutrient-Dense Handpicked Seeds & Natural Masalas'
  }
]

const InstagramFeed = () => {
  return (
    <section className="instagram-feed">
      <div className="container">
        <div className="section-header-left">
          <h2 className="section-title">From Our Instagram</h2>
        </div>

        <div className="instagram-row">
          {instagramPosts.map((post) => (
            <div key={post.id} className="instagram-post">
              <div className="post-image-wrap">
                {post.type === 'video' ? (
                  <>
                    <video
                      src={post.video}
                      className="post-video"
                      autoPlay
                      loop
                      muted
                      playsInline
                      preload="metadata"
                      aria-label={post.alt}
                    />
                    <div className="video-reel-indicator">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                      </svg>
                      <span>Reel</span>
                    </div>
                  </>
                ) : (
                  <img
                    src={post.image}
                    alt={post.alt}
                    className="post-image"
                    loading="lazy"
                  />
                )}
                <div className="post-overlay">
                  <svg
                    className="instagram-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                  >
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                  </svg>
                </div>
              </div>
            </div>
          ))}

          {/* 7th: Instagram Follow Us CTA Card */}
          <div className="instagram-follow-card">
            <div className="follow-card-content">
              <div className="follow-insta-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                  <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                  <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                </svg>
              </div>
              <p className="follow-text">
                Follow <span className="insta-handle">@prakruti</span> for daily wellness inspiration!
              </p>
              <a 
                href="https://instagram.com/prakruti" 
                className="btn btn-primary follow-btn"
                target="_blank"
                rel="noopener noreferrer"
              >
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" style={{marginRight: '6px'}}>
                  <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                  <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                  <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
                </svg>
                Follow Us
              </a>
            </div>
          </div>
        </div>

      </div>
    </section>
  )
}

export default InstagramFeed