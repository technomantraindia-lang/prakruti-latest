import React, { useEffect, useState, useMemo } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import './Testimonials.css'
import { reviewsApi } from '../../api/reviews'

// Curated authentic community reviews
const defaultTestimonials = [
  {
    id: 'def-1',
    name: 'Priya Sharma',
    location: 'Bengaluru, Karnataka',
    rating: 5,
    review: 'The quality of the A2 Bilona Ghee is simply unmatched. The aroma reminds me of the traditional ghee my grandmother made in our village. Truly pure and authentic.',
    image: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=600&q=80',
    video: null,
    source: 'Verified Buyer',
    date: 'Recent'
  },
  {
    id: 'def-2',
    name: 'Rohit Mehta',
    location: 'Pune, Maharashtra',
    rating: 5,
    review: 'Switched our complete grocery pantry to Prakruti three months ago. The unpolished pulses cook so much better and the difference in family health is noticeable.',
    image: null,
    video: null,
    source: 'Verified Buyer',
    date: 'Recent'
  },
  {
    id: 'def-3',
    name: 'Ananya Kulkarni',
    location: 'Hyderabad, Telangana',
    rating: 5,
    review: 'Received the pantry pack in premium biodegradable packaging. The wood-pressed oils have a rich golden hue and smell incredibly fresh.',
    image: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80',
    video: null,
    source: 'Verified Buyer',
    date: 'Recent'
  },
  {
    id: 'def-4',
    name: 'Meera Deshmukh',
    location: 'Mumbai, Maharashtra',
    rating: 5,
    review: 'Honest, pesticide-free staples delivered with genuine care. Prakruti has earned our lifelong trust. Highly recommended for every health-conscious family.',
    image: null,
    video: null,
    source: 'Verified Buyer',
    date: 'Recent'
  },
  {
    id: 'def-5',
    name: 'Karan Verma',
    location: 'Ahmedabad, Gujarat',
    rating: 5,
    review: 'Their raw organic honey and whole spices have transformed our cooking. Pure flavours without any artificial additives or preservatives.',
    image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80',
    video: null,
    source: 'Verified Buyer',
    date: 'Recent'
  }
]

// Helper to resolve image/video URLs cleanly
const resolveMediaUrl = (url) => {
  if (!url) return null
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url
  }
  if (url.startsWith('/')) return url
  return `/storage/${url.replace(/^\/+/, '')}`
}

const Testimonials = () => {
  const [customerTestimonials, setCustomerTestimonials] = useState([])
  const [filter, setFilter] = useState('all') // 'all' | 'photos' | 'videos' | 'five-star'
  const [currentIndex, setCurrentIndex] = useState(0)
  const [cardsPerView, setCardsPerView] = useState(3)
  const [isFormOpen, setIsFormOpen] = useState(false)
  const [activeLightbox, setActiveLightbox] = useState(null)
  const [hoverRating, setHoverRating] = useState(0)

  // Form State
  const [form, setForm] = useState({ name: '', location: '', rating: 5, review: '' })
  const [imageFile, setImageFile] = useState(null)
  const [imagePreview, setImagePreview] = useState(null)
  const [videoFile, setVideoFile] = useState(null)
  const [videoPreview, setVideoPreview] = useState(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [submitStatus, setSubmitStatus] = useState({ type: null, message: '' })

  // Responsive cards per view
  useEffect(() => {
    const handleResize = () => {
      if (window.innerWidth < 720) {
        setCardsPerView(1)
      } else if (window.innerWidth < 1120) {
        setCardsPerView(2)
      } else {
        setCardsPerView(3)
      }
    }
    handleResize()
    window.addEventListener('resize', handleResize)
    return () => window.removeEventListener('resize', handleResize)
  }, [])

  // Fetch approved customer testimonials from API
  useEffect(() => {
    let mounted = true
    reviewsApi.getTestimonials().then((res) => {
      if (!mounted || !res.success || !Array.isArray(res.data)) return
      setCustomerTestimonials(res.data)
    })
    return () => {
      mounted = false
    }
  }, [])

  // Combined list
  const allList = useMemo(() => {
    return [...customerTestimonials, ...defaultTestimonials]
  }, [customerTestimonials])

  // Filtered testimonials
  const filteredList = useMemo(() => {
    return allList.filter((item) => {
      const hasPhoto = Boolean(item.image)
      const hasVideo = Boolean(item.video)
      if (filter === 'photos') return hasPhoto
      if (filter === 'videos') return hasVideo
      if (filter === 'five-star') return Number(item.rating) >= 5
      return true
    })
  }, [allList, filter])

  // Max slide index
  const maxIndex = Math.max(0, filteredList.length - cardsPerView)

  // Carousel navigation
  const prevSlide = () => {
    setCurrentIndex((prev) => (prev > 0 ? prev - 1 : maxIndex))
  }

  const nextSlide = () => {
    setCurrentIndex((prev) => (prev < maxIndex ? prev + 1 : 0))
  }

  // Adjust index when filter changes
  useEffect(() => {
    setCurrentIndex(0)
  }, [filter])

  // Image upload handler
  const handleImageChange = (e) => {
    const file = e.target.files?.[0]
    if (!file) return

    if (!file.type.startsWith('image/')) {
      setSubmitStatus({ type: 'error', message: 'Please select a valid image file (JPG, PNG, WebP).' })
      return
    }
    if (file.size > 12 * 1024 * 1024) {
      setSubmitStatus({ type: 'error', message: 'Image size exceeds the 12 MB limit.' })
      return
    }

    if (imagePreview) URL.revokeObjectURL(imagePreview)
    setImageFile(file)
    setImagePreview(URL.createObjectURL(file))
    setSubmitStatus({ type: null, message: '' })
  }

  const removeImage = () => {
    if (imagePreview) URL.revokeObjectURL(imagePreview)
    setImageFile(null)
    setImagePreview(null)
  }

  // Video upload handler
  const handleVideoChange = (e) => {
    const file = e.target.files?.[0]
    if (!file) return

    if (!file.type.startsWith('video/')) {
      setSubmitStatus({ type: 'error', message: 'Please select a valid video file (MP4, WebM, MOV).' })
      return
    }
    if (file.size > 50 * 1024 * 1024) {
      setSubmitStatus({ type: 'error', message: 'Video size exceeds the 50 MB limit.' })
      return
    }

    if (videoPreview) URL.revokeObjectURL(videoPreview)
    setVideoFile(file)
    setVideoPreview(URL.createObjectURL(file))
    setSubmitStatus({ type: null, message: '' })
  }

  const removeVideo = () => {
    if (videoPreview) URL.revokeObjectURL(videoPreview)
    setVideoFile(null)
    setVideoPreview(null)
  }

  // Submit testimonial
  const handleSubmit = async (e) => {
    e.preventDefault()
    setSubmitStatus({ type: null, message: '' })

    if (!form.name.trim()) {
      setSubmitStatus({ type: 'error', message: 'Please provide your name.' })
      return
    }

    if (!form.review.trim() && !imageFile && !videoFile) {
      setSubmitStatus({
        type: 'error',
        message: 'Please provide a written review, photo, or video.'
      })
      return
    }

    const formData = new FormData()
    formData.append('name', form.name.trim())
    formData.append('location', form.location.trim() || 'Verified Customer')
    formData.append('rating', Number(form.rating) || 5)
    if (form.review.trim()) {
      formData.append('review', form.review.trim())
    }
    if (imageFile) {
      formData.append('image', imageFile)
    }
    if (videoFile) {
      formData.append('video', videoFile)
    }

    setIsSubmitting(true)
    try {
      const res = await reviewsApi.addTestimonial(formData)
      if (res.success) {
        setSubmitStatus({
          type: 'success',
          message:
            res.message ||
            'Thank you for your feedback! Your submission has been securely delivered to our admin team for verification. Once approved, it will be published to the community showcase.'
        })

        // Reset form
        setForm({ name: '', location: '', rating: 5, review: '' })
        removeImage()
        removeVideo()
      } else {
        setSubmitStatus({
          type: 'error',
          message: res.message || 'Failed to submit review. Please try again.'
        })
      }
    } catch (err) {
      setSubmitStatus({
        type: 'error',
        message: 'Network error. Please check your connection and try again.'
      })
    } finally {
      setIsSubmitting(false)
    }
  }

  // Cleanup blob URLs on unmount
  useEffect(() => {
    return () => {
      if (imagePreview) URL.revokeObjectURL(imagePreview)
      if (videoPreview) URL.revokeObjectURL(videoPreview)
    }
  }, [imagePreview, videoPreview])

  // Visible slice
  const visibleCards = filteredList.slice(currentIndex, currentIndex + cardsPerView)

  return (
    <section className="testimonials-section-v2" id="community-testimonials">
      <div className="container">
        {/* Section Header */}
        <div className="testimonials-header-v2">
          <div className="testimonials-pill-tag">
            <span className="sparkle">✦</span>
            <span>VERIFIED COMMUNITY REVIEWS</span>
            <span className="sparkle">✦</span>
          </div>
          <h2 className="testimonials-title-v2">
            Loved by Over <span>10,000+</span> Indian Families
          </h2>
          <p className="testimonials-subtitle-v2">
            Real experiences, unboxing photos, and authentic stories shared by patrons who chose pure organic nourishment.
          </p>

          {/* Controls Bar: Filters & Share Button */}
          <div className="testimonials-controls-row">
            <div className="testimonials-filters-pill" role="tablist">
              <button
                type="button"
                className={`filter-btn ${filter === 'all' ? 'active' : ''}`}
                onClick={() => setFilter('all')}
              >
                All Stories <span className="filter-count">({allList.length})</span>
              </button>
              <button
                type="button"
                className={`filter-btn ${filter === 'photos' ? 'active' : ''}`}
                onClick={() => setFilter('photos')}
              >
                📸 With Photos
              </button>
              <button
                type="button"
                className={`filter-btn ${filter === 'videos' ? 'active' : ''}`}
                onClick={() => setFilter('videos')}
              >
                🎬 Video Reviews
              </button>
              <button
                type="button"
                className={`filter-btn ${filter === 'five-star' ? 'active' : ''}`}
                onClick={() => setFilter('five-star')}
              >
                ⭐ 5-Star Only
              </button>
            </div>

            <button
              type="button"
              className={`share-story-btn ${isFormOpen ? 'open' : ''}`}
              onClick={() => setIsFormOpen((prev) => !prev)}
            >
              <span className="btn-icon">{isFormOpen ? '✕' : '✍️'}</span>
              <span>{isFormOpen ? 'Close Form' : 'Share Your Experience'}</span>
            </button>
          </div>
        </div>

        {/* Carousel Showcase */}
        <div className="testimonials-carousel-wrapper">
          {filteredList.length > cardsPerView && (
            <button
              type="button"
              className="carousel-nav-btn prev-btn"
              onClick={prevSlide}
              aria-label="Previous testimonials"
            >
              ‹
            </button>
          )}

          <div className="testimonials-cards-grid">
            {visibleCards.map((item, idx) => {
              const hasPhoto = Boolean(item.image)
              const hasVideo = Boolean(item.video)
              const mediaUrl = hasPhoto ? resolveMediaUrl(item.image) : null
              const videoUrl = hasVideo ? resolveMediaUrl(item.video) : null

              return (
                <article key={item.id || idx} className={`testimonial-card-v2 ${hasPhoto || hasVideo ? 'has-media-card' : 'text-only-card'}`}>
                  {/* Card Top: Stars & Verified Badge */}
                  <div className="card-top-meta">
                    <div className="card-stars">
                      {[...Array(item.rating || 5)].map((_, starIdx) => (
                        <span key={starIdx} className="star-symbol">★</span>
                      ))}
                      <span className="rating-pill">{item.rating || 5}.0</span>
                    </div>
                    <span className="verified-badge">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                      </svg>
                      Verified Buyer
                    </span>
                  </div>

                  {/* Card Body: Media + Review OR Text Highlight */}
                  {hasPhoto || hasVideo ? (
                    <div className="card-media-flow">
                      {hasVideo && videoUrl && (
                        <div className="card-media-box video-box">
                          <video
                            src={videoUrl}
                            controls
                            playsInline
                            preload="metadata"
                            className="card-video-element"
                          />
                          <span className="media-type-badge video-badge">
                            <span className="badge-dot">●</span> Video Review
                          </span>
                        </div>
                      )}

                      {hasPhoto && mediaUrl && !hasVideo && (
                        <div
                          className="card-media-box photo-box"
                          onClick={() => setActiveLightbox(item)}
                          title="Click to view full photo in high definition"
                        >
                          <img
                            src={mediaUrl}
                            alt={`${item.name}'s testimonial`}
                            className="card-photo-element"
                            loading="lazy"
                            onError={(e) => {
                              e.currentTarget.style.display = 'none'
                            }}
                          />
                          <div className="photo-expand-badge">
                            <span>🔍 Click to Enlarge</span>
                          </div>
                        </div>
                      )}

                      <div className="card-content-body">
                        <p className="card-review-text">
                          {item.review ? `"${item.review}"` : 'Customer shared an authentic verified photo of their order.'}
                        </p>
                      </div>
                    </div>
                  ) : (
                    /* Text-Only Card: Rich Editorial Quote Block (NO EMPTY VOID) */
                    <div className="card-text-highlight-flow">
                      <div className="quote-mark-large">“</div>
                      <p className="card-review-text large-editorial">
                        "{item.review}"
                      </p>
                      <div className="text-trust-pill">
                        <span className="leaf-icon">🌿</span>
                        <span>100% Certified Organic Pantry</span>
                      </div>
                    </div>
                  )}

                  {/* Customer Profile Signature (Aligned at bottom) */}
                  <div className="card-author-footer">
                    <div className="author-avatar-circle">
                      {hasPhoto && mediaUrl ? (
                        <img
                          src={mediaUrl}
                          alt={item.name}
                          className="author-avatar-img"
                          onError={(e) => {
                            e.currentTarget.style.display = 'none'
                          }}
                        />
                      ) : (
                        <span className="author-avatar-initial">
                          {item.name ? item.name.charAt(0).toUpperCase() : 'P'}
                        </span>
                      )}
                    </div>
                    <div className="author-details">
                      <h4 className="author-name">{item.name}</h4>
                      <p className="author-location">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor" style={{ marginRight: '4px' }}>
                          <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        {item.location || 'Verified Customer'}
                      </p>
                    </div>
                  </div>
                </article>
              )
            })}
          </div>

          {filteredList.length > cardsPerView && (
            <button
              type="button"
              className="carousel-nav-btn next-btn"
              onClick={nextSlide}
              aria-label="Next testimonials"
            >
              ›
            </button>
          )}
        </div>

        {/* Carousel Progress Indicators */}
        {filteredList.length > cardsPerView && (
          <div className="carousel-pagination-dots">
            {Array.from({ length: maxIndex + 1 }).map((_, dotIdx) => (
              <button
                key={dotIdx}
                type="button"
                className={`pagination-dot ${dotIdx === currentIndex ? 'active' : ''}`}
                onClick={() => setCurrentIndex(dotIdx)}
                aria-label={`Go to slide ${dotIdx + 1}`}
              />
            ))}
          </div>
        )}

        {/* Expandable Submission Form */}
        <AnimatePresence>
          {isFormOpen && (
            <motion.div
              className="submission-panel-wrapper"
              initial={{ opacity: 0, height: 0, y: -20 }}
              animate={{ opacity: 1, height: 'auto', y: 0 }}
              exit={{ opacity: 0, height: 0, y: -20 }}
              transition={{ duration: 0.35, ease: 'easeOut' }}
            >
              <form className="luxury-review-form" onSubmit={handleSubmit}>
                <div className="form-head">
                  <div className="form-head-pill">YOUR VOICE MATTERS</div>
                  <h3>Share Your Prakruti Experience</h3>
                  <p>
                    Upload photos, unboxing videos, or your genuine experience. All submissions are verified by our team before going live on our homepage.
                  </p>
                </div>

                {submitStatus.type === 'success' && (
                  <div className="form-status-alert success-alert" role="alert">
                    <span className="status-icon">✓</span>
                    <div className="status-text">
                      <strong>Submitted for Verification</strong>
                      <p>{submitStatus.message}</p>
                    </div>
                  </div>
                )}

                {submitStatus.type === 'error' && (
                  <div className="form-status-alert error-alert" role="alert">
                    <span className="status-icon">⚠️</span>
                    <div className="status-text">
                      <strong>Please Note</strong>
                      <p>{submitStatus.message}</p>
                    </div>
                  </div>
                )}

                {/* Rating Stars Picker */}
                <div className="rating-picker-section">
                  <span className="picker-label">Rate Your Experience:</span>
                  <div className="interactive-stars">
                    {[1, 2, 3, 4, 5].map((star) => (
                      <button
                        key={star}
                        type="button"
                        className={`star-choice ${(hoverRating || form.rating) >= star ? 'selected' : ''}`}
                        onMouseEnter={() => setHoverRating(star)}
                        onMouseLeave={() => setHoverRating(0)}
                        onClick={() => setForm((prev) => ({ ...prev, rating: star }))}
                        aria-label={`Rate ${star} star`}
                      >
                        ★
                      </button>
                    ))}
                    <span className="rating-text-label">
                      {(hoverRating || form.rating) === 5 && 'Outstanding Quality (5/5)'}
                      {(hoverRating || form.rating) === 4 && 'Very Satisfied (4/5)'}
                      {(hoverRating || form.rating) === 3 && 'Good Experience (3/5)'}
                      {(hoverRating || form.rating) === 2 && 'Fair (2/5)'}
                      {(hoverRating || form.rating) === 1 && 'Needs Improvement (1/5)'}
                    </span>
                  </div>
                </div>

                {/* Customer Details Row */}
                <div className="form-inputs-row">
                  <div className="input-group">
                    <label htmlFor="client-name">Your Full Name *</label>
                    <input
                      id="client-name"
                      type="text"
                      placeholder="e.g. Radhika Sharma"
                      value={form.name}
                      onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                      required
                    />
                  </div>
                  <div className="input-group">
                    <label htmlFor="client-location">City & State</label>
                    <input
                      id="client-location"
                      type="text"
                      placeholder="e.g. Vadodara, Gujarat"
                      value={form.location}
                      onChange={(e) => setForm((prev) => ({ ...prev, location: e.target.value }))}
                    />
                  </div>
                </div>

                {/* Written Review */}
                <div className="input-group full-width">
                  <label htmlFor="client-review">Your Experience / Feedback (Optional if uploading media)</label>
                  <textarea
                    id="client-review"
                    rows="3"
                    placeholder="Tell us what you loved about our organic staples, packaging, freshness, or delivery..."
                    value={form.review}
                    onChange={(e) => setForm((prev) => ({ ...prev, review: e.target.value }))}
                  />
                </div>

                {/* Media Upload Drops */}
                <div className="media-drops-grid">
                  {/* Photo Drop */}
                  <div className="media-drop-card">
                    <label className="media-drop-label" htmlFor="review-photo-file">
                      <span className="drop-icon">📷</span>
                      <span className="drop-title">{imageFile ? 'Change Photo' : 'Upload Photo'}</span>
                      <span className="drop-hint">JPG, PNG, WebP up to 12MB</span>
                      <input
                        id="review-photo-file"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        onChange={handleImageChange}
                        style={{ display: 'none' }}
                      />
                    </label>

                    {imageFile && (
                      <div className="selected-media-chip">
                        {imagePreview && (
                          <img src={imagePreview} alt="Selected preview" className="chip-thumb" />
                        )}
                        <span className="chip-name" title={imageFile.name}>
                          {imageFile.name}
                        </span>
                        <button
                          type="button"
                          className="chip-remove"
                          onClick={removeImage}
                          title="Remove file"
                        >
                          ✕
                        </button>
                      </div>
                    )}
                  </div>

                  {/* Video Drop */}
                  <div className="media-drop-card">
                    <label className="media-drop-label" htmlFor="review-video-file">
                      <span className="drop-icon">🎥</span>
                      <span className="drop-title">{videoFile ? 'Change Video' : 'Upload Video'}</span>
                      <span className="drop-hint">MP4, WebM, MOV up to 50MB</span>
                      <input
                        id="review-video-file"
                        type="file"
                        accept="video/mp4,video/webm,video/quicktime"
                        onChange={handleVideoChange}
                        style={{ display: 'none' }}
                      />
                    </label>

                    {videoFile && (
                      <div className="selected-media-chip">
                        <span className="chip-video-icon">🎬</span>
                        <span className="chip-name" title={videoFile.name}>
                          {videoFile.name} ({(videoFile.size / (1024 * 1024)).toFixed(1)} MB)
                        </span>
                        <button
                          type="button"
                          className="chip-remove"
                          onClick={removeVideo}
                          title="Remove video"
                        >
                          ✕
                        </button>
                      </div>
                    )}
                  </div>
                </div>

                {/* Form Action Button */}
                <div className="form-submit-actions">
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="submit-review-btn"
                  >
                    {isSubmitting ? (
                      <span>Uploading &amp; Submitting...</span>
                    ) : (
                      <span>Submit for Community Showcase</span>
                    )}
                  </button>
                  <p className="admin-moderation-disclaimer">
                    🔒 <strong>Admin Verified:</strong> To protect our community from spam, submissions are reviewed and approved by our team before appearing live.
                  </p>
                </div>
              </form>
            </motion.div>
          )}
        </AnimatePresence>
      </div>

      {/* Lightbox Modal for Photo Enlarge */}
      <AnimatePresence>
        {activeLightbox && (
          <motion.div
            className="testimonial-lightbox-backdrop"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setActiveLightbox(null)}
          >
            <motion.div
              className="lightbox-dialog"
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              onClick={(e) => e.stopPropagation()}
            >
              <button
                type="button"
                className="lightbox-close-btn"
                onClick={() => setActiveLightbox(null)}
                aria-label="Close photo preview"
              >
                ✕
              </button>

              <div className="lightbox-image-frame">
                <img
                  src={resolveMediaUrl(activeLightbox.image)}
                  alt={activeLightbox.name}
                  className="lightbox-img"
                />
              </div>

              <div className="lightbox-caption">
                <div className="lightbox-author-row">
                  <div>
                    <h4 className="lightbox-name">{activeLightbox.name}</h4>
                    <span className="lightbox-location">{activeLightbox.location || 'Verified Buyer'}</span>
                  </div>
                  <div className="lightbox-stars">
                    {[...Array(activeLightbox.rating || 5)].map((_, i) => (
                      <span key={i} className="star-symbol">★</span>
                    ))}
                  </div>
                </div>
                {activeLightbox.review && (
                  <p className="lightbox-quote">"{activeLightbox.review}"</p>
                )}
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </section>
  )
}

export default Testimonials
