import React, { useState, useEffect, useMemo } from 'react';
import './ProductDetailPage.css';
import { FiActivity, FiFeather, FiFileText, FiChevronDown, FiChevronUp } from 'react-icons/fi';
import { productsData as fallbackProducts } from '../../data/products';
import { api } from '../../services/api';
import { reviewsApi } from '../../api/reviews';
import { useCart } from '../../context/CartContext';
import packgringImg from '../../assets/images/new packing .png';
import gheeDetailImg from '../../assets/images/ghhee.png';
import certificationBadges from '../../data/certificationBadges';

const formatPrice = (value) => `₹${Number(value || 0).toFixed(0)}`;

const getPackMultiplier = (packSize) => {
  if (packSize === '1 kg') return 1.8;
  if (packSize === '2 kg') return 3.4;
  if (packSize === '5 kg') return 8.0;
  return 1;
};

const getOriginalPrice = (product, currentPrice = Number(product?.price || 0)) => {
  const givenOriginal = Number(product?.originalPrice || product?.original_price || product?.mrp || product?.compare_at_price || 0);
  if (givenOriginal > Number(product?.price || 0)) {
    const multiplier = Number(product?.price || 0) ? currentPrice / Number(product.price || 1) : 1;
    return givenOriginal * multiplier;
  }
  return Math.max(currentPrice + 1, Math.round((currentPrice / 0.75) / 10) * 10);
};

const renderMultilineText = (value) => {
  const lines = String(value || '').trim().split(/\r?\n/).filter(Boolean);

  return lines.map((line, index) => (
    <React.Fragment key={`${line}-${index}`}>
      {line}
      {index < lines.length - 1 && <br />}
    </React.Fragment>
  ));
};

const renderFormattedBullets = (value) => {
  if (!value) return null;
  const rawLines = String(value || '').trim().split(/\r?\n/).map(l => l.trim()).filter(Boolean);
  const items = [];
  rawLines.forEach(line => {
    const clean = line.replace(/^[•\-\*\d\.\)]+\s*/, '').trim();
    if (clean) items.push(clean);
  });

  if (items.length <= 1) {
    const sentences = String(value || '').split(/(?<=[.?!])\s+/).map(s => s.trim()).filter(Boolean);
    if (sentences.length > 1) {
      return (
        <ul className="hyphen-bullet-list">
          {sentences.map((s, idx) => (
            <li key={idx}>{s}</li>
          ))}
        </ul>
      );
    }
  }

  return (
    <ul className="hyphen-bullet-list">
      {items.map((item, idx) => (
        <li key={idx}>{item}</li>
      ))}
    </ul>
  );
};

const ProductDetailPage = ({ productId, onBack, onNavigateProduct, onBuyNow }) => {
  const { addToCart, openComingSoonModal } = useCart();
  const [product, setProduct] = useState(() => {
    const found = fallbackProducts.find(p => String(p.id) === String(productId));
    return found || fallbackProducts[0];
  });
  const [relatedProducts, setRelatedProducts] = useState([]);
  const [addingRelatedId, setAddingRelatedId] = useState(null);
  const [quantity, setQuantity] = useState(1);
  const [packSize, setPackSize] = useState('500g');
  const [activeTab, setActiveTab] = useState('Overview');
  const [addedToCart, setAddedToCart] = useState(false);
  const [adding, setAdding] = useState(false);
  const [customerReviews, setCustomerReviews] = useState([]);
  const [reviewForm, setReviewForm] = useState({ name: '', location: '', rating: 5, comment: '' });
  const [reviewSubmitting, setReviewSubmitting] = useState(false);
  const [isClinicalReportOpen, setIsClinicalReportOpen] = useState(false);

  useEffect(() => {
    let isMounted = true;
    if (productId) {
      api.getProductBySlugOrId(productId).then(res => {
        if (isMounted && res) setProduct(res);
      });
    }
    return () => { isMounted = false; };
  }, [productId]);

  useEffect(() => {
    let isMounted = true;
    if (!product?.category) return undefined;
    api.getProducts({ category: product.category, per_page: 12 }).then((items) => {
      if (!isMounted) return;
      const baseList = (items && items.length > 1) ? items : fallbackProducts;
      let matched = baseList.filter((item) => String(item.id) !== String(product.id) && item.category === product.category);
      if (matched.length < 4) {
        matched = baseList.filter((item) => String(item.id) !== String(product.id));
      }
      setRelatedProducts(matched.slice(0, 5));
    });
    return () => { isMounted = false; };
  }, [product?.category, product?.id]);

  // Handle alternate images for thumbnail grid
  const productImages = useMemo(() => {
    const list = Array.isArray(product?.images) && product.images.length
      ? product.images
      : [product?.image || packgringImg];

    return list.filter(Boolean).filter((value, index, self) => self.indexOf(value) === index).slice(0, 7);
  }, [product]);

  const [activeImage, setActiveImage] = useState(() => product?.images?.[0] || product?.image || packgringImg);

  useEffect(() => {
    setActiveImage(productImages[0] || packgringImg);
  }, [productImages]);

  useEffect(() => {
    let isMounted = true;
    const reviewProductKey = product?.slug || product?.id || productId;
    if (!reviewProductKey) return undefined;

    reviewsApi.getProductReviews(reviewProductKey).then((res) => {
      if (!isMounted || !res.success || !Array.isArray(res.data)) return;
      setCustomerReviews(res.data);
    });

    return () => { isMounted = false; };
  }, [product?.slug, product?.id, productId]);

  const packageOptions = useMemo(() => {
    const apiPackages = Array.isArray(product?.variations)
      ? product.variations.filter((variation) => variation && variation.stock_status !== 'inactive')
      : [];

    if (apiPackages.length) {
      return apiPackages.map((variation) => {
        const regularPrice = Number(variation.price || 0);
        const salePrice = variation.sale_price !== null && variation.sale_price !== undefined
          ? Number(variation.sale_price)
          : null;

        return {
          key: `variation-${variation.id}`,
          variantId: variation.id,
          label: variation.label || variation.package_size || variation.attributes?.Weight || 'Pack',
          sku: variation.sku || product.sku || null,
          price: salePrice ?? regularPrice,
          regularPrice,
          availableStock: Number(variation.available_stock || 0),
          netQty: variation.net_qty ?? null,
          netUnit: variation.net_unit ?? null,
        };
      });
    }

    return ['500g', '1 kg', '2 kg', '5 kg'].map((size) => {
      const price = Number(product.price || 0) * getPackMultiplier(size);
      return {
        key: size,
        variantId: null,
        label: size,
        sku: product.sku || null,
        price,
        regularPrice: getOriginalPrice(product, price),
        availableStock: Number(product.available_stock || product.stock_qty || 0),
      };
    });
  }, [product]);

  const selectedPackage = useMemo(
    () => packageOptions.find((option) => option.key === packSize) || packageOptions[0],
    [packageOptions, packSize]
  );

  // Reset states when the product ID changes
  useEffect(() => {
    setQuantity(1);
    setPackSize(packageOptions[0]?.key || '500g');
    setActiveTab('Overview');
    setAddedToCart(false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, [product?.id, packageOptions]);

  const isOutOfStock = Boolean(
    (selectedPackage && selectedPackage.availableStock <= 0) ||
    product?.is_out_of_stock ||
    (product?.available_stock !== undefined && product.available_stock <= 0) ||
    product?.stock_status === 'out_of_stock'
  );

  const handleAddToCart = async () => {
    if (isOutOfStock) return;
    setAdding(true);
    const res = await addToCart(product.id, quantity, selectedPackage?.variantId || null);
    setAdding(false);
    if (res?.success) {
      setAddedToCart(true);
      setTimeout(() => setAddedToCart(false), 2000);
    }
  };

  const handleBuyNow = () => {
    if (isOutOfStock) return;
    openComingSoonModal?.();
  };

  const handleReviewSubmit = async (event) => {
    event.preventDefault();
    if (!reviewForm.name.trim() || !reviewForm.comment.trim()) return;

    const payload = {
      name: reviewForm.name.trim(),
      location: reviewForm.location.trim() || 'Verified Customer',
      rating: Number(reviewForm.rating) || 5,
      comment: reviewForm.comment.trim()
    };

    setReviewSubmitting(true);
    const res = await reviewsApi.addProductReview(product.slug || product.id, payload);
    setReviewSubmitting(false);

    const savedReview = res.success && res.data
      ? res.data
      : { id: Date.now(), ...payload };

    setCustomerReviews((current) => [savedReview, ...current]);
    setReviewForm({ name: '', location: '', rating: 5, comment: '' });
  };

  const priceDisplay = Number(selectedPackage?.price ?? product.price ?? 0);
  const originalPriceDisplay = Number(selectedPackage?.regularPrice ?? getOriginalPrice(product, priceDisplay));
  const currentSku = selectedPackage?.sku || product.sku || null;
  const showGheeDetailImage = String(product?.name || '').toLowerCase().includes('a2 gir cow ghee bilona');
  const productShortDescription = product.short_description || product.short_desc || product.description || `Premium quality ${product.name} sourced from trusted organic farms.`;
  const productLongDescription = product.description || productShortDescription;
  const nutritionalInfo = product.nutritional_info || '';
  const defaultKeyIngredients = `${product.name}, 100% Native Single-Origin Grains/Seeds, Natural Trace Minerals`;
  const defaultHowToUse = `Store in a cool, dry and hygienic airtight container away from direct sunlight and humidity. Best consumed within 12 months for peak nutritional vitality.`;
  const defaultFullIngredients = `100% Pure, Unpolished ${product.name}. Zero artificial colors, zero synthetic preservatives, chemical fertilizer free, and strictly non-GMO.`;
  const defaultWhatIsIt = `• 100% pure, natural, and certified chemical-free daily organic harvest.\n• Hand-sorted and traditionally processed to preserve natural vital nutrients.\n• Rigorously tested for micro-biological purity and zero pesticide residue.`;
  const defaultHowItWorks = `• Nourishes your body with unrefined, bioavailable macro and micronutrients.\n• Easy on digestion and supports long-term metabolic vitality.\n• Naturally rich in essential antioxidants, plant enzymes, and clean dietary fiber.`;

  const productInformation = {
    product_type: product.product_information?.product_type || product.category || 'Organic Harvest',
    shelf_life: product.product_information?.shelf_life || '12 Months',
    ingredient: product.product_information?.ingredient || `Pure ${product.name}`,
    packaging_type: product.product_information?.packaging_type || 'Vacuum Sealed Food-Grade Packaging',
    storage: product.product_information?.storage || 'Store in dry, airtight container',
    what_is_it: product.product_information?.what_is_it || defaultWhatIsIt,
    how_it_works: product.product_information?.how_it_works || defaultHowItWorks,
    key_ingredients: product.product_information?.key_ingredients || defaultKeyIngredients,
    how_to_use: product.product_information?.how_to_use || defaultHowToUse,
    full_ingredients_list: product.product_information?.full_ingredients_list || defaultFullIngredients,
    clinically_tested: product.product_information?.clinically_tested || '',
    clinical_report_image: product.product_information?.clinical_report_image || '',
    faqs: product.product_information?.faqs || '',
  };

  const ingredientLines = productInformation.key_ingredients
    .split(/[,\n]/)
    .map((item) => item.trim())
    .filter(Boolean);

  const sampleCertificateReports = [
    {
      title: 'In-Vivo & In-Vitro',
      short_desc: 'Certified',
      image_url: '/product_reports/sample_lab_report.jpg',
    },
    {
      title: '100% Pesticide Free',
      short_desc: 'NABL Lab Tested',
      image_url: '/product_reports/sample_lab_report.jpg',
    }
  ];

  const rawCertificateReports = Array.isArray(product.product_information?.certificate_reports)
    ? product.product_information.certificate_reports
    : [];
  const certificateReports = rawCertificateReports.length > 0
    ? rawCertificateReports
    : (productInformation.clinical_report_image
        ? [{
            title: 'In-Vivo & In-Vitro',
            short_desc: productInformation.clinically_tested ? productInformation.clinically_tested.slice(0, 35) : 'Certified',
            image_url: productInformation.clinical_report_image,
          }]
        : sampleCertificateReports);

  const [openAccordion, setOpenAccordion] = useState({
    reports: true,
  });
  const [productInfoTab, setProductInfoTab] = useState('what_is_it');
  const [activeReportModal, setActiveReportModal] = useState(null);

  const toggleAccordion = (key) => {
    setOpenAccordion((prev) => ({ ...prev, [key]: !prev[key] }));
  };

  const hasProductInfo = Boolean(productInformation.what_is_it || productInformation.how_it_works);
  const hasKeyIngredients = Boolean(ingredientLines.length > 0);
  const hasHowToUse = Boolean(productInformation.how_to_use);
  const hasFullIngredients = Boolean(productInformation.full_ingredients_list);
  const hasCertificateReports = certificateReports.length > 0;
  const hasFaqs = Boolean(productInformation.faqs);

  const hasAnyHyphenSection = hasProductInfo || hasKeyIngredients || hasHowToUse || hasFullIngredients || hasCertificateReports || hasFaqs;

  return (
    <div className="product-detail-page">
      <div className="container">
        
        {/* Navigation Breadcrumbs */}
        <div className="detail-navigation-bar">
          <button className="back-link-btn" onClick={onBack}>
            ← Back to Categories
          </button>
          <div className="detail-breadcrumbs">
            Home &nbsp; <span>&gt;</span> &nbsp; {product.category} &nbsp; <span>&gt;</span> &nbsp; <span className="active">{product.name}</span>
          </div>
        </div>

        {/* Product Details Grid */}
        <div className="product-details-grid-rebuilt">
          
          {/* Left Column: Thumbnails + Main Showcase */}
          <div className="image-showcase-container">
            {/* Vertical thumbnails strip */}
            <div className="thumbnails-strip">
              {productImages.map((img, idx) => (
                <div 
                  key={idx} 
                  className={`thumb-box ${activeImage === img ? 'active' : ''}`}
                  onClick={() => setActiveImage(img)}
                >
                  <img src={img} alt={`Thumbnail view ${idx + 1}`} />
                </div>
              ))}
            </div>

            {/* Large Main Showcase Panel */}
            <div className="main-display-frame">
              <img src={activeImage} alt={product.name} className="active-showcase-img" />
              <span className="organic-badge-label">🌱 Pure &amp; Natural</span>
              {productImages.length > 1 && (
                <span className="gallery-count-label">{productImages.length} Photos</span>
              )}
              <button className="expand-view-trigger" title="Expand View" aria-label="Expand image view">🔍</button>
            </div>
          </div>

          {/* Right Column: Detailed Specifications */}
          <div className="info-showcase-container">
            
            <div className="product-meta-header-row">
              <span className="category-green-tag">{String(product.category || 'Organic').toUpperCase()}</span>
              {currentSku && (
                <span className="product-sku-tag" title="Stock Keeping Unit">
                  SKU: <strong>{currentSku}</strong>
                </span>
              )}
            </div>
            
            <h1 className="showcase-title">{product.name}</h1>
            
            {/* Reviews line */}
            <div className="ratings-meta-row">
              <div className="stars-fill">★★★★★</div>
              <span className="rating-score">4.8</span>
              <span className="review-count">({Math.max(Number(product.reviews || 0), customerReviews.length, 125)} Reviews)</span>
            </div>

            {/* Brief description */}
            <p className="brief-desc">
              {productShortDescription}
            </p>

            {/* Pricing Section */}
            <div className="pricing-showcase-box">
              <div className="detail-price-row">
                <span className="detail-original-price">{formatPrice(originalPriceDisplay)}</span>
                <span className="price-tag-value">{formatPrice(priceDisplay)}</span>
                {product.offer?.has_offer && (
                  <span style={{ background: '#e11d48', color: '#fff', fontSize: '0.8rem', fontWeight: 800, padding: '3px 10px', borderRadius: '4px', marginLeft: '10px', letterSpacing: '0.02em', display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                    🎁 {product.offer.badge || 'PROMOTIONAL OFFER'}
                  </span>
                )}
              </div>
              {product.offer?.has_offer && (
                <div style={{ marginTop: '8px', padding: '8px 12px', background: '#fff1f2', border: '1px solid #fecdd3', borderRadius: '8px', color: '#be123c', fontSize: '0.84rem', fontWeight: 600 }}>
                  <strong>Special Offer:</strong> {product.offer.title} ({product.offer.badge}) &bull; Discount applies automatically in cart!
                </div>
              )}
            </div>

            {/* Pack Size Selector */}
            <div className="pack-size-section">
              <span className="section-small-title">Pack Size</span>
              <div className="sizes-btn-row">
                {packageOptions.map(option => {
                  const isFamily = /family/i.test(option.label) || /\b(?:5kg|10kg|5\s*litre|15\s*litre)\b/i.test(option.label);
                  return (
                    <button 
                      key={option.key}
                      type="button"
                      className={`size-select-btn ${packSize === option.key ? 'active' : ''} ${option.availableStock <= 0 ? 'pack-out-of-stock' : ''}`}
                      onClick={() => setPackSize(option.key)}
                    >
                      <span style={{ display: 'inline-flex', alignItems: 'center', gap: '4px' }}>
                        {option.label}
                        {isFamily && (
                          <span style={{ fontSize: '0.65rem', background: '#dcfce7', color: '#166534', padding: '1px 5px', borderRadius: '4px', fontWeight: 700, border: '1px solid #86efac' }}>
                            ⭐ Family
                          </span>
                        )}
                        {option.availableStock <= 0 ? ' (Out of Stock)' : ''}
                      </span>
                      <small>{formatPrice(option.price)}</small>
                    </button>
                  );
                })}
              </div>
              {isOutOfStock && (
                <p className="pack-stock-warning">⚠️ This package is currently out of stock.</p>
              )}
            </div>

            {/* Quantity Selector & Actions Buttons */}
            <div className="cart-buy-actions-wrapper">
              <div className="qty-selector-rebuilt">
                <button type="button" onClick={() => setQuantity(q => q > 1 ? q - 1 : 1)}>-</button>
                <span className="qty-show-val">{quantity}</span>
                <button type="button" onClick={() => setQuantity(q => q + 1)}>+</button>
              </div>

              <button 
                type="button" 
                className={`btn add-to-cart-green-btn ${addedToCart ? 'success' : ''} ${isOutOfStock ? 'btn-out-of-stock' : ''}`}
                onClick={handleAddToCart}
                disabled={adding || isOutOfStock}
              >
                {isOutOfStock ? 'Out of Stock' : (addedToCart ? '✨ Added to Cart!' : (adding ? 'Adding...' : '🛒 Add to Cart'))}
              </button>

              <button
                type="button"
                className="btn buy-now-white-btn"
                onClick={handleBuyNow}
                disabled={adding || isOutOfStock}
              >
                {adding ? 'Processing...' : '⚡ Buy Now'}
              </button>
            </div>

            {/* Highlights features grid (High in Protein, Pure & Natural, No Preservatives) */}
            <div className="features-highlight-grid">
              <div className="feature-card-item">
                <span className="feature-icon" aria-hidden="true"><FiActivity /></span>
                <div className="feature-text">
                  <h4>High in Protein</h4>
                  <p>Nutritionally dense</p>
                </div>
              </div>
              <div className="feature-card-item">
                <span className="feature-icon" aria-hidden="true"><FiFeather /></span>
                <div className="feature-text">
                  <h4>Pure &amp; Natural</h4>
                  <p>No chemical fertilizer, no preservative, no pesticides</p>
                </div>
              </div>
              <div className="feature-card-item">
                <span className="feature-icon" aria-hidden="true"><FiFileText /></span>
                <div className="feature-text">
                  <h4>No Preservatives</h4>
                  <p>Lab test report</p>
                </div>
              </div>
            </div>

            {/* Hyphen-Style Product Accordions (Only displayed if content is provided by admin) */}
            {hasAnyHyphenSection && (
              <div className="hyphen-accordion-wrapper">
                {/* 1. PRODUCT INFO */}
                {hasProductInfo && (
                  <div className="hyphen-accordion-item">
                    <div
                      className={`hyphen-accordion-header ${openAccordion.product_info ? 'open' : ''}`}
                      onClick={() => toggleAccordion('product_info')}
                      role="button"
                      tabIndex={0}
                    >
                      <span className="hyphen-header-title">PRODUCT INFO</span>
                      <span className="hyphen-header-icon">
                        {openAccordion.product_info ? <FiChevronUp /> : <FiChevronDown />}
                      </span>
                    </div>
                    {openAccordion.product_info && (
                      <div className="hyphen-accordion-content">
                        {productInformation.what_is_it && productInformation.how_it_works && (
                          <div className="hyphen-pills-row">
                            <button
                              type="button"
                              className={`hyphen-pill-btn ${productInfoTab === 'what_is_it' ? 'active' : ''}`}
                              onClick={() => setProductInfoTab('what_is_it')}
                            >
                              What is it?
                            </button>
                            <button
                              type="button"
                              className={`hyphen-pill-btn ${productInfoTab === 'how_it_works' ? 'active' : ''}`}
                              onClick={() => setProductInfoTab('how_it_works')}
                            >
                              How it does?
                            </button>
                          </div>
                        )}
                        <div className="hyphen-info-text-body">
                          {renderFormattedBullets(
                            productInformation.what_is_it && productInformation.how_it_works
                              ? (productInfoTab === 'what_is_it' ? productInformation.what_is_it : productInformation.how_it_works)
                              : (productInformation.what_is_it || productInformation.how_it_works)
                          )}
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* 2. KEY INGREDIENTS */}
                {hasKeyIngredients && (
                  <div className="hyphen-accordion-item">
                    <div
                      className={`hyphen-accordion-header ${openAccordion.key_ingredients ? 'open' : ''}`}
                      onClick={() => toggleAccordion('key_ingredients')}
                      role="button"
                      tabIndex={0}
                    >
                      <span className="hyphen-header-title">KEY INGREDIENTS</span>
                      <span className="hyphen-header-icon">
                        {openAccordion.key_ingredients ? <FiChevronUp /> : <FiChevronDown />}
                      </span>
                    </div>
                    {openAccordion.key_ingredients && (
                      <div className="hyphen-accordion-content">
                        <ul className="hyphen-bullet-list">
                          {ingredientLines.map((ing, idx) => (
                            <li key={idx}>{ing}</li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                )}

                {/* 3. HOW TO USE */}
                {hasHowToUse && (
                  <div className="hyphen-accordion-item">
                    <div
                      className={`hyphen-accordion-header ${openAccordion.how_to_use ? 'open' : ''}`}
                      onClick={() => toggleAccordion('how_to_use')}
                      role="button"
                      tabIndex={0}
                    >
                      <span className="hyphen-header-title">HOW TO USE</span>
                      <span className="hyphen-header-icon">
                        {openAccordion.how_to_use ? <FiChevronUp /> : <FiChevronDown />}
                      </span>
                    </div>
                    {openAccordion.how_to_use && (
                      <div className="hyphen-accordion-content">
                        <div className="hyphen-text-paragraph">
                          {renderMultilineText(productInformation.how_to_use)}
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* 4. FULL INGREDIENTS LIST */}
                {hasFullIngredients && (
                  <div className="hyphen-accordion-item">
                    <div
                      className={`hyphen-accordion-header ${openAccordion.full_ingredients ? 'open' : ''}`}
                      onClick={() => toggleAccordion('full_ingredients')}
                      role="button"
                      tabIndex={0}
                    >
                      <span className="hyphen-header-title">FULL INGREDIENTS LIST</span>
                      <span className="hyphen-header-icon">
                        {openAccordion.full_ingredients ? <FiChevronUp /> : <FiChevronDown />}
                      </span>
                    </div>
                    {openAccordion.full_ingredients && (
                      <div className="hyphen-accordion-content">
                        <p className="hyphen-text-paragraph">
                          {renderMultilineText(productInformation.full_ingredients_list)}
                        </p>
                      </div>
                    )}
                  </div>
                )}

                {/* 5. CLINICALLY TESTED CLAIMS / CERTIFICATE REPORT */}
                {hasCertificateReports && (
                  <div className="hyphen-accordion-item">
                    <div
                      className={`hyphen-accordion-header ${openAccordion.reports ? 'open' : ''}`}
                      onClick={() => toggleAccordion('reports')}
                      role="button"
                      tabIndex={0}
                    >
                      <span className="hyphen-header-title">CLINICALLY TESTED CLAIMS</span>
                      <span className="hyphen-header-icon">
                        {openAccordion.reports ? <FiChevronUp /> : <FiChevronDown />}
                      </span>
                    </div>
                    {openAccordion.reports && (
                      <div className="hyphen-accordion-content">
                        <div className="hyphen-reports-grid">
                          {certificateReports.map((report, idx) => (
                            <div className="hyphen-report-card" key={idx}>
                              <div className="hyphen-report-card-top">
                                <h4 className="hyphen-report-card-title">{report.title || 'Lab Certificate'}</h4>
                                <span className="hyphen-report-shield-badge" title="Verified Lab Certificate">
                                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z" fill="#16a34a"/>
                                    <path d="M9 12l2 2 4-4" stroke="#ffffff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                  </svg>
                                </span>
                              </div>
                              <p className="hyphen-report-card-desc">{report.short_desc || 'Certified'}</p>
                              <button
                                type="button"
                                className="hyphen-report-check-btn"
                                onClick={() => setActiveReportModal(report)}
                              >
                                Check Reports
                              </button>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* 6. FAQS (Optional) */}
                {hasFaqs && (
                  <div className="hyphen-accordion-item">
                    <div
                      className={`hyphen-accordion-header ${openAccordion.faqs ? 'open' : ''}`}
                      onClick={() => toggleAccordion('faqs')}
                      role="button"
                      tabIndex={0}
                    >
                      <span className="hyphen-header-title">FAQS</span>
                      <span className="hyphen-header-icon">
                        {openAccordion.faqs ? <FiChevronUp /> : <FiChevronDown />}
                      </span>
                    </div>
                    {openAccordion.faqs && (
                      <div className="hyphen-accordion-content">
                        <div className="hyphen-text-paragraph">
                          {renderMultilineText(productInformation.faqs)}
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}

          </div>

        </div>

        {/* Certificate Logos Banner */}
        <div className="product-trust-accreditations-wrap">
          <div className="product-trust-logos-panel">
            <span className="trust-certification-note">OUR QUALITY MARKS</span>
            <div className="trust-logos-grid">
              {certificationBadges
                .filter((badge) => badge.label !== 'HACCP Food Safety Certified')
                .map((badge) => (
                  <div className="trust-logo-card" key={badge.label}>
                    <img src={badge.image} alt={badge.label} loading="lazy" />
                    <span>{badge.label}</span>
                  </div>
                ))}
            </div>
          </div>
          <div className="product-trust-copy">
            <span className="trust-certification-note">A STANDARD WE LIVE BY</span>
            <h3>Responsibly Sourced &amp; Packed at a Certified Organic Facility</h3>
            <p>
              At Prakruti, we believe quality begins with responsible sourcing and careful handling. Our products are sourced with attention to quality, authenticity and traceability. They are packed at a certified organic facility, following appropriate hygiene and quality practices to maintain product integrity. Every step reflects our commitment to responsible practices and consumer trust.
            </p>
            <span className="trust-copy-rule" aria-hidden="true" />
          </div>
        </div>

        {showGheeDetailImage && (
          <div className="ghee-detail-feature-image">
            <img src={gheeDetailImg} alt="A2 Gir Cow Ghee Bilona details" />
          </div>
        )}

        {/* Tabs Detailed Specification Panel */}
        <div className="details-tabs-component">
          <div className="tabs-nav-bar">
            {['Overview', 'Nutritional Info', `Reviews (${Math.max(Number(product.reviews || 0), customerReviews.length, 125)})`].map(tab => (
              <button 
                key={tab}
                type="button" 
                className={`tab-nav-btn ${activeTab === tab ? 'active' : ''}`}
                onClick={() => setActiveTab(tab)}
              >
                {tab}
              </button>
            ))}
          </div>

          <div className="tab-details-container">
            {activeTab === 'Overview' && (
              <div className="overview-tab-content">
                
                {/* Left Bullet checklist */}
                <div className="overview-left-list">
                  <h3>Product Overview</h3>
                  <p>{renderMultilineText(productLongDescription)}</p>
                  <ul className="overview-checkmarks">
                    <li><span>✓</span> Pure &amp; Natural Ingredients</li>
                    <li><span>✓</span> Rich in Protein & Fiber</li>
                    <li><span>✓</span> We Provide Vacuum Packaging for Maximum Freshness</li>
                    <li><span>✓</span> No Artificial Colors or Preservatives</li>
                    <li><span>✓</span> Sourced from Trusted Farming Communities</li>
                  </ul>
                </div>

                {/* Center table specs */}
                <div className="overview-center-table">
                  <h3>Product Information</h3>
                  <table className="specs-table-data">
                    <tbody>
                      {currentSku && (
                        <tr>
                          <th>SKU Code</th>
                          <td><span className="sku-code-value">{currentSku}</span></td>
                        </tr>
                      )}
                      <tr>
                        <th>Product Type</th>
                        <td>{productInformation.product_type}</td>
                      </tr>
                      <tr>
                        <th>Shelf Life</th>
                        <td>{productInformation.shelf_life}</td>
                      </tr>
                      <tr>
                        <th>Ingredient</th>
                        <td>{productInformation.ingredient}</td>
                      </tr>
                      <tr>
                        <th>Packaging Type</th>
                        <td>{productInformation.packaging_type}</td>
                      </tr>
                      <tr>
                        <th>Storage</th>
                        <td>{productInformation.storage}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>

              </div>
            )}

            {activeTab === 'Nutritional Info' && (
              <div className="nutrition-tab-content">
                <h3>Nutritional Info</h3>
                {nutritionalInfo ? (
                  <div className="nutrition-backend-text">{renderMultilineText(nutritionalInfo)}</div>
                ) : (
                  <p className="text-muted">Nutritional information will be updated soon.</p>
                )}
              </div>
            )}

            {activeTab.startsWith('Reviews') && (
              <div className="reviews-tab-content">
                <h3>Customer Testimonials</h3>
                <form className="product-review-form" onSubmit={handleReviewSubmit}>
                  <div className="review-form-grid">
                    <input
                      type="text"
                      value={reviewForm.name}
                      onChange={(event) => setReviewForm((current) => ({ ...current, name: event.target.value }))}
                      placeholder="Your name"
                      required
                    />
                    <input
                      type="text"
                      value={reviewForm.location}
                      onChange={(event) => setReviewForm((current) => ({ ...current, location: event.target.value }))}
                      placeholder="City"
                    />
                    <select
                      value={reviewForm.rating}
                      onChange={(event) => setReviewForm((current) => ({ ...current, rating: event.target.value }))}
                    >
                      {[5, 4, 3, 2, 1].map((rating) => (
                        <option key={rating} value={rating}>{rating} Star{rating > 1 ? 's' : ''}</option>
                      ))}
                    </select>
                  </div>
                  <textarea
                    value={reviewForm.comment}
                    onChange={(event) => setReviewForm((current) => ({ ...current, comment: event.target.value }))}
                    placeholder={`Share your experience with ${product.name}`}
                    rows="4"
                    required
                  />
                  <button type="submit" disabled={reviewSubmitting}>{reviewSubmitting ? 'Saving...' : 'Add Review'}</button>
                </form>
                {customerReviews.map((review) => (
                  <div className="review-comment-item customer-added-review" key={review.id}>
                    <div className="rev-stars">{'★'.repeat(review.rating)}</div>
                    <p className="rev-comment">"{review.comment}"</p>
                    <span className="rev-meta">- {review.name}, {review.location}</span>
                  </div>
                ))}
                <div className="review-comment-item">
                  <div className="rev-stars">★★★★★</div>
                  <p className="rev-comment">"This {product.name} is extremely pure and cooks beautifully. Smells authentic, just like traditional harvests. Highly recommend!"</p>
                  <span className="rev-meta">- Rajesh Kumar, August 2026</span>
                </div>
                <div className="review-comment-item">
                  <div className="rev-stars">★★★★★</div>
                  <p className="rev-comment">"Best organic product we have tried. The vacuum packaging is premium and the grains are clean, aromatic, and sealed for freshness."</p>
                  <span className="rev-meta">- Meera Sharma, July 2026</span>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Certificate Report Modal Popup (Hyphen style with scrollable report) */}
        {activeReportModal && (
          <div
            className="hyphen-report-modal-backdrop"
            role="presentation"
            onClick={() => setActiveReportModal(null)}
          >
            <div
              className="hyphen-report-modal-dialog"
              role="dialog"
              aria-modal="true"
              aria-label={activeReportModal.title || 'Certificate Report'}
              onClick={(e) => e.stopPropagation()}
            >
              <div className="hyphen-report-modal-header">
                <div className="hyphen-report-modal-title">
                  <span className="hyphen-report-modal-shield" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                      <path d="M12 2L4 5v6.09c0 5.05 3.41 9.76 8 10.91 4.59-1.15 8-5.86 8-10.91V5l-8-3z" fill="#16a34a"/>
                      <path d="M9 12l2 2 4-4" stroke="#ffffff" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                  </span>
                  <div>
                    <h3>{activeReportModal.title || 'Lab Test Certificate'}</h3>
                    {activeReportModal.short_desc && (
                      <span className="hyphen-report-modal-badge">{activeReportModal.short_desc}</span>
                    )}
                  </div>
                </div>
                <button
                  type="button"
                  className="hyphen-report-modal-close"
                  aria-label="Close report"
                  onClick={() => setActiveReportModal(null)}
                >
                  ×
                </button>
              </div>
              <div className="hyphen-report-modal-body">
                {activeReportModal.image_url ? (
                  <img
                    src={activeReportModal.image_url}
                    alt={`${activeReportModal.title || 'Certificate'} test report`}
                    className="hyphen-report-modal-img"
                  />
                ) : (
                  <div className="hyphen-report-empty">
                    <p>Lab verification certificate document will be attached shortly.</p>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* Related Products Section */}
        <section className="related-products-section">
          <div className="related-section-header">
            <h2>You May Also Like</h2>
            <p>Explore other premium, wholesome options in the {product.category} category</p>
          </div>
          
          <div className="products-grid-feed">
            {relatedProducts.map(relProduct => {
              const origPrice = getOriginalPrice(relProduct);
              const discountPercent = origPrice > relProduct.price 
                ? Math.round(((origPrice - relProduct.price) / origPrice) * 100)
                : 0;

              return (
                <div 
                  key={relProduct.id} 
                  className="product-feed-card"
                  onClick={() => onNavigateProduct && onNavigateProduct(relProduct.id)}
                >
                  <div className="product-image-container">
                    <div className="card-badge-container">
                      {relProduct.offer?.has_offer && (
                        <span style={{ background: '#e11d48', color: '#fff', fontSize: '0.72rem', fontWeight: 800, padding: '3px 8px', borderRadius: '4px', letterSpacing: '0.02em' }}>
                          🎁 {relProduct.offer.badge || 'OFFER'}
                        </span>
                      )}
                      {relProduct.types?.includes('Organic') && (
                        <span className="organic-tag">🌿 Organic</span>
                      )}
                      {discountPercent > 0 && (
                        <span className="discount-tag">-{discountPercent}%</span>
                      )}
                    </div>

                    <img 
                      src={relProduct.image} 
                      alt={relProduct.name} 
                      className="product-feed-img" 
                      loading="lazy"
                    />

                    <div className="product-card-overlay">
                      <button 
                        className="quick-view-btn" 
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          if (onNavigateProduct) onNavigateProduct(relProduct.id);
                        }}
                        title="View Product Details"
                      >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" className="svg-eye">
                          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                          <circle cx="12" cy="12" r="3" />
                        </svg>
                      </button>
                    </div>
                  </div>
                  
                  <div className="product-info-container">
                    <div className="product-meta-row">
                      <span className="product-feed-cat">{relProduct.category || product.category}</span>
                      <div className="product-feed-rating">
                        <span className="rating-star">★</span>
                        <span className="rating-val">5.0</span>
                        <span className="review-count">({relProduct.reviews || 16})</span>
                      </div>
                    </div>

                    <h3 className="product-feed-name" title={relProduct.name}>{relProduct.name}</h3>

                    <div className="product-pricing-row">
                      <div className="pricing-left">
                        <span className="price-val">{formatPrice(relProduct.price)}</span>
                        {origPrice > relProduct.price && (
                          <span className="price-original">{formatPrice(origPrice)}</span>
                        )}
                      </div>
                      {discountPercent > 0 && (
                        <span className="price-save-badge">Save {formatPrice(origPrice - relProduct.price)}</span>
                      )}
                    </div>

                    <button 
                      type="button" 
                      className="feed-add-cart-btn"
                      disabled={addingRelatedId === relProduct.id}
                      onClick={async (e) => {
                        e.stopPropagation();
                        setAddingRelatedId(relProduct.id);
                        try {
                          await addToCart(relProduct.id, 1);
                        } catch (err) {
                          console.error(err);
                        } finally {
                          setAddingRelatedId(null);
                        }
                      }}
                    >
                      {addingRelatedId === relProduct.id ? (
                        <span className="btn-loading-text">Adding...</span>
                      ) : (
                        <>
                          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                          </svg>
                          <span>+ Add to Cart</span>
                        </>
                      )}
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        </section>

      </div>
    </div>
  );
};

export default ProductDetailPage;
