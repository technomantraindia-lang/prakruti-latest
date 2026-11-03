import React, { useEffect, useMemo, useState } from 'react';
import './FamilyPackPage.css';
import { productsApi } from '../../api/products';
import { familyPacksApi } from '../../api/familyPacks';
import { useCart } from '../../context/CartContext';
import { FiHeart, FiUser, FiUsers, FiActivity, FiAlertTriangle, FiCheck, FiInfo } from 'react-icons/fi';
import familyPackImg from '../../assets/images/image copy 2.png';
import packgringImg from '../../assets/images/new packing .png';

/**
 * ICMR-NIN 2024 Reference Baseline Table (g/day and daily macro-nutrients per profile)
 */
const NIN_REFERENCE_TABLE = {
  C13:    { label: 'Toddler 1–3',             sex: 'any',    minAge: 1,  maxAge: 3,   cereals: 120, pulses: 40,  nuts: 10, oil: 15, sugar: 15, salt: 3, energy: 1010, protein: 11.3, carbs: 130, fat: 27, fiber: 15 },
  C46:    { label: 'Child 4–6',               sex: 'any',    minAge: 4,  maxAge: 6,   cereals: 160, pulses: 60,  nuts: 15, oil: 20, sugar: 20, salt: 5, energy: 1360, protein: 16.0, carbs: 175, fat: 30, fiber: 18 },
  C79:    { label: 'Child 7–9',               sex: 'any',    minAge: 7,  maxAge: 9,   cereals: 200, pulses: 65,  nuts: 20, oil: 25, sugar: 20, salt: 5, energy: 1700, protein: 23.0, carbs: 220, fat: 35, fiber: 22 },
  M1012:  { label: 'Boy 10–12',               sex: 'male',   minAge: 10, maxAge: 12,  cereals: 280, pulses: 90,  nuts: 30, oil: 35, sugar: 20, salt: 5, energy: 2220, protein: 32.0, carbs: 290, fat: 40, fiber: 28 },
  F1012:  { label: 'Girl 10–12',              sex: 'female', minAge: 10, maxAge: 12,  cereals: 250, pulses: 85,  nuts: 30, oil: 30, sugar: 20, salt: 5, energy: 2060, protein: 33.0, carbs: 270, fat: 38, fiber: 28 },
  M1315:  { label: 'Boy 13–15',               sex: 'male',   minAge: 13, maxAge: 15,  cereals: 390, pulses: 130, nuts: 40, oil: 45, sugar: 20, salt: 5, energy: 2860, protein: 45.0, carbs: 370, fat: 45, fiber: 35 },
  F1315:  { label: 'Girl 13–15',              sex: 'female', minAge: 13, maxAge: 15,  cereals: 300, pulses: 100, nuts: 35, oil: 40, sugar: 20, salt: 5, energy: 2400, protein: 43.0, carbs: 310, fat: 40, fiber: 30 },
  M1618:  { label: 'Boy 16–18',               sex: 'male',   minAge: 16, maxAge: 18,  cereals: 450, pulses: 150, nuts: 50, oil: 55, sugar: 20, salt: 5, energy: 3320, protein: 55.0, carbs: 430, fat: 50, fiber: 40 },
  F1618:  { label: 'Girl 16–18',              sex: 'female', minAge: 16, maxAge: 18,  cereals: 315, pulses: 105, nuts: 40, oil: 40, sugar: 20, salt: 5, energy: 2500, protein: 46.0, carbs: 320, fat: 40, fiber: 32 },
  M1949S: { label: 'Adult 19–49 Male (Sedentary)', sex: 'male',   minAge: 19, maxAge: 49, cereals: 230, pulses: 75,  nuts: 30, oil: 30, sugar: 20, salt: 5, energy: 2110, protein: 54.0, carbs: 275, fat: 30, fiber: 30 },
  M1949M: { label: 'Adult 19–49 Male (Moderate)',  sex: 'male',   minAge: 19, maxAge: 49, cereals: 320, pulses: 105, nuts: 40, oil: 35, sugar: 20, salt: 5, energy: 2710, protein: 65.0, carbs: 350, fat: 35, fiber: 40 },
  M1949H: { label: 'Adult 19–49 Male (High Physical / Manual)', sex: 'male', minAge: 19, maxAge: 49, cereals: 400, pulses: 130, nuts: 45, oil: 45, sugar: 20, salt: 5, energy: 3470, protein: 78.0, carbs: 450, fat: 45, fiber: 50 },
  F1949S: { label: 'Adult 19–49 Female (Sedentary)',sex: 'female', minAge: 19, maxAge: 49, cereals: 180, pulses: 60,  nuts: 25, oil: 25, sugar: 20, salt: 5, energy: 1660, protein: 46.0, carbs: 215, fat: 25, fiber: 25 },
  F1949M: { label: 'Adult 19–49 Female (Moderate)', sex: 'female', minAge: 19, maxAge: 49, cereals: 250, pulses: 85,  nuts: 30, oil: 30, sugar: 20, salt: 5, energy: 2130, protein: 55.0, carbs: 275, fat: 30, fiber: 30 },
  F1949H: { label: 'Adult 19–49 Female (High Physical / Manual)', sex: 'female', minAge: 19, maxAge: 49, cereals: 320, pulses: 105, nuts: 35, oil: 35, sugar: 20, salt: 5, energy: 2720, protein: 65.0, carbs: 350, fat: 38, fiber: 38 },
  M5060:  { label: 'Adult 50–59 Male',        sex: 'male',   minAge: 50, maxAge: 59,  cereals: 200, pulses: 75,  nuts: 30, oil: 25, sugar: 20, salt: 5, energy: 2000, protein: 54.0, carbs: 260, fat: 28, fiber: 30 },
  F5060:  { label: 'Adult 50–59 Female',      sex: 'female', minAge: 50, maxAge: 59,  cereals: 165, pulses: 70,  nuts: 25, oil: 20, sugar: 20, salt: 5, energy: 1600, protein: 46.0, carbs: 210, fat: 25, fiber: 25 },
  M60:    { label: 'Older adult 60+ Male',    sex: 'male',   minAge: 60, maxAge: 120, cereals: 170, pulses: 75,  nuts: 30, oil: 25, sugar: 20, salt: 5, energy: 1750, protein: 54.0, carbs: 230, fat: 25, fiber: 28 },
  F60:    { label: 'Older adult 60+ Female',  sex: 'female', minAge: 60, maxAge: 120, cereals: 140, pulses: 70,  nuts: 30, oil: 15, sugar: 20, salt: 5, energy: 1450, protein: 46.0, carbs: 190, fat: 22, fiber: 25 },
  PREG:   { label: 'Pregnant Female',         sex: 'female', minAge: 19, maxAge: 49,  cereals: 220, pulses: 85,  nuts: 40, oil: 30, sugar: 20, salt: 5, energy: 2010, protein: 61.0, carbs: 245, fat: 35, fiber: 28 },
  LACT:   { label: 'Lactating Female',        sex: 'female', minAge: 19, maxAge: 49,  cereals: 260, pulses: 95,  nuts: 40, oil: 35, sugar: 20, salt: 5, energy: 2180, protein: 65.0, carbs: 255, fat: 37, fiber: 30 },
};

/**
/**
 * Normalize and guarantee unique ID and full profile schema for each member
 */
function normalizeMember(member, index = 1) {
  const memberId = member?.id != null && String(member.id).trim() !== ''
    ? String(member.id)
    : `mem_${index}_${Date.now()}_${Math.random().toString(36).substring(2, 7)}`;

  return {
    id: memberId,
    name: member?.name || `Member ${index}`,
    age: Math.max(1, Number(member?.age || 25)),
    sex: member?.sex === 'female' ? 'female' : 'male',
    heightFt: member?.heightFt != null ? String(member.heightFt) : '5',
    heightIn: member?.heightIn != null ? String(member.heightIn) : '4',
    weightKg: member?.weightKg != null ? String(member.weightKg) : '60',
    activity: member?.activity || 'moderate',
    lifeStage: member?.lifeStage || 'none',
    diabetes: Boolean(member?.diabetes),
    bp: Boolean(member?.bp),
    heart: Boolean(member?.heart),
    kidney: Boolean(member?.kidney),
    goal: member?.goal || 'general',
  };
}

/**
 * First-time visitors start with one intentionally blank member profile.
 * Logged-in users with a saved Family Pack are hydrated from the API below.
 */
const initialMembers = [
  {
    id: 'mem_initial',
    name: '',
    age: '',
    sex: '',
    heightFt: '',
    heightIn: '',
    weightKg: '',
    activity: '',
    lifeStage: 'none',
    diabetes: false,
    bp: false,
    heart: false,
    kidney: false,
    goal: '',
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

  if (type === 'toddler') {
    age = 2;
    sex = 'female';
    activity = 'moderate';
    goal = 'active_child';
    heightFt = '2';
    heightIn = '8';
    weightKg = '12';
  } else if (type === 'child') {
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
    id: `mem_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`,
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

  if (ft < 1 || ft > 8 || inc < 0 || inc > 11 || weight < 1 || weight > 180 || heightM < 0.5) return null;
  return Number((weight / (heightM * heightM)).toFixed(1));
}

function bmiCategory(member) {
  const age = Number(member?.age || 0);
  const bmi = calculateBmi(member);
  if (age < 19) return 'Pediatric Reference';
  if (!bmi) return 'Enter measurements (Optional)';
  if (bmi < 18.5) return 'Below Range (< 18.5)';
  if (bmi < 25) return 'Healthy Range (18.5–24.9)';
  if (bmi < 30) return 'Above Range (25.0–29.9)';
  return 'High Range (≥ 30.0)';
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

  if (age <= 3) return 'C13';
  if (age < 7) return 'C46';
  if (age < 10) return 'C79';
  if (age <= 12) return sex === 'female' ? 'F1012' : 'M1012';
  if (age <= 15) return sex === 'female' ? 'F1315' : 'M1315';
  if (age <= 18) return sex === 'female' ? 'F1618' : 'M1618';

  if (age <= 49) {
    if (activity === 'high') {
      return sex === 'female' ? 'F1949H' : 'M1949H';
    }
    const isSed = activity === 'sedentary';
    if (sex === 'female') return isSed ? 'F1949S' : 'F1949M';
    return isSed ? 'M1949S' : 'M1949M';
  }

  if (age <= 59) {
    return sex === 'female' ? 'F5060' : 'M5060';
  }

  return sex === 'female' ? 'F60' : 'M60';
}

function getMemberStageAndAge(member) {
  const age = Number(member?.age || 0);
  let stage = 'Adult';
  if (age > 0 && age < 4) stage = 'Toddler';
  else if (age >= 4 && age < 10) stage = 'Child';
  else if (age >= 10 && age < 19) stage = 'Teen';
  else if (age >= 60) stage = 'Senior';
  else stage = 'Adult';

  return age > 0 ? `${stage} · ${age} Yrs` : stage;
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
  let energyMult = 1.0;

  const isAdult1949 = age >= 19 && age <= 49;

  // Activity Adjustments
  // For adults 19-49, ICMR-NIN 2024 has dedicated S/M/H profiles (e.g. M1949S, M1949M, M1949H).
  // Do NOT double-multiply baseline for sedentary, moderate, or high adults 19-49!
  if (activity === 'athlete') {
    cerealMult *= 1.10;
    proteinMult *= 1.15;
    energyMult *= 1.10;
  } else if (!isAdult1949) {
    if (activity === 'high') {
      cerealMult *= 1.10;
      proteinMult *= 1.10;
      energyMult *= 1.10;
    } else if (activity === 'sedentary') {
      cerealMult *= 0.95;
      energyMult *= 0.95;
    }
  }

  if (age >= 10 && age <= 18) {
    proteinMult *= 1.10;
  }

  if (age >= 19 && bmi !== null && bmi >= 25.0 && (goal === 'weight_loss' || goal === 'weight_management')) {
    cerealMult *= 0.90;
    energyMult *= 0.92;
  }

  const dailyNutrients = {
    energy: Math.round((base.energy || 2000) * energyMult),
    protein: Number(((base.protein || 50) * proteinMult).toFixed(1)),
    carbs: Math.round((base.carbs || 250) * cerealMult),
    fat: Math.round(base.fat || 30),
    fiber: Math.round((base.fiber || 30) * cerealMult),
  };

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
    dailyNutrients,
  };
}

function calculateHouseholdBasket(members, household) {
  if (!members || !members.length) {
    return {
      assessedMembers: [],
      dailyGrams: { cereals: 0, pulses: 0, nuts: 0, oil: 0, sugar: 0, salt: 0 },
      monthlyKg: { cereals: 0, pulses: 0, nuts: 0, oil: 0, sugar: 0, salt: 0 },
      dailyNutrients: { energy: 0, protein: 0, carbs: 0, fat: 0, fiber: 0 },
      monthlyNutrients: { energy: 0, protein: 0, carbs: 0, fat: 0, fiber: 0 },
      interpretation: 'Add members to generate smart household nutrition plan.',
      safetyAlerts: [],
    };
  }

  const hasAge = members.some((m) => Number(m.age) >= 1 && Number(m.age) <= 100);
  if (!hasAge) {
    return {
      assessedMembers: [],
      dailyGrams: { cereals: 0, pulses: 0, nuts: 0, oil: 0, sugar: 0, salt: 0 },
      monthlyKg: { cereals: 0, pulses: 0, nuts: 0, oil: 0, sugar: 0, salt: 0 },
      dailyNutrients: { energy: 0, protein: 0, carbs: 0, fat: 0, fiber: 0 },
      monthlyNutrients: { energy: 0, protein: 0, carbs: 0, fat: 0, fiber: 0 },
      interpretation: 'Enter member age (1–100 years) to generate a personalized household nutrition plan.',
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

  let totEnergy = 0;
  let totProtein = 0;
  let totCarbs = 0;
  let totFat = 0;
  let totFiber = 0;

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

    totEnergy  += m.dailyNutrients.energy;
    totProtein += m.dailyNutrients.protein;
    totCarbs   += m.dailyNutrients.carbs;
    totFat     += m.dailyNutrients.fat;
    totFiber   += m.dailyNutrients.fiber;

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
      title: 'Salt-Mindful Planning (Sodium Notice)',
      message: 'Blood pressure flag detected: salt purchase recommendation reduced by 20% to support mindful intake (< 5g/day per adult). Note: Sendha Namak (Pink Rock Salt) is naturally unrefined but consists of ~98% sodium chloride and must still be used in moderation.',
    });
  }
  if (hasHeart) {
    safetyAlerts.push({
      type: 'info',
      title: 'Cardiovascular Lipid Balance Guidance',
      message: 'Cardiovascular / lipid flag detected: cold-pressed vegetable oils (Groundnut, Sesame, Sunflower, Olive) prioritized (85–90%); saturated fats (A2 Vedic Cow Ghee) moderated to 10–15% of added cooking fat to limit saturated fatty acids.',
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
    dailyNutrients: {
      energy: Math.round(totEnergy),
      protein: Math.round(totProtein),
      carbs: Math.round(totCarbs),
      fat: Math.round(totFat),
      fiber: Math.round(totFiber),
    },
    monthlyNutrients: {
      energy: Math.round(totEnergy * 30),
      protein: Math.round(totProtein * 30),
      carbs: Math.round(totCarbs * 30),
      fat: Math.round(totFat * 30),
      fiber: Math.round(totFiber * 30),
    },
    interpretation,
    safetyAlerts,
  };
}

/**
 * Authoritative Nutritional Catalog per 100g (Packaging Lab Verifications & IFCT 2024 Reference Standards)
 */
const VERIFIED_NUTRITION_CATALOG = {
  // Packaging Verified Lab Data (from Prakruti Certified Packaging)
  'toor dal': { energy: 345, protein: 22.0, carbs: 63.0, fat: 1.0, fiber: 15.0, sodium: 17, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'tuar dal': { energy: 345, protein: 22.0, carbs: 63.0, fat: 1.0, fiber: 15.0, sodium: 17, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'arhar': { energy: 345, protein: 22.0, carbs: 63.0, fat: 1.0, fiber: 15.0, sodium: 17, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'wheat flour': { energy: 346, protein: 12.0, carbs: 71.0, fat: 1.5, fiber: 12.0, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'green gram': { energy: 375, protein: 14.0, carbs: 64.0, fat: 7.1, fiber: 7.1, sodium: 14, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'moong': { energy: 375, protein: 14.0, carbs: 64.0, fat: 7.1, fiber: 7.1, sodium: 14, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'chana dal': { energy: 340, protein: 11.0, carbs: 73.0, fat: 0.4, fiber: 8.0, sodium: 5, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'split bengal': { energy: 340, protein: 11.0, carbs: 73.0, fat: 0.4, fiber: 8.0, sodium: 5, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'desi chana': { energy: 340, protein: 11.0, carbs: 73.0, fat: 0.4, fiber: 8.0, sodium: 5, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'brown chick peas': { energy: 340, protein: 11.0, carbs: 73.0, fat: 0.4, fiber: 8.0, sodium: 5, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'white chickpeas': { energy: 347, protein: 19.0, carbs: 60.0, fat: 6.0, fiber: 17.0, sodium: 14, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'kabuli': { energy: 347, protein: 19.0, carbs: 60.0, fat: 6.0, fiber: 17.0, sodium: 14, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'sona masoori': { energy: 345, protein: 7.0, carbs: 78.0, fat: 1.0, fiber: 0.2, sodium: 17, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'basmati rice brown': { energy: 367, protein: 8.0, carbs: 77.0, fat: 3.0, fiber: 3.5, sodium: 7, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'basmati rice white': { energy: 345, protein: 7.0, carbs: 78.0, fat: 1.0, fiber: 0.2, sodium: 17, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'rajma': { energy: 346, protein: 23.0, carbs: 60.0, fat: 1.3, fiber: 4.8, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'masoor': { energy: 350, protein: 26.0, carbs: 60.0, fat: 1.0, fiber: 20.0, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'pink lentil': { energy: 350, protein: 26.0, carbs: 60.0, fat: 1.0, fiber: 20.0, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'panchratna': { energy: 344, protein: 23.0, carbs: 58.0, fat: 2.0, fiber: 2.0, sodium: 40, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'mix dal': { energy: 344, protein: 23.0, carbs: 58.0, fat: 2.0, fiber: 2.0, sodium: 40, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'urad dal': { energy: 347, protein: 24.0, carbs: 60.0, fat: 1.4, fiber: 0.9, sodium: 40, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'jowar whole': { energy: 329, protein: 11.0, carbs: 72.0, fat: 3.5, fiber: 6.7, sodium: 2, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'jowar atta': { energy: 371, protein: 11.0, carbs: 75.0, fat: 3.0, fiber: 6.0, sodium: 6, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'jowar': { energy: 371, protein: 11.0, carbs: 75.0, fat: 3.0, fiber: 6.0, sodium: 6, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'jawar': { energy: 371, protein: 11.0, carbs: 75.0, fat: 3.0, fiber: 6.0, sodium: 6, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'bajra whole': { energy: 320, protein: 7.0, carbs: 67.0, fat: 2.0, fiber: 11.0, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'bajra atta': { energy: 339, protein: 13.7, carbs: 72.6, fat: 1.9, fiber: 12.2, sodium: 5, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'bajra': { energy: 339, protein: 13.7, carbs: 72.6, fat: 1.9, fiber: 12.2, sodium: 5, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'ragi whole': { energy: 386, protein: 11.0, carbs: 75.0, fat: 4.3, fiber: 3.6, sodium: 3.9, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'ragi atta': { energy: 340, protein: 11.0, carbs: 73.0, fat: 0.4, fiber: 8.0, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'ragi': { energy: 340, protein: 11.0, carbs: 73.0, fat: 0.4, fiber: 8.0, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'finger': { energy: 340, protein: 11.0, carbs: 73.0, fat: 0.4, fiber: 8.0, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'kodo': { energy: 149, protein: 5.0, carbs: 33.0, fat: 1.6, fiber: 3.1, sodium: 0, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'little millet': { energy: 378, protein: 13.3, carbs: 71.0, fat: 4.4, fiber: 8.9, sodium: 222, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'besan': { energy: 374, protein: 22.4, carbs: 58.0, fat: 6.0, fiber: 12.2, sodium: 7, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'turmeric': { energy: 354, protein: 8.0, carbs: 65.0, fat: 10.0, fiber: 21.0, sodium: 38, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'haldi': { energy: 354, protein: 8.0, carbs: 65.0, fat: 10.0, fiber: 21.0, sodium: 38, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'coriander powder': { energy: 340, protein: 20.0, carbs: 20.0, fat: 20.0, fiber: 40.0, sodium: 40, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'cumin': { energy: 420, protein: 20.0, carbs: 40.0, fat: 20.0, fiber: 20.0, sodium: 160, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'jeera': { energy: 420, protein: 20.0, carbs: 40.0, fat: 20.0, fiber: 20.0, sodium: 160, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },
  'red chilli': { energy: 316.6, protein: 12.2, carbs: 55.0, fat: 17.2, fiber: 27.2, sodium: 30, source: 'packaging_verified', label: 'Prakruti Certified Packaging Fact' },

  // IFCT 2024 Reference Estimates
  'chia': { energy: 486, protein: 16.5, carbs: 42.1, fat: 30.7, fiber: 34.4, sodium: 16, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'flax': { energy: 534, protein: 18.3, carbs: 28.9, fat: 42.2, fiber: 27.3, sodium: 30, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'pumpkin': { energy: 559, protein: 30.2, carbs: 10.7, fat: 49.1, fiber: 6.0, sodium: 7, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'sunflower seed': { energy: 584, protein: 20.8, carbs: 20.0, fat: 51.5, fiber: 8.6, sodium: 9, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'multi grain': { energy: 350, protein: 13.0, carbs: 70.0, fat: 2.5, fiber: 10.0, sodium: 10, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'quinoa': { energy: 368, protein: 14.1, carbs: 64.2, fat: 6.1, fiber: 7.0, sodium: 5, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'ghee': { energy: 900, protein: 0.0, carbs: 0.0, fat: 100.0, fiber: 0.0, sodium: 0, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'groundnut oil': { energy: 884, protein: 0.0, carbs: 0.0, fat: 100.0, fiber: 0.0, sodium: 0, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'sesame oil': { energy: 884, protein: 0.0, carbs: 0.0, fat: 100.0, fiber: 0.0, sodium: 0, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'sunflower oil': { energy: 884, protein: 0.0, carbs: 0.0, fat: 100.0, fiber: 0.0, sodium: 0, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'olive oil': { energy: 884, protein: 0.0, carbs: 0.0, fat: 100.0, fiber: 0.0, sodium: 0, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'jaggery': { energy: 383, protein: 0.4, carbs: 85.0, fat: 0.1, fiber: 0.0, sodium: 20, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'sugar': { energy: 398, protein: 0.0, carbs: 99.8, fat: 0.0, fiber: 0.0, sodium: 1, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'khandasari': { energy: 387, protein: 0.1, carbs: 99.5, fat: 0.0, fiber: 0.0, sodium: 2, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'salt': { energy: 0, protein: 0.0, carbs: 0.0, fat: 0.0, fiber: 0.0, sodium: 38000, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
  'sendha namak': { energy: 0, protein: 0.0, carbs: 0.0, fat: 0.0, fiber: 0.0, sodium: 38000, source: 'ifct_reference_estimate', label: 'ICMR-NIN / IFCT 2024 Reference Standard' },
};

function getProductNutrition(productOrName) {
  // 1. If product object with DB nutritional_info
  if (productOrName && typeof productOrName === 'object') {
    const ni = productOrName.nutritional_info;
    if (ni?.per_100g) {
      const per = ni.per_100g;
      const isPkg = ni.source === 'packaging_verified';
      return {
        nutritionAvailable: true,
        source: ni.source,
        verified_label: ni.verified_label || (isPkg ? 'Prakruti Certified Packaging Fact' : 'ICMR-NIN / IFCT 2024 Reference Standard'),
        calories: Number(per.energy_kcal ?? 0),
        protein: Number(per.protein_g ?? 0),
        carbs: Number(per.carbs_g ?? 0),
        fat: Number(per.fat_g ?? 0),
        fiber: Number(per.fiber_g ?? 0),
        sodium: Number(per.sodium_mg ?? 0),
        badge: isPkg
          ? `🔬 Verified Fact: ${per.protein_g}g Protein, ${per.fiber_g}g Fiber`
          : `📚 IFCT 2024: ${per.protein_g}g Protein, ${per.fiber_g}g Fiber`,
      };
    }
  }

  // 2. Lookup by name against verified catalog
  const nameStr = (typeof productOrName === 'string' ? productOrName : productOrName?.name || '').toLowerCase();
  for (const [key, val] of Object.entries(VERIFIED_NUTRITION_CATALOG)) {
    if (nameStr.includes(key)) {
      const isPkg = val.source === 'packaging_verified';
      return {
        nutritionAvailable: true,
        source: val.source,
        verified_label: val.label,
        calories: val.energy,
        protein: val.protein,
        carbs: val.carbs,
        fat: val.fat,
        fiber: val.fiber,
        sodium: val.sodium,
        badge: isPkg
          ? `🔬 Verified Fact: ${val.protein}g Protein, ${val.fiber}g Fiber`
          : `📚 IFCT 2024: ${val.protein}g Protein, ${val.fiber}g Fiber`,
      };
    }
  }

  // Explicit unverified handling - DO NOT FABRICATE VALUES!
  return {
    nutritionAvailable: false,
    source: 'unverified',
    verified_label: 'Pending Nutritional Lab Verification',
    calories: 0,
    protein: 0,
    carbs: 0,
    fat: 0,
    fiber: 0,
    sodium: 0,
    badge: '⚠️ Nutrition Pending Verification',
    missing: true,
  };
}

/**
 * Future-proof dynamic product classifier based on DB nutritional_info, category, and attributes
 */
function classifyProductFoodGroup(product) {
  // 1. Direct classification from DB nutritional_info if already stored
  const dbGroup = product?.nutritional_info?.food_group;
  const dbSub = product?.nutritional_info?.food_subgroup;
  if (dbGroup) {
    return {
      food_group: dbGroup,
      food_subgroup: dbSub || 'general',
      group_label: product?.nutritional_info?.group_label || 'Prakruti Food Staple',
    };
  }

  // 2. Classify based on category name, category slug, product_information, and name
  const name = String(product?.name || '').toLowerCase();
  const cat = String(product?.category || product?.category_slug || '').toLowerCase();
  const ingredient = String(product?.product_information?.ingredient || '').toLowerCase();
  const text = `${name} ${cat} ${ingredient}`;

  // Ghee
  if (/(?:ghee|bilona|a2 gir cow ghee)/i.test(text)) {
    return { food_group: 'ghee', food_subgroup: 'a2_cow_ghee', group_label: 'Traditional Vedic Ghee' };
  }

  // Cooking Oils
  if (/(?:oil|tel|kachi ghani)/i.test(text)) {
    let sub = 'vegetable_oils';
    if (text.includes('groundnut') || text.includes('peanut')) sub = 'groundnut_oil';
    else if (text.includes('sesame') || text.includes('til')) sub = 'sesame_oil';
    else if (text.includes('mustard') || text.includes('sarson')) sub = 'mustard_oil';
    else if (text.includes('sunflower')) sub = 'sunflower_oil';
    else if (text.includes('olive')) sub = 'olive_oil';
    else if (text.includes('coconut')) sub = 'coconut_oil';
    return { food_group: 'cooking_oils', food_subgroup: sub, group_label: 'Cold-Pressed Cooking Oils' };
  }

  // Seeds & Nuts
  if (cat.includes('seed') || /(?:chia|flax|pumpkin|sunflower seed|sesame seed|watermelon seed|muskmelon seed|hemp seed|almond|walnut|cashew|pista|seeds)/i.test(text)) {
    const isNut = /(?:almond|walnut|cashew|pista|tree nut)/i.test(text);
    return { food_group: 'nuts_seeds', food_subgroup: isNut ? 'tree_nuts' : 'seeds', group_label: 'Healthy Seeds & Nuts' };
  }

  // Pulses & Legumes
  if (cat.includes('pulse') || /(?:dal|tuar|toor|arhar|pigeon pea|moong|green gram|chana|bengal gram|chickpea|kabuli|masoor|pink lentil|rajma|kidney bean|urad|black gram|horsegram|kulthi|lobia|cowpea|moth|matki|panchratna|mix dal|besan|legume)/i.test(text)) {
    let sub = 'other_pulses';
    if (/(?:tuar|toor|arhar|pigeon pea)/i.test(text)) sub = 'toor_arhar';
    else if (/(?:moong|green gram)/i.test(text)) sub = 'moong';
    else if (/(?:chana|bengal gram|chickpea|kabuli)/i.test(text)) sub = 'chana_bengal';
    else if (/(?:masoor|pink lentil|panchratna|mix dal)/i.test(text)) sub = 'lentils_masoor';
    else if (/(?:rajma|kidney bean)/i.test(text)) sub = 'beans_rajma';
    return { food_group: 'pulses_legumes', food_subgroup: sub, group_label: 'Pulses & Legumes' };
  }

  // Millets
  if (cat.includes('millet') || /(?:ragi|finger millet|jowar|jawar|sorghum|bajra|pearl millet|kodo|foxtail|barnyard|little millet|proso)/i.test(text)) {
    return { food_group: 'grains_cereals', food_subgroup: 'millets', group_label: 'Ancient Millets' };
  }

  // Rice
  if (/(?:rice|basmati|sona masoori|poha|chawal)/i.test(text)) {
    return { food_group: 'grains_cereals', food_subgroup: 'rice', group_label: 'Traditional Rice' };
  }

  // Flours & Wheats
  if (cat.includes('flour') || /(?:flour|atta|wheat|khapli|emmer|spelt|multigrain|suji|rava|dalia)/i.test(text)) {
    return { food_group: 'grains_cereals', food_subgroup: 'wheat_flours', group_label: 'Whole Flours & Wheats' };
  }

  // Natural Sweeteners
  if (cat.includes('sweetener') || /(?:jaggery|gud|sugar|khandasari|sweetener|honey)/i.test(text)) {
    return { food_group: 'sweeteners', food_subgroup: text.includes('jaggery') ? 'jaggery' : 'raw_sugar', group_label: 'Natural Sweeteners' };
  }

  // Spices
  if (cat.includes('spice') || /(?:turmeric|haldi|curcumin|cumin|jeera|coriander|dhaniya|chilli|mirch|pepper|cardamom|cinnamon|clove|masala|spice)/i.test(text)) {
    return { food_group: 'spices', food_subgroup: text.includes('turmeric') ? 'turmeric' : 'spice_powders', group_label: 'Pure Spices' };
  }

  // Salt
  if (/(?:salt|namak|sendha|rock salt|himalayan)/i.test(text)) {
    return { food_group: 'salt', food_subgroup: 'rock_salt', group_label: 'Mineral Salt' };
  }

  return { food_group: 'other', food_subgroup: 'general', group_label: 'Other Food Products' };
}

/**
 * Dynamically discover all in-stock products for any food group
 */
function getEligibleInStockProducts(products, foodGroup, subGroup = null) {
  const seenIds = new Set();
  const eligible = [];

  for (const p of products) {
    if (!p || seenIds.has(p.id)) continue;
    if (p.status && p.status !== 'active') continue;

    const groupInfo = classifyProductFoodGroup(p);
    if (groupInfo.food_group !== foodGroup) continue;
    if (subGroup && groupInfo.food_subgroup !== subGroup) continue;

    const pkg = resolvePackage(p);
    if (pkg && pkg.in_stock) {
      seenIds.add(p.id);
      eligible.push({ product: p, pkg, groupInfo });
    }
  }

  return eligible;
}

function parsePackageWeightGrams(pkg) {
  const text = String(pkg?.package_size || '').toLowerCase();
  const weightNum = Number(pkg?.pack_weight);
  if (weightNum > 0) {
    if (weightNum <= 50) return weightNum * 1000;
    return weightNum;
  }
  const kgMatch = text.match(/([0-9.]+)\s*(kg|kilo|l|ltr|liter)/);
  if (kgMatch) return Number(kgMatch[1]) * 1000;
  const gMatch = text.match(/([0-9.]+)\s*(g|gm|gram|ml)/);
  if (gMatch) return Number(gMatch[1]);
  return 1000;
}

/**
 * Single source of truth for effective unit price calculation.
 * Ensures strict agreement with backend CartApiController down to the rupee.
 */
export function getEffectiveProductPrice(product, variation = null) {
  if (variation) {
    const varSale = Number(variation.sale_price ?? 0);
    const varReg = Number(variation.price ?? 0);
    if (varSale > 0 && (varReg <= 0 || varSale < varReg)) {
      return varSale;
    }
    if (varReg > 0) {
      return varReg;
    }
  }

  const prodSale = Number(product?.sale_price ?? 0);
  const prodReg = Number(product?.price ?? 0);
  if (prodSale > 0 && (prodReg <= 0 || prodSale < prodReg)) {
    return prodSale;
  }
  return prodReg > 0 ? prodReg : 0;
}

function resolvePackage(product, preferredVarId = null) {
  if (!product) return null;

  const variations = Array.isArray(product?.variations) ? product.variations : [];

  if (variations.length > 0) {
    let selected = null;
    if (preferredVarId) {
      selected = variations.find(
        (v) => String(v.id) === String(preferredVarId) && Number(v.available_stock ?? v.stock_qty ?? 0) > 0
      );
    }
    if (!selected) {
      selected = variations.find((v) => Number(v.available_stock ?? v.stock_qty ?? 0) > 0);
    }

    if (!selected) {
      // All variations are out of stock!
      return null;
    }

    const label = selected.label || selected.package_size || selected.attributes?.Weight || product.weight || '1 pack';
    const price = getEffectiveProductPrice(product, selected);

    return {
      var_id: selected.id,
      package_size: String(label || '1 pack'),
      pack_weight: selected.weight ?? null,
      price,
      in_stock: true,
      available_stock: Number(selected.available_stock ?? selected.stock_qty ?? 0),
    };
  }

  // Single product (no variations)
  const stock = Number(product.available_stock ?? product.stock_qty ?? 0);
  const status = product.stock_status || (stock > 0 ? 'in_stock' : 'out_of_stock');
  if (stock <= 0 || status === 'out_of_stock') {
    return null;
  }

  const label = product.package_size || product.unit || (product.weight ? `${product.weight} kg` : '1 pack');
  const price = getEffectiveProductPrice(product);

  return {
    var_id: null,
    package_size: String(label || '1 pack'),
    pack_weight: product.weight ?? 1,
    price,
    in_stock: true,
    available_stock: stock,
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
  const [stockShortfalls, setStockShortfalls] = useState([]);
  const [treeNutsRecommendation, setTreeNutsRecommendation] = useState(null);
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
    productsApi.getAllProducts().then(async (res) => {
      if (!mounted) return;
      const loaded = res.data || [];
      setProducts(loaded);

      const latest = currentUser ? await familyPacksApi.getLatest() : { success: true, data: null };
      if (!mounted) return;

      const saved = currentUser && latest.success && latest.data ? latest.data : null;
      if (saved?.profile?.members?.length) {
        setSavedPack(saved);
        setMembers(saved.profile.members.map((m, idx) => normalizeMember(m, idx + 1)));
        if (saved.profile.household) {
          setHousehold(saved.profile.household);
        }
      }
    });

    return () => {
      mounted = false;
    };
  }, [currentUser]);

  // 3. Match calculated monthly kg with available store inventory & apply diversified allocation
  useEffect(() => {
    if (!products.length || !members.length || !basketPlan.assessedMembers.length) {
      setRecommendations([]);
      setStockShortfalls([]);
      setTreeNutsRecommendation(null);
      return;
    }

    const { cereals, pulses, nuts, oil, sugar, salt } = basketPlan.monthlyKg;
    const shortfalls = [];

    // Helper to find in-stock product by dynamic classification or keyword preference
    const findDynamicOrKeyword = (foodGroup, subGroup = null, keywords = []) => {
      const inStock = getEligibleInStockProducts(products, foodGroup, subGroup);
      if (inStock.length > 0) {
        return inStock[0];
      }
      if (keywords && keywords.length > 0) {
        for (const kw of keywords) {
          const match = products.find((p) => {
            const name = (p?.name || '').toLowerCase();
            return name.includes(kw.toLowerCase()) && (p.status !== 'inactive');
          });
          if (match) {
            const pkg = resolvePackage(match);
            if (pkg && pkg.in_stock) {
              return { product: match, pkg, groupInfo: classifyProductFoodGroup(match) };
            }
          }
        }
      }
      return null;
    };

    const items = [];

    // 1. Family Metabolic, Health & Lifestyle Vector Analysis
    const memberCount = Math.max(1, basketPlan.assessedMembers.length || members.length);
    const hasDiabetes = members.some((m) => !!m.diabetes);
    const hasHeart = members.some((m) => !!m.heart);
    const hasBp = members.some((m) => !!m.bp);
    const hasKidney = members.some((m) => !!m.kidney);

    const athletesCount = members.filter((m) => m.activity === 'athlete' || m.activity === 'high').length;
    const seniorsCount = members.filter((m) => Number(m.age) >= 60).length;
    const childrenCount = members.filter((m) => Number(m.age) <= 18).length;
    const weightLossCount = members.filter((m) => m.goal === 'weight_loss' || m.goal === 'weight_management').length;

    const totalDailyProtein = basketPlan.assessedMembers.reduce((sum, m) => sum + (m.daily_nutrients?.protein_g || 0), 0);
    const avgProteinPerCapita = totalDailyProtein / memberCount;

    // --- Cereals & Flours (~cereals kg) with Genuinely Dynamic Nutrient Adaptation ---
    // Base whole-grain nutritional fitness scores:
    let wheatFitness = 45; // Complex energy, whole unpolished atta
    let riceFitness = 30;  // Clean digestible carbohydrates
    let milletFitness = 25;// Slow GI, prebiotic fiber, calcium-rich

    // Dynamic adaptations based on family health profiles:
    if (hasDiabetes) {
      // Glycemic regulation: Surge slow-release millets (+35), reduce high-GI rice (-20)
      milletFitness += 35;
      wheatFitness += 5;
      riceFitness = Math.max(10, riceFitness - 20);
    }

    if (weightLossCount > 0) {
      // Satiety & resistant starch: prioritize millets for fullness & insulin sensitivity
      const wFactor = (weightLossCount / memberCount) * 20;
      milletFitness += wFactor;
      wheatFitness -= wFactor * 0.3;
      riceFitness -= wFactor * 0.5;
    }

    if (seniorsCount > 0) {
      // Senior bone density (Ragi calcium: 344mg/100g) & easy digestion
      milletFitness += (seniorsCount / memberCount) * 15;
      riceFitness += (seniorsCount / memberCount) * 10;
    }

    if (childrenCount > 0) {
      // Pediatric growth, mineral density & amino acid foundation
      milletFitness += (childrenCount / memberCount) * 15;
      wheatFitness += (childrenCount / memberCount) * 10;
    }

    if (athletesCount > 0) {
      // Glycogen replenishment for high physical/manual stamina
      const aFactor = (athletesCount / memberCount) * 15;
      wheatFitness += aFactor;
      riceFitness += aFactor;
    }

    // Household preference adjustment (gentle weighting)
    if (household.staplePreference === 'wheat') wheatFitness += 20;
    else if (household.staplePreference === 'rice') riceFitness += 20;
    else if (household.staplePreference === 'millets') milletFitness += 25;

    const totalCerealFitness = Math.max(1, wheatFitness + riceFitness + milletFitness);
    const wheatPct = wheatFitness / totalCerealFitness;
    const ricePct = riceFitness / totalCerealFitness;
    const milletPct = milletFitness / totalCerealFitness;

    // 1. Whole Wheat & Unpolished Atta
    const attaMatch = findDynamicOrKeyword('grains_cereals', 'wheat_flours', ['wheat flour', 'multi grain flour', 'multigrain']);
    if (attaMatch) {
      const kgNeeded = Math.max(1, cereals * wheatPct);
      const packGrams = parsePackageWeightGrams(attaMatch.pkg);
      const packWeightKg = packGrams / 1000.0;
      const qty = Math.max(1, Math.round(kgNeeded / packWeightKg) || Math.ceil(kgNeeded / 2));
      const totalGrams = qty * packGrams;
      const nutri = getProductNutrition(attaMatch.product);
      const proteinDelivered = Math.round((totalGrams / 100.0) * nutri.protein);
      const fiberDelivered = Math.round((totalGrams / 100.0) * nutri.fiber);

      items.push({
        key: 'atta-staple',
        label: attaMatch.product.name,
        benefit: 'Dietary Fiber & Daily Complex Energy',
        reason: `Supplies ${kgNeeded.toFixed(1)} kg whole grain flour (${Math.round(wheatPct * 100)}% dynamic cereal ration). Delivers ${proteinDelivered}g plant protein and ${fiberDelivered}g dietary fiber (${nutri.protein}g protein per 100g via ${nutri.verified_label}).`,
        nutriBadge: nutri.badge,
        product_id: attaMatch.product.id,
        var_id: attaMatch.pkg.var_id,
        name: attaMatch.product.name,
        image: attaMatch.product.image || packgringImg,
        price: attaMatch.pkg.price,
        package_size: attaMatch.pkg.package_size,
        pack_weight: attaMatch.pkg.pack_weight,
        qty,
        nutritional_info: attaMatch.product.nutritional_info,
      });
    } else {
      shortfalls.push('Whole wheat flour is currently out of stock.');
    }

    // 2. Traditional Wholesome Rice (Brown / Sona Masoori)
    const riceMatch = findDynamicOrKeyword('grains_cereals', 'rice', ['sona masoori', 'basmati rice brown', 'basmati rice white', 'rice']);
    if (riceMatch) {
      const kgNeeded = Math.max(1, cereals * ricePct);
      const packGrams = parsePackageWeightGrams(riceMatch.pkg);
      const packWeightKg = packGrams / 1000.0;
      const qty = Math.max(1, Math.round(kgNeeded / packWeightKg) || Math.ceil(kgNeeded / 2));
      const totalGrams = qty * packGrams;
      const nutri = getProductNutrition(riceMatch.product);
      const proteinDelivered = Math.round((totalGrams / 100.0) * nutri.protein);
      const fiberDelivered = Math.round((totalGrams / 100.0) * nutri.fiber);

      items.push({
        key: 'rice-staple',
        label: riceMatch.product.name,
        benefit: 'Clean Digestion & Essential Carbohydrates',
        reason: `Supplies ${kgNeeded.toFixed(1)} kg traditional rice (${Math.round(ricePct * 100)}% dynamic cereal ration). Delivers ${proteinDelivered}g protein and clean complex energy (${nutri.protein}g protein per 100g via ${nutri.verified_label}).`,
        nutriBadge: nutri.badge,
        product_id: riceMatch.product.id,
        var_id: riceMatch.pkg.var_id,
        name: riceMatch.product.name,
        image: riceMatch.product.image || packgringImg,
        price: riceMatch.pkg.price,
        package_size: riceMatch.pkg.package_size,
        pack_weight: riceMatch.pkg.pack_weight,
        qty,
        nutritional_info: riceMatch.product.nutritional_info,
      });
    } else {
      shortfalls.push('Traditional rice is currently out of stock.');
    }

    // 3. Ancient Prebiotic Millets (Jowar, Ragi, Bajra, Kodo)
    const milletMatch = findDynamicOrKeyword('grains_cereals', 'millets', ['finger (ragi)', 'ragi', 'jawar', 'jowar', 'bajra whole', 'kodo', 'little millet']);
    if (milletMatch) {
      const kgNeeded = Math.max(1, cereals * milletPct);
      const packGrams = parsePackageWeightGrams(milletMatch.pkg);
      const packWeightKg = packGrams / 1000.0;
      const qty = Math.max(1, Math.round(kgNeeded / packWeightKg));
      const totalGrams = qty * packGrams;
      const nutri = getProductNutrition(milletMatch.product);
      const proteinDelivered = Math.round((totalGrams / 100.0) * nutri.protein);
      const fiberDelivered = Math.round((totalGrams / 100.0) * nutri.fiber);

      items.push({
        key: 'millet-staple',
        label: milletMatch.product.name,
        benefit: 'Prebiotic Minerals & Slow-Release Energy',
        reason: `Supplies ${kgNeeded.toFixed(1)} kg ancient prebiotic grains (${Math.round(milletPct * 100)}% dynamic cereal ration). Delivers ${proteinDelivered}g protein and ${fiberDelivered}g dietary fiber (${nutri.protein}g protein per 100g via ${nutri.verified_label}), dynamically calibrated for metabolic health.`,
        nutriBadge: nutri.badge,
        product_id: milletMatch.product.id,
        var_id: milletMatch.pkg.var_id,
        name: milletMatch.product.name,
        image: milletMatch.product.image || packgringImg,
        price: milletMatch.pkg.price,
        package_size: milletMatch.pkg.package_size,
        pack_weight: milletMatch.pkg.pack_weight,
        qty,
        nutritional_info: milletMatch.product.nutritional_info,
      });
    } else {
      shortfalls.push('Ancient millets are currently out of stock.');
    }

    // --- Pulses & Legumes (~pulses kg) with Genuinely Dynamic Nutritional Scoring ---
    const allDiscoveredPulses = getEligibleInStockProducts(products, 'pulses_legumes');

    const availablePulses = [];
    const usedPulseKeys = new Set();

    allDiscoveredPulses.forEach((item) => {
      const sub = item.groupInfo?.food_subgroup || 'other_pulses';
      const key = `${sub}-${item.product.id}`;
      if (!usedPulseKeys.has(key)) {
        usedPulseKeys.add(key);

        const nutri = getProductNutrition(item.product);
        let fitnessScore = 20.0; // Baseline diversity credit

        // 1. Protein density scaling: products with 24g protein score higher than 18g
        fitnessScore += ((nutri.protein || 20) / 20.0) * 15.0;

        // 2. Dietary fiber density scaling for microbiome & satiety
        fitnessScore += ((nutri.fiber || 12) / 10.0) * 10.0;

        // 3. Diabetes / Glycemic Index adaptation
        if (hasDiabetes) {
          // Low-GI, high-amylose resistant starch (Chana Dal, Rajma, Desi Gram)
          if (sub === 'chana_bengal' || sub === 'beans_rajma' || /gram|chana|rajma|kulthi|horsegram/i.test(item.product.name)) {
            fitnessScore += 25.0;
          }
        }

        // 4. High Activity / Athlete / Muscle Growth adaptation
        if (athletesCount > 0 || avgProteinPerCapita >= 60) {
          if (nutri.protein >= 22.0) {
            fitnessScore += (athletesCount / memberCount) * 20.0;
          }
        }

        // 5. Senior Gut Comfort & Digestibility adaptation
        if (seniorsCount > 0) {
          if (sub === 'moong' || /moong|green gram/i.test(item.product.name)) {
            fitnessScore += (seniorsCount / memberCount) * 25.0;
          }
        }

        // 6. Pediatric Growth & Essential Folate
        if (childrenCount > 0) {
          if (sub === 'toor_arhar' || /toor|tuar|arhar/i.test(item.product.name)) {
            fitnessScore += (childrenCount / memberCount) * 15.0;
          }
        }

        availablePulses.push({
          key,
          label: item.product.name,
          benefit: item.groupInfo?.group_label || 'Wholesome Plant Protein & Dietary Fiber',
          baseWeight: fitnessScore,
          product: item.product,
          pkg: item.pkg,
          nutri,
        });
      }
    });

    if (availablePulses.length > 0) {
      const totalWeight = availablePulses.reduce((sum, p) => sum + p.baseWeight, 0);
      availablePulses.forEach((p) => {
        const sharePct = p.baseWeight / totalWeight;
        const kgNeeded = Math.max(0.5, pulses * sharePct);
        const packGrams = parsePackageWeightGrams(p.pkg);
        const packWeightKg = packGrams / 1000.0;
        const qty = Math.max(1, Math.round(kgNeeded / packWeightKg));
        const totalGrams = qty * packGrams;
        const nutri = p.nutri;
        const proteinDelivered = Math.round((totalGrams / 100.0) * nutri.protein);
        const fiberDelivered = Math.round((totalGrams / 100.0) * nutri.fiber);

        items.push({
          key: p.key,
          label: p.label,
          benefit: p.benefit,
          reason: `Dynamically allocated (${Math.round(sharePct * 100)}% pulse share, ${(totalGrams / 1000).toFixed(1)} kg packed) calibrated to family nutrient targets. Delivers ${proteinDelivered}g plant protein & ${fiberDelivered}g dietary fiber (${nutri.protein}g protein per 100g via ${nutri.verified_label}).`,
          nutriBadge: nutri.badge,
          product_id: p.product.id,
          var_id: p.pkg.var_id,
          name: p.product.name,
          image: p.product.image || packgringImg,
          price: p.pkg.price,
          package_size: p.pkg.package_size,
          pack_weight: p.pkg.pack_weight,
          qty,
          nutritional_info: p.product.nutritional_info,
        });
      });
    } else {
      shortfalls.push('All pulses and legumes are currently out of stock.');
    }

    // --- Seeds & Nuts (~nuts kg) with Dynamic Discovery & Sensible Diversification ---
    // Rule: ICMR-NIN recommended seed intake is 10–15g/day per individual (40–60g/day for a family of 4 = ~1.2–1.8 kg/mo).
    // Allocate 1 practical pack across each available in-stock seed type, and report the remainder for tree nuts.
    const allDiscoveredSeeds = getEligibleInStockProducts(products, 'nuts_seeds');
    let totalSeedsAllocatedKg = 0;

    if (allDiscoveredSeeds.length > 0) {
      allDiscoveredSeeds.forEach((item) => {
        const packGrams = parsePackageWeightGrams(item.pkg);
        const qty = 1; // 1 diversified pack per seed type
        const totalGrams = qty * packGrams;
        totalSeedsAllocatedKg += totalGrams / 1000.0;
        const nutri = getProductNutrition(item.product);
        const proteinDelivered = Math.round((totalGrams / 100.0) * nutri.protein);
        const fiberDelivered = Math.round((totalGrams / 100.0) * nutri.fiber);

        items.push({
          key: `seed-${item.product.id}`,
          label: item.product.name,
          benefit: 'Bioavailable Omega-3 ALA, Zinc, Magnesium & Plant Micronutrients',
          reason: `Practical daily super-seed serving (10–15g/person). Delivers ${proteinDelivered}g protein and ${fiberDelivered}g dietary fiber across 1 pack (${(totalGrams / 1000).toFixed(1)} kg) verified via ${nutri.verified_label}.`,
          nutriBadge: nutri.badge,
          product_id: item.product.id,
          var_id: item.pkg.var_id,
          name: item.product.name,
          image: item.product.image || packgringImg,
          price: item.pkg.price,
          package_size: item.pkg.package_size,
          pack_weight: item.pkg.pack_weight,
          qty,
          nutritional_info: item.product.nutritional_info,
        });
      });
    } else {
      shortfalls.push('All seeds and nuts are currently out of stock.');
    }

    if (nuts > totalSeedsAllocatedKg) {
      setTreeNutsRecommendation({
        allocatedSeedsKg: totalSeedsAllocatedKg.toFixed(1),
        recommendedKg: (nuts - totalSeedsAllocatedKg).toFixed(1),
      });
    } else {
      setTreeNutsRecommendation(null);
    }

    // --- Cooking Oils & Vedic A2 Ghee (~oil kg with Density Adjustments) ---
    // Physical densities: Cold-Pressed Oil ~0.915 kg/L, Vedic Cow Ghee ~0.905 kg/L
    const OIL_DENSITY = 0.915; // kg per Liter
    const GHEE_DENSITY = 0.905; // kg per Liter

    // Dynamic lipid distribution:
    let oilPct = 0.75;
    let gheePct = 0.25;
    if (hasHeart) {
      oilPct = 0.88;
      gheePct = 0.12;
    } else if (seniorsCount > 0) {
      oilPct = 0.70;
      gheePct = 0.30;
    }

    // Dynamically discover in-stock cooking oils
    const allInStockOils = getEligibleInStockProducts(products, 'cooking_oils');
    let oilMatch = null;

    if (allInStockOils.length > 0) {
      const pref = (household.oilPreference || '').toLowerCase();
      if (pref) {
        oilMatch = allInStockOils.find((o) => (o.product.name || '').toLowerCase().includes(pref)) || allInStockOils[0];
      } else {
        oilMatch = allInStockOils[0];
      }
    } else {
      oilMatch = findDynamicOrKeyword('cooking_oils', null, ['groundnut oil', 'sunflower oil', 'black sesame oil', 'olive oil']);
    }

    if (oilMatch) {
      const oilKgNeeded = oil * oilPct;
      const litersNeeded = Math.max(1, oilKgNeeded / OIL_DENSITY);
      const packGrams = parsePackageWeightGrams(oilMatch.pkg);
      const packLiters = packGrams / 1000.0;
      const qty = Math.max(1, Math.round(litersNeeded / packLiters));
      const nutri = getProductNutrition(oilMatch.product);

      items.push({
        key: 'cooking-oil',
        label: oilMatch.product.name,
        benefit: 'Heart-Healthy MUFA / PUFA Wood-Pressed at < 40°C',
        reason: hasHeart
          ? `Prioritized for cardiovascular health (${Math.round(oilPct * 100)}% fat share, ${(qty * packLiters).toFixed(1)} L calibrated at ${OIL_DENSITY} kg/L density): pure unrefined MUFA/PUFA lipids without chemical solvent extraction or trans-fats.`
          : `Traditional cold-pressed cooking oil (${Math.round(oilPct * 100)}% fat share, ${(qty * packLiters).toFixed(1)} L calibrated at ${OIL_DENSITY} kg/L density) supplying natural monounsaturated & polyunsaturated fats.`,
        nutriBadge: nutri.badge,
        product_id: oilMatch.product.id,
        var_id: oilMatch.pkg.var_id,
        name: oilMatch.product.name,
        image: oilMatch.product.image || packgringImg,
        price: oilMatch.pkg.price,
        package_size: oilMatch.pkg.package_size,
        pack_weight: oilMatch.pkg.pack_weight,
        qty,
        nutritional_info: oilMatch.product.nutritional_info,
      });
    } else {
      shortfalls.push('Preferred cold-pressed cooking oil is temporarily out of stock.');
    }

    // Dynamically discover in-stock ghee
    const allInStockGhee = getEligibleInStockProducts(products, 'ghee');
    const gheeMatch = allInStockGhee.length > 0 ? allInStockGhee[0] : findDynamicOrKeyword('ghee', null, ['a2 gir cow ghee bilona', 'a2 gir cow ghee', 'ghee']);

    if (gheeMatch) {
      const gheeKgNeeded = oil * gheePct;
      const litersNeeded = Math.max(0.5, gheeKgNeeded / GHEE_DENSITY);
      const packGrams = parsePackageWeightGrams(gheeMatch.pkg);
      const packLiters = packGrams / 1000.0;
      const qty = Math.max(1, Math.round(litersNeeded / packLiters));
      const nutri = getProductNutrition(gheeMatch.product);

      items.push({
        key: 'vedic-ghee',
        label: gheeMatch.product.name,
        benefit: 'Butyric Acid for Gut Microbiome & Fat-Soluble Vitamins',
        reason: hasHeart
          ? `Cardiovascular moderation: Vedic A2 Bilona Ghee restricted to ~${Math.round(gheePct * 100)}% share (${(qty * packLiters).toFixed(1)} L calibrated at ${GHEE_DENSITY} kg/L density) to limit saturated fat while supporting fat-soluble vitamin absorption.`
          : `Traditional bilona ghee (${Math.round(gheePct * 100)}% fat share, ${(qty * packLiters).toFixed(1)} L calibrated at ${GHEE_DENSITY} kg/L density) providing gut-nourishing butyric acid and aiding absorption of vitamins A, D, E, K.`,
        nutriBadge: nutri.badge,
        product_id: gheeMatch.product.id,
        var_id: gheeMatch.pkg.var_id,
        name: gheeMatch.product.name,
        image: gheeMatch.product.image || packgringImg,
        price: gheeMatch.pkg.price,
        package_size: gheeMatch.pkg.package_size,
        pack_weight: gheeMatch.pkg.pack_weight,
        qty,
        nutritional_info: gheeMatch.product.nutritional_info,
      });
    } else {
      shortfalls.push('Traditional Vedic A2 Ghee is temporarily out of stock.');
    }

    // --- Natural Sweetener (~sugar kg) ---
    const allInStockSweeteners = getEligibleInStockProducts(products, 'sweeteners');
    const sugarMatch = allInStockSweeteners.length > 0 ? allInStockSweeteners[0] : findDynamicOrKeyword('sweeteners', null, ['jaggery powder', 'raw sugar', 'khandasari sugar', 'sugar']);
    if (sugarMatch) {
      const kgNeeded = Math.max(0.5, sugar);
      const packGrams = parsePackageWeightGrams(sugarMatch.pkg);
      const packKg = packGrams / 1000.0;
      const qty = Math.max(1, Math.round(kgNeeded / packKg));
      const nutri = getProductNutrition(sugarMatch.product);

      items.push({
        key: 'natural-sweetener',
        label: sugarMatch.product.name,
        benefit: 'Unrefined Pure Sweetener (Controlled Use)',
        reason: hasDiabetes
          ? `Unrefined organic sweetener quantity automatically reduced by 50% in accordance with household diabetic mindfulness.`
          : `Traditional unrefined sweetener providing natural plant minerals without chemical bleaching or synthetic refining.`,
        nutriBadge: nutri.badge,
        product_id: sugarMatch.product.id,
        var_id: sugarMatch.pkg.var_id,
        name: sugarMatch.product.name,
        image: sugarMatch.product.image || packgringImg,
        price: sugarMatch.pkg.price,
        package_size: sugarMatch.pkg.package_size,
        pack_weight: sugarMatch.pkg.pack_weight,
        qty,
        nutritional_info: sugarMatch.product.nutritional_info,
      });
    }

    // --- Spices & Himalayan Salt ---
    const allInStockSpices = getEligibleInStockProducts(products, 'spices');
    const spiceMatch = allInStockSpices.length > 0 ? allInStockSpices[0] : findDynamicOrKeyword('spices', null, ['turmeric powder', 'cumin powder', 'coriander powder']);
    if (spiceMatch) {
      const nutri = getProductNutrition(spiceMatch.product);
      items.push({
        key: 'organic-turmeric',
        label: spiceMatch.product.name,
        benefit: 'Daily Antioxidant & Cellular Anti-Inflammatory Support',
        reason: `High-curcumin organic turmeric providing daily antioxidant protection and cellular wellness (${nutri.protein}g protein, ${nutri.fiber}g fiber per 100g via ${nutri.verified_label}).`,
        nutriBadge: nutri.badge,
        product_id: spiceMatch.product.id,
        var_id: spiceMatch.pkg.var_id,
        name: spiceMatch.product.name,
        image: spiceMatch.product.image || packgringImg,
        price: spiceMatch.pkg.price,
        package_size: spiceMatch.pkg.package_size,
        pack_weight: spiceMatch.pkg.pack_weight,
        qty: 1,
        nutritional_info: spiceMatch.product.nutritional_info,
      });
    }

    const allInStockSalt = getEligibleInStockProducts(products, 'salt');
    const saltMatch = allInStockSalt.length > 0 ? allInStockSalt[0] : findDynamicOrKeyword('salt', null, ['pink rock salt', 'sendha namak', 'salt']);
    if (saltMatch) {
      const kgNeeded = Math.max(0.5, salt);
      const packGrams = parsePackageWeightGrams(saltMatch.pkg);
      const packKg = packGrams / 1000.0;
      const qty = Math.max(1, Math.round(kgNeeded / packKg));
      const nutri = getProductNutrition(saltMatch.product);

      items.push({
        key: 'rock-salt',
        label: saltMatch.product.name,
        benefit: 'Naturally Mined Sendha Namak with Trace Minerals',
        reason: hasBp
          ? `Naturally mined Sendha Namak. Notice: Consists of ~98% sodium chloride; calibrated with 20% moderation factor for blood pressure mindfulness (< 5g/day per adult).`
          : `Natural Himalayan rock salt containing trace minerals (potassium, magnesium) unadulterated by anti-caking agents (~98% NaCl, use moderately).`,
        nutriBadge: nutri.badge,
        product_id: saltMatch.product.id,
        var_id: saltMatch.pkg.var_id,
        name: saltMatch.product.name,
        image: saltMatch.product.image || packgringImg,
        price: saltMatch.pkg.price,
        package_size: saltMatch.pkg.package_size,
        pack_weight: saltMatch.pkg.pack_weight,
        qty,
        nutritional_info: saltMatch.product.nutritional_info,
      });
    }

    setStockShortfalls(shortfalls);
    setRecommendations(items);
  }, [products, members, household, basketPlan]);

  // Member CRUD handlers
  const addMember = (type = 'adult') => {
    setMembers((prev) => [...prev, createMember(type, prev.length + 1)]);
  };

  const removeMember = (id, index = null) => {
    setMembers((prev) =>
      prev.filter((m, idx) => {
        if (id != null) return String(m.id) !== String(id);
        return idx !== index;
      })
    );
  };

  const updateMember = (id, field, value, index = null) => {
    let safeValue = value;

    if (value !== '' && value !== null && value !== undefined) {
      if (field === 'weightKg') {
        const num = Number(value);
        if (Number.isFinite(num)) {
          safeValue = String(Math.min(180, Math.max(1, num)));
        } else {
          safeValue = '';
        }
      } else if (field === 'heightFt') {
        const num = Number(value);
        if (Number.isFinite(num)) {
          safeValue = String(Math.min(8, Math.max(1, num)));
        } else {
          safeValue = '';
        }
      } else if (field === 'heightIn') {
        const num = Number(value);
        if (Number.isFinite(num)) {
          safeValue = String(Math.min(11, Math.max(0, num)));
        } else {
          safeValue = '';
        }
      } else if (field === 'age') {
        const num = Number(value);
        if (Number.isFinite(num)) {
          safeValue = Math.min(110, Math.max(1, num));
        } else {
          safeValue = '';
        }
      }
    }

    setMembers((prev) =>
      prev.map((m, idx) => {
        const isMatch = (id != null && String(m.id) === String(id)) || (index != null && idx === index);
        if (!isMatch) return m;
        return { ...m, [field]: safeValue };
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
    if (!pkg) {
      flash(`${product.name} is currently out of stock`);
      return;
    }
    const nutri = getProductNutrition(product);
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
          reason: `Custom item added to pantry basket (${nutri.protein}g protein, ${nutri.fiber}g fiber per 100g via ${nutri.verified_label}).`,
          nutriBadge: nutri.badge,
          product_id: product.id,
          var_id: pkg.var_id,
          name: product.name,
          image: product.image || packgringImg,
          price: pkg.price,
          package_size: pkg.package_size,
          pack_weight: pkg.pack_weight,
          qty: 1,
          nutritional_info: product.nutritional_info,
        },
      ];
    });
    flash(`Added ${product.name} to family pack`);
  };

  const packTotal = useMemo(() => {
    return recommendations.reduce((sum, item) => sum + (item.price * item.qty), 0);
  }, [recommendations]);

  // Macro-Nutrient Delivered by current custom basket
  const deliveredNutrition = useMemo(() => {
    let totCalories = 0;
    let totProtein = 0;
    let totCarbs = 0;
    let totFat = 0;
    let totFiber = 0;

    recommendations.forEach((item) => {
      const nutri = getProductNutrition(item.nutritional_info ? item : item.name);
      const packGrams = parsePackageWeightGrams(item);
      const totalGramsInBasket = (item.qty || 1) * packGrams;
      const factor = totalGramsInBasket / 100.0;

      totCalories += nutri.calories * factor;
      totProtein += nutri.protein * factor;
      totCarbs += nutri.carbs * factor;
      totFat += nutri.fat * factor;
      totFiber += nutri.fiber * factor;
    });

    return {
      energyKcal: Math.round(totCalories),
      proteinG: Math.round(totProtein),
      carbsG: Math.round(totCarbs),
      fatG: Math.round(totFat),
      fiberG: Math.round(totFiber),
    };
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
      nutrient_summary: {
        food_groups: basketPlan.monthlyKg,
        daily_nutrients: basketPlan.dailyNutrients,
        monthly_nutrients: basketPlan.monthlyNutrients,
      },
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
              <button type="button" className="btn-add-member" onClick={() => addMember('toddler')}>
                + Add Toddler (1–3)
              </button>
              <button type="button" className="btn-add-member" onClick={() => addMember('child')}>
                + Add Child
              </button>
              <button type="button" className="btn-add-member" onClick={() => addMember('teen')}>
                + Add Teen (10-18)
              </button>
              <button type="button" className="btn-add-member" onClick={() => addMember('adult')}>
                + Add Adult
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
              const cardId = member.id != null ? String(member.id) : `mem_${index}`;

              return (
                <div className="member-card-item detailed-profile" key={cardId}>
                  {/* Top Bar Header */}
                  <div className="member-card-top-bar">
                    <div className="member-top-left-badges">
                      <span className="member-index-tag">
                        Member #{index + 1}
                      </span>
                      <span className="member-profile-code">
                        {getMemberStageAndAge(member)}
                      </span>
                    </div>
                    {members.length > 1 && (
                      <button
                        type="button"
                        className="btn-remove-member"
                        onClick={() => removeMember(member.id, index)}
                        title="Remove member"
                      >
                        ✕
                      </button>
                    )}
                  </div>

                  {/* Primary Demographics (Compact structured form) */}
                  <div className="member-demographics-grid">
                    <div className="input-wrap name-wrap">
                      <label htmlFor={`member_name_${cardId}`}>Name / Label</label>
                      <input
                        id={`member_name_${cardId}`}
                        name={`member_name_${cardId}`}
                        type="text"
                        value={member.name}
                        onChange={(e) => updateMember(member.id, 'name', e.target.value, index)}
                        placeholder={`Member ${index + 1}`}
                      />
                    </div>

                    {/* Explicit Male / Female Buttons */}
                    <div className="input-wrap sex-wrap">
                      <label>Gender / Sex</label>
                      <div className="sex-toggle-group" role="group" aria-label="Select gender">
                        <button
                          type="button"
                          className={`sex-toggle-btn ${member.sex === 'male' ? 'active' : ''}`}
                          onClick={() => updateMember(member.id, 'sex', 'male', index)}
                        >
                          ♂ Male
                        </button>
                        <button
                          type="button"
                          className={`sex-toggle-btn ${member.sex === 'female' ? 'active' : ''}`}
                          onClick={() => updateMember(member.id, 'sex', 'female', index)}
                        >
                          ♀ Female
                        </button>
                      </div>
                    </div>

                    {/* 3-column inline metrics: Age, Height, Weight */}
                    <div className="member-metrics-inline-row">
                      <div className="input-wrap age-wrap">
                        <label htmlFor={`member_age_${cardId}`}>Age (Yrs)</label>
                        <input
                          id={`member_age_${cardId}`}
                          name={`member_age_${cardId}`}
                          type="number"
                          min="1"
                          max="100"
                          value={member.age}
                          onChange={(e) => updateMember(member.id, 'age', Number(e.target.value), index)}
                        />
                      </div>

                      <div className="input-wrap height-wrap">
                        <label>Height (ft/in)</label>
                        <div className="ft-in-flex">
                          <input
                            id={`member_height_ft_${cardId}`}
                            name={`member_height_ft_${cardId}`}
                            type="number"
                            min="1"
                            max="8"
                            value={member.heightFt}
                            onChange={(e) => updateMember(member.id, 'heightFt', e.target.value, index)}
                            placeholder="ft"
                          />
                          <span className="ft-in-divider">/</span>
                          <input
                            id={`member_height_in_${cardId}`}
                            name={`member_height_in_${cardId}`}
                            type="number"
                            min="0"
                            max="11"
                            value={member.heightIn}
                            onChange={(e) => updateMember(member.id, 'heightIn', e.target.value, index)}
                            placeholder="in"
                          />
                        </div>
                      </div>

                      <div className="input-wrap weight-wrap">
                        <label htmlFor={`member_weight_${cardId}`}>Weight (kg)</label>
                        <input
                          id={`member_weight_${cardId}`}
                          name={`member_weight_${cardId}`}
                          type="number"
                          min="1"
                          max="180"
                          value={member.weightKg}
                          onChange={(e) => updateMember(member.id, 'weightKg', e.target.value, index)}
                          placeholder="kg"
                        />
                      </div>
                    </div>
                  </div>

                  {/* Lifestyle & Nutrition Goals */}
                  <div className="member-lifestyle-grid">
                    <div className="input-wrap">
                      <label htmlFor={`member_activity_${cardId}`}>Daily Activity &amp; Lifestyle</label>
                      <select
                        id={`member_activity_${cardId}`}
                        name={`member_activity_${cardId}`}
                        value={member.activity || ''}
                        onChange={(e) => updateMember(member.id, 'activity', e.target.value, index)}
                      >
                        <option value="">Select activity level</option>
                        <option value="sedentary">Desk / Office Routine (Sedentary)</option>
                        <option value="moderate">Moderate (Active Daily Routine)</option>
                        <option value="high">High Physical / Manual Activity</option>
                        <option value="athlete">High-Performance Athlete / Sports</option>
                      </select>
                    </div>

                    {isAdultFemale && (
                      <div className="input-wrap">
                        <label htmlFor={`member_lifestage_${cardId}`}>Life Stage</label>
                        <select
                          id={`member_lifestage_${cardId}`}
                          name={`member_lifestage_${cardId}`}
                          value={member.lifeStage || 'none'}
                          onChange={(e) => updateMember(member.id, 'lifeStage', e.target.value, index)}
                        >
                          <option value="none">Standard Adult Profile</option>
                          <option value="pregnant">Pregnant (Life Stage++)</option>
                          <option value="lactating">Lactating (0–6 Months)</option>
                        </select>
                      </div>
                    )}

                    <div className="input-wrap">
                      <label htmlFor={`member_goal_${cardId}`}>Nutrition Goal</label>
                      <select
                        id={`member_goal_${cardId}`}
                        name={`member_goal_${cardId}`}
                        value={member.goal || ''}
                        onChange={(e) => updateMember(member.id, 'goal', e.target.value, index)}
                      >
                        <option value="">Select nutrition goal</option>
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
                      <span className="bmi-strip-label">BMI</span>
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
                          name={`member_diabetes_${cardId}`}
                          checked={!!member.diabetes}
                          onChange={(e) => updateMember(member.id, 'diabetes', e.target.checked, index)}
                        />
                        <span>🍬 Diabetes (Sugar -50%)</span>
                      </label>

                      <label className={`health-flag-chip ${member.bp ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          name={`member_bp_${cardId}`}
                          checked={!!member.bp}
                          onChange={(e) => updateMember(member.id, 'bp', e.target.checked, index)}
                        />
                        <span>🧂 High BP (Salt -20%)</span>
                      </label>

                      <label className={`health-flag-chip ${member.heart ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          name={`member_heart_${cardId}`}
                          checked={!!member.heart}
                          onChange={(e) => updateMember(member.id, 'heart', e.target.checked, index)}
                        />
                        <span>🫀 Heart / Lipid (Oil -10%)</span>
                      </label>

                      <label className={`health-flag-chip danger ${member.kidney ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          name={`member_kidney_${cardId}`}
                          checked={!!member.kidney}
                          onChange={(e) => updateMember(member.id, 'kidney', e.target.checked, index)}
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

          {/* ICMR-NIN 2024 Macro-Nutrient & Basket Coverage Dashboard */}
          <div className="macro-coverage-dashboard">
            <div className="macro-coverage-header">
              <div className="coverage-title-group">
                <span className="coverage-badge">ICMR-NIN 2024 NUTRITION INTELLIGENCE</span>
                <h3>Household 30-Day Macro-Nutrient Targets vs. Pantry Basket Coverage</h3>
                <p>
                  Aggregated member-level nutrition benchmarks for {members.length} family member(s) over a 30-day period.
                  See how your customized organic pantry supplies the essential fuel, protein, and dietary fiber foundation.
                </p>
              </div>
            </div>

            <div className="macro-metrics-grid">
              {(() => {
                const getTag = (del, tgt) => {
                  if (!tgt || tgt <= 0) return { pct: 0, text: '0% Fulfilled', cls: '' };
                  const pct = Math.round((del / tgt) * 100);
                  if (pct > 125) return { pct, text: `${pct}% (Surplus Buffer)`, cls: 'surplus' };
                  if (pct < 70) return { pct, text: `${pct}% (Pantry Baseline)`, cls: 'gap' };
                  return { pct, text: `${pct}% (Optimal Target)`, cls: 'optimal' };
                };

                const energyTag = getTag(deliveredNutrition.energyKcal, basketPlan.monthlyNutrients?.energy);
                const proteinTag = getTag(deliveredNutrition.proteinG, basketPlan.monthlyNutrients?.protein);
                const fiberTag = getTag(deliveredNutrition.fiberG, basketPlan.monthlyNutrients?.fiber);
                const carbsTag = getTag(deliveredNutrition.carbsG, basketPlan.monthlyNutrients?.carbs);
                const fatTag = getTag(deliveredNutrition.fatG, basketPlan.monthlyNutrients?.fat);

                return (
                  <>
                    <div className="macro-metric-card">
                      <div className="macro-card-top">
                        <span className="macro-name">⚡ Energy (Calories)</span>
                        <span className={`macro-coverage-tag ${energyTag.cls}`}>{energyTag.text}</span>
                      </div>
                      <div className="macro-numbers">
                        <div className="macro-num-block">
                          <small>Pantry Delivered</small>
                          <strong>{deliveredNutrition.energyKcal.toLocaleString()} <span className="unit">kcal</span></strong>
                        </div>
                        <div className="macro-divider">/</div>
                        <div className="macro-num-block target">
                          <small>30-Day Target</small>
                          <span>{(basketPlan.monthlyNutrients?.energy || 0).toLocaleString()} <span className="unit">kcal</span></span>
                        </div>
                      </div>
                      <div className="macro-bar-wrap">
                        <div
                          className="macro-bar-fill energy"
                          style={{ width: `${Math.min(100, energyTag.pct)}%` }}
                        />
                      </div>
                      <span className="macro-card-sub">{(basketPlan.dailyNutrients?.energy || 0).toLocaleString()} kcal/day family daily baseline</span>
                    </div>

                    <div className="macro-metric-card">
                      <div className="macro-card-top">
                        <span className="macro-name">🥣 Plant Protein</span>
                        <span className={`macro-coverage-tag ${proteinTag.cls}`}>{proteinTag.text}</span>
                      </div>
                      <div className="macro-numbers">
                        <div className="macro-num-block">
                          <small>Pantry Delivered</small>
                          <strong>{deliveredNutrition.proteinG.toLocaleString()} <span className="unit">g</span></strong>
                        </div>
                        <div className="macro-divider">/</div>
                        <div className="macro-num-block target">
                          <small>30-Day Target</small>
                          <span>{(basketPlan.monthlyNutrients?.protein || 0).toLocaleString()} <span className="unit">g</span></span>
                        </div>
                      </div>
                      <div className="macro-bar-wrap">
                        <div
                          className="macro-bar-fill protein"
                          style={{ width: `${Math.min(100, proteinTag.pct)}%` }}
                        />
                      </div>
                      <span className="macro-card-sub">{(basketPlan.dailyNutrients?.protein || 0).toLocaleString()} g/day bioavailable protein target</span>
                    </div>

                    <div className="macro-metric-card">
                      <div className="macro-card-top">
                        <span className="macro-name">🌾 Dietary Fibre</span>
                        <span className={`macro-coverage-tag ${fiberTag.cls}`}>{fiberTag.text}</span>
                      </div>
                      <div className="macro-numbers">
                        <div className="macro-num-block">
                          <small>Pantry Delivered</small>
                          <strong>{deliveredNutrition.fiberG.toLocaleString()} <span className="unit">g</span></strong>
                        </div>
                        <div className="macro-divider">/</div>
                        <div className="macro-num-block target">
                          <small>30-Day Target</small>
                          <span>{(basketPlan.monthlyNutrients?.fiber || 0).toLocaleString()} <span className="unit">g</span></span>
                        </div>
                      </div>
                      <div className="macro-bar-wrap">
                        <div
                          className="macro-bar-fill fiber"
                          style={{ width: `${Math.min(100, fiberTag.pct)}%` }}
                        />
                      </div>
                      <span className="macro-card-sub">{(basketPlan.dailyNutrients?.fiber || 0).toLocaleString()} g/day gut & metabolic fiber target</span>
                    </div>

                    <div className="macro-metric-card">
                      <div className="macro-card-top">
                        <span className="macro-name">🍚 Complex Carbohydrates</span>
                        <span className={`macro-coverage-tag ${carbsTag.cls}`}>{carbsTag.text}</span>
                      </div>
                      <div className="macro-numbers">
                        <div className="macro-num-block">
                          <small>Pantry Delivered</small>
                          <strong>{deliveredNutrition.carbsG.toLocaleString()} <span className="unit">g</span></strong>
                        </div>
                        <div className="macro-divider">/</div>
                        <div className="macro-num-block target">
                          <small>30-Day Target</small>
                          <span>{(basketPlan.monthlyNutrients?.carbs || 0).toLocaleString()} <span className="unit">g</span></span>
                        </div>
                      </div>
                      <div className="macro-bar-wrap">
                        <div
                          className="macro-bar-fill carbs"
                          style={{ width: `${Math.min(100, carbsTag.pct)}%` }}
                        />
                      </div>
                      <span className="macro-card-sub">{(basketPlan.dailyNutrients?.carbs || 0).toLocaleString()} g/day unpolished whole-grain carbohydrates</span>
                    </div>

                    <div className="macro-metric-card">
                      <div className="macro-card-top">
                        <span className="macro-name">🥑 Healthy Cooking Fats</span>
                        <span className={`macro-coverage-tag ${fatTag.cls}`}>{fatTag.text}</span>
                      </div>
                      <div className="macro-numbers">
                        <div className="macro-num-block">
                          <small>Pantry Delivered</small>
                          <strong>{deliveredNutrition.fatG.toLocaleString()} <span className="unit">g</span></strong>
                        </div>
                        <div className="macro-divider">/</div>
                        <div className="macro-num-block target">
                          <small>30-Day Target</small>
                          <span>{(basketPlan.monthlyNutrients?.fat || 0).toLocaleString()} <span className="unit">g</span></span>
                        </div>
                      </div>
                      <div className="macro-bar-wrap">
                        <div
                          className="macro-bar-fill fat"
                          style={{ width: `${Math.min(100, fatTag.pct)}%` }}
                        />
                      </div>
                      <span className="macro-card-sub">{(basketPlan.dailyNutrients?.fat || 0).toLocaleString()} g/day cold-pressed MUFA/PUFA & A2 bilona fat</span>
                    </div>
                  </>
                );
              })()}
            </div>

            <div className="coverage-explanation-banner">
              <div className="coverage-banner-icon">💡</div>
              <div className="coverage-banner-text">
                <strong>Understanding Household Pantry Coverage:</strong> This 30-day organic basket supplies the foundational nutritional bulk — 
                high-fiber unpolished grains, complementary legume proteins, and cold-pressed cooking lipids. 
                The remaining energy and micronutrients are completed by your routine weekly perishables (fresh greens, vegetables, fruits, and dairy).
              </div>
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
            {/* Stock Shortfall Alerts */}
            {stockShortfalls.length > 0 && (
              <div className="stock-shortfall-alert-box">
                <div className="alert-box-icon">⚠️</div>
                <div className="alert-box-content">
                  <strong>Inventory Notice:</strong>
                  <ul>
                    {stockShortfalls.map((s, idx) => (
                      <li key={idx}>{s}</li>
                    ))}
                  </ul>
                </div>
              </div>
            )}

            {/* Whole Tree Nuts Diversification Card */}
            {treeNutsRecommendation && (
              <div className="tree-nuts-education-card">
                <div className="nuts-card-icon">🥜</div>
                <div className="nuts-card-text">
                  <strong>ICMR-NIN Whole Tree Nuts Recommendation:</strong> Your family's 30-day requirement includes <strong>{treeNutsRecommendation.allocatedSeedsKg} kg</strong> of organic super-seeds (Chia, Flax, Pumpkin, Sunflower) in this basket (10–15g daily serving per member). The remaining <strong>~{treeNutsRecommendation.recommendedKg} kg</strong> is recommended as whole tree nuts (such as raw Almonds and Walnuts) for Vitamin E, plant sterols, and healthy monounsaturated fat diversity, to be procured fresh as raw tree nuts are not in our current pantry dry goods catalog.
                </div>
              </div>
            )}
            {recommendations.map((item, index) => {
              const itemTotal = item.price * item.qty;

              return (
                <div className="basket-item-row" key={item.key}>
                  <span className="item-num">{String(index + 1).padStart(2, '0')}</span>

                  <div className="item-thumb-box">
                    <img src={item.image} alt={item.name} />
                  </div>

                  <div className="item-details">
                    <div className="item-details-top-tags">
                      <span className="item-category-tag">{item.label}</span>
                      {item.nutriBadge && <span className="item-nutrition-pill">{item.nutriBadge}</span>}
                    </div>
                    <h3 className="item-title">{item.name}</h3>
                    <p className="item-benefit">{item.benefit}</p>
                    {item.reason && (
                      <p className="item-scientific-reason">
                        <strong>Why this item:</strong> {item.reason}
                      </p>
                    )}
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
                placeholder="Search rice, spices, millets, seeds..."
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
                    <span className="extra-price">
                      {formatPrice(getEffectiveProductPrice(product))}
                      {Number(product.sale_price) > 0 && Number(product.sale_price) < Number(product.price) && (
                        <span className="extra-original-price" style={{ textDecoration: 'line-through', marginLeft: '6px', color: '#888', fontSize: '0.85em' }}>
                          {formatPrice(product.price)}
                        </span>
                      )}
                    </span>
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
