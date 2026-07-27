{
"booking_summary": {
"booking_code": "CINEMA-8A9B2C",
"showtime_id": 105,
"user_id": 992
},
"items": {
"tickets": [
{
"seat_number": "H10",
"seat_type": "VIP",
"base_price": 80000,
"surcharge": 20000,
"applied_rule": {
"rule_id": 12,
"name": "Phụ thu cuối tuần",
"modifier_type": "FIXED",
"modifier_value": 10000
},
"final_seat_price": 110000
},
{
"seat_number": "H11",
"seat_type": "VIP",
"base_price": 80000,
"surcharge": 20000,
"applied_rule": {
"rule_id": 12,
"name": "Phụ thu cuối tuần",
"modifier_type": "FIXED",
"modifier_value": 10000
},
"final_seat_price": 110000
}
],
"combos": [
{
"combo_id": 5,
"name": "Couple Combo 2 Bắp 2 Nước",
"unit_price": 150000,
"quantity": 1,
"total_combo_price": 150000
}
]
},
"financial_breakdown": {
"subtotal_tickets": 220000,
"subtotal_combos": 150000,
"total_subtotal": 370000,
"discounts": {
"tier_discount": {
"tier_name": "GOLD",
"discount_percent": 5,
"deducted_amount": 18500
},
"voucher_discount": {
"voucher_code": "MEGA-WEEKEND-50K",
"discount_type": "FIXED",
"deducted_amount": 50000
},
"point_discount": {
"points_used": 10000,
"conversion_rate": 1,
"deducted_amount": 10000
}
},
"total_discount_amount": 78500,
"final_amount_to_pay": 291500
},
"metadata": {
"currency": "VND",
"calculated_at": "2026-07-20T19:59:07+07:00",
"is_zero_floor_enforced": true
}
}

Block "tickets": Bắt buộc phải chia tách thành từng ghế (dù cùng loại) vì trong thực tế, hệ thống có thể có khuyến mãi kiểu "Mua 1 vé tặng 1 vé" (lúc này final_seat_price của ghế H11 sẽ bằng 0).
deducted_amount: Tính toán cụ thể số tiền trừ đi cho từng loại khuyến mãi, giúp UI Frontend hiển thị bill cực kỳ dễ dàng (ví dụ: gạch ngang hiển thị chữ đỏ "- 50.000đ từ Voucher").
is_zero_floor_enforced: Cờ (Flag) lưu vết để xác nhận rằng hệ thống đã chạy qua hàm kiểm tra final_amount >= 0 (Luật R02 ở bảng trước), đảm bảo không bao giờ để khách thanh toán số tiền âm.
