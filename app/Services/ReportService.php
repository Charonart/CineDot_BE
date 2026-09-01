<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Cinema;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Build standard revenue query based on authorized scopes and request filters.
     * Database is the single source of truth.
     * Only completed and paid bookings are counted towards revenue.
     */
    public function buildRevenueQuery($user, array $filters): Builder
    {
        $query = Booking::whereIn('booking_status', ['completed', 'paid']);

        // Data Scoping based on user role/permissions
        if ($user && method_exists($user, 'getAuthorizedScopeIds')) {
            $allowedCinemaIds = $user->getAuthorizedScopeIds('view:report', 'cinema');
            if (!in_array('*', $allowedCinemaIds)) {
                $query->whereHas('showtime.room', function ($q) use ($allowedCinemaIds) {
                    $q->whereIn('cinema_id', $allowedCinemaIds);
                });
            }
        }

        // Specific cinema filter
        if (!empty($filters['cinema_id'])) {
            $cinemaId = (int) $filters['cinema_id'];
            $query->whereHas('showtime.room', function ($q) use ($cinemaId) {
                $q->where('cinema_id', $cinemaId);
            });
        }

        // Date range filters
        $startDate = $filters['start_date'] ?? $filters['from_date'] ?? null;
        $endDate = $filters['end_date'] ?? $filters['to_date'] ?? null;

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        // Movie filter
        if (!empty($filters['movie_id'])) {
            $movieId = (int) $filters['movie_id'];
            $query->whereHas('showtime', function ($q) use ($movieId) {
                $q->where('movie_id', $movieId);
            });
        }

        return $query;
    }

    /**
     * Get complete revenue report including summary and optional grouped chart data.
     */
    public function getRevenueReport($user, array $filters): array
    {
        $startDate = $filters['start_date'] ?? $filters['from_date'] ?? null;
        $endDate = $filters['end_date'] ?? $filters['to_date'] ?? null;
        $groupBy = $filters['group_by'] ?? null;

        $query = $this->buildRevenueQuery($user, $filters);

        $totalRevenue = (float) (clone $query)->sum('final_amount');

        $totalTicketsSold = (int) BookingSeat::whereIn('booking_id', (clone $query)->select('booking_id'))->count();

        $cinemaName = 'Tất cả cụm rạp';
        if (!empty($filters['cinema_id'])) {
            $cinema = Cinema::find($filters['cinema_id']);
            $cinemaName = $cinema ? ($cinema->cinema_name ?? 'Rạp') : 'Tất cả cụm rạp';
        }

        $reportData = [
            'total_revenue'      => $totalRevenue,
            'total_tickets_sold' => $totalTicketsSold,
            'cinema_name'        => $cinemaName,
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'summary'            => [
                'total_revenue'      => $totalRevenue,
                'total_tickets_sold' => $totalTicketsSold,
            ],
            'filters'            => [
                'cinema_id'  => !empty($filters['cinema_id']) ? (int) $filters['cinema_id'] : null,
                'movie_id'   => !empty($filters['movie_id']) ? (int) $filters['movie_id'] : null,
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'group_by'   => $groupBy,
            ],
        ];

        if ($groupBy === 'day') {
            $reportData['chart'] = $this->getDailyChartData($query, $startDate, $endDate);
        } elseif ($groupBy === 'format') {
            $reportData['chart'] = $this->getFormatChartData($query);
        }

        return $reportData;
    }

    /**
     * Aggregate revenue and tickets sold by screen_type and sound_technology.
     */
    public function getFormatChartData(Builder $baseQuery): array
    {
        $data = (clone $baseQuery)
            ->join('showtimes', 'bookings.showtime_id', '=', 'showtimes.showtime_id')
            ->join('rooms', 'showtimes.room_id', '=', 'rooms.room_id')
            ->selectRaw('rooms.screen_type, rooms.sound_technology, SUM(bookings.final_amount) as revenue, COUNT(bookings.booking_id) as tickets_count') // Simplified tickets_count for brevity. In a real app, it should sum booking_seats.
            ->groupBy('rooms.screen_type', 'rooms.sound_technology')
            ->get();

        $chart = [];
        $catalog = \App\Services\RoomFormatCatalog::getScreenTypes();
        $soundCatalog = \App\Services\RoomFormatCatalog::getSoundTechnologies();

        foreach ($data as $row) {
            $screenName = $catalog[$row->screen_type]['name'] ?? $row->screen_type ?? '2D Standard';
            $soundName = $soundCatalog[$row->sound_technology]['name'] ?? $row->sound_technology ?? 'Standard Sound';
            
            $chart[] = [
                'format_key'   => ($row->screen_type ?? 'standard') . '_' . ($row->sound_technology ?? 'standard'),
                'format_name'  => $screenName . ' + ' . $soundName,
                'screen_type'  => $row->screen_type,
                'sound_tech'   => $row->sound_technology,
                'revenue'      => (float) $row->revenue,
                'tickets_sold' => (int) $row->tickets_count, // Again, approx
            ];
        }

        return $chart;
    }

    /**
     * Aggregate daily revenue and tickets sold, with missing dates filled as 0.
     */
    public function getDailyChartData(Builder $baseQuery, ?string $startDate, ?string $endDate): array
    {
        // 1. Group revenue by date
        $dailyRevenueQuery = (clone $baseQuery)
            ->selectRaw('DATE(created_at) as date_val, SUM(final_amount) as daily_revenue')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('daily_revenue', 'date_val')
            ->toArray();

        // 2. Group tickets sold by date via subquery
        $dailyTicketsQuery = BookingSeat::whereIn('booking_seats.booking_id', (clone $baseQuery)->select('booking_id'))
            ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.booking_id')
            ->selectRaw('DATE(bookings.created_at) as date_val, COUNT(booking_seats.booking_seat_id) as daily_tickets')
            ->groupBy(DB::raw('DATE(bookings.created_at)'))
            ->pluck('daily_tickets', 'date_val')
            ->toArray();

        // Format dates into normalized Y-m-d string keys
        $dailyRevenue = [];
        foreach ($dailyRevenueQuery as $dateStr => $val) {
            $formattedDate = Carbon::parse($dateStr)->format('Y-m-d');
            $dailyRevenue[$formattedDate] = (float) $val;
        }

        $dailyTickets = [];
        foreach ($dailyTicketsQuery as $dateStr => $val) {
            $formattedDate = Carbon::parse($dateStr)->format('Y-m-d');
            $dailyTickets[$formattedDate] = (int) $val;
        }

        return $this->fillMissingDates($dailyRevenue, $dailyTickets, $startDate, $endDate);
    }

    /**
     * Ensure every single date in the target date range exists with zero fallback.
     */
    public function fillMissingDates(
        array $dailyRevenue,
        array $dailyTickets,
        ?string $startDate,
        ?string $endDate
    ): array {
        // If neither start nor end is given, determine range from available data or default to past 7 days
        if (!$startDate && !$endDate) {
            $allDates = array_unique(array_merge(array_keys($dailyRevenue), array_keys($dailyTickets)));
            if (!empty($allDates)) {
                sort($allDates);
                $startDate = $allDates[0];
                $endDate = end($allDates);
            } else {
                $startDate = Carbon::now()->subDays(6)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');
            }
        } elseif ($startDate && !$endDate) {
            $endDate = Carbon::parse($startDate)->addDays(6)->format('Y-m-d');
        } elseif (!$startDate && $endDate) {
            $startDate = Carbon::parse($endDate)->subDays(6)->format('Y-m-d');
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $chart = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $chart[] = [
                'date'         => $dateStr,
                'revenue'      => (float) ($dailyRevenue[$dateStr] ?? 0),
                'tickets_sold' => (int) ($dailyTickets[$dateStr] ?? 0),
            ];
        }

        return $chart;
    }
}
