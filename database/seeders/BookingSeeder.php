<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $priceBreakdown = [
            'booking_summary' => [
                'booking_code' => 'CND2026072101',
                'showtime_id' => 1,
                'user_id' => 3,
            ],
            'items' => [
                'tickets' => [
                    [
                        'seat_number' => 'A1',
                        'seat_type' => 'STD',
                        'base_price' => 90000,
                        'surcharge' => 0,
                        'applied_rule' => [
                            'rule_id' => 1,
                            'name' => 'Phụ thu cuối tuần',
                            'modifier_type' => 'FIXED',
                            'modifier_value' => 10000,
                        ],
                        'final_seat_price' => 100000,
                    ],
                    [
                        'seat_number' => 'A2',
                        'seat_type' => 'STD',
                        'base_price' => 90000,
                        'surcharge' => 0,
                        'applied_rule' => [
                            'rule_id' => 1,
                            'name' => 'Phụ thu cuối tuần',
                            'modifier_type' => 'FIXED',
                            'modifier_value' => 10000,
                        ],
                        'final_seat_price' => 100000,
                    ],
                ],
                'combos' => [
                    [
                        'combo_id' => 2,
                        'name' => 'Combo Đôi (Couple)',
                        'unit_price' => 119000,
                        'quantity' => 1,
                        'total_combo_price' => 119000,
                    ],
                ],
            ],
            'financial_breakdown' => [
                'subtotal_tickets' => 200000,
                'subtotal_combos' => 119000,
                'total_subtotal' => 319000,
                'discounts' => [
                    'tier_discount' => [
                        'tier_name' => 'Bronze',
                        'discount_percent' => 0,
                        'deducted_amount' => 0,
                    ],
                    'voucher_discount' => [
                        'voucher_code' => 'CINEDOT20',
                        'discount_type' => 'FIXED',
                        'deducted_amount' => 50000,
                    ],
                    'point_discount' => [
                        'points_used' => 20000,
                        'conversion_rate' => 1,
                        'deducted_amount' => 20000,
                    ],
                ],
                'total_discount_amount' => 70000,
                'final_amount_to_pay' => 249000,
            ],
            'metadata' => [
                'currency' => 'VND',
                'calculated_at' => '2026-07-21T21:00:00+07:00',
                'is_zero_floor_enforced' => true,
            ],
        ];

        $bookings = [
            [
                'booking_id' => 1,
                'user_id' => 3,
                'showtime_id' => 1,
                'voucher_id' => 1,
                'price_breakdown' => json_encode($priceBreakdown),
                'final_amount' => 249000.00,
                'discount_amount' => 70000.00,
                'booking_status' => 'confirmed',
                'booking_code' => 'CND2026072101',
                'notes' => 'Thanh toán MoMo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('bookings')->insertOrIgnore($bookings);
    }
}
