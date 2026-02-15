<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PageViewController extends Controller
{
    /**
     * Record a page view (public endpoint).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'page' => 'nullable|string|max:500',
        ]);

        PageView::create([
            'page' => $validated['page'] ?? '/',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ]);

        return response()->json(['message' => 'ok'], 201);
    }

    /**
     * Get visitor stats (admin only).
     */
    public function stats()
    {
        $total = PageView::count();
        $today = PageView::whereDate('created_at', Carbon::today())->count();
        $yesterday = PageView::whereDate('created_at', Carbon::yesterday())->count();
        $thisWeek = PageView::where('created_at', '>=', Carbon::now()->startOfWeek())->count();
        $thisMonth = PageView::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
        $uniqueVisitors = PageView::distinct('ip')->count('ip');

        // Growth: compare today vs yesterday
        $growthPercent = $yesterday > 0 ? round((($today - $yesterday) / $yesterday) * 100) : ($today > 0 ? 100 : 0);

        // Last 7 days breakdown
        $daily = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $daily[] = [
                'date' => $date->format('M d'),
                'count' => PageView::whereDate('created_at', $date)->count(),
            ];
        }

        // Last 30 days breakdown (for monthly trend)
        $monthly = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $monthly[] = [
                'date' => $date->format('M d'),
                'count' => PageView::whereDate('created_at', $date)->count(),
            ];
        }

        // Top pages
        $topPages = PageView::select('page', DB::raw('COUNT(*) as count'))
            ->groupBy('page')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Top referrers
        $topReferrers = PageView::select('referrer', DB::raw('COUNT(*) as count'))
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                // Extract domain from referrer URL
                $parsed = parse_url($item->referrer);
                $item->domain = $parsed['host'] ?? $item->referrer;
                return $item;
            });

        // Browser breakdown (parse user_agent)
        $browsers = PageView::select('user_agent', DB::raw('COUNT(*) as count'))
            ->whereNotNull('user_agent')
            ->groupBy('user_agent')
            ->get()
            ->groupBy(function ($item) {
                $ua = $item->user_agent;
                if (str_contains($ua, 'Firefox')) return 'Firefox';
                if (str_contains($ua, 'Edg')) return 'Edge';
                if (str_contains($ua, 'Chrome')) return 'Chrome';
                if (str_contains($ua, 'Safari')) return 'Safari';
                if (str_contains($ua, 'Opera') || str_contains($ua, 'OPR')) return 'Opera';
                return 'Other';
            })
            ->map(function ($group, $name) {
                return ['name' => $name, 'count' => $group->sum('count')];
            })
            ->sortByDesc('count')
            ->values();

        // Device breakdown (mobile vs desktop)
        $mobileCount = PageView::whereNotNull('user_agent')
            ->where(function ($q) {
                $q->where('user_agent', 'like', '%Mobile%')
                  ->orWhere('user_agent', 'like', '%Android%')
                  ->orWhere('user_agent', 'like', '%iPhone%')
                  ->orWhere('user_agent', 'like', '%iPad%');
            })->count();
        $desktopCount = $total - $mobileCount;

        // Peak hours (24h distribution)
        $hourly = PageView::select(DB::raw('strftime("%H", created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Fill in missing hours
        $peakHours = [];
        for ($h = 0; $h < 24; $h++) {
            $key = str_pad($h, 2, '0', STR_PAD_LEFT);
            $peakHours[] = ['hour' => $key, 'count' => $hourly[$key] ?? 0];
        }

        // Recent views (last 10)
        $recentViews = PageView::orderByDesc('created_at')
            ->limit(10)
            ->get(['page', 'ip', 'referrer', 'created_at']);

        return response()->json([
            'total' => $total,
            'today' => $today,
            'yesterday' => $yesterday,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'unique_visitors' => $uniqueVisitors,
            'growth_percent' => $growthPercent,
            'daily' => $daily,
            'monthly' => $monthly,
            'top_pages' => $topPages,
            'top_referrers' => $topReferrers,
            'browsers' => $browsers,
            'devices' => ['mobile' => $mobileCount, 'desktop' => $desktopCount],
            'peak_hours' => $peakHours,
            'recent_views' => $recentViews,
        ]);
    }
}
