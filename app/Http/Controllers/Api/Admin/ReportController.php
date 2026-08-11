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
     * RBAC: view:cinema_report. Tự động kẹp WHERE cinema_id = {user.cinema_id} nếu có.
     */
    public function revenue(Request $request)
    {
        $user = $request->user();
        
        $query = Booking::where('booking_status', 'paid');

        // Phân quyền theo rạp của admin/staff
        if ($user && isset($user->cinema_id) && $user->cinema_id) {
            $cinemaId = $user->cinema_id;
            $cinema = Cinema::find($cinemaId);
            $query->whereHas('showtime.room', function ($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            });
        } elseif ($request->has('cinema_id')) {
            $cinemaId = $request->cinema_id;
            $cinema = Cinema::find($cinemaId);
            $query->whereHas('showtime.room', function ($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            });
        } else {
            $cinema = null;
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('movie_id')) {
            $movieId = $request->movie_id;
            $query->whereHas('showtime', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            });
        }

        $totalRevenue = (int) $query->sum('total_amount');
        
        $totalTicketsSold = (int) \App\Models\BookingSeat::whereHas('booking', function ($q) use ($query) {
            $q->whereIn('booking_id', $query->pluck('booking_id'));
        })->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'total_revenue'      => $totalRevenue,
                'total_tickets_sold' => $totalTicketsSold,
                'cinema_name'        => $cinema ? $cinema->name : 'Tất cả cụm rạp',
            ]
        ]);
    }
}
