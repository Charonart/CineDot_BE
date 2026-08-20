<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::all()->keyBy('name');

        // Helper to get permission IDs by name list
        $getIds = function (array $names) use ($allPermissions) {
            $ids = [];
            foreach ($names as $n) {
                if (isset($allPermissions[$n])) {
                    $ids[] = $allPermissions[$n]->permission_id;
                }
            }
            return $ids;
        };

        // 1. Admin (Super Admin) -> wildcard *
        $admin = Role::where('name', 'admin')->first();
        if ($admin && isset($allPermissions['*'])) {
            $admin->permissions()->sync([$allPermissions['*']->permission_id]);
        }

        // 2. Cinema Manager
        $manager = Role::where('name', 'cinema_manager')->first();
        if ($manager) {
            $manager->permissions()->sync($getIds([
                'reports.dashboard.view',
                'movies.view',
                'movies.create',
                'movies.edit',
                'reviews.view',
                'cinemas.view',
                'cinemas.manage_rooms',
                'seat_types.manage',
                'showtimes.*',
                'showtimes.view',
                'showtimes.create',
                'showtimes.edit',
                'showtimes.delete',
                'bookings.view',
                'bookings.refund',
                'bookings.cancel',
                'tickets.scan',
                'tickets.checkin',
                'fnb.claim',
                'concessions.view',
                'concessions.manage',
                'staff.view',
                'staff.manage',
                'reports.revenue',
                'reports.tickets',
                'reports.occupancy',
            ]));
        }

        // 3. Ticket Staff
        $ticketStaff = Role::where('name', 'ticket_staff')->first();
        if ($ticketStaff) {
            $ticketStaff->permissions()->sync($getIds([
                'showtimes.view',
                'bookings.view',
                'tickets.scan',
                'tickets.checkin',
                'fnb.claim',
            ]));
        }

        // 4. F&B Staff
        $fnbStaff = Role::where('name', 'fnb_staff')->first();
        if ($fnbStaff) {
            $fnbStaff->permissions()->sync($getIds([
                'concessions.view',
                'fnb.claim',
                'bookings.view',
            ]));
        }

        // 5. Marketing
        $marketing = Role::where('name', 'marketing')->first();
        if ($marketing) {
            $marketing->permissions()->sync($getIds([
                'campaigns.manage',
                'vouchers.*',
                'vouchers.view',
                'vouchers.manage',
                'banners.manage',
                'movies.view',
                'reviews.view',
                'reports.dashboard.view',
            ]));
        }

        // 6. Accountant
        $accountant = Role::where('name', 'accountant')->first();
        if ($accountant) {
            $accountant->permissions()->sync($getIds([
                'reports.*',
                'reports.dashboard.view',
                'reports.revenue',
                'reports.tickets',
                'reports.occupancy',
                'bookings.view',
                'bookings.refund',
            ]));
        }

        // 7. General Staff
        $staff = Role::where('name', 'staff')->first();
        if ($staff) {
            $staff->permissions()->sync($getIds([
                'showtimes.view',
                'bookings.view',
                'tickets.scan',
                'tickets.checkin',
                'fnb.claim',
                'concessions.view',
            ]));
        }

        // 8. Customer
        $customer = Role::where('name', 'customer')->first();
        if ($customer) {
            $customer->permissions()->sync($getIds([
                'movies.view',
                'showtimes.view',
                'reviews.view',
            ]));
        }
    }
}
