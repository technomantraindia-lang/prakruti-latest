import React, { useState, useEffect, useRef } from 'react'
import './InstagramFeed.css'
import { apiClient } from '../../api/client'
import prakrutiLogo from '../../assets/images/Prakruti Logo_cc.png'

const DEFAULT_INSTAGRAM_URL = 'https://www.instagram.com/prakrutiorganics_/'
const DEFAULT_INSTAGRAM_HANDLE = '@prakrutiorganics_'

const initialBoxes = Array.from({ length: 6 }, (_, i) => ({
  slot: i + 1,
  url: DEFAULT_INSTAGRAM_URL,
  shortcode: '',
  embed_url: '',
  type: 'image',
  is_empty: true,
}))

const InstagramFeed = () => {
  const [profileUrl, setProfileUrl] = useState(DEFAULT_INSTAGRAM_URL)
  const [handle, setHandle] = useState(DEFAULT_INSTAGRAM_HANDLE)
  const [boxes, setBoxes] = useState(initialBoxes)
  const [embedScale, setEmbedScale] = useState(0.52)
  const rowRef = useRef(null)

  // Dynamically calculate exact scale to crop out Instagram's top header & fill square box
  useEffect(() => {
    const updateDimensions = () => {
      if (!rowRef.current) return
      const firstCard = rowRef.current.querySelector('.instagram-post')
      if (firstCard) {
        const w = firstCard.offsetWidth || 160
        // Instagram embed inner width is standard 326px
        setEmbedScale(Math.max(0.35, w / 326))
      }
    }

    updateDimensions()
    window.addEventListener('resize', updateDimensions)
    return () => window.removeEventListener('resize', updateDimensions)
  }, [boxes])

  useEffect(() => {
    let mounted = true
    apiClient('/instagram-feed')
      .then((res) => {
        if (!mounted || !res || !res.success) return

        const payload = (res.data && typeof res.data === 'object' && res.data.boxes) ? res.data : res
        const rawBoxes = payload.boxes || res.boxes || (Array.isArray(res.data) ? res.data : [])
        const profile = payload.profile_url || res.profile_url || DEFAULT_INSTAGRAM_URL
        const h = payload.handle || res.handle || DEFAULT_INSTAGRAM_HANDLE

        if (profile) setProfileUrl(profile)
        if (h) setHandle(h)

        if (Array.isArray(rawBoxes) && rawBoxes.length > 0) {
          const fullBoxes = Array.from({ length: 6 }, (_, i) => {
            const b = rawBoxes[i]
            if (b && (!b.is_empty || b.embed_url || b.shortcode || (b.url && b.url !== profile))) {
              const url = b.url || profile
              const shortcode = b.shortcode || ''
              const embedUrl = b.embed_url || (shortcode ? `https://www.instagram.com/p/${shortcode}/embed/` : '')
              const isEmpty = !embedUrl && Boolean(b.is_empty)

              return {
                slot: i + 1,
                url,
                shortcode,
                embed_url: embedUrl,
                type: b.type === 'video' ? 'video' : 'image',
                is_empty: isEmpty,
              }
            }
            return {
              slot: i + 1,
              url: profile,
              shortcode: '',
              embed_url: '',
              type: 'image',
              is_empty: true,
            }
          })
          setBoxes(fullBoxes)
        }
      })
      .catch((err) => {
        console.error('Error fetching Instagram feed:', err)
      })

    return () => { mounted = false }
  }, [])

  return (
    <section className="instagram-feed">
      <div className="container">
        <div className="section-header-left">
          <h2 className="section-title">From Our Instagram</h2>
        </div>

        <div className="instagram-row" ref={rowRef}>
          {boxes.map((box) => {
            const hasEmbed = !box.is_empty && Boolean(box.embed_url)
            const targetUrl = (!box.is_empty && box.url) ? box.url : profileUrl

            return (
              <a
                key={box.slot}
                href={targetUrl}
                target="_blank"
                rel="noopener noreferrer"
                className={`instagram-post ${!hasEmbed ? 'instagram-post-empty' : 'instagram-post-live'}`}
                aria-label={hasEmbed ? `Prakruti Instagram Post ${box.slot}` : 'Visit Prakruti on Instagram'}
              >
                <div className="post-image-wrap">
                  {hasEmbed ? (
                    <>
                      <div className="instagram-embed-holder">
                        <iframe
                          src={box.embed_url}
                          className="instagram-feed-iframe"
                          title={`Instagram Post ${box.slot}`}
                          frameBorder="0"
                          scrolling="no"
                          allowTransparency="true"
                          style={{
                            width: '326px',
                            height: '460px',
                            transform: `scale(${embedScale})`,
                            transformOrigin: 'top left',
                            marginTop: `-${Math.round(54 * embedScale)}px`,
                          }}
                        />
                      </div>
                      {box.type === 'video' && (
                        <div className="video-reel-indicator">
                          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5v14l11-7z" />
                          </svg>
                          <span>Reel</span>
                        </div>
                      )}
                    </>
                  ) : (
                    /* Empty box: Displays official Prakruti logo with brand tag */
                    <div className="instagram-empty-box">
                      <img
                        src={prakrutiLogo}
                        alt="Prakruti Organic"
                        className="empty-box-logo"
                        loading="lazy"
                      />
                      <span className="empty-box-tag">Prakruti</span>
                    </div>
                  )}

                  {/* Hover Overlay with Instagram Icon */}
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
              </a>
            )
          })}

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
                Follow <span className="insta-handle">{handle}</span> for daily wellness inspiration!
              </p>
              <a 
                href={profileUrl} 
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
