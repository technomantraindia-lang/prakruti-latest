  import React, { useEffect, useState } from 'react'
import './ShopByCategory.css'
import { categoriesApi } from '../../api/categories'
import cerealPulsesImg from '../../assets/images/Final packaging/mix dal.png'
import indianSpicesImg from '../../assets/images/Final packaging/lal mirch.png'
import milletsImg from '../../assets/images/milltet.png'
import oilsGheeImg from '../../assets/images/Final packaging/gheee.png'
import littleMilletImg from '../../assets/images/Final packaging/litlle millet.png'
import sweetenersImg from '../../assets/images/sweeterns.png'
import healthySeedsImg from '../../assets/images/Final packaging/image copy 4.png'
import riceFlourImg from '../../assets/images/Final packaging/bajra at.png'

const localCategories = [
  { name: 'Cereal & Pulses', image: cerealPulsesImg, imageFit: 'contain' },
  { name: 'Indian Spices', image: indianSpicesImg, imageFit: 'contain' },
  { name: 'Oils & Ghee', image: oilsGheeImg, imageFit: 'contain' },
  { name: 'Millets', image: littleMilletImg, imageFit: 'contain' },
  { name: 'Sweeteners', image: sweetenersImg },
  { name: 'Healthy Seeds', image: healthySeedsImg, imageFit: 'contain' },
  { name: 'Rice & Flours', image: riceFlourImg, imageFit: 'contain' },
]

const categoryOrder = localCategories.reduce((order, category, index) => {
  order[category.name.toLowerCase()] = index
  return order
}, {})

const ShopByCategory = ({ onCategoryClick }) => {
  const [categories, setCategories] = useState(localCategories)

  useEffect(() => {
    let mounted = true
    categoriesApi.getCategories().then((res) => {
      if (!mounted || !res.success || !res.data?.length) return
      const merged = res.data.map((cat) => {
        const local = localCategories.find((item) => item.name.toLowerCase() === String(cat.name || '').toLowerCase())
        return {
          name: cat.name,
          image: local?.imageFit ? local.image : (cat.image || local?.image),
          imageFit: local?.imageFit,
        }
      }).filter((cat) => cat.image)
        .sort((a, b) => {
          const aOrder = categoryOrder[String(a.name || '').toLowerCase()] ?? 999
          const bOrder = categoryOrder[String(b.name || '').toLowerCase()] ?? 999
          return aOrder - bOrder
        })
      if (merged.length) setCategories(merged)
    })
    return () => { mounted = false }
  }, [])

  return (
    <section className="shop-by-category" id="shop">
      <div className="container">
        <div className="section-header">
          <h2 className="section-title">Shop by Category</h2>
          <p className="section-subtitle">Discover our handpicked selection of pure and organic daily staples</p>
        </div>

        <div className="category-grid">
          {categories.map((category, index) => (
            <a
              key={category.name}
              href={`#${category.name.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`}
              className="category-card animate-stagger"
              style={{ '--slide-order': index }}
              aria-label={`Shop ${category.name}`}
              onClick={(e) => {
                if (onCategoryClick) {
                  e.preventDefault();
                  onCategoryClick(category.name);
                }
              }}
            >
              <div className="category-image-wrap">
                <img
                  src={category.image || cerealPulsesImg}
                  alt={category.name}
                  className={`category-image${category.imageFit ? ` category-image-${category.imageFit}` : ''}`}
                  loading="lazy"
                  onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = cerealPulsesImg;
                  }}
                />
                <div className="card-shine-effect"></div>
              </div>
              <div className="category-card-footer">
                <h3 className="category-name">
                  {category.name} <span className="cat-arrow">→</span>
                </h3>
              </div>
            </a>
          ))}
        </div>
      </div>
    </section>
  )
}

export default ShopByCategory
