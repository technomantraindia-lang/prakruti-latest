import React, { useEffect, useMemo, useRef, useState } from 'react'
import './BestSellers.css'
import { useCart } from '../../context/CartContext'
import { productsApi } from '../../api/products'

import masoorDalImg from '../../assets/images/bestseller_masoor_dal.jpg'
import girGheeImg from '../../assets/images/ghee.png'
import jeerakaImg from '../../assets/images/bestseller_jeeraka.jpg'
import bengaluGunduImg from '../../assets/images/bestseller_bengalu_gundu.jpg'
import tuarDalImg from '../../assets/images/bestseller_tuar_dal.jpg'

export const BEST_SELLER_PRODUCTS = [
  {
    id: 1,
    name: 'Masoor Dal (Split Lentil)\n(1kg)',
    displayName: 'Masoor Dal (Split Lentil) (1kg)',
    rating: 5,
    reviews: 34,
    originalPrice: 160,
    price: 120,
    image: masoorDalImg,
    category: 'Cereal & Pulses'
  },
  {
    id: 29,
    name: 'Gir Organic A2 Desi\nCow Ghee (500ml)',
    displayName: 'Gir Organic A2 Desi Cow Ghee (500ml)',
    rating: 5,
    reviews: 28,
    originalPrice: 750,
    price: 599,
    image: girGheeImg,
    category: 'Oils & Ghee'
  },
  {
    id: 50,
    name: 'Pilipili Jeeraka\n(200g)',
    displayName: 'Pilipili Jeeraka (200g)',
    rating: 5,
    reviews: 12,
    originalPrice: 200,
    price: 150,
    image: jeerakaImg,
    category: 'Indian Spices'
  },
  {
    id: 51,
    name: 'Ralii Bengalu Gundu\n(2kg)',
    displayName: 'Ralii Bengalu Gundu (2kg)',
    rating: 5,
    reviews: 10,
    originalPrice: 160,
    price: 110,
    image: bengaluGunduImg,
    category: 'Cereal & Pulses'
  },
  {
    id: 5,
    name: 'Tuar Dal/Tur/Arhar\n(1kg)',
    displayName: 'Tuar Dal/Tur/Arhar (1kg)',
    rating: 5,
    reviews: 14,
    originalPrice: 160,
    price: 120,
    image: tuarDalImg,
    category: 'Cereal & Pulses'
  }
]

function normalizeBestSeller(product) {
  const firstVariation = Array.isArray(product.variations) ? product.variations[0] : null
  const price = Number(product.price || firstVariation?.price || 0)
  const originalPrice = Number(product.originalPrice || product.regular_price || price)
  const availableStock = product.available_stock !== undefined
    ? Number(product.available_stock)
    : (firstVariation?.available_stock !== undefined
        ? Number(firstVariation.available_stock)
        : Number(product.stock_qty || 0))
  const isOutOfStock = Boolean(product.is_out_of_stock || availableStock <= 0 || product.stock_status === 'out_of_stock')

  return {
    ...product,
    id: product.id,
    varId: firstVariation?.id || null,
    displayName: product.displayName || product.name,
    rating: Math.min(5, Math.max(1, Math.round(Number(product.rating || 5)))),
    reviews: product.reviews || 0,
    originalPrice: originalPrice > price ? originalPrice : null,
    price,
    image: product.image,
    category: product.category || 'Organic',
    availableStock,
    isOutOfStock,
  }
}

const BestSellers = ({ onViewProduct, onViewAll }) => {
  const [addingId, setAddingId] = useState(null)
  const [products, setProducts] = useState([])
  const [isInteracting, setIsInteracting] = useState(false)
  const marqueeRef = useRef(null)
  const interactionTimerRef = useRef(null)
  const { addToCart } = useCart()

  useEffect(() => {
    let mounted = true

    productsApi.getProducts({ featured: true, per_page: 100 }).then((res) => {
      if (!mounted) return
      setProducts((res.data || []).map(normalizeBestSeller))
    }).catch((error) => {
      console.error(error)
      if (mounted) setProducts(BEST_SELLER_PRODUCTS)
    })

    return () => {
      mounted = false
    }
  }, [])

  const marqueeSets = useMemo(() => (
    products.length ? Array.from({ length: 4 }, () => products) : []
  ), [products])

  const pauseAutoScroll = (duration = 4500) => {
    setIsInteracting(true)
    if (interactionTimerRef.current) clearTimeout(interactionTimerRef.current)
    interactionTimerRef.current = setTimeout(() => {
      setIsInteracting(false)
    }, duration)
  }

  const handleManualScroll = (direction) => {
    const el = marqueeRef.current
    if (!el) return

    pauseAutoScroll(4500)

    const scrollAmount = 320
    const setWidth = el.scrollWidth / 4

    if (direction === 'left') {
      if (el.scrollLeft <= 50 && setWidth > 0) {
        el.scrollLeft += setWidth * 2
      }
      el.scrollBy({ left: -scrollAmount, behavior: 'smooth' })
    } else {
      if (el.scrollLeft >= setWidth * 3 - 50 && setWidth > 0) {
        el.scrollLeft -= setWidth * 2
      }
      el.scrollBy({ left: scrollAmount, behavior: 'smooth' })
    }
  }

  useEffect(() => {
    const el = marqueeRef.current
    if (!el || !products.length) return

    let animationFrameId
    let lastTime = performance.now()
    const speed = 0.65

    const step = (now) => {
      const delta = (now - lastTime) / 16.67
      lastTime = now

      if (!isInteracting && el) {
        el.scrollLeft += speed * Math.min(delta, 3)

        const setWidth = el.scrollWidth / 4
        if (setWidth > 0 && el.scrollLeft >= setWidth * 2) {
          el.scrollLeft -= setWidth
        } else if (setWidth > 0 && el.scrollLeft <= 0) {
          el.scrollLeft += setWidth
        }
      }

      animationFrameId = requestAnimationFrame(step)
    }

    animationFrameId = requestAnimationFrame(step)
    return () => {
      cancelAnimationFrame(animationFrameId)
      if (interactionTimerRef.current) clearTimeout(interactionTimerRef.current)
    }
  }, [isInteracting, products.length])

  if (!products.length) {
    return null
  }

  return (
    <section className="best-sellers" id="bestsellers">
      <div className="container">
        <div className="best-sellers-header">
          <h2 className="section-title">Best Sellers</h2>
          <div className="best-sellers-actions">
            <div className="bestsellers-nav-arrows">
              <button
                type="button"
                className="bestsellers-nav-btn prev"
                onClick={() => handleManualScroll('left')}
                aria-label="Scroll Best Sellers Left"
                title="Scroll Left"
              >
                <span aria-hidden="true">&larr;</span>
              </button>
              <button
                type="button"
                className="bestsellers-nav-btn next"
                onClick={() => handleManualScroll('right')}
                aria-label="Scroll Best Sellers Right"
                title="Scroll Right"
              >
                <span aria-hidden="true">&rarr;</span>
              </button>
            </div>
            <button
              type="button"
              className="view-all-link"
              onClick={onViewAll}
            >
              <span>View All Products</span>
              <span className="view-all-arrow">&rarr;</span>
            </button>
          </div>
        </div>

        <div className="bestsellers-carousel-wrapper">
          <button
            type="button"
            className="bestsellers-side-btn prev"
            onClick={() => handleManualScroll('left')}
            aria-label="Previous Products"
            title="Previous"
          >
            &#10094;
          </button>

          <div 
            className="products-marquee"
            ref={marqueeRef}
            onMouseEnter={() => setIsInteracting(true)}
            onMouseLeave={() => setIsInteracting(false)}
            onTouchStart={() => setIsInteracting(true)}
            onTouchEnd={() => pauseAutoScroll(3000)}
          >
            <div className="products-track">
              {marqueeSets.map((set, setIndex) => (
                <div className="products-set" key={setIndex} aria-hidden={setIndex > 0}>
                  {set.map((product) => {
                    const isDuplicate = setIndex > 0

                    return (
                      <div
                        key={`${product.id}-${setIndex}`}
                        className="product-card"
                        onClick={() => onViewProduct && onViewProduct(product.id)}
                      >
                        <div className="product-image-wrap">
                          {product.isOutOfStock && (
                            <span className="out-of-stock-badge">Out of Stock</span>
                          )}
                          <img
                            src={product.image}
                            alt={product.displayName}
                            className="product-image"
                            loading="lazy"
                          />
                        </div>

                        <div className="product-info">
                          <h3 className="product-name">{product.displayName}</h3>

                          <div className="product-rating">
                            <div className="stars">
                              {[...Array(product.rating || 5)].map((_, i) => (
                                <span key={i} className="star filled">&#9733;</span>
                              ))}
                            </div>
                            <span className="review-count">({product.reviews})</span>
                          </div>

                          <div className="product-pricing">
                            {product.originalPrice ? (
                              <span className="original-price">&#8377;{product.originalPrice}</span>
                            ) : null}
                            <span className="current-price">&#8377;{product.price}</span>
                          </div>

                          <button
                            className={`add-to-cart-btn ${product.isOutOfStock ? 'btn-out-of-stock' : ''}`}
                            aria-label={product.isOutOfStock ? `${product.displayName} is Out of Stock` : `Add ${product.displayName} to Cart`}
                            disabled={product.isOutOfStock || addingId === product.id}
                            tabIndex={isDuplicate ? -1 : undefined}
                            onClick={async (event) => {
                              event.stopPropagation()
                              if (product.isOutOfStock) return
                              setAddingId(product.id)
                              try {
                                await addToCart(product.id, 1, product.varId || null)
                              } catch (e) {
                                console.error(e)
                              } finally {
                                setAddingId(null)
                              }
                            }}
                          >
                            {product.isOutOfStock ? 'Out of Stock' : (addingId === product.id ? 'Adding...' : '+ Add to Cart')}
                          </button>
                        </div>
                      </div>
                    )
                  })}
                </div>
              ))}
            </div>
          </div>

          <button
            type="button"
            className="bestsellers-side-btn next"
            onClick={() => handleManualScroll('right')}
            aria-label="Next Products"
            title="Next"
          >
            &#10095;
          </button>
        </div>
      </div>
    </section>
  )
}

export default BestSellers
