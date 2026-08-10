<?php

namespace App\Services;

use App\Models\Combo;
use App\Models\PricingRule;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Models\UserTier;
use App\Models\Voucher;
use Carbon\Carbon;

class PricingEngineService
{
    /**
     * Calculate financial summary and snapshot breakdown for booking.
     */
    public function calculateSummary(
        int $showtimeId,
        array $showtimeSeatIds,
        array $comboInputs = [],
        ?string $voucherCode = null,
        int $pointsUsed = 0,
        ?User $user = null
    ): array {
        $showtime = Showtime::with(['movie', 'room.cinema'])->findOrFail($showtimeId);
        $seats = ShowtimeSeat::with('seatType')
            ->whereIn('showtime_seat_id', $showtimeSeatIds)
            ->where('showtime_id', $showtimeId)
            ->orderBy('showtime_seat_id')
            ->get();

        $showtimeStart = Carbon::parse($showtime->showtime_start);
        $dayOfWeek = $showtimeStart->format('l'); // e.g. "Saturday"
        $timeStr = $showtimeStart->format('H:i'); // e.g. "19:00"

        // Fetch active pricing rules sorted by priority
        $activeRules = PricingRule::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        $ticketsBreakdown = [];
        $subtotalTickets = 0;

        foreach ($seats as $seat) {
            $basePrice = (float) $showtime->base_price;
            $surcharge = $seat->seatType ? (float) $seat->seatType->surcharge_amount : 0.0;

            $appliedRuleData = null;
            $ruleModifier = 0.0;

            foreach ($activeRules as $rule) {
                if ($this->matchesRuleConditions($rule, $dayOfWeek, $timeStr)) {
                    if ($rule->modifier_type === 'fixed_amount') {
                        $ruleModifier = (float) $rule->modifier_value;
                    } elseif ($rule->modifier_type === 'percentage') {
                        $ruleModifier = ($basePrice + $surcharge) * ((float) $rule->modifier_value / 100);
                    }

                    $appliedRuleData = [
                        'rule_id' => $rule->pricing_rule_id,
                        'name' => $rule->name,
                        'modifier_type' => strtoupper($rule->modifier_type === 'fixed_amount' ? 'FIXED' : 'PERCENT'),
                        'modifier_value' => (float) $rule->modifier_value,
                    ];
                    break; // Apply highest priority matched rule
                }
            }

            $finalSeatPrice = max(0, $basePrice + $surcharge + $ruleModifier);
            $subtotalTickets += $finalSeatPrice;

            $ticketsBreakdown[] = [
                'showtime_seat_id' => $seat->showtime_seat_id,
                'seat_number' => $seat->row_name . $seat->seat_number,
                'seat_type' => $seat->seat_type,
                'base_price' => (int) round($basePrice),
                'surcharge' => (int) round($surcharge),
                'applied_rule' => $appliedRuleData,
                'final_seat_price' => (int) round($finalSeatPrice),
            ];
        }

        // Combos subtotal
        $combosBreakdown = [];
        $subtotalCombos = 0;

        if (!empty($comboInputs)) {
            $comboIds = array_column($comboInputs, 'combo_id');
            $dbCombos = Combo::whereIn('combo_id', $comboIds)->where('is_active', true)->get()->keyBy('combo_id');

            foreach ($comboInputs as $cInput) {
                $cId = $cInput['combo_id'];
                $qty = (int) $cInput['quantity'];
                if (isset($dbCombos[$cId]) && $qty > 0) {
                    $combo = $dbCombos[$cId];
                    $unitPrice = (float) $combo->price;
                    $totalComboPrice = $unitPrice * $qty;
                    $subtotalCombos += $totalComboPrice;

                    $combosBreakdown[] = [
                        'combo_id' => $combo->combo_id,
                        'name' => $combo->name,
                        'unit_price' => (int) round($unitPrice),
                        'quantity' => $qty,
                        'total_combo_price' => (int) round($totalComboPrice),
                    ];
                }
            }
        }

        $totalSubtotal = $subtotalTickets + $subtotalCombos;
        $runningAmount = $totalSubtotal;

        // 1. Tier Discount
        $tierDiscountData = null;
        $tierDeducted = 0.0;

        if ($user) {
            $userTier = $user->tier_id ? UserTier::find($user->tier_id) : null;
            if (!$userTier && $user->point > 0) {
                $userTier = UserTier::where('min_points', '<=', $user->point)->orderByDesc('min_points')->first();
            }

            if ($userTier && (float) $userTier->discount_percent > 0) {
                $percent = (float) $userTier->discount_percent;
                $tierDeducted = min($runningAmount, $totalSubtotal * ($percent / 100));
                $runningAmount -= $tierDeducted;

                $tierDiscountData = [
                    'tier_name' => $userTier->tier_name,
                    'discount_percent' => $percent,
                    'deducted_amount' => (int) round($tierDeducted),
                ];
            }
        }

        // 2. Voucher Discount
        $voucherDiscountData = null;
        $voucherDeducted = 0.0;
        $voucherModel = null;

        if (!empty($voucherCode)) {
            $voucherModel = Voucher::where('code', $voucherCode)->where('is_active', true)->first();

            if ($voucherModel && $this->isVoucherValid($voucherModel, $totalSubtotal, $user)) {
                if ($voucherModel->discount_type === 'percentage') {
                    $voucherDeducted = $runningAmount * ((float) $voucherModel->discount_value / 100);
                    if ($voucherModel->max_discount_value) {
                        $voucherDeducted = min($voucherDeducted, (float) $voucherModel->max_discount_value);
                    }
                } else {
                    $voucherDeducted = (float) $voucherModel->discount_value;
                }

                $voucherDeducted = min($runningAmount, $voucherDeducted);
                $runningAmount -= $voucherDeducted;

                $voucherDiscountData = [
                    'voucher_code' => $voucherModel->code,
                    'discount_type' => strtoupper($voucherModel->discount_type === 'fixed_amount' ? 'FIXED' : 'PERCENT'),
                    'deducted_amount' => (int) round($voucherDeducted),
                ];
            }
        }

        // 3. Point Discount
        $pointDiscountData = null;
        $pointDeducted = 0.0;

        if ($pointsUsed > 0 && $user && $user->point >= $pointsUsed) {
            $pointDeducted = min($runningAmount, (float) $pointsUsed);
            $runningAmount -= $pointDeducted;

            $pointDiscountData = [
                'points_used' => $pointsUsed,
                'conversion_rate' => 1,
                'deducted_amount' => (int) round($pointDeducted),
            ];
        }

        $totalDiscountAmount = $tierDeducted + $voucherDeducted + $pointDeducted;
        $finalAmountToPay = max(0.0, $totalSubtotal - $totalDiscountAmount);

        return [
            'booking_summary' => [
                'booking_code' => 'CINEMA-' . strtoupper(substr(md5(uniqid()), 0, 6)),
                'showtime_id' => $showtimeId,
                'user_id' => $user ? $user->user_id : null,
            ],
            'items' => [
                'tickets' => $ticketsBreakdown,
                'combos' => $combosBreakdown,
            ],
            'financial_breakdown' => [
                'subtotal_tickets' => (int) round($subtotalTickets),
                'subtotal_combos' => (int) round($subtotalCombos),
                'total_subtotal' => (int) round($totalSubtotal),
                'discounts' => array_filter([
                    'tier_discount' => $tierDiscountData,
                    'voucher_discount' => $voucherDiscountData,
                    'point_discount' => $pointDiscountData,
                ]),
                'total_discount_amount' => (int) round($totalDiscountAmount),
                'final_amount_to_pay' => (int) round($finalAmountToPay),
            ],
            'metadata' => [
                'currency' => 'VND',
                'calculated_at' => now()->toIso8601String(),
                'is_zero_floor_enforced' => true,
            ],
            'voucher_id' => $voucherModel ? $voucherModel->voucher_id : null,
        ];
    }

    private function matchesRuleConditions(PricingRule $rule, string $dayOfWeek, string $timeStr): bool
    {
        if (empty($rule->conditions)) {
            return false;
        }

        $conds = is_string($rule->conditions) ? json_decode($rule->conditions, true) : $rule->conditions;
        if (!$conds) {
            return false;
        }

        if (isset($conds['days']) && is_array($conds['days'])) {
            if (!in_array($dayOfWeek, $conds['days'])) {
                return false;
            }
        }

        if (isset($conds['time_from']) && isset($conds['time_to'])) {
            if ($timeStr < $conds['time_from'] || $timeStr > $conds['time_to']) {
                return false;
            }
        }

        return true;
    }

    private function isVoucherValid(Voucher $voucher, float $subtotal, ?User $user): bool
    {
        $now = now();

        if ($voucher->valid_from && Carbon::parse($voucher->valid_from)->greaterThan($now)) {
            return false;
        }

        if ($voucher->valid_until && Carbon::parse($voucher->valid_until)->lessThan($now)) {
            return false;
        }

        if ($voucher->min_order_value && $subtotal < (float) $voucher->min_order_value) {
            return false;
        }

        if ($voucher->system_limit && $voucher->used_count >= $voucher->system_limit) {
            return false;
        }

        return true;
    }
}
