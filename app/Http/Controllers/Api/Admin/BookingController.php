<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\ShowtimeSeat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Thống kê tổng hợp số liệu đơn đặt vé (Admin)
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        $query = Booking::query();

        // Data Scoping theo rạp/khu vực được phân quyền
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:booking', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->whereHas('showtime.room', function ($rq) use ($allowedCinemaIds) {
                    $rq->whereIn('cinema_id', $allowedCinemaIds);
                });
            }
        }

        $todayStr = now()->toDateString();

        $stats = (clone $query)->selectRaw("
            COUNT(*) as total_bookings,
            COALESCE(SUM(CASE WHEN booking_status IN ('completed', 'paid') THEN final_amount ELSE 0 END), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN booking_status IN ('completed', 'paid') AND DATE(created_at) = '{$todayStr}' THEN final_amount ELSE 0 END), 0) as today_revenue,
            COUNT(CASE WHEN checked_in_at IS NOT NULL THEN 1 END) as total_checked_in,
            COUNT(CASE WHEN booking_status IN ('cancelled', 'refunded') THEN 1 END) as total_refunded
        ")->first();

        $totalBookings = (int) ($stats->total_bookings ?? 0);
        $totalRevenue = (float) ($stats->total_revenue ?? 0);
        $todayRevenue = (float) ($stats->today_revenue ?? 0);
        $totalCheckedIn = (int) ($stats->total_checked_in ?? 0);
        $totalRefunded = (int) ($stats->total_refunded ?? 0);
        $checkInRate = $totalBookings > 0 ? round(($totalCheckedIn / $totalBookings) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'totalBookings'   => $totalBookings,
                'totalRevenue'    => $totalRevenue,
                'todayRevenue'    => $todayRevenue,
                'totalCheckedIn'  => $totalCheckedIn,
                'totalRefunded'   => $totalRefunded,
                'checkInRate'     => $checkInRate,
            ]
        ]);
    }

    /**
     * Danh sách tất cả đơn đặt vé (Admin) - Standardized with FilterableAndSortable & Context Scoping
     */
    public function index(Request $request)
    {
        $allowedFilters = ['booking_code', 'booking_status', 'final_amount', 'discount_amount', 'created_at', 'checked_in_at', 'status'];
        $allowedSorts = ['booking_id', 'id', 'booking_code', 'final_amount', 'created_at', 'booking_status'];
        $searchableFields = ['booking_code'];
        $columnAliases = ['id' => 'booking_id', 'status' => 'booking_status', 'total_amount' => 'final_amount'];

        $query = Booking::with(['user', 'showtime.movie', 'showtime.room.cinema', 'bookingSeats.showtimeSeat', 'bookingCombos.combo', 'voucher']);

        // Data Scoping theo rạp/khu vực được phân quyền
        $user = $request->user();
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:booking', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->whereHas('showtime.room', function ($rq) use ($allowedCinemaIds) {
                    $rq->whereIn('cinema_id', $allowedCinemaIds);
                });
            }
        }

        // Backward compatibility status query
        if ($request->has('status') && $request->status !== 'ALL' && !$request->has('filters.status')) {
            $st = $request->status;
            if ($st === 'checked_in') {
                $query->whereNotNull('checked_in_at')->whereNotIn('booking_status', ['cancelled', 'refunded']);
            } elseif ($st === 'completed' || $st === 'paid') {
                $query->whereIn('booking_status', ['completed', 'paid']);
            } else {
                $query->where('booking_status', $st);
            }
        }

        // Search with User fields
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('fullname', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                  });
            });
        }

        $query->applyDataTableQuery($request, $allowedFilters, $allowedSorts, [], $columnAliases);

        $perPage = (int) $request->get('per_page', $request->get('limit', 15));
        $page = (int) $request->get('page', 1);
        $bookings = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($bookings->items())->map(function ($b) {
            $arr = $b->toArray();
            $arr['id'] = $b->booking_id;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
                'totalPages'   => $bookings->lastPage(),
                'totalResults' => $bookings->total(),
            ],
            'pagination' => [
                'page'       => $bookings->currentPage(),
                'perPage'    => $bookings->perPage(),
                'total'      => $bookings->total(),
                'totalPages' => $bookings->lastPage(),
            ]
        ]);
    }

    /**
     * Chi tiết đơn đặt vé (Admin)
     */
    public function show(string $id, Request $request)
    {
        $booking = Booking::with([
            'user',
            'showtime.movie',
            'showtime.room.cinema',
            'bookingSeats.showtimeSeat',
            'bookingCombos.combo',
            'voucher'
        ])->findOrFail($id);

        $user = $request->user();
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:booking', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $cinemaId = $booking->showtime?->room?->cinema_id;
                if (!in_array($cinemaId, $allowedCinemaIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Bạn không có quyền truy cập đơn đặt vé của rạp này.'
                    ], 403);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $booking
        ]);
    }

    /**
     * Xử lý hoàn tiền sự cố (Admin Refund)
     */
    public function refund(Request $request, string $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $booking = Booking::with(['user', 'bookingSeats.showtimeSeat', 'voucher'])->findOrFail($id);

        if (!in_array($booking->booking_status, ['paid', 'completed', 'cancelling'])) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có thể hoàn tiền cho đơn đã thanh toán hoặc đang yêu cầu hủy.'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Cập nhật trạng thái đơn sang refunded
            $booking->update([
                'booking_status' => 'refunded',
                'notes'          => 'Admin hoàn tiền: ' . $request->reason,
            ]);

            // 2. Nhả ghế trong showtime_seats
            $showtimeSeatIds = BookingSeat::where('booking_id', $booking->booking_id)
                ->pluck('showtime_seat_id');

            if ($showtimeSeatIds->isNotEmpty()) {
                ShowtimeSeat::whereIn('showtime_seat_id', $showtimeSeatIds)
                    ->update(['status' => 'available']);
            }

            // 3. Trừ lại điểm tích lũy của user nếu có
            $user = $booking->user;
            $earnedPoints = (int) round(($booking->final_amount ?? 0) / 10000);
            if ($user && $earnedPoints > 0) {
                $userObj = \App\Models\User::where('user_id', $user->user_id)->lockForUpdate()->first();
                if ($userObj) {
                    $userObj->decrement('total_points', $earnedPoints);
                }
            }

            // 4. Khôi phục lượt dùng voucher nếu có
            if ($booking->voucher_id) {
                \App\Models\Voucher::where('voucher_id', $booking->voucher_id)
                    ->where('used_count', '>', 0)
                    ->decrement('used_count');
            }

            DB::commit();

            // Real-time Revenue Updated Broadcast (Triggered after DB Commit)
            $booking->loadMissing('showtime.room');
            \App\Events\RevenueUpdated::dispatchSafely(
                $booking->booking_id,
                'booking_refunded',
                $booking->showtime?->room?->cinema_id,
                $booking->showtime?->movie_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Xử lý hoàn tiền sự cố thành công.',
                'data'    => $booking->fresh(['user', 'showtime.movie', 'showtime.room.cinema', 'bookingSeats.showtimeSeat', 'bookingCombos.combo', 'voucher'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hoàn tiền: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions for bookings
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Danh sách ID không được để trống.'
            ], 422);
        }

        $count = count($ids);

        switch ($action) {
            case 'cancel':
                Booking::whereIn('booking_id', $ids)->whereNotIn('booking_status', ['completed', 'refunded'])->update(['booking_status' => 'cancelled']);
                $msg = "Đã hủy {$count} đơn đặt vé.";
                break;

            case 'check_in':
                Booking::whereIn('booking_id', $ids)->whereNull('checked_in_at')->update(['checked_in_at' => now()]);
                $msg = "Đã soát vé check-in cho {$count} đơn đặt vé.";
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => "Hành động không hợp lệ: {$action}"
                ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }
}
