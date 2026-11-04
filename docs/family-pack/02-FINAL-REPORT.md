# Family Pack — Final Report (branch `family-pack-audit`, nothing deployed, nothing committed)

## 1. How the Family Pack works now (plain English)

1. **Each person** is matched to an ICMR-NIN reference group (age, sex, activity). Energy and protein come straight from the RDA 2020 tables; raw food amounts (grains, pulses, nuts, cooking fat) come from the Dietary Guidelines 2024 Table 1.6. Every number in `backend/config/family_pack.php` carries its source and whether it is *official*, *derived* or an *estimate*.
2. **Health flags** make small changes for that person only: diabetes −25% added sugar, high BP −10% salt, heart −5% fat and less ghee. A person with several flags never gets stacked cuts on the same food. Kidney conditions change nothing and show a "professional review" warning. All percentages live in one config block for a dietitian to approve.
3. **Household totals** = sum of members. 30-day need = daily × 30, adjusted for home-cooking habits. Millets are given 20–30% of grains (ICMR guidance).
4. **Products** are read live from the database (any active product with a food group, known pack size, price and stock). Nothing is hard-coded to a product name, so a product added tomorrow is picked up automatically.
5. **Allocation**: each group's need is shared across products by protein/fibre content, limited by stock and per-person caps (chia, flax), a maximum number of products per group, and no token amounts. Real pack sizes are chosen so the group total lands within a few percent of the need.
6. **Coverage** compares what the basket provides with the household's reference needs, split into "from product labels" and "estimate", with the remaining gap and an explanation that fresh foods are expected to fill it.
7. **Price** is one shared rule (sale price counts only if > 0 and below the regular price). The page shows backend prices; the cart subtotal equals the page total.
8. **Add to cart** is all-or-nothing and counts stock already in the cart.

## 2. Worked example: Father 45 (heavy work), Mother 40 (sedentary), Son 12, Grandmother 70 (BP flag)

| Person | Reference group | kcal/day | Protein g | Cereals g | Pulses g | Nuts g | Visible fat g |
|---|---|---|---|---|---|---|---|
| Father | Adult man, heavy work (derived) | 3,470 | 54.0 | 474 | 154 | 50 | 50 |
| Mother | Adult woman, sedentary | 1,660 | 45.7 | 190 | 60 | 30 | 25 |
| Son | Boy 10–12 | 2,220 | 31.8 | 280 | 90 | 30 | 35 |
| Grandmother | Older woman 60+ | 1,530 | 45.7 | 140 | 70 | 30 | 15 |
| **Household** | | **8,880** | **177.2** | 1,084 | 374 | 140 | 125 |

30-day needs: cereals 32.52 kg, pulses 11.22 kg, nuts/seeds 4.2 kg, oil 3.07 L, ghee 1.03 L, sugar 3.33 kg, salt 0.59 kg, spices 0.72 kg. (Earlier test output was 29.79 / 10.77 / 3.99: lower because adult sedentary/moderate amounts were below the Guidelines.)

Basket against a realistic fixture catalog (pack sizes and prices below are test fixtures, not live prices): 32 lines, groups filled to 97–107% (pack granularity: 1 kg salt for a 0.59 kg need), coverage energy 79%, protein 128%, fibre 67%, carbohydrate 68%, fat 86%. Protein above 100% is consistent with the Guidelines' own food-group table (72 g protein from food groups against a 54 g RDA).

## 3. Root cause of "29.79 kg target but ~16 kg basket"

The live catalog (read from the public API) has **5 packs in stock per 1 kg item** (wheat, rice, millets). A stock-limited cereal basket is therefore about 15–16 kg. The old page did not enforce stock, so the number most likely came from an older stock-aware version tested against that catalog; this cannot be proven from the supplied ZIP. The new engine fills what stock allows, passes unfilled shares to other products and reports the shortfall in words.

## 4. Changes made (files)

Backend, new: `config/family_pack.php`, `app/Support/PriceRules.php`, `app/Support/PackSize.php`, `app/Services/FamilyPack/{FamilyPackCatalog,FamilyPackBasketBuilder,FamilyPackPlanner,FamilyPackQuote}.php`, migration `2026_11_04_000001_add_net_quantity_to_products_and_variations.php`.
Backend, rewritten: `SmartFamilyGroceryEngine.php`, `ProductNutritionService.php`, `FamilyPackApiController.php` (plan / quote / add-to-cart / validation).
Backend, edited: `routes/api.php`, `bootstrap/app.php` (validation errors now return 422 instead of 500), `CartApiController`, `CheckoutApiController`, `CustomerOrderApiController`, `Frontend/CartController`, `ProductResource`, `Product`, `ProductVariation` (all price through `PriceRules`), admin product form and variation form/controller (pack contents, honest nutrition status, food-group override).
Backend, removed: `ocr_extract.py`, `ocr_extract.ps1` (Tesseract only now, with a timeout, safe failure to manual entry).
Frontend: `FamilyPackPage.jsx` (display layer over the backend; 2,462 → about 1,300 lines; browser no longer downloads the whole catalog), `api/familyPacks.js`.
Tests: 187 Family Pack tests plus fixtures (`tests/Support/FamilyPackFixtures.php`).

## 5. Test evidence

| Run | Result |
|---|---|
| Family Pack backend tests | **187 passed** (803 assertions) |
| Engine, parser, pack-size, price unit tests (subset of the above) | 133 passed |
| Full backend suite | 240 passed / **43 failed — identical count to before any change**. All 43 come from a stale `DatabaseSeeder` (Coupon, Invoice, WooCommerce classes were removed from the app) in other modules. Not touched. |
| Frontend production build | passes |
| Real-browser test (Chrome, scratch database, real backend) | **27 / 27 checks**: family entered through the form, 32-line basket, targets match hand calculation, quantity edit re-prices exactly, remove line, save → reload restores the edited basket, add to cart: **page total ₹9,213 = cart subtotal ₹9,213**, no horizontal scroll at 375 and 768 px, no console errors, none of the old claims on the page |
| Fresh database migration | full chain incl. new migration applies on SQLite |

Notable covered cases: missing sex (422, not a crash), child BMI, no age-50 cliff, kidney = no change, combined conditions, >100 products (120 extra pulses → still ≤ 6 pulse products), out-of-stock/inactive, new admin product discovered, pack rounding, oils in litres vs kg, sale price 0 / above regular, all-or-nothing cart, existing cart stock, admin form → nutrition → API → basket → cart.

## 6. Remaining risks and decisions

1. **ICMR sources**: values are traceable to the two official documents, but heavy-work food groups, ages 55–59 blending, older-adult activity uplift, fat/carbohydrate split, sugar and salt allowances are marked *derived* or *estimate*. A dietitian should approve the health percentages in `config/family_pack.php`.
2. **Production data**: the live catalog has no nutrition on any product (all basket nutrition is therefore a labelled group estimate until admins enter labels), no cooking oils other than ghee, no sweetener or salt, ghee has no pack size, and some variation labels are bare numbers (`250`, `500`). These show as shortfalls or are read as grams and flagged. Admins should set pack contents.
3. **Tesseract** is not installed here, so OCR was not exercised against real label images (the parser is tested on text). Install it on the server and set `TESSERACT_PATH` if it is not on PATH; without it, admins type values.
4. **Existing records** stamped "packaging_verified" by the old code (any pasted text) remain labelled as before until an admin re-saves them; consider re-saving or auditing.
5. **"Nutrition goal"** was removed from the form because it had no effect on the new calculation (stored for old saved packs only). Confirm this is acceptable.
6. **Pre-existing failing tests** (43) and the stale `DatabaseSeeder` are outside this work.
7. **API exception change** (`bootstrap/app.php`) affects all `/api` routes: validation errors now return 422 with field errors, not 500. Please regression-test the storefront forms on staging.

## 6b. Review round (flow check, glitches, optimisation)

| Finding | Fix | Evidence |
|---|---|---|
| One plan request made **345 database queries** with 176 products (341 were a repeated per-variation stock lookup) | Stock accessors reuse eager-loaded batches (same stock rules); catalog and quote eager-load | Query count is now constant as the catalog grows (test); stock identical with and without eager loading, incl. expired-only batches (3 tests) |
| Typing in the **name** box re-planned and **wiped the customer's basket edits** | Name/id no longer trigger a new plan | Browser probe: qty 2 → 2 |
| **Add to Cart stayed clickable** while the plan recalculated (stale basket could be added) | Button disabled until the new plan is shown (also after a plan error) | Browser probe |
| Clearing the age box snapped it to 1 | Raw text is kept; empty age shows a message | Browser probe |
| Restoring a saved basket could be lost if the family changed during the restore | Saved basket is kept for the next plan run | Code path |

Result: 191 Family Pack tests, 27/27 full browser flow, 6/6 glitch probes. Full backend suite unchanged at 43 pre-existing failures.

Known and left as is: adding the same basket to the cart twice doubles quantities (merging is deliberate, stock is checked); `/family-packs/plan` is public but protected by the existing 60/min API throttle.

## 7. Deployment readiness

**Ready for staging, not for production.** Before production: run the migration on staging, set pack contents for live products, enter label nutrition, install Tesseract, have a dietitian review the health adjustments, and regression-test checkout and login forms. Nothing was deployed, no production database or credentials were touched, and no payment was triggered.
