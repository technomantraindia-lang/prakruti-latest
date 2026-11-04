# Family Pack — Phase 1 Gap Analysis (evidence, no code changed)

Branch: `family-pack-audit`. Date: 2026-11-04.
Evidence: real PHP run of the engine/parser/classifier, read-only SELECTs on the local Laragon MySQL (port 3307), and the official ICMR-NIN documents.

## 0. Baselines

| Item | Result |
|---|---|
| Existing test suite | 53 pass / 43 fail **before any change**. Failures come from a stale `DatabaseSeeder` (Coupon, Invoice and WooCommerce classes were removed from the app) and are in other modules. Not touched. |
| Family Pack tests | **None exist.** |
| Fresh SQLite schema | Builds correctly (products, variations, family_packs, carts). Family Pack tests will use it without the stale seeder. |
| Local catalog | 49 active products, **0 variations**, every product `weight=1.00 unit=pack`, **0 products with stored nutrition**, 0 saved family packs. |
| Local data is NOT production | Real pack sizes (500 g / 1 kg / 5 kg) and real nutrition are unknown to me. |

## 1. Source ledger (ICMR-NIN)

Primary sources downloaded and read:
- NIN, *A Brief Note on Nutrient Requirements for Indians (RDA 2020)* — https://www.nin.res.in/rdabook/brief_note.pdf
- ICMR-NIN, *Dietary Guidelines for Indians 2024* — https://nin.res.in/dietaryguidelines/pdfjs/locale/DGI_2024.pdf (Table 1.6, Tables 16.1/16.2, text on fibre/fat)

| Profile(s) in code | Code value | Official value | Status |
|---|---|---|---|
| Adult men energy S/M/H | 2110 / 2710 / 3470 | 2110 / 2710 / 3470 (RDA 2020 T1a) | **Confirmed** |
| Adult women energy S/M/H | 1660 / 2130 / 2720 | same | **Confirmed** |
| Children 4-18 energy | 1360 … 3320 | same (T1a) | **Confirmed** |
| Toddler 1-3 energy | 1010 | **1070** (RDA 2020); 1110 from diet (DGI T1.6) | **Wrong** |
| Toddler 1-3 protein | 11.3 g | **12.5 g** (RDA T2a) | **Wrong** |
| Toddler 1-3 oil | 15 g | **20 g** (DGI T1.6) | **Wrong** |
| Protein, men 54 / women 46 | 54 / 46 | 54.0 / 45.7 (0.83 g/kg, 65 kg / 55 kg) | Confirmed (rounding) |
| Protein, children/teens | various | T2a values (e.g. 4-6y 15.9, boys 10-12 31.8) | **Slightly off** (code 16, 32, 33…); to be replaced with exact values |
| Food groups, children/teens (cereal/pulse/nut/oil) | e.g. M1012 280/90/30/35 | DGI T1.6 identical | **Confirmed** |
| Elderly 60+ (cereal/pulse/nuts/oil) | M 170/75/30/25, F 140/70/30/15 | DGI T1.6 identical | **Confirmed** (energy 1750/1450 vs table ~1740/~1530: partly off) |
| Adults 19-49 sedentary/moderate food groups | M 230/75, 320/105; F 180/60, 250/85 | M 260/85, 370/120; F 190/60, 270/90 (DGI T1.6) | **Wrong** (code lower than guideline) |
| Adults "heavy work" food groups | 400/130, 320/105 | **No official row** | **Unsourced extrapolation** |
| Adults 50-59 (all values) | 2000/1600 kcal etc. | **No official profile exists** (RDA 2020 has only adult S/M/H) | **Unsourced** |
| Pregnant / lactating | 2010 / 2180 kcal | +350 pregnancy; lactating +600 (0-6 m) / +520 (7-12 m) on top of a base | Pregnancy plausible; lactating mixes the two periods |
| Fibre targets (15-50 g) | invented-looking | DGI: ~25 g per 1,000 kcal | **Unsourced** |
| Carbs / fat grams per profile | n/a | not given as RDA | Energy from P+C+F is 20-28% short of stated kcal in all 21 profiles → internally inconsistent |
| Salt cap | 5 g | max 5 g/day (DGI) | Confirmed |
| Cooking oil cap | 15-55 g | 20-50 g/person/day, 27 g for 2000 kcal | Consistent |
| Millet share | none | DGI: 20-30% of cereals should be millets (adults), 20% for children ≤10 | **Missing guidance** |

Also relevant: DGI T1.6 footnote and NIN note give "cereal : legume : milk = 3 : 1 : 2.5" for protein quality.

## 2. Requirement matrix

Legend: ✅ implemented & verified · ❌ implemented but incorrect · 🟡 partial · ⛔ missing · 🔒 cannot verify

| # | Requirement | Status | Evidence |
|---|---|---|---|
| 3 | Customer flow end-to-end | 🟡 | Flow exists in `FamilyPackPage.jsx`; saved packs restore members/household only, not edited quantities |
| 4 | Members: age 1-100, sex, height, weight, activity, flags | 🟡 | Missing `sex` crashes the backend (`Undefined array key "sex"`); blank height/weight default to 5'4"/60 kg; no dietary preference; "athlete" is in code but not in the brief |
| 4 | Adult BMI only; child safeguards | ❌ | Child gets adult BMI (8 y, 127 cm, 27 kg → 16.7); "Pediatric Growth Reference Applied" has no growth data behind it |
| 5 | 21 profiles verified against references | ❌ | See ledger: energy mostly right; toddler wrong; 50-59 and fibre unsourced; macros inconsistent |
| 5 | No double counting of activity | ❌ | Teens get an extra +10% protein on top of a table that already includes growth; athlete multiplier stacks on "high" profile |
| 5 | Smooth age transitions | ❌ | Active man 49 → 50: 3470 → 2200 kcal; 59 → 60: 2200 → 1925 |
| 6 | Household aggregation | ✅ | Sums verified (8,728 kcal / 205.2 g / 128 g reproduced). But displayed only in the JS copy |
| 6 | Target vs basket vs gap distinguished | ❌ | UI shows one bar; 70-125% is called "Optimal Target"; no "outside basket" explanation |
| 7A | Diabetes rule | ❌ | -50% sugar for the **whole household** if one member flags; min-quantity floors can undo it |
| 7B | BP rule + sendha namak caution | 🟡 | -20% whole-household salt; caution text present; wrong scope |
| 7C | Heart rule | ❌ | -10% oil for household; ghee split 75/25 or 88/12 heuristic; "A2 ghee … gut-nourishing butyric acid" claims in UI |
| 7D | Kidney warning only | ✅ | Warning shown, no automatic restriction |
| 7E | Multiple conditions | 🟡 | Flags are independent multipliers; no conflict handling |
| 8 | Food-group conversion | ❌ | Uses the stale JS copy; see 5 for base quantities |
| 8 | Realistic quantities | 🟡 | Seeds: 1 pack of every seed product regardless of need; pulses: ≥0.5 kg per product |
| 9 | Dynamic product discovery | 🟡 | Reads whole catalog client-side (`all=true`, no cap) — works for 49, not scalable; no server-side eligibility |
| 9 | Eligibility checks (status/stock/nutrition/verification) | 🟡 | Status + stock checked; nutrition/verification not checked |
| 10 | Nutrition from product data first | ❌ | **0/49 products have stored nutrition; the page falls back to a hardcoded name table labelled "Prakruti Certified Packaging Fact"** |
| 10 | Label parser | ❌ | Serving 30 g misread (22 g → 73.3 g), saturated fat as total fat, "Protein (g) 22" unparsed, kJ-first labels lose energy |
| 10 | Honest verification state | ❌ | Pasted text with no image is stamped "packaging_verified"; OCR success stamps "Certified" |
| 10 | OCR fail-safe | 🔒 | No Tesseract/pytesseract/easyocr on this machine; falls back to empty. Production unverified |
| 11 | Smart allocation | 🟡 | Pulses scored dynamically, but with invented defaults (20 g protein, 12 g fibre) for unknown products; cereals use 3 hardcoded slots (wheat/rice/millet), one product each; oil/ghee/sweetener/spice/salt use first product found |
| 11 | Explains why | ❌ | Reason text is mostly fixed marketing; turmeric text shown for any spice |
| 12 | Real pack sizes / units | ❌ | `weight` has no unit (1 → kg, ≤50 → kg else g); seeder sets 1 on everything; oil via density constant; `Math.round` can undershoot by up to ½ pack |
| 12 | Stock-aware quantities | ❌ | Qty is never limited to available stock; cart add can fail silently |
| 12 | Authoritative price | ❌ | Three different rules: JS helper, `CartApiController::resolveEffectivePrice`, `CheckoutApiController` (uses `sale_price ?? price`, trusting any sale value) |
| 12 | Total reconciles with cart | 🔒 | Cannot verify claimed ₹4,779 locally (no variations, no saved basket). Will be proven by test |
| 13 | Coverage honesty | ❌ | See row 6 |
| 13 | Root cause of 29.79 vs ~16 kg cereals | 🔒 | **Not reproducible locally.** Re-implemented rules on local data give ≈30 kg (13 kg wheat + 9 kg rice + 8 kg millet, 1 kg packs). So the 16 kg figure came from different catalog data. Real, confirmed contributors: `Math.round` under-rounds, one product per cereal slot, no stock cap. Needs the live pack sizes to settle |
| 14 | Antigravity claims | see below | |
| 15 | Tests | ⛔ | None exist |
| 16 | No deploy/prod access | ✅ | Nothing deployed, nothing written to any DB |

### Previous-agent claims, checked

| Claim | Result |
|---|---|
| 21 reference profiles | Present, but 1-3 y wrong, 50-59 unsourced, fibre unsourced, macros inconsistent |
| Adult BMI and pediatric safeguards | Adult BMI ok; pediatric safeguard is a text label only |
| Dynamic product discovery | Works on name/category keyword matching; classifier mislabels rice/dalia/soap/pickle |
| Admin nutrition + OCR | Admin form exists; OCR cannot run here; verification labels misleading |
| Effective sale price | JS and CartApi agree; Checkout differs |
| Cart reconciliation ₹4,779 | Unverifiable here |
| 16-product basket | Not reproducible (local catalog gives ~15 lines) |
| New product discovered | Plausible by design; will be tested |
| Local build and tests | No Family Pack tests exist; no automated check of any number |

## 3. Decisions that follow from the evidence

1. Reference table is rebuilt with a per-field **source tag** (`rda2020`, `dgi2024`, `derived`, `unsourced`). Unsourced fields stay clearly labelled or are derived by a documented rule (e.g. fibre = 25 g/1000 kcal; 50-59 interpolated between adult-moderate and elderly, shown as an estimate).
2. Adult 19-49 food-group grams switch to DGI Table 1.6 (sedentary/moderate). "Heavy" is scaled from moderate by energy ratio and labelled as derived.
3. Millet share rule (20-30% of cereals; 20% for children ≤10) becomes part of cereal allocation.
4. Missing local data (variations, nutrition) means tests must build their own fixtures; production behaviour must be rechecked after deployment.
