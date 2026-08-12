<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Cinema;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Báo cáo doanh thu theo Rạp/Ngày/Phim
     * Hỗ trợ Context-Aware Data Scoping theo rạp/khu vực.
     */
    public function revenue(Request $request)
    {
        $user = $request->user();
        
        $query = Booking::whereIn('booking_status', ['completed', 'paid']);

        // Data Scoping theo rạp được phân quyền
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:report', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->whereHas('showtime.room', function ($q) use ($allowedCinemaIds) {
                    $q->whereIn('cinema_id', $allowedCinemaIds);
                });
            }
        }

        if ($request->has('cinema_id')) {
            $cinemaId = $request->cinema_id;
            $cinema = Cinema::find($cinemaId);
            $query->whereHas('showtime.room', function ($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            });
        } else {
            $cinema = null;
        }

        $startDate = $request->input('start_date', $request->input('from_date'));
        $endDate = $request->input('end_date', $request->input('to_date'));

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($request->has('movie_id')) {
            $movieId = $request->movie_id;
            $query->whereHas('showtime', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            });
        }

        $totalRevenue = (float) (clone $query)->sum('final_amount');
        $bookingIds = (clone $query)->pluck('booking_id');
        
        $totalTicketsSold = (int) \App\Models\BookingSeat::whereIn('booking_id', $bookingIds)->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_revenue'      => $totalRevenue,
                'total_tickets_sold' => $totalTicketsSold,
                'cinema_name'        => $cinema ? ($cinema->cinema_name ?? 'Rạp') : 'Tất cả cụm rạp',
                'start_date'         => $startDate,
                'end_date'           => $endDate,
            ]
        ]);
    }
}
