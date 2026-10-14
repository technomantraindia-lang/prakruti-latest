import React, { useEffect, useMemo, useState } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import './EducationPage.css'
import { apiClient } from '../../api/client'

// Import assets
import bannerBg from '../../assets/images/nutrition_banner.jpg'
import healthyPlateImg from '../../assets/images/image copy 5.png'
import healthyPlateBanner from '../../assets/images/healthy_food_plate.jpg'
import junkPlateBanner from '../../assets/images/junk_food_plate.jpg'

// Custom realistic cereal & protein pulse icon (reliable across all OS / devices)
const ProteinCerealIcon = () => (
  <svg width="36" height="36" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
    <ellipse cx="24" cy="39" rx="15" ry="3.5" fill="#2d5a27" opacity="0.12"/>
    <path d="M8 22C8 31 15 37.5 24 37.5C33 37.5 40 31 40 22C40 20.8 39 20 37.5 20H10.5C9 20 8 20.8 8 22Z" fill="#D7CCC8" stroke="#8D6E63" strokeWidth="1.6"/>
    <ellipse cx="24" cy="20" rx="14" ry="4.5" fill="#EFEBE9"/>
    <circle cx="16" cy="19.5" r="3.6" fill="#D97706"/>
    <circle cx="15.2" cy="18.5" r="1.2" fill="#FEF3C7" opacity="0.9"/>
    <ellipse cx="22.5" cy="18" rx="4" ry="2.6" transform="rotate(-20 22.5 18)" fill="#991B1B"/>
    <ellipse cx="21.8" cy="17.2" rx="1.6" ry="0.9" transform="rotate(-20 21.8 17.2)" fill="#FCA5A5" opacity="0.7"/>
    <circle cx="29" cy="19" r="3.3" fill="#F59E0B"/>
    <circle cx="33.5" cy="20.5" r="2.8" fill="#D97706"/>
    <ellipse cx="19.5" cy="22.5" rx="3.2" ry="2.2" fill="#15803D"/>
    <ellipse cx="26" cy="23" rx="3.2" ry="2.3" fill="#16A34A"/>
    <path d="M33 22C35 16 38.5 9 41 6" stroke="#B45309" strokeWidth="1.8" strokeLinecap="round"/>
    <ellipse cx="39" cy="8.5" rx="2.2" ry="3.8" transform="rotate(35 39 8.5)" fill="#F59E0B"/>
    <ellipse cx="36" cy="12.5" rx="2" ry="3.4" transform="rotate(-25 36 12.5)" fill="#FBBF24"/>
    <ellipse cx="38.5" cy="15" rx="2" ry="3.4" transform="rotate(40 38.5 15)" fill="#F59E0B"/>
    <path d="M22 17C21 11.5 17.5 7.5 12 7.5C13 13 17.5 16 22 17Z" fill="#22C55E"/>
    <path d="M22 16C23 10.5 27 7.5 31 8.5C30 14 26.5 16.5 22 16Z" fill="#16A34A"/>
    <path d="M22 18V12" stroke="#15803D" strokeWidth="2" strokeLinecap="round"/>
  </svg>
);

const FOOD_GROUPS = [
  {
    icon: '🥦',
    title: 'Vegetables',
    role: 'Vitamins, minerals and essential prebiotic fibre',
    examples: 'Spinach, methi, bottle gourd, carrots, tomatoes, beans, cauliflower, seasonal local greens'
  },
  {
    icon: '🍎',
    title: 'Fruits',
    role: 'Vitamins, antioxidants, fibre & hydration',
    examples: 'Guava, papaya, apples, oranges, amla, seasonal regional whole fruits'
  },
  {
    icon: '🌾',
    title: 'Whole Grains & Staples',
    role: 'Sustained energy, B-complex & complex carbohydrates',
    examples: 'Khapli/desi wheat, bajra, jowar, ragi, brown rice, unpolished oats, red poha'
  },
  {
    icon: '🥣',
    title: 'Dal, Pulses & Beans',
    role: 'Plant protein, potassium, folate & dietary fibre',
    examples: 'Moong, masoor, unpolished tuvar, chana, rajma, black urad, cowpeas (chawli)'
  },
  {
    icon: '🥛',
    title: 'Dairy / Fermented Milk',
    role: 'High biological value protein, calcium & gut probiotics',
    examples: 'A2 cow milk, fresh curd (dahi), homemade buttermilk (chaas), paneer'
  },
  {
    icon: '🥜',
    title: 'Nuts & Seeds',
    role: 'Healthy unsaturated fats, vitamin E, zinc & magnesium',
    examples: 'Groundnuts, almonds, walnuts, white/black sesame, flaxseeds, pumpkin seeds'
  },
  {
    icon: <ProteinCerealIcon />,
    title: 'Quality Proteins',
    role: 'Complete amino acid profile for muscle maintenance & vitality',
    examples: 'Sprouted moong & pulses, fresh organic paneer, homemade curd, chana, and rajma'
  },
  {
    icon: '💧',
    title: 'Pure Water',
    role: 'Cellular hydration, digestion & body temperature',
    examples: 'Plain drinking water, lemon infused water, unsweetened buttermilk'
  }
]

const MEAL_TIMINGS = [
  {
    id: 'morning',
    time: '🌅 Morning Breakfast',
    tagline: 'Start with wholesome energy, not just tea & biscuits',
    description: 'Fuel your metabolism after an overnight fast with a combination of whole grains and protein.',
    items: [
      'Vegetable Poha with roasted peanuts + Fruits',
      'Steamed Idli + Sambar + coconut chutney',
      'Besan / Moong Dal Chilla with fresh vegetables',
      'Upma loaded with carrots, beans and peas',
      'Whole Wheat / Multigrain Dalia khichdi + Ghee',
      'Methi Thepla with fresh homemade curd',
      'Paneer bhurji / Sprouted moong with whole grain roti + Ghee'
    ]
  },
  {
    id: 'afternoon',
    time: '☀️ Balanced Lunch',
    tagline: 'Balance your plate: don\'t fill it only with rice or roti',
    description: 'A balanced midday meal prevents afternoon lethargy and cravings.',
    items: [
      'Seasonal cooked sabzi (50% of the plate)',
      'A bowl of thick unpolished dal / chana / rajma',
      '1–2 Whole grain rotis / unpolished brown rice / millet bhakri',
      'Fresh kachumber salad (cucumber, tomato, lemon)',
      'A glass of fresh digestive chaas with roasted cumin'
    ]
  },
  {
    id: 'evening',
    time: '🌇 Sensible Evening Snack',
    tagline: 'Satisfy genuine hunger with nourishing wholesome snacks',
    description: 'Avoid automatically reaching for fried farsan, bakery biscuits, or packaged chips.',
    items: [
      'A fresh seasonal fruit (apple, guava, papaya)',
      'Roasted chana with jaggery or spices',
      'Handful of soaked almonds and walnuts',
      'Light sprouted moong chaat with lemon & coriander',
      'A small bowl of homemade curd or roasted makhana'
    ]
  },
  {
    id: 'night',
    time: '🌙 Light & Balanced Dinner',
    tagline: 'Comforting, easily digestible meals for restful sleep',
    description: 'Keep dinner appropriate for your activity level, ideally eaten 2–3 hours before bedtime.',
    items: [
      'Warm Moong Dal Khichdi with a dash of A2 ghee',
      'Lauki / Palak soup with a multigrain paratha',
      'Stir-fried vegetables with paneer / tofu',
      'Steamed vegetable daliya with curd'
    ]
  }
]

const MYTHS_AND_FACTS = [
  {
    myth: '“Rice makes you fat.”',
    fact: 'Rice does not make you fat on its own. It is an easily digestible complex carbohydrate. What matters is the portion size, preparation, and what you eat with it (pairing it with fiber-rich dal and vegetables).',
    category: 'Grains & Carbs'
  },
  {
    myth: '“Fruit should be avoided because it contains sugar.”',
    fact: 'Whole fruit contains natural fructose bound with dietary fiber, water, polyphenols, and micronutrients. The fiber slows glucose absorption. Whole fruits are deeply protective against chronic lifestyle diseases.',
    category: 'Fruits'
  },
  {
    myth: '“Carbohydrates are the enemy of weight loss.”',
    fact: 'Carbohydrates are your brain and body’s preferred fuel. The key is choosing minimally processed whole grains (millets, oats, whole wheat) over refined flours and ultra-processed bakery items.',
    category: 'Grains & Carbs'
  },
  {
    myth: '“Ghee is completely unhealthy and clogs arteries.”',
    fact: 'Traditional Vedic bilona A2 ghee contains butyric acid that nourishes gut microbes and supports fat-soluble vitamins (A, D, E, K). In moderation (1–2 teaspoons daily), it is a vital part of traditional wellness.',
    category: 'Fats & Oils'
  },
  {
    myth: '“More protein is always better; everyone needs protein powder.”',
    fact: 'Most healthy adults can easily meet their protein requirements from everyday real food like dals, chana, rajma, curd, paneer, seeds, and nuts. Unsupervised protein supplements can strain the kidneys and digestive system.',
    category: 'Protein'
  },
  {
    myth: '“Drinking fruit juice is just as healthy as eating whole fruit.”',
    fact: 'Juicing removes the beneficial dietary fiber and concentrates free sugars, leading to rapid blood sugar spikes. Always prefer chewing whole seasonal fruit over drinking juice.',
    category: 'Fruits'
  },
  {
    myth: '“Healthy eating requires expensive superfoods and imported goods.”',
    fact: 'Everyday Indian staples—unpolished moong, chickpeas, seasonal gourd vegetables, groundnuts, amla, and curd—are world-class nutrient-dense foods without the expensive imported price tag.',
    category: 'Lifestyle'
  },
  {
    myth: '“Detox drinks and herbal teas clean your body.”',
    fact: 'Your liver and kidneys work 24/7 to detoxify your system naturally. Commercial "detox drinks" cannot replace a clean, chemical-free diet, adequate water intake, and restful sleep.',
    category: 'Lifestyle'
  }
]

const LIFE_STAGES = [
  {
    stage: '👧 Children & Teens',
    focus: 'Growth & Brain Development',
    points: 'High need for quality proteins, calcium (milk/curd/ragi), healthy fats for brain development, and iron. Limit sugary packaged snacks and sweetened beverages.'
  },
  {
    stage: '👨 Working Adults',
    focus: 'Metabolic Energy & Stress Management',
    points: 'Balanced macros to sustain focus throughout work hours. Focus on high-fiber whole grains, hydration, desk-friendly snacks (nuts, roasted chana), and limiting late-night heavy dinners.'
  },
  {
    stage: '🤰 Pregnant & Nursing Mothers',
    focus: 'Maternal Nutrition & Fetal Health',
    points: 'Elevated micronutrient needs: folate, iron, calcium, vitamin B12, and healthy fatty acids. Requires individualized guidance from a registered healthcare professional.'
  },
  {
    stage: '👵 Older Adults',
    focus: 'Bone Density & Digestive Ease',
    points: 'Easily digestible proteins (khichdi, curd, soft dals), adequate hydration, fiber for bowel regularity, and calcium/vitamin D to preserve bone and muscle mass.'
  }
]

const BMI_PRODUCT_RECOMMENDATIONS = {
  Underweight: ['Organic Desi Khand & Jaggery', 'Cold Pressed Oils', 'Organic Pulses & Dals'],
  'Normal weight': ['Wood Pressed Mustard Oil', 'Vedic Cow Bilona Ghee', 'Raw Natural Honey'],
  Overweight: ['Organic Millets', 'Low GI Brown Basmati', 'Unpolished Moong Dal'],
  Obesity: ['Flaxseeds & Chia Seeds', 'Cold Pressed Sesame Oil', 'Organic Spices & Turmeric']
}

const AWARENESS_CATEGORIES = [
  { id: 'all', label: 'All Reality Alerts' },
  { id: 'spices', label: 'Spices & Masalas' },
  { id: 'dairy', label: 'Ghee & Dairy' },
  { id: 'fruits', label: 'Fruits & Produce' },
  { id: 'grains', label: 'Grains & Pulses' },
  { id: 'general', label: 'General Food Safety' }
]

const FALLBACK_AWARENESS_ALERTS = [
  {
    id: 1,
    title: 'Chemical & Synthetic Dye Adulteration in Commercial Spice Powders',
    category: 'spices',
    category_label: 'Spices & Masalas',
    severity: 'critical',
    source: 'Food Safety Audits & Investigative Media',
    incident_date: 'March 2026',
    image: 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=900&q=80',
    video: null,
    summary: 'Widespread raids across multiple states revealed industrial dyes like Sudan Red, Metanil Yellow, and lead chromate added to commercial chilli and turmeric powders to impart vivid artificial colors.',
    details: 'Commercial spice manufacturers have been exposed using prohibited chemical dyes (Sudan I-IV, Metanil Yellow) along with starch, sawdust, and brick powder to boost volume and color brightness. These synthetic compounds pose severe risks including gastric toxicity and organ stress when consumed daily.',
    how_to_test: 'Mix 1 tsp of turmeric powder in a glass of warm water. Pure unadulterated turmeric will settle slowly at the bottom, leaving the water light yellow. If the water instantly turns cloudy bright yellow or leaves color residue on the glass walls, synthetic dye is present.'
  },
  {
    id: 2,
    title: 'Rampant Synthetic & Palm-Oil Ghee Racket Busted in Major Markets',
    category: 'dairy',
    category_label: 'Ghee & Dairy',
    severity: 'critical',
    source: 'State Food Safety & Consumer Affairs Alerts',
    incident_date: 'February 2026',
    image: 'https://images.unsplash.com/photo-1589927986089-35812388d1f4?auto=format&fit=crop&w=900&q=80',
    video: null,
    summary: 'Authorities seized thousands of litres of fake desi ghee manufactured using hydrogenated palm oil, refined animal fats, synthetic dairy flavorings, and starch sold under counterfeit premium branding.',
    details: 'Chemical investigations uncovered factories blending palm stearin, detergent-emulsified milk fat, and artificial ghee essence (diacetyl compounds) to mimic the distinct aroma and grain of traditional bilona ghee. Regular intake of synthetic hydrogenated fats directly elevates arterial plaque and LDL cholesterol.',
    how_to_test: 'Add 1 tsp of melted ghee with a pinch of sugar in a glass bowl, then add a drop of hydrochloric acid (or iodine test: add a pinch of boiled potato/iodine). Pure bilona ghee turns clear golden. If starch or synthetic oils are present, it turns purple/crimson.'
  },
  {
    id: 3,
    title: 'Calcium Carbide & Chemical Dips in Early-Season Papayas & Mangoes',
    category: 'fruits',
    category_label: 'Fruits & Produce',
    severity: 'high',
    source: 'Public Health Investigation & News Media',
    incident_date: 'January 2026',
    image: 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?auto=format&fit=crop&w=900&q=80',
    video: null,
    summary: 'Fruit wholesalers continue illegal use of banned Calcium Carbide (masala) packets and synthetic ethylene dips to force unripe fruits to look yellow and ripe within 24 hours.',
    details: 'Commercial ripening agents like industrial-grade calcium carbide emit acetylene gas containing traces of arsenic and phosphorus hydrides. Consuming carbide-ripened fruits causes chronic mouth ulcers, digestive burning, nausea, and neurological fatigue over time.',
    how_to_test: 'Submerge the fruit in a bucket of clean water. Naturally tree-ripened whole fruits are dense and typically sink, while artificially force-ripened fruits have trapped gas pockets and float on top. Also look for uniform unnatural skin color with sour, dry flesh inside.'
  },
  {
    id: 4,
    title: 'Talcum Powder & Metanil Yellow Polishing on Market Pulses (Dals)',
    category: 'grains',
    category_label: 'Grains & Pulses',
    severity: 'high',
    source: 'Consumer Research Council of India',
    incident_date: 'December 2025',
    image: 'https://images.unsplash.com/photo-1585994192701-f1a505c817bc?auto=format&fit=crop&w=900&q=80',
    video: null,
    summary: 'Mass market Toor, Chana, and Moong dals are subjected to heavy leather and talcum-oil polishing and Metanil Yellow dye so low-grade grains look shiny and golden in supermarket packets.',
    details: 'Industrial polishing strips the crucial outer nutrient-dense aleurone layer and bran of pulses, replacing them with talc powder, mineral oil, and carcinogenic non-permitted synthetic dyes to give uniform luster. This converts high-fibre whole pulses into stripped, chemically coated legumes.',
    how_to_test: 'Take 2 tablespoons of dal in warm water and shake vigorously. If the water immediately becomes murky yellow or oily sheen forms on the surface, talc polish or chemical color has been used. Pure unpolished dal water remains clean with slight natural turbidity only after extensive rubbing.'
  }
]

const FALLBACK_GUIDELINES = [
  {
    id: 1,
    title: 'ICMR-NIN Dietary Guidelines for Indians (2024)',
    organization: 'ICMR - National Institute of Nutrition',
    category: 'icmr_nin',
    category_label: 'ICMR-NIN',
    published_year: '2024',
    file_size: '12.4 MB',
    description: 'Official comprehensive dietary guidelines for all age groups, macro/micronutrient allowances, and prevention of lifestyle disorders.',
    external_url: 'https://nin.res.in/RDA_Full_Report_2024.html',
    download_url: 'https://nin.res.in/RDA_Full_Report_2024.html'
  },
  {
    id: 2,
    title: 'WHO Guideline: Sodium Intake for Adults and Children',
    organization: 'World Health Organization (WHO)',
    category: 'who',
    category_label: 'WHO Guidelines',
    published_year: '2023',
    file_size: '2.8 MB',
    description: 'Evidence-based global recommendations to reduce sodium intake below 2g/day (approx 5g salt) to prevent hypertension and cardiovascular risks.',
    external_url: 'https://www.who.int/news-room/fact-sheets/detail/sodium-reduction',
    download_url: 'https://www.who.int/news-room/fact-sheets/detail/sodium-reduction'
  },
  {
    id: 3,
    title: 'WHO Guideline: Sugars Intake for Adults and Children',
    organization: 'World Health Organization (WHO)',
    category: 'who',
    category_label: 'WHO Guidelines',
    published_year: '2022',
    file_size: '3.1 MB',
    description: 'Recommendations to restrict free sugars to under 10% (ideally under 5%) of total daily energy to control dental caries and metabolic disorders.',
    external_url: 'https://www.who.int/publications/i/item/9789241549028',
    download_url: 'https://www.who.int/publications/i/item/9789241549028'
  },
  {
    id: 4,
    title: 'WHO Guideline: Total Fat Intake for Prevention of Unhealthy Weight Gain',
    organization: 'World Health Organization (WHO)',
    category: 'who',
    category_label: 'WHO Guidelines',
    published_year: '2023',
    file_size: '4.5 MB',
    description: 'Scientific overview on essential fatty acids, eliminating industrial trans fats, and balancing saturated vs polyunsaturated/monounsaturated fats.',
    external_url: 'https://www.who.int/westernpacific/publications/i/item/9789240073630',
    download_url: 'https://www.who.int/westernpacific/publications/i/item/9789240073630'
  },
  {
    id: 5,
    title: 'WHO Guideline: Carbohydrate Intake for Adults and Children',
    organization: 'World Health Organization (WHO)',
    category: 'who',
    category_label: 'WHO Guidelines',
    published_year: '2023',
    file_size: '3.8 MB',
    description: 'Guidance emphasizing dietary fiber from whole grains, legumes, vegetables, and whole fruits over refined flours and ultra-processed carbohydrates.',
    external_url: 'https://www.who.int/publications/i/item/9789240073593',
    download_url: 'https://www.who.int/publications/i/item/9789240073593'
  },
  {
    id: 6,
    title: 'FSSAI Food Safety & Adulteration Testing Quick Manual (DART)',
    organization: 'Food Safety and Standards Authority of India (FSSAI)',
    category: 'fssai',
    category_label: 'FSSAI Standards',
    published_year: '2023',
    file_size: '6.2 MB',
    description: 'Detect Adulteration with Rapid Test (DART) handbook for testing milk, edible oils, spices, pulses, and honey using household methods.',
    external_url: 'https://www.fssai.gov.in',
    download_url: 'https://www.fssai.gov.in'
  },
  {
    id: 7,
    title: 'Prakruti Zero-Adulteration & Cold-Pressed Blueprint',
    organization: 'Prakruti Corporation',
    category: 'prakruti',
    category_label: 'Prakruti Research',
    published_year: '2024',
    file_size: '1.9 MB',
    description: 'Comprehensive whitepaper on traditional A2 Vedic Bilona ghee processing, wood-pressed extraction temperatures under 40°C, and vacuum preservation.',
    external_url: null,
    download_url: null
  }
]

export default function EducationPage({ onShopClick, onNavigate }) {
  const [activeTiming, setActiveTiming] = useState('morning')
  const [openMythIdx, setOpenMythIdx] = useState(null)
  const [searchMyth, setSearchMyth] = useState('')
  const [aiRedirectFeedback, setAiRedirectFeedback] = useState('')

  // PDF Guidelines states
  const [guidelines, setGuidelines] = useState([])
  const [guidelinesLoading, setGuidelinesLoading] = useState(true)
  const [selectedGuidelineCategory, setSelectedGuidelineCategory] = useState('all')
  const [searchGuideline, setSearchGuideline] = useState('')

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
        console.warn('Could not fetch guidelines from API, using fallback data:', err)
        if (isMounted) {
          setGuidelines(FALLBACK_GUIDELINES)
        }
      } finally {
        if (isMounted) {
          setGuidelinesLoading(false)
        }
      }
    }
    fetchGuidelines()
    return () => {
      isMounted = false
    }
  }, [])

  const filteredGuidelines = useMemo(() => {
    return guidelines.filter((g) => {
      const matchesCategory =
        selectedGuidelineCategory === 'all' ||
        g.category === selectedGuidelineCategory ||
        (selectedGuidelineCategory === 'who' && (g.category === 'who' || g.organization?.includes('WHO'))) ||
        (selectedGuidelineCategory === 'icmr_nin' && (g.category === 'icmr_nin' || g.organization?.includes('NIN') || g.organization?.includes('ICMR'))) ||
        (selectedGuidelineCategory === 'fssai' && (g.category === 'fssai' || g.organization?.includes('FSSAI'))) ||
        (selectedGuidelineCategory === 'prakruti' && (g.category === 'prakruti' || g.organization?.includes('Prakruti')))

      if (!matchesCategory) return false

      if (!searchGuideline.trim()) return true
      const q = searchGuideline.toLowerCase()
      return (
        (g.title && g.title.toLowerCase().includes(q)) ||
        (g.organization && g.organization.toLowerCase().includes(q)) ||
        (g.description && g.description.toLowerCase().includes(q)) ||
        (g.category_label && g.category_label.toLowerCase().includes(q))
      )
    })
  }, [guidelines, selectedGuidelineCategory, searchGuideline])

  const handleSeeAnswer = (customQ) => {
    const rawQuestion = (typeof customQ === 'string' ? customQ : searchMyth).trim()
    const question = rawQuestion || 'Why does diabetes happen and what is the best diet for it?'
    const prompt = `Please explain from a scientific nutritional and health perspective in simple, clear terms: "${question}". Explain why this happens, common food myths vs facts, healthy traditional Indian dietary habits, and practical nutritional tips to manage or prevent this.`

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(prompt).catch(() => {})
    }

    const chatGptUrl = `https://chatgpt.com/?q=${encodeURIComponent(prompt)}`
    window.open(chatGptUrl, '_blank', 'noopener,noreferrer')

    setAiRedirectFeedback('✓ Opening answer...')
    setTimeout(() => {
      setAiRedirectFeedback('')
    }, 3500)
  }

  // Plate zoom modal state
  const [isPlateZoomed, setIsPlateZoomed] = useState(false)

  // Food Awareness states
  const [awarenessAlerts, setAwarenessAlerts] = useState([])
  const [awarenessLoading, setAwarenessLoading] = useState(true)
  const [selectedAlertCategory, setSelectedAlertCategory] = useState('all')
  const [selectedAlertModal, setSelectedAlertModal] = useState(null)

  useEffect(() => {
    let isMounted = true
    const fetchAlerts = async () => {
      try {
        const res = await apiClient('/food-awareness')
        if (isMounted) {
          if (res?.data && Array.isArray(res.data) && res.data.length > 0) {
            setAwarenessAlerts(res.data)
          } else {
            setAwarenessAlerts(FALLBACK_AWARENESS_ALERTS)
          }
        }
      } catch (err) {
        console.warn('Could not fetch food awareness alerts from API, using fallback data:', err)
        if (isMounted) {
          setAwarenessAlerts(FALLBACK_AWARENESS_ALERTS)
        }
      } finally {
        if (isMounted) {
          setAwarenessLoading(false)
        }
      }
    }
    fetchAlerts()
    return () => {
      isMounted = false
    }
  }, [])

  const filteredAwarenessAlerts = useMemo(() => {
    if (selectedAlertCategory === 'all') return awarenessAlerts
    return awarenessAlerts.filter((a) => {
      if (selectedAlertCategory === 'spices') return a.category === 'spices'
      if (selectedAlertCategory === 'dairy') return a.category === 'dairy' || a.category === 'ghee_dairy'
      if (selectedAlertCategory === 'fruits') return a.category === 'fruits' || a.category === 'produce'
      if (selectedAlertCategory === 'grains') return a.category === 'grains' || a.category === 'grains_pulses'
      return a.category === selectedAlertCategory
    })
  }, [awarenessAlerts, selectedAlertCategory])

  // BMI Calculator states
  const [heightUnit, setHeightUnit] = useState('cm')
  const [heightCm, setHeightCm] = useState('')
  const [feet, setFeet] = useState('')
  const [inches, setInches] = useState('')
  const [weightKg, setWeightKg] = useState('')
  const [age, setAge] = useState('')
  const [gender, setGender] = useState('female')
  const [activityLevel, setActivityLevel] = useState('moderate')
  const [calculatedBmiResult, setCalculatedBmiResult] = useState(null)
  const [errors, setErrors] = useState({})

  const toggleMyth = (idx) => {
    setOpenMythIdx(openMythIdx === idx ? null : idx)
  }

  const filteredMyths = useMemo(() => {
    if (!searchMyth.trim()) return MYTHS_AND_FACTS
    const q = searchMyth.toLowerCase()
    return MYTHS_AND_FACTS.filter(
      (item) => item.myth.toLowerCase().includes(q) || item.fact.toLowerCase().includes(q)
    )
  }, [searchMyth])

  const calculateBmi = () => {
    const nextErrors = {}
    let computedHeightCm = Number(heightCm)

    if (heightUnit === 'ft-in') {
      const ft = Number(feet)
      const inc = Number(inches)
      if (!ft && !inc) {
        nextErrors.height = 'Please enter your height.'
      } else {
        computedHeightCm = ft * 30.48 + (inc || 0) * 2.54
      }
    } else if (!computedHeightCm || computedHeightCm <= 50 || computedHeightCm > 260) {
      nextErrors.height = 'Enter a valid height between 50 and 260 cm.'
    }

    const wt = Number(weightKg)
    if (!wt || wt <= 20 || wt > 300) {
      nextErrors.weight = 'Enter a valid weight between 20 and 300 kg.'
    }

    const ageNum = Number(age)
    if (!ageNum || ageNum < 18 || ageNum > 110) {
      nextErrors.age = 'Enter adult age between 18 and 110.'
    }

    setErrors(nextErrors)
    if (Object.keys(nextErrors).length > 0) return

    const heightM = computedHeightCm / 100
    const rawBmi = wt / (heightM * heightM)
    const rounded = Number(rawBmi.toFixed(1))

    let category = 'Normal weight'
    let className = 'bmi-normal'
    let message = 'Your BMI is in the healthy adult range. Maintain your wholesome organic dietary habits.'

    if (rounded < 18.5) {
      category = 'Underweight'
      className = 'bmi-underweight'
      message = 'Your BMI indicates you may need more nutrient-dense whole foods, healthy fats, and protein.'
    } else if (rounded >= 25 && rounded < 29.9) {
      category = 'Overweight'
      className = 'bmi-overweight'
      message = 'Slightly above standard range. Focusing on portion control and fiber-rich unpolished grains can help.'
    } else if (rounded >= 30) {
      category = 'Obesity'
      className = 'bmi-obesity'
      message = 'Above recommended range. We suggest consulting our clinical nutritionist for an organic meal blueprint.'
    }

    setCalculatedBmiResult({
      value: rounded,
      category,
      className,
      message,
      heightCm: Math.round(computedHeightCm),
      weightKg: wt
    })
  }

  const handleResetBmi = () => {
    setHeightCm('')
    setFeet('')
    setInches('')
    setWeightKg('')
    setAge('')
    setErrors({})
    setCalculatedBmiResult(null)
  }

  const navigateToConsultation = (e) => {
    e.preventDefault()
    if (onNavigate) {
      onNavigate('home')
      setTimeout(() => {
        document.getElementById('doctor-consultation')?.scrollIntoView({ behavior: 'smooth' })
      }, 200)
    } else {
      window.location.hash = 'doctor-consultation'
    }
  }

  // Animation variants
  const fadeInUp = {
    hidden: { opacity: 0, y: 28 },
    visible: (i = 0) => ({
      opacity: 1,
      y: 0,
      transition: { duration: 0.55, delay: i * 0.08, ease: [0.25, 1, 0.5, 1] }
    })
  }

  const staggerContainer = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: { staggerChildren: 0.09 }
    }
  }

  return (
    <div className="education-page">
      
      {/* 1. Hero Banner with Generated High-Resolution Image */}
      <section className="edu-hero" style={{ backgroundImage: `url(${bannerBg})` }}>
        <div className="edu-hero-overlay"></div>
        <div className="edu-hero-ambient-glow"></div>
        <div className="container edu-hero-container">
          <motion.div
            className="edu-hero-content"
            initial={{ opacity: 0, y: 35 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.8, ease: [0.25, 1, 0.5, 1] }}
          >
            <span className="edu-hero-badge">
              <span className="badge-sparkle">✨</span> FOOD & NUTRITION MASTERCLASS
            </span>
            <h1 className="edu-hero-title">Eat Better. Live Better. Every Day.</h1>
            <p className="edu-hero-subtitle">
              Food is not just something we eat when we are hungry. It fuels our cells, repairs muscle, protects our heart, and builds lifelong immunity. Healthy eating is about balance, not deprivation.
            </p>
            <div className="edu-hero-ctas">
              <a href="#pdf-guidelines-library" className="edu-btn-library">
                📚 PDF Guidelines Library ↓
              </a>
              <a href="#healthy-eating" className="edu-btn-primary">
                Explore Nutrition Guide ↓
              </a>
              <button type="button" className="edu-btn-secondary" onClick={navigateToConsultation}>
                Consult Our Expert →
              </button>
            </div>
          </motion.div>
        </div>
      </section>

      {/* 2. Core Philosophy Cards with Animated Hover Lift */}
      <section className="edu-philosophy-strip" id="healthy-eating">
        <div className="container">
          <motion.div
            className="philosophy-grid"
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, amount: 0.15 }}
            variants={staggerContainer}
          >
            <motion.div
              className="philosophy-card"
              variants={fadeInUp}
              custom={0}
              whileHover={{ y: -6, transition: { duration: 0.2 } }}
            >
              <span className="philo-icon">⚖️</span>
              <h4>Healthy Eating Is About Balance</h4>
              <p>Not about expensive foods, extreme calorie counting, or giving up everything you love. It's about variety and portion wisdom.</p>
            </motion.div>

            <motion.div
              className="philosophy-card"
              variants={fadeInUp}
              custom={1}
              whileHover={{ y: -6, transition: { duration: 0.2 } }}
            >
              <span className="philo-icon">🌾</span>
              <h4>ICMR–NIN 2024 Dietary Aligned</h4>
              <p>Adhering to National Institute of Nutrition guidelines: variety, traditional home kitchens, and minimizing ultra-processed foods.</p>
            </motion.div>

            <motion.div
              className="philosophy-card"
              variants={fadeInUp}
              custom={2}
              whileHover={{ y: -6, transition: { duration: 0.2 } }}
            >
              <span className="philo-icon">🍲</span>
              <h4>Built for the Indian Kitchen</h4>
              <p>Whether your home serves dal-roti, bhakri-shaak, khichdi-kadhi, idli-dosa, or poha, real nutrition starts right on your stove.</p>
            </motion.div>

            <motion.div
              className="philosophy-card"
              variants={fadeInUp}
              custom={3}
              whileHover={{ y: -6, transition: { duration: 0.2 } }}
            >
              <span className="philo-icon">🌿</span>
              <h4>WHO 4-Pillar Standard</h4>
              <p>Adequacy, Balance, Moderation, and Diversity — ensuring your body receives all micro and macronutrients in natural synergy.</p>
            </motion.div>
          </motion.div>
        </div>
      </section>

      {/* 3. What Does Healthy Eating Actually Mean? (Animated 8-box grid) */}
      <section className="edu-section">
        <div className="container">
          <motion.div
            className="section-header-center"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <span className="section-kicker">HOLISTIC NUTRITION</span>
            <h2>What Does “Healthy Eating” Actually Mean?</h2>
            <p className="section-desc">
              You do not need to find one “magic” superfood. No single ingredient provides everything. Your body thrives when you bring these essential food groups together in natural balance:
            </p>
          </motion.div>

          <motion.div
            className="food-groups-grid"
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, amount: 0.1 }}
            variants={staggerContainer}
          >
            {FOOD_GROUPS.map((group, idx) => (
              <motion.div
                key={idx}
                className="food-group-card"
                variants={fadeInUp}
                custom={idx}
                whileHover={{ y: -6, scale: 1.02, transition: { duration: 0.22 } }}
              >
                <div className="fg-icon">{group.icon}</div>
                <div className="fg-body">
                  <h4 className="fg-title">{group.title}</h4>
                  <p className="fg-role">{group.role}</p>
                  <p className="fg-examples"><strong>Examples:</strong> {group.examples}</p>
                </div>
              </motion.div>
            ))}
          </motion.div>
        </div>
      </section>

      {/* 4. The Simple Healthy Plate (Animated Showcase) */}
      <section className="edu-section plate-section-bg">
        <div className="container">
          <motion.div
            className="plate-showcase-box"
            initial={{ opacity: 0, y: 32 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{ duration: 0.7, ease: [0.25, 1, 0.5, 1] }}
          >
            <div className="plate-copy-col">
              <span className="section-kicker">PORTION WISDOM</span>
              <h2>🍽️ The Simple Healthy Plate</h2>
              <p className="plate-intro">
                Instead of stressing over every calorie, look at your plate. Both Harvard's Healthy Eating Plate and ICMR-NIN emphasize visual portions:
              </p>

              <div className="plate-rules-list">
                <motion.div className="plate-rule-item" whileHover={{ x: 6, transition: { duration: 0.2 } }}>
                  <span className="rule-badge rule-green">50%</span>
                  <div>
                    <strong>Make Vegetables & Fruits Half Your Plate</strong>
                    <p>Choose vibrant seasonal colors: leafy greens, gourd veggies, carrots, cucumbers, and tomatoes.</p>
                  </div>
                </motion.div>

                <motion.div className="plate-rule-item" whileHover={{ x: 6, transition: { duration: 0.2 } }}>
                  <span className="rule-badge rule-amber">25%</span>
                  <div>
                    <strong>Sensible Portion of Whole Grains / Staples</strong>
                    <p>Unpolished brown rice, whole wheat roti, millet bhakri (bajra/jowar), or oats.</p>
                  </div>
                </motion.div>

                <motion.div className="plate-rule-item" whileHover={{ x: 6, transition: { duration: 0.2 } }}>
                  <span className="rule-badge rule-blue">25%</span>
                  <div>
                    <strong>Quality Protein Source</strong>
                    <p>Unpolished dal, chana, rajma, fresh curd/chaas, sprouts, and organic paneer.</p>
                  </div>
                </motion.div>

                <motion.div className="plate-rule-item" whileHover={{ x: 6, transition: { duration: 0.2 } }}>
                  <span className="rule-badge rule-water">💧</span>
                  <div>
                    <strong>Hydrate with Clean Water</strong>
                    <p>Make plain water your primary everyday beverage, limiting sweetened and bottled drinks.</p>
                  </div>
                </motion.div>
              </div>
            </div>

            <div className="plate-visual-col">
              <motion.div
                className="healthy-plate-image-wrapper"
                whileHover={{ scale: 1.02 }}
                transition={{ duration: 0.3 }}
                onClick={() => setIsPlateZoomed(true)}
                title="Click to view full-size high-resolution Healthy Eating Plate"
              >
                <div className="plate-zoom-badge">
                  <span>🔍 Click to View Full Size</span>
                </div>
                <img
                  src={healthyPlateImg}
                  alt="The Simple Healthy Plate - Balanced Portions"
                  className="healthy-plate-img"
                  loading="lazy"
                />
              </motion.div>
              <p className="plate-click-hint">💡 Tap or click the plate graphic to inspect the full high-resolution nutritional breakdown</p>
            </div>
          </motion.div>
        </div>
      </section>

      {/* 5. When Should I Eat? Interactive Daily Rhythm with AnimatePresence */}
      <section className="edu-section">
        <div className="container">
          <motion.div
            className="section-header-center"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <span className="section-kicker">MEAL TIMING</span>
            <h2>⏰ When Should I Eat? A Practical Daily Routine</h2>
            <p className="section-desc">
              Healthy eating is not just what you eat, but having a practical daily pattern that fits your life without rigid clocks.
            </p>
          </motion.div>

          <div className="timing-tabs-nav">
            {MEAL_TIMINGS.map((timing) => (
              <button
                key={timing.id}
                type="button"
                className={`timing-tab-btn ${activeTiming === timing.id ? 'active' : ''}`}
                onClick={() => setActiveTiming(timing.id)}
              >
                {timing.time}
              </button>
            ))}
          </div>

          <AnimatePresence mode="wait">
            {(() => {
              const currentTiming = MEAL_TIMINGS.find((t) => t.id === activeTiming)
              return (
                <motion.div
                  key={currentTiming.id}
                  className="timing-content-card"
                  initial={{ opacity: 0, y: 16 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -16 }}
                  transition={{ duration: 0.35, ease: 'easeOut' }}
                >
                  <div className="timing-card-header">
                    <h3>{currentTiming.time}</h3>
                    <span className="timing-tagline">{currentTiming.tagline}</span>
                    <p className="timing-desc">{currentTiming.description}</p>
                  </div>

                  <div className="timing-items-box">
                    <h4>Recommended Wholesome Plates:</h4>
                    <ul className="timing-items-grid">
                      {currentTiming.items.map((item, idx) => (
                        <motion.li
                          key={idx}
                          className="timing-item-pill"
                          whileHover={{ scale: 1.02, x: 4, transition: { duration: 0.18 } }}
                        >
                          <span className="pill-dot">✓</span> {item}
                        </motion.li>
                      ))}
                    </ul>
                  </div>
                </motion.div>
              )
            })()}
          </AnimatePresence>
        </div>
      </section>

      {/* 6. Eat More Often vs Eat Less Often (Contrast Grid with Entrance Animation) */}
      <section className="edu-section contrast-section">
        <div className="container">
          <motion.div
            className="section-header-center"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <span className="section-kicker">EVERYDAY HABITS</span>
            <h2>What to Eat More Often vs What to Eat Less Often</h2>
            <p className="section-desc">
              Think about adding nourishing food before removing foods. It's not about "never eat this"—it's about "don't make this your daily habit."
            </p>
          </motion.div>

          <div className="contrast-columns-grid">
            {/* Green Column */}
            <motion.div
              className="contrast-col col-more"
              initial={{ opacity: 0, x: -30 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true, amount: 0.2 }}
              transition={{ duration: 0.65 }}
              whileHover={{ y: -4, transition: { duration: 0.2 } }}
            >
              <div className="contrast-col-head head-green">
                <span className="col-icon">🌿</span>
                <h3>Eat More Often (Daily Staples)</h3>
              </div>
              <div className="contrast-card-media-box">
                <img
                  src={healthyPlateBanner}
                  alt="Proper Balanced Healthy Food Plate"
                  className="contrast-card-img"
                  loading="lazy"
                />
                <span className="contrast-media-badge badge-healthy">🥗 Proper Balanced Healthy Plate</span>
              </div>
              <div className="contrast-col-body">
                <div className="contrast-block">
                  <strong>🥬 Vegetables & Greens</strong>
                  <p>Spinach, methi, bottle gourd, carrots, tomatoes, cucumber, beans, cauliflower, seasonal local vegetables.</p>
                </div>
                <div className="contrast-block">
                  <strong>🥣 Pulses & Legumes</strong>
                  <p>Moong dal, masoor, unpolished tuvar, chana, rajma, chole, and sprouted beans.</p>
                </div>
                <div className="contrast-block">
                  <strong>🌾 Better Grain Choices</strong>
                  <p>Whole wheat, millets (bajra, jowar, ragi), oats, and unpolished rice.</p>
                </div>
                <div className="contrast-block">
                  <strong>🍊 Fresh Seasonal Fruits</strong>
                  <p>Eat whole regional seasonal fruits rather than relying on one expensive "superfruit".</p>
                </div>
                <div className="contrast-block">
                  <strong>🥜 Nuts & Seeds & Dairy</strong>
                  <p>Groundnuts, almonds, walnuts, sesame, flaxseed, fresh curd, and homemade chaas.</p>
                </div>
              </div>
            </motion.div>

            {/* Red Column */}
            <motion.div
              className="contrast-col col-less"
              initial={{ opacity: 0, x: 30 }}
              whileInView={{ opacity: 1, x: 0 }}
              viewport={{ once: true, amount: 0.2 }}
              transition={{ duration: 0.65 }}
              whileHover={{ y: -4, transition: { duration: 0.2 } }}
            >
              <div className="contrast-col-head head-amber">
                <span className="col-icon">⚠️</span>
                <h3>Eat Less Often (Occasional Treats)</h3>
              </div>
              <div className="contrast-card-media-box">
                <img
                  src={junkPlateBanner}
                  alt="Ultra Processed Junk Food Plate"
                  className="contrast-card-img"
                  loading="lazy"
                />
                <span className="contrast-media-badge badge-junk">🍟 Ultra-Processed Junk Food Plate</span>
              </div>
              <div className="contrast-col-body">
                <div className="contrast-block">
                  <strong>🥤 Sugary Drinks & Packaged Juices</strong>
                  <p>High in empty fructose without dietary fiber; easily overconsumed.</p>
                </div>
                <div className="contrast-block">
                  <strong>🍪 Bakery Goods & Confectionery</strong>
                  <p>Biscuits, cookies, cakes, and pastries containing refined palm oil and excess sugar.</p>
                </div>
                <div className="contrast-block">
                  <strong>🍟 Deep Fried Foods & Farsan</strong>
                  <p>Fried packaged snacks, commercial chips, and repeatedly heated refined oils.</p>
                </div>
                <div className="contrast-block">
                  <strong>🍜 Ultra-Processed Instant Foods</strong>
                  <p>Instant noodles, ready-to-eat gravy packets, and preservative-heavy convenience foods.</p>
                </div>
                <div className="contrast-block">
                  <strong>🧂 Hidden Sodium Sources</strong>
                  <p>Excess commercial pickles, packaged namkeen, sauces, and heavily salted restaurant meals.</p>
                </div>
              </div>
            </motion.div>
          </div>

          <motion.div
            className="balance-reminder-banner"
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5 }}
          >
            <span>💡 <strong>Simple Golden Rule:</strong> Occasionally is different from every day. A festival sweet or birthday cake does not need guilt. The bigger goal is what you eat most days.</span>
          </motion.div>
        </div>
      </section>

      {/* 7. Nutrition Deep Dives (Animated Boxes) */}
      <section className="edu-section">
        <div className="container">
          <motion.div
            className="section-header-center"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <span className="section-kicker">NUTRIENT DEEP DIVES</span>
            <h2>The Science Behind Everyday Food Choices</h2>
          </motion.div>

          <motion.div
            className="deep-dive-grid"
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, amount: 0.15 }}
            variants={staggerContainer}
          >
            <motion.a
              href="https://www.who.int/news-room/fact-sheets/detail/sodium-reduction"
              target="_blank"
              rel="noopener noreferrer"
              className="deep-dive-card deep-dive-link-card"
              variants={fadeInUp}
              whileHover={{ y: -8, transition: { duration: 0.22 } }}
            >
              <div className="dd-card-top">
                <span className="dd-icon">🧂</span>
                <span className="dd-source-badge">WHO Global Fact Sheet ↗</span>
              </div>
              <h4>Salt: The Hidden Habit</h4>
              <p>WHO recommends adult salt intake below <strong>5 grams per day</strong> (approx. 2g sodium). Much of our salt doesn't come from the salt shaker, but from papad, namkeen, pickles, sauces, and packaged snacks. <em>Taste your food before sprinkling extra salt.</em></p>
              <span className="dd-link-action">Read Official Guideline <span className="dd-link-arrow">→</span></span>
            </motion.a>

            <motion.a
              href="https://www.who.int/publications/i/item/9789241549028"
              target="_blank"
              rel="noopener noreferrer"
              className="deep-dive-card deep-dive-link-card"
              variants={fadeInUp}
              whileHover={{ y: -8, transition: { duration: 0.22 } }}
            >
              <div className="dd-card-top">
                <span className="dd-icon">🍭</span>
                <span className="dd-source-badge">WHO Guideline (Sugars) ↗</span>
              </div>
              <h4>Sugar: Beyond the Chai Spoon</h4>
              <p>WHO recommends limiting free sugars to less than 10% of total energy. Beware of tea + biscuits + cold drinks + sweetened juices compounding together. Prefer whole fruits over juices and plain curd over sweetened flavored yogurts.</p>
              <span className="dd-link-action">Read Official Guideline <span className="dd-link-arrow">→</span></span>
            </motion.a>

            <motion.a
              href="https://www.who.int/westernpacific/publications/i/item/9789240073630"
              target="_blank"
              rel="noopener noreferrer"
              className="deep-dive-card deep-dive-link-card"
              variants={fadeInUp}
              whileHover={{ y: -8, transition: { duration: 0.22 } }}
            >
              <div className="dd-card-top">
                <span className="dd-icon">🫒</span>
                <span className="dd-source-badge">WHO Guideline (Fats) ↗</span>
              </div>
              <h4>Oil & Ghee: Fat Is Not the Enemy</h4>
              <p>Your body needs healthy fats for cell membranes and hormone health. Instead of asking “which single oil is best?”, ask “am I using too much oil overall?”. Rotating cold-pressed oils and using a teaspoon of Vedic A2 bilona ghee fits a wholesome diet.</p>
              <span className="dd-link-action">Read Official Guideline <span className="dd-link-arrow">→</span></span>
            </motion.a>

            <motion.a
              href="https://nin.res.in/RDA_Full_Report_2024.html"
              target="_blank"
              rel="noopener noreferrer"
              className="deep-dive-card deep-dive-link-card"
              variants={fadeInUp}
              whileHover={{ y: -8, transition: { duration: 0.22 } }}
            >
              <div className="dd-card-top">
                <span className="dd-icon">💪</span>
                <span className="dd-source-badge badge-nin">ICMR - NIN RDA 2024 ↗</span>
              </div>
              <h4>Protein: Everyday Plate First</h4>
              <p>Protein is vital for tissue repair and muscles, but you don't need expensive commercial powders. Everyday Indian plates with moong, chana, rajma, curd, paneer, and sprouted lentils provide rich, balanced amino acids naturally.</p>
              <span className="dd-link-action">Read Official Guideline <span className="dd-link-arrow">→</span></span>
            </motion.a>

            <motion.a
              href="https://www.who.int/publications/i/item/9789240073593"
              target="_blank"
              rel="noopener noreferrer"
              className="deep-dive-card deep-dive-link-card"
              variants={fadeInUp}
              whileHover={{ y: -8, transition: { duration: 0.22 } }}
            >
              <div className="dd-card-top">
                <span className="dd-icon">🌾</span>
                <span className="dd-source-badge">WHO Guideline (Carbs) ↗</span>
              </div>
              <h4>Carbohydrates Are Not the Enemy</h4>
              <p>Rice and roti are not bad by themselves. What matters is the type of carbohydrate and what you eat with it. Whole minimally processed grains (millets, whole wheat, brown rice) provide clean fuel for your brain and muscles.</p>
              <span className="dd-link-action">Read Official Guideline <span className="dd-link-arrow">→</span></span>
            </motion.a>

            <motion.a
              href="https://nin.res.in/RDA_Full_Report_2024.html"
              target="_blank"
              rel="noopener noreferrer"
              className="deep-dive-card deep-dive-link-card"
              variants={fadeInUp}
              whileHover={{ y: -8, transition: { duration: 0.22 } }}
            >
              <div className="dd-card-top">
                <span className="dd-icon">🛒</span>
                <span className="dd-source-badge badge-nin">ICMR - NIN Report 2024 ↗</span>
              </div>
              <h4>Learn to Read the Food Label</h4>
              <p>The front of the box is advertising; the back label is reality. Always check the ingredients list and watch out for added sugars, palm oil, artificial flavors, and elevated sodium levels before buying packaged items.</p>
              <span className="dd-link-action">Read Official Guideline <span className="dd-link-arrow">→</span></span>
            </motion.a>
          </motion.div>
        </div>
      </section>

      {/* 8. Food Myths vs Facts (Animated Accordion Drawer) */}
      <section className="edu-section myths-section-bg">
        <div className="container">
          <motion.div
            className="section-header-center"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <span className="section-kicker">FACT CHECK</span>
            <h2>🧠 Food Myths vs Facts</h2>
            <p className="section-desc">
              Separate nutritional science from internet trends. Click any myth below to uncover the scientific fact:
            </p>
            <div className="myths-search-wrap">
              <input
                type="text"
                placeholder="Ask any question or search myths (e.g. why diabetes happen, why eat protein)..."
                value={searchMyth}
                onChange={(e) => setSearchMyth(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    handleSeeAnswer()
                  }
                }}
                className="myths-search-input"
              />
              {searchMyth && (
                <button
                  type="button"
                  className="myths-search-clear-btn"
                  onClick={() => setSearchMyth('')}
                  title="Clear input"
                >
                  ✕
                </button>
              )}
              <button
                type="button"
                className="myths-see-answer-btn"
                onClick={() => handleSeeAnswer()}
                title="See the Answer"
              >
                See the Answer →
              </button>
            </div>

            {aiRedirectFeedback && (
              <div className="myths-ai-feedback">
                {aiRedirectFeedback}
              </div>
            )}

            {/* Popular Questions Quick Chips */}
            <div className="myths-suggestion-chips">
              <span className="chips-label">Popular Questions:</span>
              {[
                'Why does diabetes happen?',
                'Why is it important to eat protein?',
                'Is pure desi ghee good or bad for cholesterol?',
                'Does eating rice at night cause belly fat?',
                'Why are commercial market spices adulterated?'
              ].map((sampleQ) => (
                <button
                  key={sampleQ}
                  type="button"
                  className="myths-chip-btn"
                  onClick={() => {
                    setSearchMyth(sampleQ)
                    handleSeeAnswer(sampleQ)
                  }}
                >
                  {sampleQ}
                </button>
              ))}
            </div>
          </motion.div>

          {filteredMyths.length === 0 ? (
            <div className="myths-empty-notice">
              <p>
                No pre-saved myths found for <strong>"{searchMyth}"</strong>. Click <strong>"See the Answer →"</strong> above to view the complete scientific explanation!
              </p>
            </div>
          ) : (
            <div className="myths-accordion-grid">
            {filteredMyths.map((item, idx) => {
              const isOpen = openMythIdx === idx
              return (
                <motion.div
                  key={idx}
                  className={`myth-card ${isOpen ? 'open' : ''}`}
                  onClick={() => toggleMyth(idx)}
                  whileHover={{ y: -3, transition: { duration: 0.18 } }}
                  layout
                >
                  <div className="myth-header">
                    <div className="myth-title-row">
                      <span className="myth-cross">❌</span>
                      <h4 className="myth-text">{item.myth}</h4>
                    </div>
                    <span className="myth-toggle-arrow">{isOpen ? '▲' : '▼'}</span>
                  </div>

                  <AnimatePresence>
                    {isOpen && (
                      <motion.div
                        className="fact-drawer"
                        initial={{ opacity: 0, height: 0 }}
                        animate={{ opacity: 1, height: 'auto' }}
                        exit={{ opacity: 0, height: 0 }}
                        transition={{ duration: 0.25, ease: 'easeOut' }}
                      >
                        <div className="fact-badge">✅ THE SCIENTIFIC FACT</div>
                        <p className="fact-text">{item.fact}</p>
                      </motion.div>
                    )}
                  </AnimatePresence>
                </motion.div>
              )
            })}
          </div>
        )}
        </div>
      </section>

      {/* 9. One Kitchen for the Whole Family & Life Stages (Animated Grid) */}
      <section className="edu-section">
        <div className="container">
          <motion.div
            className="section-header-center"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <span className="section-kicker">FAMILY NOURISHMENT</span>
            <h2>👨‍👩‍👧 One Healthy Kitchen for the Whole Family</h2>
            <p className="section-desc">
              You do not need separate "diet food" for everyone in your family. The same kitchen can prepare healthy meals for children, adults, working professionals, and seniors:
            </p>
          </motion.div>

          <motion.div
            className="life-stages-grid"
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, amount: 0.15 }}
            variants={staggerContainer}
          >
            {LIFE_STAGES.map((ls, idx) => (
              <motion.div
                key={idx}
                className="life-stage-card"
                variants={fadeInUp}
                whileHover={{ y: -6, transition: { duration: 0.2 } }}
              >
                <h4>{ls.stage}</h4>
                <span className="stage-focus">{ls.focus}</span>
                <p>{ls.points}</p>
              </motion.div>
            ))}
          </motion.div>

          <motion.div
            className="family-philosophy-quote"
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <blockquote>
              “You don't need a perfect diet. You need a better everyday diet. You don't have to stop eating your traditional food. Build habits you can sustain for years.”
            </blockquote>
          </motion.div>
        </div>
      </section>

      {/* 10. Food Safety Watch: The Adulteration Reality & Consumer Guide */}
      <section className="edu-section food-awareness-section" id="food-safety-watch">
        <div className="container">
          <motion.div
            className="section-header-center"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <span className="section-kicker reality-kicker">CONSUMER ALERT & MARKET REALITY</span>
            <h2 className="food-awareness-main-title">🛡️ Food Safety Watch: The Adulteration Reality</h2>
            <p className="section-desc food-awareness-desc">
              Every day across India and global markets, news exposés reveal chemical-dyed spices, counterfeit ghee, carbide-ripened produce, and talc-polished staples. Awareness is your family's strongest shield. Explore verified alerts and learn how to test food purity right at home.
            </p>
          </motion.div>

          {/* Cards Grid */}
          {awarenessLoading ? (
            <div className="awareness-loading-state">
              <div className="awareness-spinner"></div>
              <p>Fetching verified food safety reports...</p>
            </div>
          ) : awarenessAlerts.length === 0 ? (
            <div className="awareness-empty-state">
              <p>No safety alerts found.</p>
            </div>
          ) : (
            <motion.div
              className="awareness-cards-grid"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.1 }}
              variants={staggerContainer}
            >
              {awarenessAlerts.map((alert) => (
                <motion.article
                  key={alert.id}
                  className="awareness-card"
                  variants={fadeInUp}
                  whileHover={{ y: -6, transition: { duration: 0.22 } }}
                >
                  {/* Card Media (Video or Image) */}
                  <div
                    className="awareness-card-media-wrapper"
                    onClick={() => setSelectedAlertModal(alert)}
                    role="button"
                    tabIndex={0}
                  >
                    {alert.video ? (
                      <div
                        className="awareness-video-preview"
                        onMouseEnter={(e) => {
                          const vid = e.currentTarget.querySelector('video')
                          if (vid) vid.play().catch(() => {})
                        }}
                        onMouseLeave={(e) => {
                          const vid = e.currentTarget.querySelector('video')
                          if (vid) {
                            vid.pause()
                            vid.currentTime = 0
                          }
                        }}
                      >
                        <video
                          src={`${alert.video}#t=0.1`}
                          poster={alert.image || undefined}
                          muted
                          playsInline
                          preload="metadata"
                          className="awareness-media-asset"
                        />
                        <div className="awareness-media-overlay">
                          <span className="awareness-play-btn" title="Watch Video">▶</span>
                          <span className="awareness-media-label">Video Evidence</span>
                        </div>
                      </div>
                    ) : (
                      <div className="awareness-image-preview">
                        <img
                          src={alert.image || alert.image_url || 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=900&q=80'}
                          alt={alert.title}
                          className="awareness-media-asset"
                          loading="lazy"
                          onError={(e) => {
                            e.currentTarget.onerror = null
                            e.currentTarget.src = 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=900&q=80'
                          }}
                        />
                        <div className="awareness-media-overlay">
                          <span className="awareness-zoom-icon">🔍</span>
                          <span className="awareness-media-label">View Investigation</span>
                        </div>
                      </div>
                    )}
                    <span className={`awareness-severity-badge severity-${alert.severity || 'high'}`}>
                      {alert.severity === 'critical'
                        ? '🚨 CRITICAL ADULTERATION'
                        : alert.severity === 'high'
                        ? '⚠️ HIGH RISK'
                        : 'ℹ️ SAFETY NOTICE'}
                    </span>
                  </div>

                  {/* Card Content */}
                  <div className="awareness-card-body">
                    <div className="awareness-card-meta">
                      <span className="awareness-category-tag">{alert.category_label || alert.category}</span>
                      {alert.incident_date && (
                        <span className="awareness-date-tag">🗓️ {alert.incident_date}</span>
                      )}
                    </div>

                    <h3
                      className="awareness-card-title"
                      onClick={() => setSelectedAlertModal(alert)}
                    >
                      {alert.title}
                    </h3>

                    <p className="awareness-card-summary">
                      {alert.summary}
                    </p>

                    <div className="awareness-card-actions">
                      <button
                        type="button"
                        className="awareness-details-btn"
                        onClick={() => setSelectedAlertModal(alert)}
                      >
                        {alert.video ? '▶ Watch Video' : '🔍 View Photo'}
                      </button>
                    </div>
                  </div>
                </motion.article>
              ))}
            </motion.div>
          )}

          {/* Prakruti Zero-Adulteration Shield Banner */}
          <motion.div
            className="prakruti-shield-banner"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <div className="shield-banner-content">
              <span className="shield-badge">🌿 THE PRAKRUTI PURITY PROMISE</span>
              <h3>Never Bleached. Never Dyed. Never Synthetic.</h3>
              <p>
                In a market driven by cost-cutting and artificial cosmetics, Prakruti adheres strictly to single-origin ethical farming, cold wood-pressed extraction under 40°C, traditional stone grinding, and zero preservatives.
              </p>
              <div className="shield-features-row">
                <div className="shield-feat-item">
                  <span className="feat-check">✓</span>
                  <span><strong>0% Artificial Dyes:</strong> No Sudan Red, no Metanil Yellow</span>
                </div>
                <div className="shield-feat-item">
                  <span className="feat-check">✓</span>
                  <span><strong>Pure A2 Desi Gir Cow Ghee:</strong> Traditional Bilona churned</span>
                </div>
                <div className="shield-feat-item">
                  <span className="feat-check">✓</span>
                  <span><strong>100% Unpolished Dals:</strong> Zero talcum, zero synthetic oils</span>
                </div>
                <div className="shield-feat-item">
                  <span className="feat-check">✓</span>
                  <span><strong>Pure Cold Wood-Pressed Oils:</strong> Zero solvent hexanes</span>
                </div>
              </div>
            </div>
            <div className="shield-banner-cta">
              <button
                type="button"
                className="shield-shop-btn"
                onClick={onShopClick}
              >
                Explore Lab-Tested Prakruti Pantry →
              </button>
            </div>
          </motion.div>
        </div>
      </section>

      {/* 11. PDF Guidelines & Research Library Section */}
      <section className="edu-section pdf-library-section" id="pdf-guidelines-library">
        <div className="container">
          <motion.div
            className="section-header-center"
            initial={{ opacity: 0, y: 24 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.6 }}
          >
            <span className="library-kicker">OFFICIAL RESEARCH & SCIENCE ARCHIVE</span>
            <h2 className="library-main-title">📚 PDF Guidelines & Research Library</h2>
            <p className="library-desc">
              Access verified dietary frameworks, WHO international nutrition manuals, ICMR-NIN RDA blueprints, and Prakruti cold-pressed research whitepapers directly in one place.
            </p>

            {/* Search and Category Filter */}
            <div className="library-controls-wrap">
              <div className="library-search-box">
                <span className="library-search-icon">🔍</span>
                <input
                  type="text"
                  placeholder="Search guidelines by keyword, organization, or title..."
                  value={searchGuideline}
                  onChange={(e) => setSearchGuideline(e.target.value)}
                  className="library-search-input"
                />
                {searchGuideline && (
                  <button
                    type="button"
                    className="library-search-clear"
                    onClick={() => setSearchGuideline('')}
                    title="Clear search"
                  >
                    ✕
                  </button>
                )}
              </div>

              <div className="library-category-pills">
                {[
                  { key: 'all', label: 'All Guidelines' },
                  { key: 'who', label: 'WHO Global' },
                  { key: 'icmr_nin', label: 'ICMR - NIN' },
                  { key: 'fssai', label: 'FSSAI Safety' },
                  { key: 'prakruti', label: 'Prakruti Research' }
                ].map((cat) => (
                  <button
                    key={cat.key}
                    type="button"
                    className={`library-cat-pill ${selectedGuidelineCategory === cat.key ? 'active' : ''}`}
                    onClick={() => setSelectedGuidelineCategory(cat.key)}
                  >
                    {cat.label}
                  </button>
                ))}
              </div>
            </div>
          </motion.div>

          {guidelinesLoading ? (
            <div className="library-loading">
              <div className="library-spinner" />
              <p>Loading official dietary & nutrition archives...</p>
            </div>
          ) : filteredGuidelines.length === 0 ? (
            <div className="library-empty">
              <p>No guidelines found matching your filter criteria.</p>
              <button
                type="button"
                className="library-reset-btn"
                onClick={() => {
                  setSelectedGuidelineCategory('all')
                  setSearchGuideline('')
                }}
              >
                Reset Filters
              </button>
            </div>
          ) : (
            <motion.div
              className="library-grid"
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.1 }}
              variants={staggerContainer}
            >
              {filteredGuidelines.map((item) => {
                const targetUrl = item.file_path
                  ? `/storage/${item.file_path}`
                  : item.external_url || item.download_url

                return (
                  <motion.div
                    key={item.id}
                    className="guideline-card"
                    variants={fadeInUp}
                    whileHover={{ y: -6, transition: { duration: 0.22 } }}
                  >
                    <div className="guideline-card-header">
                      <div className="guideline-meta-tags">
                        <span className={`guideline-badge badge-${item.category || 'general'}`}>
                          {item.organization || item.category_label || 'Official'}
                        </span>
                        {item.published_year && (
                          <span className="guideline-year-badge">{item.published_year}</span>
                        )}
                      </div>
                      {item.file_size && (
                        <span className="guideline-size-badge">📦 {item.file_size}</span>
                      )}
                    </div>

                    <div className="guideline-card-body">
                      <div className="guideline-icon-title">
                        <span className="guideline-pdf-icon">📄</span>
                        <h3 className="guideline-card-title">{item.title}</h3>
                      </div>
                      <p className="guideline-card-desc">{item.description}</p>
                    </div>

                    <div className="guideline-card-footer">
                      {targetUrl ? (
                        <a
                          href={targetUrl}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="guideline-download-btn"
                        >
                          {item.file_path ? '📥 Download PDF' : '🌐 View Official Report ↗'}
                        </a>
                      ) : (
                        <span className="guideline-available-soon">Archived Report</span>
                      )}

                      {item.external_url && item.file_path && (
                        <a
                          href={item.external_url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="guideline-web-link"
                          title="Open official web source"
                        >
                          Source ↗
                        </a>
                      )}
                    </div>
                  </motion.div>
                )
              })}
            </motion.div>
          )}
        </div>
      </section>

      {/* 11. BMI Calculator & Expert Guidance with Smooth Animation */}
      <section className="quiz-section wellness-tools-section" id="bmi-calculator">
        <div className="container">
          <motion.div
            className="quiz-container-box bmi-calculator-box"
            initial={{ opacity: 0, y: 30 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, amount: 0.2 }}
            transition={{ duration: 0.7 }}
          >
            <div className="quiz-active-view">
              <span className="quiz-badge">INTERACTIVE HEALTH TOOL</span>
              <h2 className="quiz-main-title">Calculate Your Adult BMI</h2>
              <p className="quiz-subtitle">
                Body Mass Index (BMI = kg / m²) offers a quick baseline of weight in relation to height according to WHO standards.
              </p>

              <div className="bmi-form-grid">
                <div className="bmi-field-group">
                  <label className="bmi-field-label">Height Unit</label>
                  <div className="bmi-unit-selector">
                    <button
                      type="button"
                      className={`bmi-unit-btn ${heightUnit === 'cm' ? 'active' : ''}`}
                      onClick={() => setHeightUnit('cm')}
                    >
                      Centimeters (cm)
                    </button>
                    <button
                      type="button"
                      className={`bmi-unit-btn ${heightUnit === 'ft-in' ? 'active' : ''}`}
                      onClick={() => setHeightUnit('ft-in')}
                    >
                      Feet & Inches
                    </button>
                  </div>
                </div>

                {heightUnit === 'cm' ? (
                  <div className={`bmi-field-group ${errors.height ? 'has-error' : ''}`}>
                    <label className="bmi-field-label" htmlFor="bmi-height">
                      Height <span className="bmi-label-unit">(cm)</span>
                    </label>
                    <input
                      id="bmi-height"
                      type="number"
                      placeholder="e.g. 165"
                      value={heightCm}
                      onChange={(e) => setHeightCm(e.target.value)}
                    />
                    {errors.height && <span className="bmi-field-error">{errors.height}</span>}
                  </div>
                ) : (
                  <div className={`bmi-field-group ${errors.height ? 'has-error' : ''}`}>
                    <label className="bmi-field-label">Height (Feet & Inches)</label>
                    <div className="bmi-inline-inputs">
                      <input
                        type="number"
                        placeholder="Ft (e.g. 5)"
                        value={feet}
                        onChange={(e) => setFeet(e.target.value)}
                      />
                      <input
                        type="number"
                        placeholder="In (e.g. 7)"
                        value={inches}
                        onChange={(e) => setInches(e.target.value)}
                      />
                    </div>
                    {errors.height && <span className="bmi-field-error">{errors.height}</span>}
                  </div>
                )}

                <div className={`bmi-field-group ${errors.weight ? 'has-error' : ''}`}>
                  <label className="bmi-field-label" htmlFor="bmi-weight">
                    Weight <span className="bmi-label-unit">(kg)</span>
                  </label>
                  <input
                    id="bmi-weight"
                    type="number"
                    placeholder="e.g. 64"
                    value={weightKg}
                    onChange={(e) => setWeightKg(e.target.value)}
                  />
                  {errors.weight && <span className="bmi-field-error">{errors.weight}</span>}
                </div>

                <div className={`bmi-field-group ${errors.age ? 'has-error' : ''}`}>
                  <label className="bmi-field-label" htmlFor="bmi-age">
                    Age <span className="bmi-label-unit">(Years)</span>
                  </label>
                  <input
                    id="bmi-age"
                    type="number"
                    placeholder="e.g. 32"
                    value={age}
                    onChange={(e) => setAge(e.target.value)}
                  />
                  {errors.age && <span className="bmi-field-error">{errors.age}</span>}
                </div>
              </div>

              <div className="bmi-actions-bar">
                <button type="button" className="quiz-next-btn bmi-calculate-btn" onClick={calculateBmi}>
                  Calculate My BMI
                </button>
                <button type="button" className="bmi-clear-btn" onClick={handleResetBmi}>
                  Reset
                </button>
              </div>

              <AnimatePresence>
                {calculatedBmiResult && (
                  <motion.div
                    className="bmi-result-panel"
                    initial={{ opacity: 0, y: 20, scale: 0.98 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    exit={{ opacity: 0, y: -10 }}
                    transition={{ duration: 0.4 }}
                  >
                    <div className="bmi-score-card">
                      <span className="bmi-score-caption">Your Calculated BMI</span>
                      <div className={`bmi-score-number ${calculatedBmiResult.className}`}>
                        {calculatedBmiResult.value}
                      </div>
                      <span className={`bmi-category-badge ${calculatedBmiResult.className}`}>
                        {calculatedBmiResult.category}
                      </span>
                    </div>

                    <div className="bmi-guidance-card">
                      <h4>Nutritional Guidance</h4>
                      <p className="bmi-guidance-text">{calculatedBmiResult.message}</p>
                      
                      <div className="bmi-recommendations-box">
                        <span className="recom-title">Recommended Pantry Categories:</span>
                        <div className="recom-chips">
                          {(BMI_PRODUCT_RECOMMENDATIONS[calculatedBmiResult.category] || []).map((cat) => (
                            <button
                              key={cat}
                              type="button"
                              className="bmi-chip-btn"
                              onClick={onShopClick}
                            >
                              {cat} →
                            </button>
                          ))}
                        </div>
                      </div>

                      <button
                        type="button"
                        className="consult-expert-link-btn"
                        onClick={navigateToConsultation}
                      >
                        Book Nutrition Consultation on Home Page →
                      </button>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>

            </div>
          </motion.div>
        </div>
      </section>

      {/* Lightbox Modal for Healthy Plate Full Zoom */}
      <AnimatePresence>
        {isPlateZoomed && (
          <motion.div
            className="plate-lightbox-backdrop"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setIsPlateZoomed(false)}
          >
            <motion.div
              className="plate-lightbox-dialog"
              initial={{ scale: 0.9, opacity: 0, y: 20 }}
              animate={{ scale: 1, opacity: 1, y: 0 }}
              exit={{ scale: 0.9, opacity: 0, y: 20 }}
              transition={{ duration: 0.28, ease: [0.16, 1, 0.3, 1] }}
              onClick={(e) => e.stopPropagation()}
            >
              <div className="plate-lightbox-header">
                <div>
                  <span className="plate-lightbox-kicker">HIGH-RESOLUTION NUTRITIONAL GUIDE</span>
                  <h3 className="plate-lightbox-title">🍽️ The Power Plate: Eat to Fuel Your Performance</h3>
                </div>
                <button
                  type="button"
                  className="plate-lightbox-close-btn"
                  onClick={() => setIsPlateZoomed(false)}
                  aria-label="Close Guide"
                >
                  ✕
                </button>
              </div>

              <div className="plate-lightbox-body">
                <img
                  src={healthyPlateImg}
                  alt="The Power Plate Full Size Nutritional Guide"
                  className="plate-lightbox-img"
                />
              </div>

              <div className="plate-lightbox-footer">
                <p>
                  Adapted from national & sports nutrition plates. Focus on vibrant seasonal veggies (1/2), whole complex grains (1/4), high-quality plant proteins (1/4), and adequate clean hydration.
                </p>
                <button
                  type="button"
                  className="plate-lightbox-done-btn"
                  onClick={() => setIsPlateZoomed(false)}
                >
                  Close Guide
                </button>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Food Awareness Media Lightbox Modal (Photo / Video Only - No Text) */}
      <AnimatePresence>
        {selectedAlertModal && (
          <motion.div
            className="awareness-modal-backdrop"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setSelectedAlertModal(null)}
          >
            <motion.div
              className="awareness-lightbox-dialog"
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.9, opacity: 0 }}
              transition={{ duration: 0.22, ease: [0.16, 1, 0.3, 1] }}
              onClick={(e) => e.stopPropagation()}
            >
              <button
                type="button"
                className="awareness-lightbox-close-btn"
                onClick={() => setSelectedAlertModal(null)}
                aria-label="Close"
                title="Close"
              >
                ✕
              </button>

              {selectedAlertModal.video ? (
                <div className="awareness-lightbox-video-wrapper">
                  <video
                    src={selectedAlertModal.video}
                    poster={selectedAlertModal.image || undefined}
                    controls
                    autoPlay
                    playsInline
                    className="awareness-lightbox-video"
                  />
                </div>
              ) : (selectedAlertModal.image || selectedAlertModal.image_url) ? (
                <div className="awareness-lightbox-image-wrapper">
                  <img
                    src={selectedAlertModal.image || selectedAlertModal.image_url}
                    alt={selectedAlertModal.title || 'Awareness Media'}
                    className="awareness-lightbox-image"
                    onError={(e) => {
                      e.currentTarget.onerror = null
                      e.currentTarget.src = 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&w=1200&q=80'
                    }}
                  />
                </div>
              ) : null}
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

    </div>
  )
}
