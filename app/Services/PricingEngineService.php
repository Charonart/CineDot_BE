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
        ?User $user = null,
        ?string $existingBookingCode = null
    ): array {
        $showtime = Showtime::with(['movie', 'room.cinema'])->findOrFail($showtimeId);
        $seats = ShowtimeSeat::with('seat.seatType')
            ->whereIn('showtime_seat_id', $showtimeSeatIds)
            ->where('showtime_id', $showtimeId)
            ->orderBy('showtime_seat_id')
            ->get();

        $showtimeStart = Carbon::parse($showtime->showtime_start);
        $dayOfWeek = $showtimeStart->format('l'); // e.g. "Saturday"
        $timeStr = $showtimeStart->format('H:i'); // e.g. "19:00"
        $dateStr = $showtimeStart->format('Y-m-d'); // e.g. "2026-08-15"

        // Fetch active pricing rules sorted by priority (higher priority first) with caching
        $activeRules = \Illuminate\Support\Facades\Cache::remember('pricing_rules:active', 300, function () {
            return PricingRule::where('is_active', true)
                ->orderBy('priority', 'desc')
                ->get();
        });

        $ticketsBreakdown = [];
        $subtotalTickets = 0;

        foreach ($seats as $seat) {
            $basePrice = (float) $showtime->base_price;
            $physicalSeat = $seat->seat;
            $seatTypeModel = $physicalSeat?->seatType;
            $seatTypeStr = $physicalSeat?->seat_type ?? 'standard';
            $surcharge = $seatTypeModel ? (float) $seatTypeModel->surcharge_amount : 0.0;

            $appliedRuleData = null;
            $ruleModifier = 0.0;

            foreach ($activeRules as $rule) {
                if ($this->matchesRuleConditions($rule, $dayOfWeek, $timeStr, $dateStr, $seatTypeStr, $showtime, count($seats), $user)) {
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

            $rowName = $physicalSeat ? $physicalSeat->row_name : '';
            $seatNum = $physicalSeat ? (string) $physicalSeat->seat_number : '';

            $ticketsBreakdown[] = [
                'showtime_seat_id' => $seat->showtime_seat_id,
                'seat_number'      => $rowName . $seatNum,
                'seat_type'        => $seatTypeStr,
                'base_price'       => (int) round($basePrice),
                'surcharge'        => (int) round($surcharge),
                'applied_rule'     => $appliedRuleData,
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
            $userTier = UserTier::where('min_points', '<=', $user->total_points ?? 0)->orderByDesc('min_points')->first();

            if ($userTier && (float) $userTier->discount_percent > 0) {
                $percent = (float) $userTier->discount_percent;
                $tierDeducted = min($runningAmount, $totalSubtotal * ($percent / 100));
                $runningAmount -= $tierDeducted;

                $tierDiscountData = [
                    'tier_name' => $userTier->tier,
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

        $totalDiscountAmount = $tierDeducted + $voucherDeducted;
        $finalAmountToPay = max(0.0, $totalSubtotal - $totalDiscountAmount);

        // VAT calculation (VAT-inclusive: 5% for movie tickets, 8% for F&B concessions)
        $ticketVatRate = 0.05;
        $comboVatRate = 0.08;

        $ticketNet = round($subtotalTickets / (1 + $ticketVatRate));
        $ticketVat = $subtotalTickets - $ticketNet;

        $comboNet = round($subtotalCombos / (1 + $comboVatRate));
        $comboVat = $subtotalCombos - $comboNet;

        $totalVat = $ticketVat + $comboVat;

        return [
            'booking_summary' => [
                'booking_code' => $existingBookingCode ?: ('CINEMA-' . strtoupper(substr(md5(uniqid()), 0, 6))),
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
                'tier_discount_amount' => (int) round($tierDeducted),
                'voucher_discount_amount' => (int) round($voucherDeducted),
                'vat_breakdown' => [
                    'ticket_vat_rate' => 5,
                    'ticket_vat_amount' => (int) round($ticketVat),
                    'combo_vat_rate' => 8,
                    'combo_vat_amount' => (int) round($comboVat),
                    'total_vat_amount' => (int) round($totalVat),
                    'is_included_in_price' => true,
                ],
                'discounts' => array_filter([
                    'tier_discount' => $tierDiscountData,
                    'voucher_discount' => $voucherDiscountData,
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

    private function matchesRuleConditions(
        PricingRule $rule,
        string $dayOfWeek,
        string $timeStr,
        ?string $dateStr = null,
        ?string $seatType = null,
        ?Showtime $showtime = null,
        int $seatCount = 1,
        ?User $user = null
    ): bool {
        if (empty($rule->conditions)) {
            return false;
        }

        $conds = is_string($rule->conditions) ? json_decode($rule->conditions, true) : $rule->conditions;
        if (!is_array($conds) || empty($conds)) {
            return false;
        }

        // 1. Lọc theo thứ trong tuần: e.g. ["Saturday", "Sunday"] hoặc ["weekend"] / ["weekday"]
        if (isset($conds['days']) && is_array($conds['days'])) {
            $isWeekend = in_array($dayOfWeek, ['Saturday', 'Sunday']);
            $matchedDay = false;
            foreach ($conds['days'] as $day) {
                if (strcasecmp($day, $dayOfWeek) === 0) { $matchedDay = true; break; }
                if (strcasecmp($day, 'weekend') === 0 && $isWeekend) { $matchedDay = true; break; }
                if (strcasecmp($day, 'weekday') === 0 && !$isWeekend) { $matchedDay = true; break; }
            }
            if (!$matchedDay) {
                return false;
            }
        }

        // 2. Lọc theo ngày cụ thể (Lễ/Tết): e.g. ["2026-02-16", "2026-02-17"]
        if ($dateStr && isset($conds['dates']) && is_array($conds['dates'])) {
            if (!in_array($dateStr, $conds['dates'])) {
                return false;
            }
        }

        // 3. Lọc theo khoảng ngày (date_range): e.g. {"from": "2026-02-15", "to": "2026-02-20"}
        if ($dateStr && isset($conds['date_range']['from']) && isset($conds['date_range']['to'])) {
            if ($dateStr < $conds['date_range']['from'] || $dateStr > $conds['date_range']['to']) {
                return false;
            }
        }

        // 4. Lọc theo khung giờ: e.g. time_from: "18:00", time_to: "22:00" hoặc time_range
        $timeFrom = $conds['time_from'] ?? $conds['time_range']['from'] ?? null;
        $timeTo = $conds['time_to'] ?? $conds['time_range']['to'] ?? null;
        if ($timeFrom && $timeTo) {
            if ($timeStr < $timeFrom || $timeStr > $timeTo) {
                return false;
            }
        }

        // 5. Lọc theo loại ghế: e.g. ["vip", "couple"]
        if ($seatType && isset($conds['seat_types']) && is_array($conds['seat_types'])) {
            $normalizedSeatType = strtolower($seatType);
            $targetTypes = array_map('strtolower', $conds['seat_types']);
            if (!in_array($normalizedSeatType, $targetTypes)) {
                return false;
            }
        }

        // 6. Lọc theo Rạp áp dụng: e.g. cinema_ids: [1, 3]
        if ($showtime && isset($conds['cinema_ids']) && is_array($conds['cinema_ids'])) {
            $cinemaId = $showtime->room?->cinema_id;
            if ($cinemaId && !in_array($cinemaId, $conds['cinema_ids'])) {
                return false;
            }
        }

        // 7. Lọc theo số lượng vé tối thiểu: e.g. min_seats: 2
        if (isset($conds['min_seats']) && $seatCount < (int) $conds['min_seats']) {
            return false;
        }

        // 8. Lọc theo Người dùng cụ thể: e.g. user_ids: [4, 12, 105]
        if (isset($conds['user_ids']) && is_array($conds['user_ids'])) {
            if (!$user || !in_array($user->user_id, $conds['user_ids'])) {
                return false;
            }
        }

        // 9. Lọc theo Độ tuổi (HSSV / Người cao tuổi): e.g. min_age: 60, max_age: 100 hoặc max_age: 22 (HSSV)
        if (isset($conds['min_age']) || isset($conds['max_age'])) {
            $userBirth = $user?->birthday ?? $user?->birth_date ?? $user?->dob;
            if (!$userBirth) {
                return false;
            }
            $userAge = \Carbon\Carbon::parse($userBirth)->age;
            if (isset($conds['min_age']) && $userAge < (int) $conds['min_age']) {
                return false;
            }
            if (isset($conds['max_age']) && $userAge > (int) $conds['max_age']) {
                return false;
            }
        }

        // 10. Lọc theo Giới tính (Ưu đãi 8/3, 20/10): e.g. genders: ["female"]
        if (isset($conds['genders']) && is_array($conds['genders'])) {
            if (!$user || !$user->gender || !in_array(strtolower($user->gender), array_map('strtolower', $conds['genders']))) {
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
