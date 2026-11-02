import React from 'react'
import './HeroBanner.css'
import heroBannerVideo from '../../assets/images/Untitled design (1).mp4'

const HeroBanner = () => {
  return (
    <section className="hero-banner-section" aria-label="Prakruti Home Banner">
      <div className="hero-banner-container">
        <video
          className="hero-banner-video"
          src={heroBannerVideo}
          autoPlay
          loop
          muted
          playsInline
          preload="auto"
        />
      </div>
    </section>
  )
}

export default HeroBanner
