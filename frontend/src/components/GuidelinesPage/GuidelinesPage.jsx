import React, { useState, useEffect, useMemo } from 'react'
import { motion } from 'framer-motion'
import { apiClient } from '../../api/client'
import './GuidelinesPage.css'

const FALLBACK_GUIDELINES = [
  {
    id: 'fb-1',
    title: 'ICMR-NIN Dietary Guidelines for Indians (2024)',
    description: 'Official comprehensive dietary guidelines for all age groups, macro/micronutrient allowances, food group recommendations, and prevention of lifestyle disorders.',
    file_size: '4.8 MB',
    file_path: null,
    download_url: 'https://www.nin.res.in/dietaryguidelines/dietaryguidelinesforindians.pdf'
  },
  {
    id: 'fb-2',
    title: 'WHO Guideline: Sodium & Salt Intake for Adults and Children',
    description: 'Official WHO dietary guideline outlining daily sodium thresholds (less than 2000mg sodium or 5g salt per day) to reduce blood pressure and cardiovascular risk.',
    file_size: '1.2 MB',
    file_path: null,
    download_url: 'https://www.who.int/publications/i/item/9789241504836'
  },
  {
    id: 'fb-3',
    title: 'WHO Guideline: Sugars Intake for Adults and Children',
    description: 'Guidelines on limiting free sugars to less than 10% of total daily energy intake, and recommendations to reduce below 5% for enhanced health protection.',
    file_size: '1.4 MB',
    file_path: null,
    download_url: 'https://www.who.int/publications/i/item/9789241549028'
  },
  {
    id: 'fb-4',
    title: 'WHO Guideline: Total Fat & Trans-Fatty Acid Reduction',
    description: 'Evidence-based global recommendations for reducing saturated fatty acids and eliminating industrially-produced trans-fatty acids from consumer foods.',
    file_size: '2.1 MB',
    file_path: null,
    download_url: 'https://www.who.int/publications/i/item/9789240073654'
  },
  {
    id: 'fb-5',
    title: 'WHO Guideline: Carbohydrate Quality & Whole Grains',
    description: 'Science-backed guidance prioritizing natural intact dietary fiber, millets, pulses, vegetables, and whole grain nutrition for metabolic wellness.',
    file_size: '1.6 MB',
    file_path: null,
    download_url: 'https://www.who.int/publications/i/item/9789240073593'
  },
  {
    id: 'fb-6',
    title: 'Prakruti Cold-Pressed Oil & Vedic Ghee Purity Research',
    description: 'Comprehensive research paper on traditional A2 Vedic Bilona ghee processing, wood-pressed oil extraction under 40°C, and antioxidant nutrient retention.',
    file_size: '1.9 MB',
    file_path: null,
    download_url: null
  }
]

export default function GuidelinesPage({ onBackToEducation, onShopClick }) {
  const [guidelines, setGuidelines] = useState([])
  const [loading, setLoading] = useState(true)
  const [searchQuery, setSearchQuery] = useState('')

  useEffect(() => {
    let isMounted = true

    const fetchGuidelines = async () => {
      try {
        const res = await apiClient('/guidelines')
        if (isMounted) {
          if (res?.data && Array.isArray(res.data) && res.data.length > 0) {
            setGuidelines(res.data)
          } else {
            setGuidelines(FALLBACK_GUIDELINES)
          }
        }
      } catch (err) {
        console.warn('Could not fetch guidelines from backend, using default library:', err)
        if (isMounted) {
          setGuidelines(FALLBACK_GUIDELINES)
        }
      } finally {
        if (isMounted) {
          setLoading(false)
        }
      }
    }

    fetchGuidelines()
    return () => {
      isMounted = false
    }
  }, [])

  const filteredGuidelines = useMemo(() => {
    if (!searchQuery.trim()) return guidelines
    const q = searchQuery.toLowerCase().trim()
    return guidelines.filter(
      (g) =>
        g.title?.toLowerCase().includes(q) ||
        g.description?.toLowerCase().includes(q)
    )
  }, [guidelines, searchQuery])

  return (
    <div className="guidelines-page">
      {/* Top Banner & Header */}
      <section className="guidelines-hero">
        <div className="container">
          <div className="guidelines-breadcrumb">
            <button
              type="button"
              className="guidelines-back-btn"
              onClick={onBackToEducation || (() => { window.location.hash = 'education' })}
            >
              ← Back to Education &amp; Awareness
            </button>
          </div>

          <motion.div
            className="guidelines-hero-content"
            initial={{ opacity: 0, y: 24 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6 }}
          >
            <span className="guidelines-hero-badge">
              <span className="badge-sparkle">📄</span> OFFICIAL RESEARCH ARCHIVE
            </span>
            <h1 className="guidelines-hero-title">PDF Guidelines &amp; Research Library</h1>
            <p className="guidelines-hero-subtitle">
              Browse, read, and download verified dietary guidelines, nutritional blueprints, and authentic health research documents.
            </p>

            {/* Search Box */}
            <div className="guidelines-search-wrap">
              <div className="guidelines-search-box">
                <span className="guidelines-search-icon">🔍</span>
                <input
                  type="text"
                  placeholder="Search guidelines by title or description..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="guidelines-search-input"
                />
                {searchQuery && (
                  <button
                    type="button"
                    className="guidelines-search-clear"
                    onClick={() => setSearchQuery('')}
                    title="Clear search"
                  >
                    ✕
                  </button>
                )}
              </div>
            </div>
          </motion.div>
        </div>
      </section>

      {/* Guidelines Grid */}
      <section className="guidelines-content-section">
        <div className="container">
          {loading ? (
            <div className="guidelines-loading-box">
              <div className="guidelines-spinner" />
              <p>Loading PDF guidelines library...</p>
            </div>
          ) : filteredGuidelines.length === 0 ? (
            <div className="guidelines-empty-box">
              <span className="empty-icon">📁</span>
              <h3>No Guidelines Found</h3>
              <p>We could not find any guidelines matching &ldquo;{searchQuery}&rdquo;.</p>
              <button
                type="button"
                className="guidelines-reset-btn"
                onClick={() => setSearchQuery('')}
              >
                Clear Search
              </button>
            </div>
          ) : (
            <div className="guidelines-grid">
              {filteredGuidelines.map((item, index) => {
                const targetUrl = item.file_path
                  ? `/storage/${item.file_path}`
                  : item.download_url || item.external_url

                return (
                  <motion.div
                    key={item.id || index}
                    className="guideline-card"
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ duration: 0.4, delay: Math.min(index * 0.05, 0.4) }}
                    whileHover={{ y: -6, transition: { duration: 0.2 } }}
                  >
                    <div className="guideline-card-top">
                      <div className="guideline-doc-icon">
                        <span>PDF</span>
                      </div>
                      {item.file_size && (
                        <span className="guideline-size-tag">{item.file_size}</span>
                      )}
                    </div>

                    <h2 className="guideline-item-title">{item.title}</h2>
                    <p className="guideline-item-desc">{item.description}</p>

                    <div className="guideline-card-action">
                      {targetUrl ? (
                        <a
                          href={targetUrl}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="guideline-action-btn"
                        >
                          <span className="btn-icon">📥</span> Download PDF Document
                        </a>
                      ) : (
                        <span className="guideline-archived-tag">Official PDF Archived</span>
                      )}
                    </div>
                  </motion.div>
                )
              })}
            </div>
          )}
        </div>
      </section>
    </div>
  )
}

