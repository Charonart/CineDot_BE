<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Helper to apply context authorization scoping for bookings.
     */
    protected function scopeCinemaQuery($query, $user, string $permission = 'view:report')
    {
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds($permission, 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->whereHas('showtime.room', function ($q) use ($allowedCinemaIds) {
                    $q->whereIn('cinema_id', $allowedCinemaIds);
                });
            }
        }
        return $query;
    }

    /**
     * GET /api/v1/admin/dashboard/overview
     * KPI Overview: Today's Revenue, Total Bookings, Tickets Sold, Active Movies, Occupancy Rate
     */
    public function overview(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $bookingQuery = Booking::query();
        $this->scopeCinemaQuery($bookingQuery, $user);

        // Aggregate today and all-time metrics in single query
        $todayStats = (clone $bookingQuery)
            ->whereDate('created_at', $today)
            ->selectRaw("
                COUNT(*) as total_bookings_today,
                SUM(CASE WHEN booking_status IN ('completed', 'paid') THEN final_amount ELSE 0 END) as revenue_today,
                SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as checkins_today
            ")
            ->first();

        $totalRevenue = (float) (clone $bookingQuery)
            ->whereIn('booking_status', ['completed', 'paid'])
            ->sum('final_amount');

        $totalBookings = (clone $bookingQuery)->count();

        $totalUsers = User::count();
        $activeMoviesCount = Movie::where('status', 'now_showing')->count();

        // Today's total showtimes & seat occupancy
        $showtimeQuery = Showtime::whereDate('showtime_start', $today);
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:showtime', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $showtimeQuery->whereHas('room', fn($q) => $q->whereIn('cinema_id', $allowedCinemaIds));
            }
        }

        $todayShowtimesCount = (clone $showtimeQuery)->count();
        $todayShowtimeIds = (clone $showtimeQuery)->pluck('showtime_id');

        $seatOccupancyStats = null;
        if ($todayShowtimeIds->isNotEmpty()) {
            $seatOccupancyStats = ShowtimeSeat::whereIn('showtime_id', $todayShowtimeIds)
                ->selectRaw("
                    COUNT(*) as total_seats,
                    SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as booked_seats
                ")
                ->first();
        }

        $totalSeats = $seatOccupancyStats?->total_seats ?? 0;
        $bookedSeats = $seatOccupancyStats?->booked_seats ?? 0;
        $occupancyRate = $totalSeats > 0 ? round(($bookedSeats / $totalSeats) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'revenue_today'         => (float) ($todayStats?->revenue_today ?? 0),
                'total_revenue'         => $totalRevenue,
                'bookings_today'        => (int) ($todayStats?->total_bookings_today ?? 0),
                'total_bookings'        => $totalBookings,
                'checkins_today'        => (int) ($todayStats?->checkins_today ?? 0),
                'active_movies'         => $activeMoviesCount,
                'total_users'           => $totalUsers,
                'today_showtimes'       => $todayShowtimesCount,
                'seat_occupancy_rate'   => $occupancyRate,
                'total_seats_today'     => (int) $totalSeats,
                'booked_seats_today'    => (int) $bookedSeats,
            ]
        ]);
    }

    /**
     * GET /api/v1/admin/dashboard/revenue-chart
     * Revenue and Tickets chart data for target period
     */
    public function revenueChart(Request $request)
    {
        $user = $request->user();
        $filters = $request->all();
        $filters['group_by'] = $filters['group_by'] ?? 'day';
        
        $reportData = $this->reportService->getRevenueReport($user, $filters);

        return response()->json([
            'success' => true,
            'data'    => $reportData['chart'] ?? [],
            'summary' => $reportData['summary'] ?? [],
        ]);
    }

    /**
     * GET /api/v1/admin/dashboard/top-movies
     * Top selling movies with tickets sold & revenue
     */
    public function topMovies(Request $request)
    {
        $limit = min((int) $request->get('limit', 5), 20);
        $user = $request->user();

        $query = DB::table('movies')
            ->join('showtimes', 'movies.movie_id', '=', 'showtimes.movie_id')
            ->join('bookings', 'showtimes.showtime_id', '=', 'bookings.showtime_id')
            ->whereIn('bookings.booking_status', ['completed', 'paid']);

        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:report', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->join('rooms', 'showtimes.room_id', '=', 'rooms.room_id')
                      ->whereIn('rooms.cinema_id', $allowedCinemaIds);
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('bookings.created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('bookings.created_at', '<=', $request->end_date);
        }

        $topMovies = $query->groupBy('movies.movie_id', 'movies.title', 'movies.poster_path', 'movies.status')
            ->select(
                'movies.movie_id',
                'movies.title',
                'movies.poster_path',
                'movies.status',
                DB::raw('COUNT(DISTINCT bookings.booking_id) as total_bookings'),
                DB::raw('SUM(bookings.final_amount) as total_revenue')
            )
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $topMovies
        ]);
    }

    /**
     * GET /api/v1/admin/dashboard/showtimes
     * Upcoming showtimes overview with live booking progress
     */
    public function showtimes(Request $request)
    {
        $limit = min((int) $request->get('limit', 10), 50);
        $user = $request->user();

        $query = Showtime::with(['movie', 'room.cinema'])
            ->withCount([
                'showtimeSeats as total_seats',
                'showtimeSeats as booked_seats' => fn($q) => $q->where('status', 'booked')
            ])
            ->where('showtime_start', '>=', now())
            ->orderBy('showtime_start', 'asc');

        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:showtime', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->whereHas('room', fn($q) => $q->whereIn('cinema_id', $allowedCinemaIds));
            }
        }

        $showtimes = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data'    => $showtimes
        ]);
    }

    /**
     * GET /api/v1/admin/dashboard/check-in-stats
     * Live QR Check-in Rate & Ticket verification summary
     */
    public function checkInStats(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $query = Booking::whereIn('booking_status', ['completed', 'paid'])
            ->whereDate('created_at', $today);

        $this->scopeCinemaQuery($query, $user, 'view:booking');

        $stats = (clone $query)
            ->selectRaw("
                COUNT(*) as total_valid_bookings,
                SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as checked_in_count,
                SUM(CASE WHEN checked_in_at IS NULL THEN 1 ELSE 0 END) as pending_check_in_count
            ")
            ->first();

        $total = (int) ($stats?->total_valid_bookings ?? 0);
        $checkedIn = (int) ($stats?->checked_in_count ?? 0);
        $rate = $total > 0 ? round(($checkedIn / $total) * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data'    => [
                'total_valid_bookings'    => $total,
                'checked_in_count'        => $checkedIn,
                'pending_check_in_count'  => (int) ($stats?->pending_check_in_count ?? 0),
                'check_in_rate'           => $rate,
            ]
        ]);
    }

    /**
     * GET /api/v1/admin/dashboard/activities
     * Recent real-time activities (Bookings, Check-ins, Status changes)
     */
    public function activities(Request $request)
    {
        $limit = min((int) $request->get('limit', 10), 30);
        $user = $request->user();

        $query = Booking::with(['user', 'showtime.movie', 'showtime.room.cinema'])
            ->orderByDesc('created_at');

        $this->scopeCinemaQuery($query, $user, 'view:booking');

        $recentBookings = $query->limit($limit)->get();

        $activities = $recentBookings->map(function ($b) {
            $isPaid = in_array($b->booking_status, ['completed', 'paid']);
            $action = $b->checked_in_at ? 'TICKET_CHECKED_IN' : ($isPaid ? 'BOOKING_PAID' : 'BOOKING_CREATED');
            
            return [
                'id'           => $b->booking_id,
                'booking_code' => $b->booking_code,
                'action'       => $action,
                'user_name'    => $b->user?->fullname ?? $b->user?->username ?? 'Khách vãng lai',
                'movie_title'  => $b->showtime?->movie?->title ?? 'N/A',
                'cinema_name'  => $b->showtime?->room?->cinema?->cinema_name ?? 'N/A',
                'amount'       => (float) $b->final_amount,
                'status'       => $b->booking_status,
                'created_at'   => $b->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $activities
        ]);
    }
}
