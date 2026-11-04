import { apiClient, getAuthToken } from './client';

const LOCAL_KEY = 'prakruti_family_pack';

export const getLocalFamilyPack = () => {
  try {
    const saved = localStorage.getItem(LOCAL_KEY);
    return saved ? JSON.parse(saved) : null;
  } catch (error) {
    return null;
  }
};

export const saveLocalFamilyPack = (pack) => {
  try {
    localStorage.setItem(LOCAL_KEY, JSON.stringify({
      ...pack,
      saved_at: new Date().toISOString(),
    }));
  } catch (error) {
    // storage can be unavailable (private window); the server copy is the one that matters
  }
};

/** Flatten Laravel validation errors ({field: [msg]}) or a plain list into an array of strings. */
export const errorMessages = (res) => {
  const e = res?.errors;
  if (Array.isArray(e)) return e.map(String);
  if (e && typeof e === 'object') return Object.values(e).flat().map(String);
  return res?.message ? [res.message] : [];
};

const post = (path, payload) => apiClient(path, { method: 'POST', body: JSON.stringify(payload) });

export const familyPacksApi = {
  getLatest: async () => {
    if (!getAuthToken()) {
      return { success: true, data: null, requiresLogin: true };
    }

    const res = await apiClient('/family-packs/latest');
    if (res.success && res.data) saveLocalFamilyPack(res.data);
    return res;
  },

  /** Targets only (no products). */
  calculate: (payload) => post('/family-packs/calculate', payload),

  /** Targets, 30-day needs, real products with prices and nutrient coverage. */
  plan: (payload) => post('/family-packs/plan', payload),

  /** Authoritative price/stock check (and coverage) for an edited basket. */
  quote: (payload) => post('/family-packs/quote', payload),

  /** Adds every line to the cart, or none of them. */
  addToCart: async (payload) => {
    if (!getAuthToken()) {
      return { success: false, status: 401, requiresLogin: true, message: 'Please login or register to buy your family pack.' };
    }
    return post('/family-packs/add-to-cart', payload);
  },

  save: async (payload) => {
    if (!getAuthToken()) {
      return {
        success: false,
        status: 401,
        requiresLogin: true,
        message: 'Please login or register to save your family pack.',
      };
    }

    const res = await post('/family-packs', payload);
    if (res.success && res.data) saveLocalFamilyPack(res.data);

    return res;
  },
};
