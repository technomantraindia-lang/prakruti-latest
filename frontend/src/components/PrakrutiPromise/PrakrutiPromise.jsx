import React, { useEffect, useRef, useState } from 'react'
import './PrakrutiPromise.css'
import organicOriginsImg from '../../assets/images/organic origins.png'
import pureIngredientsImg from '../../assets/images/pure indgrdients.png'
import pantryImg from '../../assets/images/image copy 3.png'
import goodnessHomeImg from '../../assets/images/godness home.png'

const promiseCards = [
  {
    number: '01',
    title: 'Organic origins',
    description: 'Our story starts with organic grains, pulses and spices.',
    image: organicOriginsImg,
    alt: 'Organic grains growing in a field',
  },
  {
    number: '02',
    title: 'Pure ingredients',
    description: 'The pantry essentials at the heart of everyday cooking.',
    image: pureIngredientsImg,
    alt: 'Pure pulses, rice and spices in serving bowls',
  },
  {
    number: '03',
    title: 'Ready for your pantry',
    description: 'Air-tight glass jars and bio-degradable pouches to preserve pure natural freshness and aroma.',
    image: pantryImg,
    alt: 'Glass jars and paper pouches filled with organic pantry essentials',
  },
  {
    number: '04',
    title: 'Goodness at home',
    description: 'Bring organic essentials into your daily meals.',
    image: goodnessHomeImg,
    alt: 'A wholesome meal made with organic essentials',
  },
]

const PrakrutiPromise = () => {
  const sectionRef = useRef(null)
  const [isSectionVisible, setIsSectionVisible] = useState(false)
  const [activeCard, setActiveCard] = useState(0)

  useEffect(() => {
    const section = sectionRef.current
    if (!section) return undefined

    const observer = new IntersectionObserver(
      ([entry]) => setIsSectionVisible(entry.isIntersecting),
      { threshold: 0.28 }
    )
    observer.observe(section)
    return () => observer.disconnect()
  }, [])

  useEffect(() => {
    if (!isSectionVisible || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return undefined

    const timer = window.setInterval(() => {
      setActiveCard((current) => (current + 1) % promiseCards.length)
    }, 1350)

    return () => window.clearInterval(timer)
  }, [isSectionVisible])

  return (
  <section ref={sectionRef} className={`prakruti-promise ${isSectionVisible ? 'is-visible' : ''}`} aria-labelledby="prakruti-promise-title">
    <div className="container">
      <div className="promise-intro">
        <p className="promise-eyebrow">The Prakruti promise</p>
        <span className="promise-eyebrow-line" aria-hidden="true" />
        <h2 id="prakruti-promise-title" className="promise-title">
          Pure goodness. <em>Every step of the way.</em>
        </h2>
        <p className="promise-subtitle">A simple journey from organic ingredients to your everyday kitchen.</p>
      </div>

      <div className="prakruti-promise-grid">
        <span className="promise-flow-beam" aria-hidden="true" />
        {promiseCards.map((card, index) => (
          <React.Fragment key={card.title}>
            <article
              className={`prakruti-promise-card card-index-${index} ${isSectionVisible && activeCard === index ? 'is-active' : ''}`}
              style={{ '--card-order': index }}
            >
              <span className="promise-number">{card.number}</span>
              <div className="promise-image-wrap">
                <img src={card.image} alt={card.alt} className="promise-image" loading="lazy" />
              </div>
              <div className="promise-card-content">
                <h3>{card.title}</h3>
                <p>{card.description}</p>
              </div>
            </article>
            {index < promiseCards.length - 1 && (
              <span className={`prakruti-promise-arrow arrow-${index}`} aria-hidden="true" />
            )}
          </React.Fragment>
        ))}
      </div>

      <a className="promise-cta" href="#categories">
        Shop organic essentials <span aria-hidden="true">-&gt;</span>
      </a>
    </div>
  </section>
  )
}

export default PrakrutiPromise
