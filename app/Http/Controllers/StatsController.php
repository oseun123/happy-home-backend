<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\AddressVerification;

class StatsController extends Controller
{
    //

    public function getDashboardStats()
    {
        $now = Carbon::now();

        $totalUsers = User::count();

        $newUsersThisMonth = User::whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->count();

        $verifiedUsersCount = AddressVerification::where('status', 'success')
            ->distinct('user_id')
            ->count('user_id');

        $totalSubscriptionSales = Subscription::where('verified', true)
            ->sum('amount_paid') / 100;

        $totalAddressVerificationSales = AddressVerification::where('status', 'success')
            ->sum('amount') / 100;

        $deletedAccountsCount = User::onlyTrashed()->count();

        return ResponseHelper::withSuccess('Record fetched successfully', [
            'total_users_count' => $totalUsers,
            'new_users_count' => $newUsersThisMonth,
            'verified_users_count' => $verifiedUsersCount,
            'deleted_accounts_count' => $deletedAccountsCount,
            'total_sales' => $totalSubscriptionSales + $totalAddressVerificationSales,
        ]);
    }



    public function getMonthlySales()
    {
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        $monthlySales = collect(range(1, 12))->mapWithKeys(function ($month) use ($currentYear, $previousYear) {
            // Current year totals
            $currentSub = Subscription::where('verified', true)
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('amount_paid') / 100;

            $currentAddr = AddressVerification::where('status', 'success')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $month)
                ->sum('amount') / 100;

            // Previous year totals
            $prevSub = Subscription::where('verified', true)
                ->whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $month)
                ->sum('amount_paid') / 100;

            $prevAddr = AddressVerification::where('status', 'success')
                ->whereYear('created_at', $previousYear)
                ->whereMonth('created_at', $month)
                ->sum('amount') / 100;

            return [
                Carbon::create()->month($month)->format('F') => [
                    'current_year' => $currentSub + $currentAddr,
                    'previous_year' => $prevSub + $prevAddr,
                ],
            ];
        });

        return ResponseHelper::withSuccess('Data fetched successfully', [
            'year' => $currentYear,
            'previous_year' => $previousYear,
            'monthly_sales_comparison' => $monthlySales,
        ]);
    }


    public function getDailySalesForMonth(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
        ]);

        $month = $request->month;
        $year = Carbon::now()->year;
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;

        $dailySales = collect(range(1, $daysInMonth))->mapWithKeys(function ($day) use ($year, $month) {
            $subscriptionSum = Subscription::where('verified', true)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereDay('created_at', $day)
                ->sum('amount_paid') / 100;

            $addressSum = AddressVerification::where('status', 'success')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->whereDay('created_at', $day)
                ->sum('amount') / 100;

            return [
                $day => $subscriptionSum + $addressSum,
            ];
        });

        return ResponseHelper::withSuccess('Record fetched successfully', [
            'year' => $year,
            'month' => Carbon::create()->month($month)->format('F'),
            'daily_sales' => $dailySales,
        ]);
    }

    public function getUserList(Request $request)
    {
        $perPage = $request->input('per_page', 15); // Default to 15 if not provided

        $users = User::with(['personalProfile'])
            ->paginate($perPage);

        $transformed = $users->getCollection()->map(fn(User $u) => [
            'name' => trim(
                ($u->personalProfile->first_name ?? '') . ' ' .
                    ($u->personalProfile->middle_name ?? '') . ' ' .
                    ($u->personalProfile->last_name ?? '')
            ),
            'phone_number' => $u->personalProfile->phone_number ?? null,
            'email' => $u->email,
            'subscribe_account_count' => $u->subscribers()->count(),
            'is_address_verified' => $u->hasVerifiedAddress(),
        ]);

        // Replace the collection with the transformed one while keeping pagination structure
        $users->setCollection($transformed);

        return ResponseHelper::withSuccess('Users fetched successfully.', $users);
    }

    public function getDeletedUserList(Request $request)
    {
        $perPage = $request->input('per_page', 15); // Default to 15 if not provided

        $users = User::onlyTrashed()
            ->with(['personalProfile'])
            ->paginate($perPage);

        $transformed = $users->getCollection()->map(fn(User $u) => [
            'name' => trim(
                ($u->personalProfile->first_name ?? '') . ' ' .
                    ($u->personalProfile->middle_name ?? '') . ' ' .
                    ($u->personalProfile->last_name ?? '')
            ),
            'phone_number' => $u->personalProfile->phone_number ?? null,
            'email' => $u->email,
            'subscribe_account_count' => $u->subscribers()->count(),
            'is_address_verified' => $u->hasVerifiedAddress(),
        ]);

        $users->setCollection($transformed);

        return ResponseHelper::withSuccess('Deleted users fetched successfully.', $users);
    }
}
