<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $bookingCode = $this->relationLoaded('booking') && $this->booking ? $this->booking->booking_code : null;

        $description = match ($this->action) {
            'earn_booking'       => "Tích lũy từ đơn hàng " . ($bookingCode ? "#" . $bookingCode : ""),
            'earn_register'      => "Thưởng chào mừng thành viên mới",
            'earn_birthday'      => "Quà tặng tri ân sinh nhật",
            'earn_referral'      => "Thưởng giới thiệu bạn bè",
            'earn_review'        => "Thưởng đánh giá & bình luận phim",
            'earn_event'         => "Điểm thưởng sự kiện",
            'spend_voucher'      => "Quy đổi mã giảm giá (Voucher)",
            'spend_combo'        => "Quy đổi combo bắp nước",
            'spend_seat_upgrade' => "Quy đổi nâng cấp hạng ghế",
            'spend_merch'        => "Quy đổi quà tặng lưu niệm",
            'deduct_expired'     => "Trừ điểm hết hạn sử dụng",
            'deduct_refund'      => "Thu hồi điểm do hoàn trả đơn hàng " . ($bookingCode ? "#" . $bookingCode : ""),
            default              => "Thay đổi điểm thưởng",
        };

        return [
            'id'          => $this->id,
            'bookingId'   => $this->booking_id,
            'bookingCode' => $bookingCode,
            'amount'      => $this->amount,
            'action'      => $this->action,
            'description' => $description,
            'createdAt'   => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
