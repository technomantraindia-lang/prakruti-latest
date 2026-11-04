import React, { useEffect, useMemo, useRef, useState } from 'react';
import './FamilyPackPage.css';
import { productsApi } from '../../api/products';
import { familyPacksApi, errorMessages } from '../../api/familyPacks';
import { useCart } from '../../context/CartContext';
import { FiAlertTriangle, FiInfo } from 'react-icons/fi';
import familyPackImg from '../../assets/images/image copy 2.png';
import packgringImg from '../../assets/images/new packing .png';

/**
 * Family Pack page.
 *
 * This page is a DISPLAY layer. Every nutrition target, product choice, pack size, price and total comes from the
 * backend (/family-packs/plan, /quote and /add-to-cart), so the numbers shown here are the numbers the cart uses.
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
  mealsCookedAtHome: '3',
  staplePreference: 'balanced',
  pulseFrequency: 'daily',
  nutsFrequency: 'daily',
  sugarUse: 'moderate',
  oilPreference: 'cold_pressed_rotation',
};

const normalizeActivity = (a) => (['athlete', 'heavy', 'high'].includes(String(a || '').toLowerCase()) ? 'high' : (a || ''));

function normalizeMember(member, index = 1) {
  const memberId = member?.id != null && String(member.id).trim() !== ''
    ? String(member.id)
    : `mem_${index}_${Date.now()}_${Math.random().toString(36).substring(2, 7)}`;

  return {
    id: memberId,
    name: member?.name || `Member ${index}`,
    age: member?.age != null && member.age !== '' ? Number(member.age) : '',
    sex: member?.sex === 'female' ? 'female' : member?.sex === 'male' ? 'male' : '',
    heightFt: member?.heightFt != null ? String(member.heightFt) : '',
    heightIn: member?.heightIn != null ? String(member.heightIn) : '',
    weightKg: member?.weightKg != null ? String(member.weightKg) : '',
    activity: normalizeActivity(member?.activity),
    lifeStage: member?.lifeStage || 'none',
    diabetes: Boolean(member?.diabetes),
    bp: Boolean(member?.bp),
    heart: Boolean(member?.heart),
    kidney: Boolean(member?.kidney),
    goal: member?.goal || '',
  };
}

function createMember(type = 'adult', index = 1) {
  const presets = {
    toddler: { age: 2, sex: 'female', activity: 'moderate' },
    child: { age: 8, sex: 'male', activity: 'moderate' },
    teen: { age: 15, sex: 'male', activity: 'moderate' },
    senior: { age: 65, sex: 'female', activity: 'sedentary' },
    adult: { age: 32, sex: 'female', activity: 'moderate' },
  };
  const p = presets[type] || presets.adult;

  return {
    id: `mem_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`,
    name: `Member ${index}`,
    age: p.age,
    sex: p.sex,
    heightFt: '',
    heightIn: '',
    weightKg: '',
    activity: p.activity,
    lifeStage: 'none',
    diabetes: false,
    bp: false,
    heart: false,
    kidney: false,
    goal: '',
  };
}

/** Display only. Adult BMI; children and teenagers never get an adult BMI. */
function calculateBmi(member) {
  if (Number(member?.age || 0) < 19) return null;
  const ft = Number(member?.heightFt || 0);
  const inc = Number(member?.heightIn || 0);
  const heightM = (((ft * 12) + inc) * 2.54) / 100.0;
  const weight = Number(member?.weightKg || 0);
  if (ft < 1 || ft > 8 || inc < 0 || inc > 11.99 || weight < 10 || weight > 250 || heightM < 0.5) return null;
  return Number((weight / (heightM * heightM)).toFixed(1));
}

function bmiCategory(member) {
  const age = Number(member?.age || 0);
  const bmi = calculateBmi(member);
  if (age > 0 && age < 19) return 'Not interpreted for children';
  if (!bmi) return 'Enter height & weight (optional)';
  if (bmi < 18.5) return 'Below range (< 18.5)';
  if (bmi < 25) return 'Healthy range (18.5–24.9)';
  if (bmi < 30) return 'Above range (25.0–29.9)';
  return 'High range (≥ 30.0)';
}

function getMemberStageAndAge(member) {
  const age = Number(member?.age || 0);
  let stage = 'Adult';
  if (age > 0 && age < 4) stage = 'Toddler';
  else if (age >= 4 && age < 10) stage = 'Child';
  else if (age >= 10 && age < 19) stage = 'Teen';
  else if (age >= 60) stage = 'Senior';
  return age > 0 ? `${stage} · ${age} Yrs` : stage;
}

/** What is missing before the backend can plan (shown to the customer, never guessed). */
function memberIssues(members) {
  const issues = [];
  members.forEach((m, i) => {
    const age = Number(m.age);
    const label = m.name || `Member ${i + 1}`;
    if (!Number.isFinite(age) || age < 1 || age > 120) issues.push(`${label}: enter an age between 1 and 120.`);
    else if (age >= 10 && m.sex !== 'male' && m.sex !== 'female') issues.push(`${label}: select male or female.`);
  });
  return issues;
}

const num = (v) => (v === '' || v == null || Number.isNaN(Number(v)) ? undefined : Number(v));

function toApiMember(m) {
  return {
    id: String(m.id),
    name: m.name || undefined,
    age: Number(m.age),
    sex: m.sex || undefined,
    heightFt: num(m.heightFt),
    heightIn: num(m.heightIn),
    weightKg: num(m.weightKg),
    activity: m.activity || undefined,
    lifeStage: m.lifeStage && m.lifeStage !== 'none' ? m.lifeStage : 'none',
    goal: m.goal || undefined,
    diabetes: !!m.diabetes,
    bp: !!m.bp,
    heart: !!m.heart,
    kidney: !!m.kidney,
  };
}

function formatPrice(val) {
  return `₹${Number(val || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 })}`;
}

/** Display of a sale price only. The price actually charged always comes from the backend quote. */
export function getEffectiveProductPrice(product) {
  const sale = Number(product?.sale_price ?? 0);
  const reg = Number(product?.price ?? 0);
  if (sale > 0 && (reg <= 0 || sale < reg)) return sale;
  return reg > 0 ? reg : 0;
}

const GROUP_BENEFIT = {
  cereals: 'Daily staple grains & flours',
  pulses: 'Plant protein & dietary fibre',
  nuts: 'Nuts & seeds',
  oil: 'Cooking oil',
  ghee: 'Ghee',
  sweetener: 'Added-sugar allowance',
  salt: 'Salt',
  spices: 'Everyday spice',
};

function nutritionBadge(n) {
  const per = n?.per_100g;
  if (!per) return 'Nutrition information not available';
  const text = `${per.protein_g} g protein · ${per.fiber_g} g fibre per 100 g`;
  return n.basis === 'label' ? `From label: ${text}` : `Estimate: ${text}`;
}

/** Backend plan line / quote line → what the basket rows display. */
function toItem(line, extra = {}) {
  return {
    key: line.key || `p${line.product_id}-v${line.var_id || 0}`,
    label: line.group_label || extra.label || 'Pantry item',
    benefit: GROUP_BENEFIT[line.group_key] || extra.benefit || '',
    reason: line.reason || extra.reason || '',
    nutriBadge: nutritionBadge(line.nutrition),
    product_id: line.product_id,
    var_id: line.var_id ?? null,
    name: line.name,
    image: line.image || packgringImg,
    price: Number(line.price || 0),
    package_size: line.package_size || line.pack_text || '',
    pack_grams: line.pack_grams ?? null,
    qty: Number(line.qty || 1),
    max_qty: Number(line.max_qty || 999),
    group_key: line.group_key,
    nutrition: line.nutrition,
    manual: !!extra.manual,
  };
}

const emptyPlan = (message) => ({
  assessedMembers: [],
  dailyGrams: { cereals: 0, pulses: 0, nuts: 0, fat: 0, sugar: 0, salt: 0 },
  monthlyKg: { cereals: 0, pulses: 0, nuts: 0, oilL: 0, sugar: 0, salt: 0 },
  dailyNutrients: {},
  monthlyNutrients: {},
  interpretation: message,
  safetyAlerts: [],
});

const FamilyPackPage = ({ onGoToCart, currentUser, onLoginRequired }) => {
  const { flash, refreshCart } = useCart();
  const [members, setMembers] = useState(initialMembers);
  const [household, setHousehold] = useState(initialHousehold);
  const [plan, setPlan] = useState(null);
  const [recommendations, setRecommendations] = useState([]);
  const [coverage, setCoverage] = useState(null);
  const [stockShortfalls, setStockShortfalls] = useState([]);
  const [planState, setPlanState] = useState({ loading: false, error: '' });
  const [planStale, setPlanStale] = useState(false);   // true from the moment the family/habits change until the new plan is shown
  const [cartErrors, setCartErrors] = useState([]);
  const [savedMessage, setSavedMessage] = useState('');
  const [addingPack, setAddingPack] = useState(false);
  const [savedPack, setSavedPack] = useState(null);
  const [extraSearch, setExtraSearch] = useState('');
  const [extraResults, setExtraResults] = useState([]);
  const [editVersion, setEditVersion] = useState(0);
  const restoreRef = useRef(null);

  const issues = useMemo(() => memberIssues(members), [members]);
  const requestKey = useMemo(
    () => JSON.stringify({ m: members.map((m) => ({ ...toApiMember(m), id: undefined, name: undefined })), h: household }),
    [members, household],
  );

  const basketPlan = useMemo(() => {
    if (!plan) {
      return emptyPlan(issues.length
        ? 'Complete the member details below (age, and male/female for members aged 10 and over) to see your plan.'
        : 'Calculating your plan…');
    }
    const r = plan.monthly_requirements || {};
    const g = plan.daily_household_grams || {};
    return {
      assessedMembers: plan.members_assessed || [],
      dailyGrams: { cereals: g.cereals, pulses: g.pulses, nuts: g.nuts, fat: g.fat, sugar: g.sugar, salt: g.salt },
      monthlyKg: { cereals: r.cereals_kg, pulses: r.pulses_kg, nuts: r.nuts_kg, oilL: r.oil_liters, sugar: r.sugar_kg, salt: r.salt_kg },
      dailyNutrients: plan.daily_household_nutrients || {},
      monthlyNutrients: plan.monthly_nutrients || {},
      interpretation: plan.interpretation,
      safetyAlerts: plan.safety_alerts || [],
    };
  }, [plan, issues]);

  // 1. Restore the saved pack (members, habits and the exact basket the customer last saved)
  useEffect(() => {
    let mounted = true;
    if (!currentUser) return undefined;
    familyPacksApi.getLatest().then((latest) => {
      if (!mounted) return;
      const saved = latest.success && latest.data ? latest.data : null;
      if (saved?.profile?.members?.length) {
        setSavedPack(saved);
        restoreRef.current = Array.isArray(saved.recommendations) ? saved.recommendations : null;
        setMembers(saved.profile.members.map((m, idx) => normalizeMember(m, idx + 1)));
        if (saved.profile.household) setHousehold({ ...initialHousehold, ...saved.profile.household });
      }
    });
    return () => { mounted = false; };
  }, [currentUser]);

  // 2. Ask the backend for the plan whenever the family or habits change
  useEffect(() => {
    if (issues.length) {
      setPlanStale(false);
      setPlan(null);
      setRecommendations([]);
      setCoverage(null);
      setStockShortfalls([]);
      setPlanState({ loading: false, error: '' });
      return undefined;
    }
    let cancelled = false;
    setPlanStale(true);
    const timer = setTimeout(async () => {
      setPlanState({ loading: true, error: '' });
      const res = await familyPacksApi.plan({ members: members.map(toApiMember), household });
      if (cancelled) return;
      if (!res.success) {
        setPlanState({ loading: false, error: errorMessages(res).join(' ') || 'Could not calculate your plan. Please try again.' });
        return;
      }
      const { plan: p, basket } = res.data;
      setPlan(p);
      setStockShortfalls(basket.shortfalls || []);

      const saved = restoreRef.current;
      restoreRef.current = null;
      let restored = false;
      if (saved?.length) {
        const q = await familyPacksApi.quote({
          items: saved.map((s) => ({ product_id: s.product_id, var_id: s.var_id || null, qty: s.qty })),
          monthly_nutrients: p.monthly_nutrients,
        });
        if (cancelled) {
          restoreRef.current = saved;   // keep the saved basket for the next plan run
          return;
        }
        if (q.success) {
          const byKey = Object.fromEntries(saved.map((s) => [`${s.product_id}:${s.var_id || 0}`, s]));
          const okLines = q.data.lines.filter((l) => l.ok);
          if (okLines.length) {
            setRecommendations(okLines.map((l) => toItem(
              { ...l, key: `p${l.product_id}-v${l.var_id || 0}`, group_label: byKey[`${l.product_id}:${l.var_id || 0}`]?.label },
              { reason: 'Saved in your family pack.', manual: !!byKey[`${l.product_id}:${l.var_id || 0}`]?.manual },
            )));
            setCoverage(q.data.coverage);
            restored = true;
          }
          if (q.data.errors?.length) setCartErrors(q.data.errors.map((e) => `Saved item no longer available — ${e}`));
        }
      }
      if (!restored) {
        setRecommendations(basket.lines.map((l) => toItem(l)));
        setCoverage(basket.coverage);
      }
      setPlanState({ loading: false, error: '' });
      setPlanStale(false);
    }, 600);
    return () => { cancelled = true; clearTimeout(timer); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [requestKey, issues.length]);

  // 3. After the customer edits quantities / items, the backend re-prices and re-calculates coverage
  useEffect(() => {
    if (!editVersion || !plan) return undefined;
    let cancelled = false;
    const timer = setTimeout(async () => {
      if (!recommendations.length) {
        setCoverage(null);
        return;
      }
      const q = await familyPacksApi.quote({
        items: recommendations.map((i) => ({ product_id: i.product_id, var_id: i.var_id || null, qty: i.qty })),
        monthly_nutrients: plan.monthly_nutrients,
      });
      if (cancelled || !q.success) return;
      setCoverage(q.data.coverage);
      setCartErrors(q.data.all_ok ? [] : q.data.errors);
      const priced = Object.fromEntries(q.data.lines.filter((l) => l.ok).map((l) => [`${l.product_id}:${l.var_id || 0}`, l]));
      setRecommendations((prev) => prev.map((i) => {
        const l = priced[`${i.product_id}:${i.var_id || 0}`];
        return l ? { ...i, price: Number(l.price), max_qty: Number(l.max_qty) } : i;
      }));
    }, 450);
    return () => { cancelled = true; clearTimeout(timer); };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [editVersion]);

  // 4. "Add more essentials" search (server-side, so the whole catalogue is never downloaded)
  useEffect(() => {
    let cancelled = false;
    const timer = setTimeout(async () => {
      const res = await productsApi.getProducts({ search: extraSearch.trim() || undefined, per_page: 8 });
      if (cancelled) return;
      setExtraResults(res.success && !res.fromFallback && Array.isArray(res.data) ? res.data.slice(0, 8) : []);
    }, 350);
    return () => { cancelled = true; clearTimeout(timer); };
  }, [extraSearch]);

  // Member CRUD
  const addMember = (type = 'adult') => {
    setMembers((prev) => [...prev, createMember(type, prev.length + 1)]);
  };

  const removeMember = (id, index = null) => {
    setMembers((prev) => prev.filter((m, idx) => (id != null ? String(m.id) !== String(id) : idx !== index)));
  };

  const updateMember = (id, field, value, index = null) => {
    let safeValue = value;
    if (value !== '' && value !== null && value !== undefined) {
      const n = Number(value);
      if (field === 'weightKg') safeValue = Number.isFinite(n) ? String(Math.min(250, Math.max(1, n))) : '';
      else if (field === 'heightFt') safeValue = Number.isFinite(n) ? String(Math.min(8, Math.max(1, n))) : '';
      else if (field === 'heightIn') safeValue = Number.isFinite(n) ? String(Math.min(11, Math.max(0, n))) : '';
      else if (field === 'age') safeValue = Number.isFinite(n) ? Math.min(120, Math.max(1, n)) : '';
    }
    setMembers((prev) => prev.map((m, idx) => {
      const isMatch = (id != null && String(m.id) === String(id)) || (index != null && idx === index);
      return isMatch ? { ...m, [field]: safeValue } : m;
    }));
  };

  const touch = () => setEditVersion((v) => v + 1);

  const updateRecommendationQty = (key, delta) => {
    setRecommendations((prev) => prev.map((item) => (
      item.key === key ? { ...item, qty: Math.min(item.max_qty, Math.max(1, item.qty + delta)) } : item
    )));
    touch();
  };

  const removeRecommendation = (key) => {
    setRecommendations((prev) => prev.filter((item) => item.key !== key));
    touch();
  };

  const addExtraProduct = async (product) => {
    const variation = (product.variations || []).find((v) => Number(v.available_stock ?? 0) > 0) || null;
    const q = await familyPacksApi.quote({ items: [{ product_id: product.id, var_id: variation?.id || null, qty: 1 }] });
    const line = q.success ? q.data.lines[0] : null;
    if (!line || !line.ok) {
      flash(`${product.name}: ${line?.error || q.message || 'could not be added'}`, 'error');
      return;
    }
    const key = `p${line.product_id}-v${line.var_id || 0}`;
    setRecommendations((prev) => {
      const existing = prev.find((i) => i.key === key);
      if (existing) {
        return prev.map((i) => (i.key === key ? { ...i, qty: Math.min(i.max_qty, i.qty + 1) } : i));
      }
      return [...prev, toItem({ ...line, key, group_label: 'Added by you' }, { reason: 'Added by you.', manual: true })];
    });
    touch();
    flash(`Added ${product.name} to family pack`);
  };

  const packTotal = useMemo(
    () => Math.round(recommendations.reduce((sum, item) => sum + (item.price * item.qty), 0) * 100) / 100,
    [recommendations],
  );

  const savePack = async () => {
    if (!currentUser) {
      setSavedMessage('Please login or register to save your Smart Family Pack.');
      setTimeout(() => {
        setSavedMessage('');
        if (onLoginRequired) onLoginRequired();
      }, 1500);
      return false;
    }
    if (issues.length || !recommendations.length) {
      setSavedMessage('Complete the member details first so there is a basket to save.');
      setTimeout(() => setSavedMessage(''), 3500);
      return false;
    }

    const res = await familyPacksApi.save({
      profile: { members: members.map(toApiMember), household, interpretation: basketPlan.interpretation },
      nutrient_summary: { daily_nutrients: basketPlan.dailyNutrients, monthly_nutrients: basketPlan.monthlyNutrients },
      recommendations: recommendations.map((i) => ({
        product_id: i.product_id,
        var_id: i.var_id || null,
        name: i.name,
        qty: i.qty,
        price: i.price,
        label: i.label,
        benefit: i.benefit,
        package_size: i.package_size,
        pack_weight: i.pack_grams,
        manual: i.manual,
      })),
      monthly_total: packTotal,
    });
    if (res.success) {
      setSavedMessage('✓ Family pack configuration saved.');
      setSavedPack(res.data);
    } else {
      setSavedMessage(errorMessages(res).join(' ') || 'Could not save configuration. Please try again.');
    }
    setTimeout(() => setSavedMessage(''), 3500);
    return res.success;
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
    if (issues.length || planStale || !recommendations.length) return;

    setAddingPack(true);
    setCartErrors([]);
    await savePack();

    const res = await familyPacksApi.addToCart({
      items: recommendations.map((i) => ({ product_id: i.product_id, var_id: i.var_id || null, qty: i.qty })),
    });
    setAddingPack(false);

    if (!res.success) {
      if (res.requiresLogin && onLoginRequired) onLoginRequired();
      setCartErrors(errorMessages(res));
      flash('Nothing was added to your cart. Please review the notice above the basket.', 'error');
      return;
    }

    localStorage.setItem('prakruti_pending_order_type', 'family_pack');
    if (refreshCart) await refreshCart();
    flash('Smart Family Pack added to cart');
    if (onGoToCart) onGoToCart();
  };

  const filteredExtras = extraResults;
  const assessedByIndex = basketPlan.assessedMembers;

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
                            Member-level grocery planning based on <strong>ICMR-NIN reference values</strong> (RDA 2020 and Dietary Guidelines 2024).
              We work out reference needs for every family member, add them up, and suggest a practical 30-day pantry pack from our real products.
            </p>

            <div className="positioning-disclaimer-pill">
              <FiInfo className="disclaimer-info-icon" />
              <span>
                <strong>Smart Family Grocery Planner:</strong> Designed for routine household grocery estimation. 
                Not medical advice, a diagnosis, a diet for any condition, or a complete meal plan. Fresh vegetables, fruit and dairy are not part of this basket.
              </span>
            </div>

            <div className="family-pack-actions">
              <button
                type="button"
                className="btn btn-primary-fp"
                onClick={addPackToCart}
                disabled={addingPack || planStale || !recommendations.length || issues.length > 0}
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
                <span className="stat-lbl">30-Day Total</span>
              </div>
            </div>
          </div>

          <div className="family-pack-hero-image-wrap">
            <div className="hero-img-frame">
              <img src={familyPackImg} alt="Smart Family Grocery Planner Basket" />
            </div>
            <div className="hero-img-caption-tag">
              <span className="floating-icon">🌱</span>
              <span className="caption-text">Based on ICMR-NIN reference values</span>
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

      {(issues.length > 0 || planState.error || planState.loading) && (
        <section className="container fp-section-container">
          {issues.length > 0 && (
            <div className="fp-safety-alert info">
              <div className="alert-icon-col"><FiInfo className="alert-svg-icon" /></div>
              <div className="alert-text-col">
                <h4>A few details are needed</h4>
                <ul>{issues.map((t) => <li key={t}>{t}</li>)}</ul>
              </div>
            </div>
          )}
          {planState.error && (
            <div className="fp-safety-alert critical">
              <div className="alert-icon-col"><FiAlertTriangle className="alert-svg-icon" /></div>
              <div className="alert-text-col"><h4>We could not build your plan</h4><p>{planState.error}</p></div>
            </div>
          )}
          {planState.loading && !planState.error && <p className="interp-text" role="status">Updating your plan…</p>}
        </section>
      )}

      {/* Multi-Parameter Family Interpretation Report */}
      <section className="container fp-section-container">
        <div className="smart-interpretation-card">
          <div className="interp-header">
            <span className="interp-badge">YOUR PLAN AT A GLANCE</span>
            <h3>How Prakruti Reads Your Household Profile</h3>
          </div>
          <p className="interp-text">{basketPlan.interpretation}</p>
          <div className="interp-tags-row">
            <span className="interp-tag">✓ Member-Level First, Household Second</span>
            <span className="interp-tag">✓ No Generic Family Average</span>
            <span className="interp-tag">✓ ICMR-NIN reference portions</span>
            <span className="interp-tag">✓ Real pack sizes &amp; live stock</span>
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
                                Enter each member's age, sex and activity level. Each person is matched to the closest ICMR-NIN reference group;
                height and weight are optional and only used to show an adult BMI.
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
                          max="120"
                          value={member.age}
                          onChange={(e) => updateMember(member.id, 'age', e.target.value, index)}
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
                          max="250"
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
                        value={normalizeActivity(member.activity)}
                        onChange={(e) => updateMember(member.id, 'activity', e.target.value, index)}
                      >
                        <option value="">Select activity level</option>
                        <option value="sedentary">Desk / Office Routine (Sedentary)</option>
                        <option value="moderate">Moderate (Active Daily Routine)</option>
                        <option value="high">High Physical / Manual Activity (incl. athletes)</option>
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
                          <option value="pregnant">Pregnant</option>
                          <option value="lactating">Lactating (0–6 Months)</option>
                        </select>
                      </div>
                    )}

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

                  {assessedByIndex[index] && (
                    <div className="member-bmi-strip" style={{ display: 'block' }}>
                      <p style={{ margin: 0 }}>
                        <strong>{assessedByIndex[index].profile_label}</strong>
                        {' · '}{assessedByIndex[index].daily_nutrients.energy_kcal.toLocaleString()} kcal/day
                        {' · '}{assessedByIndex[index].daily_nutrients.protein_g} g protein/day
                        {assessedByIndex[index].profile_basis !== 'official' && (
                          <em> ({assessedByIndex[index].profile_basis === 'derived' ? 'derived from ICMR-NIN figures' : 'estimate'})</em>
                        )}
                      </p>
                      <small>{assessedByIndex[index].bmi_note}</small>
                      {assessedByIndex[index].adjustments.map((t) => (<small key={t} style={{ display: 'block' }}>• {t}</small>))}
                    </div>
                  )}

                  {/* Health Flags Bar */}
                  <div className="member-health-section">
                    <div className="health-section-header">
                      <span className="flags-label">Health &amp; Dietary Flags</span>
                      <span className="flags-hint">(Small planning adjustments for this person only — not medical advice)</span>
                    </div>
                    <div className="health-flags-chips">
                      <label className={`health-flag-chip ${member.diabetes ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          name={`member_diabetes_${cardId}`}
                          checked={!!member.diabetes}
                          onChange={(e) => updateMember(member.id, 'diabetes', e.target.checked, index)}
                        />
                        <span>🍬 Diabetes (lower added sugar)</span>
                      </label>

                      <label className={`health-flag-chip ${member.bp ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          name={`member_bp_${cardId}`}
                          checked={!!member.bp}
                          onChange={(e) => updateMember(member.id, 'bp', e.target.checked, index)}
                        />
                        <span>🧂 High BP (lower salt)</span>
                      </label>

                      <label className={`health-flag-chip ${member.heart ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          name={`member_heart_${cardId}`}
                          checked={!!member.heart}
                          onChange={(e) => updateMember(member.id, 'heart', e.target.checked, index)}
                        />
                        <span>🫀 Heart (less ghee, slightly less fat)</span>
                      </label>

                      <label className={`health-flag-chip danger ${member.kidney ? 'active' : ''}`}>
                        <input
                          type="checkbox"
                          name={`member_kidney_${cardId}`}
                          checked={!!member.kidney}
                          onChange={(e) => updateMember(member.id, 'kidney', e.target.checked, index)}
                        />
                        <span>⚠️ Kidney condition (needs professional review)</span>
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
                <option value="0.5">Low home cooking / frequent travel (about 15% less planned)</option>
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
                <option value="rare">Infrequent / low pulse intake (about 15% less planned)</option>
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
                <option value="rare">Infrequent / rare (about 15% less planned)</option>
              </select>
            </div>

            <div className="habit-item">
              <label>Added sugar use at home</label>
              <select
                value={household.sugarUse}
                onChange={(e) => setHousehold({ ...household, sugarUse: e.target.value })}
              >
                <option value="low">Mindful / low added sugar (about 20% less planned)</option>
                <option value="moderate">Moderate / standard use</option>
                <option value="high">High tea &amp; dessert use (we still follow the added-sugar guideline)</option>
              </select>
            </div>

            <div className="habit-item">
              <label>Cooking oil &amp; ghee preference</label>
              <select
                value={household.oilPreference}
                onChange={(e) => setHousehold({ ...household, oilPreference: e.target.value })}
              >
                <option value="cold_pressed_rotation">Mix of available cooking oils + ghee</option>
                <option value="groundnut">Prefer groundnut oil</option>
                <option value="mustard">Prefer mustard oil</option>
                <option value="sesame">Prefer sesame oil</option>
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
              <h2>Household Reference Needs</h2>
              <p>Daily family reference amounts (ICMR-NIN) converted into a 30-day grocery plan.</p>
            </div>
          </div>

          <div className="nutrition-cards-grid six-col">
            <div className="nutrition-stat-card grains">
              <span className="nutri-icon">🌾</span>
              <span className="nutri-label">Grains, Flours &amp; Millets</span>
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
              <span className="nutri-label">Cooking Oil &amp; Ghee</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.fat} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.oilL} L / month</span>
            </div>

            <div className="nutrition-stat-card nuts">
              <span className="nutri-icon">🥜</span>
              <span className="nutri-label">Nuts &amp; Seeds</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.nuts} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.nuts} kg / month</span>
            </div>

            <div className="nutrition-stat-card sugar">
              <span className="nutri-icon">🍯</span>
              <span className="nutri-label">Added Sugar / Sweetener</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.sugar} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.sugar} kg / month</span>
            </div>

            <div className="nutrition-stat-card salt">
              <span className="nutri-icon">🧂</span>
              <span className="nutri-label">Salt</span>
              <strong className="nutri-val">{basketPlan.dailyGrams.salt} <small>g/day</small></strong>
              <span className="nutri-sub">~{basketPlan.monthlyKg.salt} kg / month</span>
            </div>
          </div>

          {/* Coverage: what the basket provides against the household's reference needs */}
          <div className="macro-coverage-dashboard">
            <div className="macro-coverage-header">
              <div className="coverage-title-group">
                <span className="coverage-badge">WHAT THIS BASKET PROVIDES</span>
                <h3>30-Day Reference Needs vs. This Basket</h3>
                <p>
                  Reference needs for {members.length} family member(s) compared with the dry groceries in the basket below.
                  The rest of a normal diet comes from foods outside this basket.
                </p>
              </div>
            </div>

            {coverage ? (
              <>
                <div className="macro-metrics-grid">
                  {[
                    ['energy_kcal', '⚡ Energy', 'kcal', 'energy'],
                    ['protein_g', '🥣 Protein', 'g', 'protein'],
                    ['fiber_g', '🌾 Dietary Fibre', 'g', 'fiber'],
                    ['carbs_g', '🍚 Carbohydrates', 'g', 'carbs'],
                    ['fat_g', '🥑 Fat', 'g', 'fat'],
                  ].map(([k, title, unit, cls]) => {
                    const n = coverage.nutrients?.[k];
                    if (!n) return null;
                    const tag = n.pct >= 100 ? 'optimal' : n.pct < 60 ? 'gap' : '';
                    return (
                      <div className="macro-metric-card" key={k}>
                        <div className="macro-card-top">
                          <span className="macro-name">{title}</span>
                          <span className={`macro-coverage-tag ${tag}`}>{n.pct}% of reference</span>
                        </div>
                        <div className="macro-numbers">
                          <div className="macro-num-block">
                            <small>This basket</small>
                            <strong>{Math.round(n.basket_monthly).toLocaleString()} <span className="unit">{unit}</span></strong>
                          </div>
                          <div className="macro-divider">/</div>
                          <div className="macro-num-block target">
                            <small>30-day reference</small>
                            <span>{Math.round(n.target_monthly).toLocaleString()} <span className="unit">{unit}</span></span>
                          </div>
                        </div>
                        <div className="macro-bar-wrap">
                          <div className={`macro-bar-fill ${cls}`} style={{ width: `${Math.min(100, n.pct)}%` }} />
                        </div>
                        <span className="macro-card-sub">
                          {n.gap_monthly > 0
                            ? `About ${Math.round(n.gap_monthly).toLocaleString()} ${unit} (${Math.max(0, 100 - n.pct)}%) is expected from foods outside this basket.`
                            : 'The basket alone reaches the reference amount.'}
                          {n.from_estimate > 0 && ` ${Math.round(n.from_label).toLocaleString()} ${unit} from product labels, ${Math.round(n.from_estimate).toLocaleString()} ${unit} from estimates.`}
                        </span>
                      </div>
                    );
                  })}
                </div>

                {(coverage.estimated_products?.length > 0 || coverage.not_counted_products?.length > 0) && (
                  <p className="macro-card-sub" style={{ marginTop: 12 }}>
                    {coverage.estimated_products?.length > 0 && (
                      <>Values for <strong>{coverage.estimated_products.join(', ')}</strong> are food-group estimates because those products have no complete nutrition label yet. </>
                    )}
                    {coverage.not_counted_products?.length > 0 && (
                      <><strong>{coverage.not_counted_products.join(', ')}</strong> {coverage.not_counted_products.length > 1 ? 'have' : 'has'} no nutrition information and {coverage.not_counted_products.length > 1 ? 'are' : 'is'} not counted.</>
                    )}
                  </p>
                )}
              </>
            ) : (
              <p className="macro-card-sub">The comparison appears once your basket is ready.</p>
            )}

            <div className="coverage-explanation-banner">
              <div className="coverage-banner-icon">💡</div>
              <div className="coverage-banner-text">
                <strong>Understanding coverage:</strong> {coverage?.explanation || 'This basket covers dry groceries only. Fresh vegetables, fruit, dairy and other foods are expected to provide the rest.'}
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
              <p>Adjust pack quantities. Prices, pack sizes and stock come live from the store, and the total below is the total in your cart.</p>
            </div>

            <div className="basket-total-badge">
              <span>30-Day Total</span>
              <strong>{formatPrice(packTotal)}</strong>
            </div>
          </div>

          <div className="basket-items-list">
            {/* Stock Shortfall Alerts */}
            {stockShortfalls.length > 0 && (
              <div className="stock-shortfall-alert-box">
                <div className="alert-box-icon">⚠️</div>
                <div className="alert-box-content">
                  <strong>Availability notice:</strong>
                  <ul>
                    {stockShortfalls.map((s, idx) => (
                      <li key={idx}>{s}</li>
                    ))}
                  </ul>
                </div>
              </div>
            )}

            {cartErrors.length > 0 && (
              <div className="stock-shortfall-alert-box" role="alert">
                <div className="alert-box-icon">⚠️</div>
                <div className="alert-box-content">
                  <strong>Please review:</strong>
                  <ul>{cartErrors.map((t, idx) => <li key={idx}>{t}</li>)}</ul>
                </div>
              </div>
            )}
            {recommendations.length === 0 && !planState.loading && (
              <p className="interp-text">{issues.length ? 'Your basket appears once the member details are complete.' : 'No basket items yet.'}</p>
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
                    <button type="button" onClick={() => updateRecommendationQty(item.key, 1)} disabled={item.qty >= item.max_qty} title={item.qty >= item.max_qty ? 'No more in stock' : undefined} aria-label="Increase quantity">+</button>
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
              <span className="item-count-badge">{recommendations.length} Items Selected</span>
            </div>

            <div className="footer-right">
              <button type="button" className="btn btn-outline-fp" onClick={savePack}>
                💾 Save Configuration
              </button>
              <button
                type="button"
                className="btn btn-primary-fp"
                onClick={addPackToCart}
                disabled={addingPack || planStale || !recommendations.length || issues.length > 0}
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
