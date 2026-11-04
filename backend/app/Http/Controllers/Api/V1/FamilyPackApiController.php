<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\FamilyPack;
use App\Services\FamilyPack\FamilyPackPlanner;
use App\Services\FamilyPack\FamilyPackQuote;
use App\Services\SmartFamilyGroceryEngine;
use App\Support\PriceRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FamilyPackApiController extends Controller
{
    private const MEMBER_RULES = [
        'members' => 'required|array|min:1|max:15',
        'members.*.id' => 'nullable',
        'members.*.name' => 'nullable|string|max:80',
        'members.*.age' => 'required|integer|min:1|max:120',
        'members.*.sex' => 'nullable|in:male,female',
        'members.*.heightCm' => 'nullable|numeric|min:50|max:250',
        'members.*.heightFt' => 'nullable|numeric|min:0|max:8',
        'members.*.heightIn' => 'nullable|numeric|min:0|max:11.99',
        'members.*.weightKg' => 'nullable|numeric|min:1|max:250',
        'members.*.activity' => 'nullable|string|max:30',
        'members.*.lifeStage' => 'nullable|in:none,pregnant,lactating',
        'members.*.goal' => 'nullable|string|max:50',
        'members.*.diabetes' => 'nullable|boolean',
        'members.*.bp' => 'nullable|boolean',
        'members.*.heart' => 'nullable|boolean',
        'members.*.kidney' => 'nullable|boolean',
        'household' => 'nullable|array',
    ];

    /** @return array{members: array, household: array} */
    private function validatedFamily(Request $request): array
    {
        $validated = $request->validate(self::MEMBER_RULES);
        foreach ($validated['members'] as $i => $m) {
            if ((int) ($m['age'] ?? 0) >= 10 && empty($m['sex'] ?? null)) {
                throw ValidationException::withMessages(["members.{$i}.sex" => 'Please select male or female for members aged 10 and above.']);
            }
        }

        return ['members' => $validated['members'], 'household' => $validated['household'] ?? []];
    }

    /** Targets and 30-day needs only (no products). */
    public function calculate(Request $request, SmartFamilyGroceryEngine $engine): JsonResponse
    {
        $family = $this->validatedFamily($request);

        return response()->json([
            'success' => true,
            'message' => 'Family nutrition plan calculated.',
            'data' => $engine->calculateBasket($family['members'], $family['household']),
        ]);
    }

    /** Targets, needs, real products, prices and nutrient coverage in one call. */
    public function plan(Request $request, FamilyPackPlanner $planner): JsonResponse
    {
        $family = $this->validatedFamily($request);

        return response()->json([
            'success' => true,
            'message' => 'Family pack planned.',
            'data' => $planner->plan($family['members'], $family['household']),
        ]);
    }

    /** Authoritative price/stock check (and nutrient coverage) for an edited basket. */
    public function quote(Request $request, FamilyPackQuote $quote, FamilyPackPlanner $planner): JsonResponse
    {
        $data = $request->validate($this->lineRules() + ['monthly_nutrients' => 'nullable|array']);
        $result = $quote->quote($data['items']);
        $result['coverage'] = isset($data['monthly_nutrients'])
            ? $planner->coverage(array_values(array_filter($result['lines'], fn ($l) => $l['ok'])), $data['monthly_nutrients'])
            : null;

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Adds the whole basket to the cart or nothing at all.
     * Every line is checked (active, stock including what is already in the cart, price) before anything is written.
     */
    public function addToCart(Request $request, FamilyPackQuote $quote): JsonResponse
    {
        $data = $request->validate($this->lineRules());
        $user = $request->user();

        $result = $quote->quote($data['items']);
        if (! $result['all_ok']) {
            return response()->json([
                'success' => false,
                'message' => 'Some items could not be added, so nothing was added to your cart.',
                'errors' => $result['errors'],
                'lines' => $result['lines'],
            ], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => $user->id], ['session_id' => $request->header('X-Session-ID')]);

        // merge duplicates of the same product/pack inside the request
        $wanted = [];
        foreach ($result['lines'] as $l) {
            $k = $l['product_id'] . ':' . ($l['var_id'] ?? 0);
            $wanted[$k] = ['line' => $l, 'qty' => ($wanted[$k]['qty'] ?? 0) + $l['qty']];
        }

        $problems = [];
        foreach ($wanted as $w) {
            $l = $w['line'];
            $existing = (int) CartItem::where('cart_id', $cart->id)->where('product_id', $l['product_id'])->where('var_id', $l['var_id'])->value('qty');
            if ($existing + $w['qty'] > $l['max_qty']) {
                $problems[] = "{$l['name']}: only {$l['max_qty']} available" . ($existing ? " ({$existing} already in your cart)." : '.');
            }
        }
        if ($problems) {
            return response()->json([
                'success' => false,
                'message' => 'Some items could not be added, so nothing was added to your cart.',
                'errors' => $problems,
            ], 422);
        }

        DB::transaction(function () use ($wanted, $cart) {
            foreach ($wanted as $w) {
                $l = $w['line'];
                $item = CartItem::where('cart_id', $cart->id)->where('product_id', $l['product_id'])->where('var_id', $l['var_id'])->lockForUpdate()->first();
                if ($item) {
                    $item->update(['qty' => $item->qty + $w['qty']]);
                } else {
                    $product = \App\Models\Product::find($l['product_id']);
                    $variation = $l['var_id'] ? \App\Models\ProductVariation::find($l['var_id']) : null;
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $l['product_id'],
                        'var_id' => $l['var_id'],
                        'qty' => $w['qty'],
                        'price' => PriceRules::forItem($product, $variation),
                        'sale_price' => $variation?->sale_price ?? $product->sale_price,
                    ]);
                }
            }
        });

        $cartData = app(CartApiController::class)->index($request)->getData(true)['data'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Family pack added to your cart.',
            'data' => [
                'cart' => $cartData,
                'added_subtotal' => $result['subtotal'],
                'added_lines' => count($result['lines']),
            ],
        ]);
    }

    private function lineRules(): array
    {
        return [
            'items' => 'required|array|min:1|max:80',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.var_id' => 'nullable|integer|exists:product_variations,id',
            'items.*.qty' => 'required|integer|min:1|max:200',
        ];
    }

    public function latest(Request $request): JsonResponse
    {
        if (! Schema::hasTable('family_packs')) {
            return response()->json(['success' => true, 'message' => 'No family pack saved yet.', 'data' => null]);
        }

        $pack = FamilyPack::where('user_id', $request->user()->id)->where('status', 'active')->latest()->first();

        return response()->json([
            'success' => true,
            'message' => $pack ? 'Family pack fetched successfully.' : 'No family pack saved yet.',
            'data' => $pack ? $this->serialize($pack) : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile' => 'required|array',
            'profile.members' => 'required|array|min:1|max:15',
            'profile.members.*.id' => 'nullable',
            'profile.members.*.name' => 'nullable|string|max:80',
            'profile.members.*.age' => 'required|integer|min:1|max:120',
            'profile.members.*.sex' => 'nullable|string|max:20',
            'profile.members.*.heightCm' => 'nullable|numeric|min:0|max:250',
            'profile.members.*.heightFt' => 'nullable|numeric|min:0|max:8',
            'profile.members.*.heightIn' => 'nullable|numeric|min:0|max:11.99',
            'profile.members.*.weightKg' => 'nullable|numeric|min:0|max:250',
            'profile.members.*.activity' => 'nullable|string|max:50',
            'profile.members.*.lifeStage' => 'nullable|string|max:50',
            'profile.members.*.goal' => 'nullable|string|max:50',
            'profile.members.*.diabetes' => 'nullable|boolean',
            'profile.members.*.bp' => 'nullable|boolean',
            'profile.members.*.heart' => 'nullable|boolean',
            'profile.members.*.kidney' => 'nullable|boolean',
            'profile.household' => 'nullable|array',
            'profile.interpretation' => 'nullable|string',
            'nutrient_summary' => 'nullable|array',
            'recommendations' => 'required|array|min:1|max:80',
            'recommendations.*.product_id' => 'required|exists:products,id',
            'recommendations.*.var_id' => 'nullable|exists:product_variations,id',
            'recommendations.*.name' => 'required|string|max:255',
            'recommendations.*.qty' => 'required|integer|min:1|max:200',
            'recommendations.*.price' => 'nullable|numeric|min:0',
            'recommendations.*.label' => 'nullable|string|max:120',
            'recommendations.*.benefit' => 'nullable|string|max:255',
            'recommendations.*.package_size' => 'nullable|string|max:120',
            'recommendations.*.pack_weight' => 'nullable|numeric|min:0',
            'recommendations.*.manual' => 'nullable|boolean',
            'monthly_total' => 'nullable|numeric|min:0',
        ]);

        $nextPurchaseAt = now()->addDays(30);
        $nextReminderAt = now()->addDays(27);

        if (! Schema::hasTable('family_packs')) {
            return response()->json([
                'success' => true,
                'message' => 'Family pack could not be saved on the server yet. Please run the database migrations.',
                'data' => null,
            ], 503);
        }

        $pack = FamilyPack::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'profile' => $validated['profile'],
                'nutrient_summary' => $validated['nutrient_summary'] ?? [],
                'recommendations' => $validated['recommendations'],
                'monthly_total' => $validated['monthly_total'] ?? 0,
                'next_purchase_at' => $nextPurchaseAt,
                'next_reminder_at' => $nextReminderAt,
                'reminder_sent_at' => null,
                'status' => 'active',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Family pack saved successfully.',
            'data' => $this->serialize($pack->fresh()),
        ]);
    }

    private function serialize(FamilyPack $pack): array
    {
        return [
            'id' => $pack->id,
            'profile' => $pack->profile,
            'nutrient_summary' => $pack->nutrient_summary,
            'recommendations' => $pack->recommendations,
            'monthly_total' => (float) $pack->monthly_total,
            'next_purchase_at' => $pack->next_purchase_at?->toISOString(),
            'next_reminder_at' => $pack->next_reminder_at?->toISOString(),
            'reminder_sent_at' => $pack->reminder_sent_at?->toISOString(),
            'status' => $pack->status,
        ];
    }
}
