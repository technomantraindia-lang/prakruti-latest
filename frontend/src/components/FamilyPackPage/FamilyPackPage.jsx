import React, { useEffect, useMemo, useState } from 'react';
import './FamilyPackPage.css';
import { productsApi } from '../../api/products';
import { familyPacksApi } from '../../api/familyPacks';
import { useCart } from '../../context/CartContext';
import { FiHeart, FiUser, FiUsers, FiActivity, FiAlertTriangle, FiCheck, FiInfo } from 'react-icons/fi';
import familyPackImg from '../../assets/images/image copy 2.png';
import packgringImg from '../../assets/images/new packing .png';

/**
 * ICMR-NIN 2024 Reference Baseline Table (g/day per profile)
 */
const NIN_REFERENCE_TABLE = {
  C46:    { label: 'Child 4–6',       sex: 'any',    minAge: 4,  maxAge: 6,   cereals: 160, pulses: 60,  nuts: 15, oil: 20, sugar: 20, salt: 5 },
  C79:    { label: 'Child 7–9',       sex: 'any',    minAge: 7,  maxAge: 9,   cereals: 200, pulses: 65,  nuts: 20, oil: 25, sugar: 20, salt: 5 },
  M1012:  { label: 'Boy 10–12',       sex: 'male',   minAge: 10, maxAge: 12,  cereals: 280, pulses: 90,  nuts: 30, oil: 35, sugar: 20, salt: 5 },
  F1012:  { label: 'Girl 10–12',      sex: 'female', minAge: 10, maxAge: 12,  cereals: 250, pulses: 85,  nuts: 30, oil: 30, sugar: 20, salt: 5 },
  M1315:  { label: 'Boy 13–15',       sex: 'male',   minAge: 13, maxAge: 15,  cereals: 390, pulses: 130, nuts: 40, oil: 45, sugar: 20, salt: 5 },
  F1315:  { label: 'Girl 13–15',      sex: 'female', minAge: 13, maxAge: 15,  cereals: 300, pulses: 100, nuts: 35, oil: 40, sugar: 20, salt: 5 },
  M1618:  { label: 'Boy 16–18',       sex: 'male',   minAge: 16, maxAge: 18,  cereals: 450, pulses: 150, nuts: 50, oil: 55, sugar: 20, salt: 5 },
  F1618:  { label: 'Girl 16–18',      sex: 'female', minAge: 16, maxAge: 18,  cereals: 315, pulses: 105, nuts: 40, oil: 40, sugar: 20, salt: 5 },
  M1949S: { label: 'Adult 19–49 Male (Sedentary)', sex: 'male',   minAge: 19, maxAge: 49, cereals: 230, pulses: 75,  nuts: 30, oil: 30, sugar: 20, salt: 5 },
  M1949M: { label: 'Adult 19–49 Male (Moderate)',  sex: 'male',   minAge: 19, maxAge: 49, cereals: 320, pulses: 105, nuts: 40, oil: 35, sugar: 20, salt: 5 },
  F1949S: { label: 'Adult 19–49 Female (Sedentary)',sex: 'female', minAge: 19, maxAge: 49, cereals: 180, pulses: 60,  nuts: 25, oil: 25, sugar: 20, salt: 5 },
  F1949M: { label: 'Adult 19–49 Female (Moderate)', sex: 'female', minAge: 19, maxAge: 49, cereals: 250, pulses: 85,  nuts: 30, oil: 30, sugar: 20, salt: 5 },
  M5060:  { label: 'Adult 50–59 Male', sex: 'male',   minAge: 50, maxAge: 59,  cereals: 200, pulses: 75,  nuts: 30, oil: 25, sugar: 20, salt: 5 },
  F5060:  { label: 'Adult 50–59 Female', sex: 'female', minAge: 50, maxAge: 59, cereals: 165, pulses: 70,  nuts: 25, oil: 20, sugar: 20, salt: 5 },
  M60:    { label: 'Older adult 60+ Male', sex: 'male', minAge: 60, maxAge: 120, cereals: 170, pulses: 75,  nuts: 30, oil: 25, sugar: 20, salt: 5 },
  F60:    { label: 'Older adult 60+ Female', sex: 'female', minAge: 60, maxAge: 120, cereals: 140, pulses: 70,  nuts: 30, oil: 15, sugar: 20, salt: 5 },
  PREG:   { label: 'Pregnant Female',  sex: 'female', minAge: 19, maxAge: 49,  cereals: 220, pulses: 75,  nuts: 40, oil: 30, sugar: 20, salt: 5 },
  LACT:   { label: 'Lactating Female', sex: 'female', minAge: 19, maxAge: 49,  cereals: 260, pulses: 85,  nuts: 40, oil: 35, sugar: 20, salt: 5 },
};

/**
 * Default sample family (4 members representing multi-generational Indian household)
 */
const initialMembers = [
  {
    id: 1,
    name: 'Boy',
    age: 10,
    sex: 'male',
    heightFt: '4',
    heightIn: '8',
    weightKg: '34',
    activity: 'athlete',
    lifeStage: 'none',
    diabetes: false,
    bp: false,
    heart: false,
    kidney: false,
    goal: 'active_child',
  },
  {
    id: 2,
    name: 'Girl',
    age: 14,
    sex: 'female',
    heightFt: '5',
    heightIn: '2',
    weightKg: '45',
    activity: 'moderate',
    lifeStage: 'none',
    diabetes: false,
    bp: false,
    heart: false,
    kidney: false,
    goal: 'general',
  },
  {
    id: 3,
    name: 'Father',
    age: 45,
    sex: 'male',
    heightFt: '5',
    heightIn: '9',
    weightKg: '76',
    activity: 'sedentary',
    lifeStage: 'none',
    diabetes: false,
    bp: false,
    heart: false,
    kidney: false,
    goal: 'weight_loss',
  },
  {
    id: 4,
    name: 'Grandmother',
    age: 65,
    sex: 'female',
    heightFt: '5',
    heightIn: '1',
    weightKg: '58',
    activity: 'sedentary',
    lifeStage: 'none',
    diabetes: true,
    bp: true,
    heart: false,
    kidney: false,
    goal: 'healthy_aging',
  },
];

const initialHousehold = {
  mealsCookedAtHome: '3', // '3', '2', '1', '0.5'
  staplePreference: 'balanced', // 'balanced', 'wheat', 'rice', 'millets'
  pulseFrequency: 'daily', // 'daily', 'moderate', 'rare'
  nutsFrequency: 'daily', // 'daily', 'occasional', 'rare'
  sugarUse: 'moderate', // 'low', 'moderate', 'high'
  snackHabit: 'normal', // 'normal', 'low', 'high'
  oilPreference: 'cold_pressed_rotation', // 'cold_pressed_rotation', 'groundnut', 'mustard', 'sesame'
};

function createMember(type = 'adult', index = 1) {
  let age = 32;
  let sex = 'female';
  let activity = 'moderate';
  let goal = 'general';
  let heightFt = '5';
  let heightIn = '4';
  let weightKg = '60';

  if (type === 'child') {
    age = 8;
    sex = 'male';
    activity = 'moderate';
    goal = 'active_child';
    heightFt = '4';
    heightIn = '2';
    weightKg = '27';
  } else if (type === 'teen') {
    age = 15;
    sex = 'male';
    activity = 'athlete';
    goal = 'high_protein';
    heightFt = '5';
    heightIn = '5';
    weightKg = '54';
  } else if (type === 'senior') {
    age = 65;
    sex = 'female';
    activity = 'sedentary';
    goal = 'healthy_aging';
    heightFt = '5';
    heightIn = '1';
    weightKg = '58';
  }

  return {
    id: Date.now() + Math.random(),
    name: `Member ${index}`,
    age,
    sex,
    heightFt,
    heightIn,
    weightKg,
    activity,
    lifeStage: 'none',
    diabetes: false,
    bp: false,
    heart: false,
    kidney: false,
    goal,
  };
}

function calculateBmi(member) {
  const ft = Number(member?.heightFt || 0);
  const inc = Number(member?.heightIn || 0);
  const totalInches = (ft * 12) + inc;
  const heightM = (totalInches * 2.54) / 100.0;
  const weight = Number(member?.weightKg || 0);

  if (heightM < 0.6 || weight < 5) return null;
  return Number((weight / (heightM * heightM)).toFixed(1));
}

function bmiCategory(member) {
  const age = Number(member?.age || 0);
  const bmi = calculateBmi(member);
  if (!bmi) return 'Enter measurements';
  if (age < 19) return 'Child / Teen Standard';
  if (bmi < 18.5) return 'Below Standard';
  if (bmi < 25) return 'Healthy Adult Range';
  if (bmi < 30) return 'Above Standard';
  return 'High Range (Review)';
}

function matchProfileKey(member) {
  const age = Math.max(1, Number(member?.age || 25));
  const sex = String(member?.sex || 'male').toLowerCase();
  const activity = String(member?.activity || 'moderate').toLowerCase();
  const lifeStage = String(member?.lifeStage || 'none').toLowerCase();

  if (sex === 'female' && age >= 19 && age <= 49) {
    if (lifeStage === 'pregnant') return 'PREG';
    if (lifeStage === 'lactating') return 'LACT';
  }

  if (age < 7) return 'C46';
  if (age < 10) return 'C79';
  if (age <= 12) return sex === 'female' ? 'F1012' : 'M1012';
  if (age <= 15) return sex === 'female' ? 'F1315' : 'M1315';
  if (age <= 18) return sex === 'female' ? 'F1618' : 'M1618';

  if (age <= 49) {
    const isSed = activity === 'sedentary';
    if (sex === 'female') return isSed ? 'F1949S' : 'F1949M';
    return isSed ? 'M1949S' : 'M1949M';
  }

  if (age <= 59) {
    return sex === 'female' ? 'F5060' : 'M5060';
  }

  return sex === 'female' ? 'F60' : 'M60';
}

function assessMember(member) {
  const key = matchProfileKey(member);
  const base = NIN_REFERENCE_TABLE[key] || NIN_REFERENCE_TABLE['M1949M'];
  const age = Number(member.age || 25);
  const activity = String(member.activity || 'moderate').toLowerCase();
  const goal = String(member.goal || 'general').toLowerCase();
  const bmi = calculateBmi(member);

  let cerealMult = 1.0;
  let proteinMult = 1.0;

  if (activity === 'athlete') {
    cerealMult *= 1.20;
    proteinMult *= 1.25;
  } else if (activity === 'high') {
    cerealMult *= 1.10;
    proteinMult *= 1.15;
  } else if (activity === 'sedentary') {
    cerealMult *= 0.95;
  }

  if (age >= 10 && age <= 18) {
    proteinMult *= 1.10;
  }

  if (age >= 19 && bmi !== null && bmi >= 25.0 && (goal === 'weight_loss' || goal === 'weight_management')) {
    cerealMult *= 0.90;
  }

  return {
    ...member,
    profileKey: key,
    profileLabel: base.label,
    bmi,
    dailyGrams: {
      cereals: Math.round(base.cereals * cerealMult),
      pulses: Math.round(base.pulses * proteinMult),
      nuts: Math.round(base.nuts * proteinMult),
      oil: Math.round(base.oil),
      sugar: Math.round(base.sugar),
      salt: Math.round(base.salt),
    },
  };
}

function calculateHouseholdBasket(members, household) {
  if (!members.length) {
    return {
      assessedMembers: [],
      dailyGrams: { cereals: 0, pulses: 0, nuts: 0, oil: 0, sugar: 0, salt: 0 },
      monthlyKg: { cereals: 0, pulses: 0, nuts: 0, oil: 0, sugar: 0, salt: 0 },
      interpretation: 'Add members to generate smart household nutrition plan.',
      safetyAlerts: [],
    };
  }

  const assessedMembers = members.map(assessMember);
  let totCereals = 0;
  let totPulses = 0;
  let totNuts = 0;
  let totOil = 0;
  let totSugar = 0;
  let totSalt = 0;

  let hasDiabetes = false;
  let hasBp = false;
  let hasHeart = false;
  let hasKidney = false;
  let hasAthlete = false;
  let hasTeen = false;
  let hasSenior = false;

  assessedMembers.forEach((m) => {
    totCereals += m.dailyGrams.cereals;
    totPulses  += m.dailyGrams.pulses;
    totNuts    += m.dailyGrams.nuts;
    totOil     += m.dailyGrams.oil;
    totSugar   += m.dailyGrams.sugar;
    totSalt    += m.dailyGrams.salt;

    if (m.diabetes) hasDiabetes = true;
    if (m.bp) hasBp = true;
    if (m.heart) hasHeart = true;
    if (m.kidney) hasKidney = true;
    if (m.activity === 'athlete') hasAthlete = true;
    if (m.age >= 10 && m.age <= 18) hasTeen = true;
    if (m.age >= 60) hasSenior = true;
  });

  // 30 days monthly scaling
  let mCereals = (totCereals * 30) / 1000.0;
  let mPulses  = (totPulses * 30) / 1000.0;
  let mNuts    = (totNuts * 30) / 1000.0;
  let mOil     = (totOil * 30) / 1000.0;
  let mSugar   = (totSugar * 30) / 1000.0;
  let mSalt    = (totSalt * 30) / 1000.0;

  // Household habits adjustments
  const mealsCooked = Number(household.mealsCookedAtHome || 3);
  if (mealsCooked < 2) {
    const f = 0.85;
    mCereals *= f;
    mPulses  *= f;
    mNuts    *= f;
    mOil     *= f;
    mSugar   *= f;
    mSalt    *= f;
  }

  if (household.pulseFrequency === 'rare') mPulses *= 1.15;
  if (household.nutsFrequency === 'rare')  mNuts *= 1.15;

  if (hasDiabetes) {
    mSugar *= 0.50;
  } else if (household.sugarUse === 'high') {
    mSugar *= 1.15;
  }

  if (hasBp)    mSalt *= 0.80;
  if (hasHeart) mOil  *= 0.90;

  // Safety alerts
  const safetyAlerts = [];
  if (hasKidney) {
    safetyAlerts.push({
      type: 'critical',
      title: 'Kidney Condition Detected – Professional Review Required',
      message: 'Kidney condition detected: grocery engine requires professional review. No automated disease-specific restriction. A qualified clinical nephrologist or dietitian should review the basket before purchase.',
    });
  }
  if (hasDiabetes) {
    safetyAlerts.push({
      type: 'info',
      title: 'Sugar-Conscious Basket Adjustment',
      message: 'Diabetes flag detected: routine added sugar purchase is reduced by 50% across the household basket, emphasizing unpolished high-fiber grains and legumes.',
    });
  }
  if (hasBp) {
    safetyAlerts.push({
      type: 'info',
      title: 'Salt-Mindful Planning',
      message: 'Blood pressure flag detected: salt purchase signal reduced by 20% to support a mindful intake below WHO 5g/day threshold.',
    });
  }

  // Multi-parameter website interpretation text
  const priorities = [];
  if (hasAthlete)  priorities.push('active/growing members');
  if (hasTeen)     priorities.push('adequate protein & mineral availability');
  if (hasDiabetes) priorities.push('lower added sugar with high-fiber grains');
  if (hasHeart)    priorities.push('cold-pressed healthy fat moderation');
  if (hasSenior)   priorities.push('nutrient-dense senior vitality');

  const priorityStr = priorities.length
    ? priorities.join(', ')
    : 'balanced whole-food household nourishment';

  const interpretation = `Your family profile shows ${members.length} member(s) with priorities: ${priorityStr}. `
    + `The basket allocates approx. ${mCereals.toFixed(1)} kg unpolished grains & flours, `
    + `${mPulses.toFixed(1)} kg varied pulses/legumes, `
    + `${mOil.toFixed(1)} L cold-pressed cooking oils & A2 ghee, and `
    + `${mNuts.toFixed(1)} kg nuts/seeds for a 30-day supply.`;

  return {
    assessedMembers,
    dailyGrams: {
      cereals: Math.round(totCereals),
      pulses: Math.round(totPulses),
      nuts: Math.round(totNuts),
      oil: Math.round(totOil),
      sugar: Math.round(totSugar),
      salt: Math.round(totSalt),
    },
    monthlyKg: {
      cereals: Number(mCereals.toFixed(1)),
      pulses: Number(mPulses.toFixed(1)),
      nuts: Number(mNuts.toFixed(1)),
      oil: Number(mOil.toFixed(1)),
      sugar: Number(mSugar.toFixed(1)),
      salt: Number(mSalt.toFixed(1)),
    },
    interpretation,
    safetyAlerts,
  };
}

function resolvePackage(product, preferredVarId = null) {
  const variations = Array.isArray(product?.variations) ? product.variations : [];
  const selected = variations.find((v) => String(v.id) === String(preferredVarId)) ||
    variations.find((v) => Number(v.available_stock ?? 0) > 0) ||
    variations[0] ||
    null;
  const label = selected?.label || selected?.package_size || selected?.attributes?.Weight || product?.weight || '1 pack';
  const price = Number(selected?.sale_price ?? selected?.price ?? product?.price ?? 0);

  return {
    var_id: selected?.id || null,
    package_size: String(label || '1 pack'),
    pack_weight: selected?.weight ?? null,
    price,
  };
}

function formatPrice(val) {
  return `₹${Number(val || 0).toLocaleString('en-IN')}`;
}

const FamilyPackPage = ({ onGoToCart, currentUser, onLoginRequired }) => {
  const { addToCart, flash } = useCart();
  const [products, setProducts] = useState([]);
  const [members, setMembers] = useState(initialMembers);
  const [household, setHousehold] = useState(initialHousehold);
  const [recommendations, setRecommendations] = useState([]);
  const [savedMessage, setSavedMessage] = useState('');
  const [addingPack, setAddingPack] = useState(false);
  const [savedPack, setSavedPack] = useState(null);
  const [extraSearch, setExtraSearch] = useState('');

  // 1. Calculate Household Requirements using ICMR-NIN 2024 Engine
  const basketPlan = useMemo(() => {
    return calculateHouseholdBasket(members, household);
  }, [members, household]);

  // 2. Fetch Products and Saved Pack
  useEffect(() => {
    let mounted = true;
    productsApi.getProducts({ per_page: 100 }).then(async (res) => {
      if (!mounted) return;
      const loaded = res.data || [];
      setProducts(loaded);

      const latest = currentUser ? await familyPacksApi.getLatest() : { success: true, data: null };
      if (!mounted) return;

      const saved = currentUser && latest.success && latest.data ? latest.data : null;
      if (saved?.profile?.members?.length) {
        setSavedPack(saved);
        setMembers(saved.profile.members);
        if (saved.profile.household) {
          setHousehold(saved.profile.household);
        }
      }
    });

    return () => {
      mounted = false;
    };
  }, [currentUser]);

  // 3. Match calculated monthly kg with available store inventory
  useEffect(() => {
    if (!products.length || !members.length) return;

    const { cereals, pulses, nuts, oil, sugar, salt } = basketPlan.monthlyKg;

    // Helper to find product by keyword
    const findProd = (keywords) => {
      for (const kw of keywords) {
        const found = products.find((p) => (p.name || '').toLowerCase().includes(kw.toLowerCase()));
        if (found) return found;
      }
      return null;
    };

    const items = [];

    // --- Cereals & Flours (~cereals kg) ---
    // Wheat / Atta (approx 50% of cereals)
    const attaProd = findProd(['wheat flour', 'multigrain flour', 'bajra atta']);
    if (attaProd) {
      const kgNeeded = Math.max(1, cereals * 0.5);
      const pkg = resolvePackage(attaProd);
      items.push({
        key: 'atta-staple',
        label: 'Unpolished Whole Wheat & Flours',
        benefit: 'Dietary Fiber & Daily Complex Energy',
        product_id: attaProd.id,
        var_id: pkg.var_id,
        name: attaProd.name,
        image: attaProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.round(kgNeeded / 5) || Math.ceil(kgNeeded / 2)),
      });
    }

    // Rice (approx 30% of cereals)
    const riceProd = findProd(['sona masoori', 'basmati rice brown', 'basmati rice white', 'rice']);
    if (riceProd) {
      const kgNeeded = Math.max(1, cereals * 0.3);
      const pkg = resolvePackage(riceProd);
      items.push({
        key: 'rice-staple',
        label: 'Traditional Wholesome Rice',
        benefit: 'Clean Digestion & Essential Carbohydrates',
        product_id: riceProd.id,
        var_id: pkg.var_id,
        name: riceProd.name,
        image: riceProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.round(kgNeeded / 5) || Math.ceil(kgNeeded / 2)),
      });
    }

    // Millets (approx 20% of cereals)
    const milletProd = findProd(['ragi', 'jowar', 'bajra whole', 'kodo']);
    if (milletProd) {
      const kgNeeded = Math.max(1, cereals * 0.2);
      const pkg = resolvePackage(milletProd);
      items.push({
        key: 'millet-staple',
        label: 'Ancient Millets (Jowar / Ragi / Bajra)',
        benefit: 'Prebiotic Minerals & Slow-Release Energy',
        product_id: milletProd.id,
        var_id: pkg.var_id,
        name: milletProd.name,
        image: milletProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(kgNeeded / 1)),
      });
    }

    // --- Pulses & Legumes (~pulses kg) ---
    // Main Dal (Toor / Pigeon Pea)
    const toorProd = findProd(['tuar dal', 'arhar']);
    if (toorProd) {
      const kgNeeded = Math.max(1, pulses * 0.4);
      const pkg = resolvePackage(toorProd);
      items.push({
        key: 'toor-dal',
        label: 'Daily Unpolished Toor Dal',
        benefit: 'Rich Plant-Based Protein & Folate',
        product_id: toorProd.id,
        var_id: pkg.var_id,
        name: toorProd.name,
        image: toorProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(kgNeeded / 1)),
      });
    }

    // Secondary Dal (Moong / Panchratna)
    const moongProd = findProd(['moong', 'panchratna', 'masoor']);
    if (moongProd) {
      const kgNeeded = Math.max(1, pulses * 0.3);
      const pkg = resolvePackage(moongProd);
      items.push({
        key: 'moong-dal',
        label: 'Sprouted Green Gram / Yellow Moong',
        benefit: 'Easy Digestion & Bioavailable Amino Acids',
        product_id: moongProd.id,
        var_id: pkg.var_id,
        name: moongProd.name,
        image: moongProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(kgNeeded / 1)),
      });
    }

    // Legumes (Chana / Rajma)
    const legumeProd = findProd(['rajma', 'chana dal', 'kabuli chana']);
    if (legumeProd) {
      const kgNeeded = Math.max(1, pulses * 0.3);
      const pkg = resolvePackage(legumeProd);
      items.push({
        key: 'legumes-chana',
        label: 'Hearty Legumes (Rajma / Desi Chana)',
        benefit: 'Iron, Potassium & Sustained Satiety',
        product_id: legumeProd.id,
        var_id: pkg.var_id,
        name: legumeProd.name,
        image: legumeProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(kgNeeded / 1)),
      });
    }

    // --- Cooking Oils & Vedic A2 Ghee (~oil Liters) ---
    const oilProd = findProd(['groundnut oil', 'sunflower oil', 'sesame oil']);
    if (oilProd) {
      const litersNeeded = Math.max(1, oil * 0.7);
      const pkg = resolvePackage(oilProd);
      items.push({
        key: 'cooking-oil',
        label: 'Cold-Pressed Wooden Ghani Cooking Oil',
        benefit: 'Heart-Healthy MUFA / PUFA under 40°C',
        product_id: oilProd.id,
        var_id: pkg.var_id,
        name: oilProd.name,
        image: oilProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(litersNeeded / 1)),
      });
    }

    const gheeProd = findProd(['a2 gir cow ghee', 'ghee']);
    if (gheeProd) {
      const litersNeeded = Math.max(0.5, oil * 0.3);
      const pkg = resolvePackage(gheeProd);
      items.push({
        key: 'vedic-ghee',
        label: 'Traditional Vedic A2 Bilona Cow Ghee',
        benefit: 'Butyric Acid for Gut Microbiome & Hormones',
        product_id: gheeProd.id,
        var_id: pkg.var_id,
        name: gheeProd.name,
        image: gheeProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(litersNeeded / 0.5)),
      });
    }

    // --- Seeds & Nuts (~nuts kg) ---
    const seedsProd = findProd(['chia seed', 'flax seed', 'pumpkin seeds', 'sunflower seed']);
    if (seedsProd) {
      const pkg = resolvePackage(seedsProd);
      items.push({
        key: 'super-seeds',
        label: 'Raw Omega-3 Essential Seeds & Nuts',
        benefit: 'Plant Omega-3, Zinc, Magnesium & Minerals',
        product_id: seedsProd.id,
        var_id: pkg.var_id,
        name: seedsProd.name,
        image: seedsProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(nuts / 0.5)),
      });
    }

    // --- Natural Sweetener (~sugar kg) ---
    const sugarProd = findProd(['jaggery powder', 'raw sugar', 'khandasari']);
    if (sugarProd) {
      const pkg = resolvePackage(sugarProd);
      items.push({
        key: 'natural-sweetener',
        label: 'Organic Desi Jaggery / Raw Sugar',
        benefit: 'Unrefined Pure Sweetener (Controlled Use)',
        product_id: sugarProd.id,
        var_id: pkg.var_id,
        name: sugarProd.name,
        image: sugarProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(sugar / 1)),
      });
    }

    // --- Spices & Himalayan Salt ---
    const spiceProd = findProd(['turmeric powder', 'cumin powder', 'coriander powder']);
    if (spiceProd) {
      const pkg = resolvePackage(spiceProd);
      items.push({
        key: 'organic-turmeric',
        label: 'Salem High-Curcumin Organic Turmeric',
        benefit: 'Daily Antioxidant & Anti-Inflammatory Shield',
        product_id: spiceProd.id,
        var_id: pkg.var_id,
        name: spiceProd.name,
        image: spiceProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: 1,
      });
    }

    const saltProd = findProd(['pink rock salt', 'sendha namak', 'salt']);
    if (saltProd) {
      const pkg = resolvePackage(saltProd);
      items.push({
        key: 'rock-salt',
        label: 'Pink Rock Salt / Sendha Namak',
        benefit: 'Natural Trace Minerals & Controlled Sodium',
        product_id: saltProd.id,
        var_id: pkg.var_id,
        name: saltProd.name,
        image: saltProd.image || packgringImg,
        price: pkg.price,
        package_size: pkg.package_size,
        qty: Math.max(1, Math.ceil(salt / 1)),
      });
    }

    setRecommendations(items);
  }, [products, members, household, basketPlan]);

  // Member CRUD handlers
  const addMember = (type = 'adult') => {
    setMembers((prev) => [...prev, createMember(type, prev.length + 1)]);
  };

  const removeMember = (id) => {
    setMembers((prev) => prev.filter((m) => m.id !== id));
  };

  const updateMember = (id, field, value) => {
    setMembers((prev) =>
      prev.map((m) => {
        if (m.id !== id) return m;
        return { ...m, [field]: value };
      })
    );
  };

  const updateRecommendationQty = (key, delta) => {
    setRecommendations((prev) =>
      prev
        .map((item) => (item.key === key ? { ...item, qty: Math.max(1, item.qty + delta) } : item))
        .filter((item) => item.qty > 0)
    );
  };

  const removeRecommendation = (key) => {
    setRecommendations((prev) => prev.filter((item) => item.key !== key));
  };

  const addExtraProduct = (product) => {
    const pkg = resolvePackage(product);
    setRecommendations((prev) => {
      const existing = prev.find((item) => item.product_id === product.id);
      if (existing) {
        return prev.map((item) =>
          item.product_id === product.id ? { ...item, qty: item.qty + 1 } : item
        );
      }
      return [
        ...prev,
        {
          key: `extra-${product.id}-${Date.now()}`,
          label: product.category || 'Pantry Staple',
          benefit: 'Wholesome organic staple',
          product_id: product.id,
          var_id: pkg.var_id,
          name: product.name,
          image: product.image || packgringImg,
          price: pkg.price,
          package_size: pkg.package_size,
          qty: 1,
        },
      ];
    });
    flash(`Added ${product.name} to family pack`);
  };

  const packTotal = useMemo(() => {
    return recommendations.reduce((sum, item) => sum + (item.price * item.qty), 0);
  }, [recommendations]);

  const savePack = async () => {
    if (!currentUser) {
      setSavedMessage('Please login or register to save your Smart Family Pack.');
      setTimeout(() => {
        setSavedMessage('');
        if (onLoginRequired) onLoginRequired();
      }, 1500);
      return;
    }

    const payload = {
      profile: {
        members,
        household,
        interpretation: basketPlan.interpretation,
      },
      nutrient_summary: basketPlan.monthlyKg,
      recommendations,
      monthly_total: packTotal,
    };

    const res = await familyPacksApi.save(payload);
    if (res.success) {
      setSavedMessage('✓ Smart Family Pack configuration saved successfully!');
      setSavedPack(res.data);
    } else {
      setSavedMessage(res.message || 'Could not save configuration. Please try again.');
    }
    setTimeout(() => setSavedMessage(''), 3500);
  };

  const addPackToCart = async () => {
    if (!currentUser) {
      setSavedMessage('Please login or register to purchase your family pack.');
      setTimeout(() => {
        setSavedMessage('');
        if (onLoginRequired) onLoginRequired();
      }, 1200);
      return;
    }

    setAddingPack(true);
    await savePack();

    localStorage.setItem('prakruti_pending_order_type', 'family_pack');
    for (const item of recommendations) {
      await addToCart(item.product_id, item.qty, item.var_id || null);
    }
    setAddingPack(false);
    flash('Smart Family Pack added to cart');
    if (onGoToCart) onGoToCart();
  };

  const filteredExtras = useMemo(() => {
    if (!extraSearch.trim()) return products.slice(0, 8);
    const q = extraSearch.toLowerCase();
    return products.filter((p) => (p.name || '').toLowerCase().includes(q));
  }, [products, extraSearch]);

  return (
    <div className="family-pack-page">
      {/* 1. Hero Section */}
      <section className="family-pack-hero">
        <div className="container family-pack-hero-grid">
          <div className="family-pack-hero-copy">
            <span className="family-pack-pill-badge">🌾 Smart Family Grocery Planner</span>
            <h1>
              Household <em>Nutrition Basket</em>
            </h1>
            <p className="fp-hero-subtext">
              Scientific, member-level grocery planning aligned with <strong>ICMR-NIN 2024 Dietary Guidelines</strong>. 
              We calculate baseline nutrition for every individual family member, then aggregate a wholesome 30-day organic pantry pack.
            </p>

            <div className="positioning-disclaimer-pill">
              <FiInfo className="disclaimer-info-icon" />
              <span>
                <strong>Smart Family Grocery Planner:</strong> Designed for routine household grocery estimation. 
                Not a medical diet, diabetes treatment, kidney disease prescription, or clinical meal plan.
              </span>
            </div>

            <div className="family-pack-actions">
              <button
                type="button"
                className="btn btn-primary-fp"
                onClick={addPackToCart}
                disabled={addingPack || !recommendations.length}
              >
                {addingPack ? 'Adding to Cart...' : '🛒 Add Entire Smart Basket to Cart'}
              </button>
              <button type="button" className="btn btn-outline-fp" onClick={savePack}>
                💾 Save Configuration
              </button>
            </div>

            {savedMessage && <div className="family-pack-save-toast">{savedMessage}</div>}

            <div className="family-pack-stats-row">
              <div className="stat-card">
                <span className="stat-num">{members.length}</span>
                <span className="stat-lbl">Family Members</span>
              </div>
              <div className="stat-card">
                <span className="stat-num">{recommendations.length}</span>
                <span className="stat-lbl">Pantry Items</span>
              </div>
              <div className="stat-card highlight">
                <span className="stat-num">{formatPrice(packTotal)}</span>
                <span className="stat-lbl">Est. Monthly Total</span>
              </div>
            </div>
          </div>

          <div className="family-pack-hero-image-wrap">
            <div className="hero-img-frame">
              <img src={familyPackImg} alt="Smart Family Grocery Planner Basket" />
            </div>
            <div className="hero-img-caption-tag">
              <span className="floating-icon">🌱</span>
              <span className="caption-text">ICMR-NIN 2024 Aligned Basket</span>
            </div>
          </div>
        </div>
      </section>

      {/* Safety & Clinical Flags (if any) */}
      {basketPlan.safetyAlerts.length > 0 && (
        <section className="container fp-section-container">
          {basketPlan.safetyAlerts.map((alert, idx) => (
            <div key={idx} className={`fp-safety-alert ${alert.type}`}>
              <div className="alert-icon-col">
                <FiAlertTriangle className="alert-svg-icon" />
              </div>
              <div className="alert-text-col">
                <h4>{alert.title}</h4>
                <p>{alert.message}</p>
              </div>
            </div>
          ))}
        </section>
      )}

      {/* Multi-Parameter Family Interpretation Report */}
      <section className="container fp-section-container">
        <div className="smart-interpretation-card">
          <div className="interp-header">
            <span className="interp-badge">🧠 MULTI-PARAMETER ENGINE INSIGHT</span>
            <h3>How Prakruti Interprets Your Household Profile</h3>
          </div>
          <p className="interp-text">{basketPlan.interpretation}</p>
          <div className="interp-tags-row">
            <span className="interp-tag">✓ Member-Level First, Household Second</span>
            <span className="interp-tag">✓ No Generic Family Average</span>
            <span className="interp-tag">✓ ICMR-NIN 2024 Standard Portions</span>
            <span className="interp-tag">✓ 30-Day Vacuum-Packed Reserve</span>
          </div>
        </div>
      </section>

      {/* STEP 1: Family Member Nutrition Profiles */}
      <section className="container fp-section-container">
        <div className="fp-card fp-members-card">
          <div className="fp-card-header">
            <div>
              <span className="step-tag">Step 01</span>
              <h2>Family Member Nutrition Profiles</h2>
              <p>
                Enter each member's age, biological sex, activity level, and health priorities. 
                Our engine matches each person to their specific ICMR-NIN baseline.
              </p>
            </div>

            <div className="member-quick-add">
              <button type="button" className="btn-add-member" onClick={() => addMember('adult')}>
                + Add Adult
              </button>
              <button type="button" className="btn-add-member" onClick={() => addMember('teen')}>
                + Add Teen (10-18)
              </button>
              <button type="button" className="btn-add-member" onClick={() => addMember('child')}>
                + Add Child
              </button>
              <button type="button" className="btn-add-member" onClick={() => addMember('senior')}>
                + Add Senior (60+)
              </button>
            </div>
          </div>

          <div className="member-grid-list">
            {members.map((member, index) => {
              const bmi = calculateBmi(member);
              const isFemale = member.sex === 'female';
              const isAdultFemale = isFemale && member.age >= 19 && member.age <= 49;
              const bmiCat = bmiCategory(member);

              return (
                <div className="member-card-item detailed-profile" key={member.id}>
                  {/* Top Bar Header */}
                  <div className="member-card-top-bar">
                    <div className="member-top-left-badges">
                      <span className="member-index-tag">Member #{index + 1}</span>
                      <span className="member-profile-code">
                        {matchProfileKey(member)} · {NIN_REFERENCE_TABLE[matchProfileKey(member)]?.label}
                      </span>
                    </div>
                    {members.length > 1 && (
                      <button
                        type="button"
                        className="btn-remove-member"
                        onClick={() => removeMember(member.id)}
                        title="Remove member"
                      >
                        ✕
                      </button>
                    )}
                  </div>

                  {/* Primary Demographics (Structured 2-row grid) */}
                  <div className="member-demographics-grid">
                    <div className="input-wrap name-wrap">
                      <label>Name / Label</label>
                      <input
                        type="text"
                        value={member.name}
                        onChange={(e) => updateMember(member.id, 'name', e.target.value)}
                        placeholder={`Member ${index + 1}`}
                      />
                    </div>

                    <div className="input-wrap sex-wrap">
                      <label>Sex</label>
                      <select
                        value={member.sex || 'male'}
                        onChange={(e) => updateMember(member.id, 'sex', e.target.value)}
                      >
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                      </select>
                    </div>

                    <div className="input-wrap age-wrap">
                      <label>Age (Yrs)</label>
                      <input
                        type="number"
                        min="1"
                        max="110"
                        value={member.age}
                        onChange={(e) => updateMember(member.id, 'age', Number(e.target.value))}
                      />
                    </div>

                    <div className="input-wrap height-wrap">
                      <label>Height (ft / in)</label>
                      <div className="ft-in-flex">
                        <input
                          type="number"
                          min="1"
                          max="7"
                          value={member.heightFt}
                          onChange={(e) => updateMember(member.id, 'heightFt', e.target.value)}
                          placeholder="ft"
                        />
                        <span className="ft-in-divider">/</span>
                        <input
                          type="number"
                          min="0"
                          max="11"
                          value={member.heightIn}
                          onChange={(e) => updateMember(member.id, 'heightIn', e.target.value)}
                          placeholder="in"
                        />
                      </div>
                    </div>

                    <div className="input-wrap weight-wrap">
                      <label>Weight (kg)</label>
                      <input
                        type="number"
                        min="5"
                        max="250"
                        value={member.weightKg}
                        onChange={(e) => updateMember(member.id, 'weightKg', e.target.value)}
                        placeholder="kg"
                      />
                    </div>
                  </div>

                  {/* Lifestyle & Nutrition Goals */}
                  <div className="member-lifestyle-grid">
                    <div className="input-wrap">
                      <label>Daily Activity &amp; Lifestyle</label>
                      <select
                        value={member.activity || 'moderate'}
                        onChange={(e) => updateMember(member.id, 'activity', e.target.value)}
                      >
                        <option value="sedentary">Desk / Office Routine (Sedentary)</option>
                        <option value="moderate">Moderate (Active Daily Routine)</option>
                        <option value="high">High Physical / Manual Activity</option>
                        <option value="athlete">High-Performance Athlete / Sports</option>
                      </select>
                    </div>

                    {isAdultFemale && (
                      <div className="input-wrap">
                        <label>Life Stage</label>
                        <select
                          value={member.lifeStage || 'none'}
                          onChange={(e) => updateMember(member.id, 'lifeStage', e.target.value)}
                        >
                          <option value="none">Standard Adult Profile</option>
                          <option value="pregnant">Pregnant (Life Stage++)</option>
                          <option value="lactating">Lactating (0–6 Months)</option>
                        </select>
                      </div>
                    )}

                    <div className="input-wrap">
                      <label>Nutrition Goal</label>
                      <select
                        value={member.goal || 'general'}
                        onChange={(e) => updateMember(member.id, 'goal', e.target.value)}
                      >
                        <option value="general">General Wholesome Nutrition</option>
                        <option value="weight_loss">Weight Management</option>
                        <option value="high_protein">Muscle &amp; Protein Priority</option>
                        <option value="active_child">Active Child Growth</option>
                        <option value="healthy_aging">Senior Vitality &amp; Density</option>
                      </select>
                    </div>
                  </div>

                  {/* BMI Screening Strip */}
                  <div className="member-bmi-strip">
                    <div className="bmi-strip-left">
                      <span className="bmi-strip-label">BMI SCREENING</span>
                      <strong className="bmi-strip-value">{bmi || '—'}</strong>
                    </div>
                    <div className="bmi-strip-right">
                      <span className={`bmi-status-badge ${bmi ? (member.age < 19 ? 'child' : bmi < 18.5 ? 'low' : bmi <= 24.9 ? 'normal' : bmi <= 29.9 ? 'over' : 'high') : 'empty'}`}>
                        {bmiCat}
                      </span>
                    </div>
                  </div>

                  {/* Health Flags Bar */}
                  <div className="member-health-section">
                    <div className="health-section-header">
                      <span className="flags-label">Health &amp; Dietary Flags</span>
                      <span className="flags-hint">(Automatic ICMR-NIN recipe formula)</span>
                    </div>
                    <div className="health-flags-chips">
                      <label className={`health-flag-chip ${member.diabetes ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          checked={!!member.diabetes}
                          onChange={(e) => updateMember(member.id, 'diabetes', e.target.checked)}
                        />
                        <span>🍬 Diabetes (Sugar -50%)</span>
                      </label>

                      <label className={`health-flag-chip ${member.bp ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          checked={!!member.bp}
                          onChange={(e) => updateMember(member.id, 'bp', e.target.checked)}
                        />
                        <span>🧂 High BP (Salt -20%)</span>
                      </label>

                      <label className={`health-flag-chip ${member.heart ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          checked={!!member.heart}
                          onChange={(e) => updateMember(member.id, 'heart', e.target.checked)}
                        />
                        <span>🫀 Heart / Lipid (Oil -10%)</span>
                      </label>

                      <label className={`health-flag-chip danger ${member.kidney ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          checked={!!member.kidney}
                          onChange={(e) => updateMember(member.id, 'kidney', e.target.checked)}
                        />
                        <span>⚠️ Kidney Condition (Review)</span>
                      </label>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* STEP 2: Household & Food Habit Questions */}
      <section className="container fp-section-container">
        <div className="fp-card fp-household-card">
          <div className="fp-card-header">
            <div>
              <span className="step-tag">Step 02</span>
              <h2>Household &amp; Food Habit Questions</h2>
              <p>Fine-tune family cooking frequencies, staple grain preferences, and pantry rotation habits.</p>
            </div>
          </div>

          <div className="household-habits-grid">
            <div className="habit-item">
              <label>Meals cooked at home / day</label>
              <select
                value={household.mealsCookedAtHome}
                onChange={(e) => setHousehold({ ...household, mealsCookedAtHome: e.target.value })}
              >
                <option value="3">All meals cooked at home (3 meals/day)</option>
                <option value="2">Most meals cooked at home (2 meals/day)</option>
                <option value="1">Dinner only (1 meal/day)</option>
                <option value="0.5">Low home cooking / frequent travel (0.85x Factor)</option>
              </select>
            </div>

            <div className="habit-item">
              <label>Staple grain preference</label>
              <select
                value={household.staplePreference}
                onChange={(e) => setHousehold({ ...household, staplePreference: e.target.value })}
              >
                <option value="balanced">Balanced Whole Grains &amp; Millets (Recommended)</option>
                <option value="wheat">Whole Wheat &amp; Atta Dominant</option>
                <option value="rice">Traditional Rice Dominant</option>
                <option value="millets">Ancient Millets Dominant (Ragi/Jowar/Bajra)</option>
              </select>
            </div>

            <div className="habit-item">
              <label>Dal / Pulse cooking frequency</label>
              <select
                value={household.pulseFrequency}
                onChange={(e) => setHousehold({ ...household, pulseFrequency: e.target.value })}
              >
                <option value="daily">Daily (1–2 times every day)</option>
                <option value="moderate">Regular (3–4 days/week)</option>
                <option value="rare">Infrequent / Low pulse intake (+15% Variety Boost)</option>
              </select>
            </div>

            <div className="habit-item">
              <label>Nuts &amp; seeds frequency</label>
              <select
                value={household.nutsFrequency}
                onChange={(e) => setHousehold({ ...household, nutsFrequency: e.target.value })}
              >
                <option value="daily">Daily snacking &amp; smoothie use</option>
                <option value="occasional">Occasional (2–3 times/week)</option>
                <option value="rare">Infrequent / Rare (+15% Availability Boost)</option>
              </select>
            </div>

            <div className="habit-item">
              <label>Added sugar use at home</label>
              <select
                value={household.sugarUse}
                onChange={(e) => setHousehold({ ...household, sugarUse: e.target.value })}
              >
                <option value="low">Mindful / Low added sugar</option>
                <option value="moderate">Moderate / Standard use</option>
                <option value="high">High tea &amp; dessert use (Reduction encouraged)</option>
              </select>
            </div>

            <div className="habit-item">
              <label>Cooking oil &amp; ghee preference</label>
              <select
                value={household.oilPreference}
                onChange={(e) => setHousehold({ ...household, oilPreference: e.target.value })}
              >
                <option value="cold_pressed_rotation">Cold-Pressed Multi-Oil Rotation + Vedic A2 Ghee</option>
                <option value="groundnut">Cold-Pressed Groundnut Oil dominant</option>
                <option value="mustard">Cold-Pressed Mustard Oil dominant</option>
                <option value="sesame">Cold-Pressed Sesame Oil dominant</option>
              </select>
            </div>
          </div>
        </div>
      </section>

      {/* STEP 3: ICMR-NIN 2024 Daily & Monthly Targets */}
      <section className="container fp-section-container">
        <div className="fp-card fp-nutrition-card">
          <div className="fp-card-header">
            <div>
              <span className="step-tag">Step 03</span>
              <h2>ICMR-NIN 2024 Household Targets</h2>
              <p>Daily family intake benchmarks converted into a 30-day vacuum-packed organic grocery basket.</p>
            </div>
          </div>

          <div className="nutrition-cards-grid six-col">
            <div className="nutrition-stat-card grains">
              <span className="nutri-icon">🌾</span>
              <span className="nutri-label">Whole Grains &amp; Atta</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.cereals} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.cereals} kg / month</span>
            </div>

            <div className="nutrition-stat-card pulses">
              <span className="nutri-icon">🥣</span>
              <span className="nutri-label">Pulses &amp; Legumes</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.pulses} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.pulses} kg / month</span>
            </div>

            <div className="nutrition-stat-card oils">
              <span className="nutri-icon">🫒</span>
              <span className="nutri-label">Cold-Pressed Oils &amp; Ghee</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.oil} <small>ml/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.oil} L / month</span>
            </div>

            <div className="nutrition-stat-card nuts">
              <span className="nutri-icon">🥜</span>
              <span className="nutri-label">Seeds &amp; Pure Nuts</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.nuts} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.nuts} kg / month</span>
            </div>

            <div className="nutrition-stat-card sugar">
              <span className="nutri-icon">🍯</span>
              <span className="nutri-label">Natural Sweeteners</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.sugar} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.sugar} kg / month</span>
            </div>

            <div className="nutrition-stat-card salt">
              <span className="nutri-icon">🧂</span>
              <span className="nutri-label">Rock &amp; Iodized Salt</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.salt} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.salt} kg / month</span>
            </div>
          </div>
        </div>
      </section>

      {/* STEP 4: Recommended Monthly Ration Basket */}
      <section className="container fp-section-container fp-basket-section">
        <div className="fp-card fp-basket-card">
          <div className="fp-card-header basket-header">
            <div>
              <span className="step-tag">Step 04</span>
              <h2>Your Customized Monthly Basket</h2>
              <p>Adjust pack quantities or sizes. Items are packed fresh in eco-friendly vacuum packaging and delivered to your doorstep.</p>
            </div>

            <div className="basket-total-badge">
              <span>Estimated 30-Day Total</span>
              <strong>{formatPrice(packTotal)}</strong>
            </div>
          </div>

          <div className="basket-items-list">
            {recommendations.map((item, index) => {
              const itemTotal = item.price * item.qty;

              return (
                <div className="basket-item-row" key={item.key}>
                  <span className="item-num">{String(index + 1).padStart(2, '0')}</span>

                  <div className="item-thumb-box">
                    <img src={item.image} alt={item.name} />
                  </div>

                  <div className="item-details">
                    <span className="item-category-tag">{item.label}</span>
                    <h3 className="item-title">{item.name}</h3>
                    <p className="item-benefit">{item.benefit}</p>
                    <div className="item-pack-size">
                      <span>Package:</span>
                      <strong>{item.qty} × {item.package_size}</strong>
                    </div>
                  </div>

                  <div className="item-unit-price">
                    <span className="price-label">Unit Price</span>
                    <span className="price-amount">{formatPrice(item.price)}</span>
                  </div>

                  <div className="item-qty-stepper">
                    <button type="button" onClick={() => updateRecommendationQty(item.key, -1)} aria-label="Decrease quantity">-</button>
                    <span className="qty-val">{item.qty}</span>
                    <button type="button" onClick={() => updateRecommendationQty(item.key, 1)} aria-label="Increase quantity">+</button>
                  </div>

                  <div className="item-subtotal">
                    <span className="price-label">Subtotal</span>
                    <span className="subtotal-amount">{formatPrice(itemTotal)}</span>
                  </div>

                  <button
                    type="button"
                    className="btn-delete-item"
                    onClick={() => removeRecommendation(item.key)}
                    title="Remove from pack"
                  >
                    ✕
                  </button>
                </div>
              );
            })}
          </div>

          {/* Action Footer */}
          <div className="basket-action-footer">
            <div className="footer-left">
              <span className="item-count-badge">{recommendations.length} Staples Selected</span>
            </div>

            <div className="footer-right">
              <button type="button" className="btn btn-outline-fp" onClick={savePack}>
                💾 Save Configuration
              </button>
              <button
                type="button"
                className="btn btn-primary-fp"
                onClick={addPackToCart}
                disabled={addingPack || !recommendations.length}
              >
                {addingPack ? 'Adding Pack...' : `🛒 Add Entire Pack to Cart (${formatPrice(packTotal)})`}
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* STEP 5: Add More Organic Essentials */}
      <section className="container fp-section-container fp-extras-section">
        <div className="fp-card fp-extras-card">
          <div className="fp-card-header extras-header">
            <div>
              <span className="step-tag">Optional</span>
              <h2>Add More Organic Essentials</h2>
              <p>Search our complete organic pantry and include extra items into your family basket.</p>
            </div>

            <div className="extras-search-wrap">
              <input
                type="search"
                placeholder="Search rice, spices, millets, honey..."
                value={extraSearch}
                onChange={(e) => setExtraSearch(e.target.value)}
              />
            </div>
          </div>

          <div className="extras-products-grid">
            {filteredExtras.map((product) => (
              <div className="extra-item-card" key={product.id}>
                <div className="extra-img-box">
                  <img src={product.image || packgringImg} alt={product.name} />
                  <span className="extra-cat-tag">{product.category || 'Organic'}</span>
                </div>

                <div className="extra-info-box">
                  <h4 className="extra-name" title={product.name}>{product.name}</h4>
                  <div className="extra-price-row">
                    <span className="extra-price">{formatPrice(product.price)}</span>
                    <button
                      type="button"
                      className="btn-add-extra"
                      onClick={() => addExtraProduct(product)}
                    >
                      + Add to Pack
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};

export default FamilyPackPage;
