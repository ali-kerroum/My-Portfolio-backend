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
     * Get the real client IP, accounting for reverse proxies.
     */
    private function getRealIp(Request $request): string
    {
        // X-Forwarded-For may contain: client, proxy1, proxy2
        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            $ips = array_map('trim', explode(',', $forwarded));
            if (!empty($ips[0]) && filter_var($ips[0], FILTER_VALIDATE_IP)) {
                return $ips[0];
            }
        }
        return $request->ip();
    }

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
            'ip' => $this->getRealIp($request),
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
        $uniqueVisitors = DB::table('page_views')->distinct()->count('ip');

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

        // Visits by day of week
        $driver = DB::getDriverName();
        $dowExpr = $driver === 'sqlite'
            ? DB::raw('strftime("%w", created_at) as dow')  // 0=Sun, 6=Sat
            : DB::raw('EXTRACT(DOW FROM created_at) as dow');

        $dowRaw = PageView::select($dowExpr, DB::raw('COUNT(*) as count'))
            ->groupBy('dow')
            ->orderBy('dow')
            ->get()
            ->pluck('count', 'dow')
            ->toArray();

        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $dayOfWeek = [];
        for ($d = 0; $d < 7; $d++) {
            $key = (string) $d;
            $dayOfWeek[] = ['day' => $dayNames[$d], 'count' => (int) ($dowRaw[$key] ?? 0)];
        }

        // New vs returning visitors
        $todayIps = DB::table('page_views')
            ->whereDate('created_at', Carbon::today())
            ->distinct()
            ->pluck('ip');

        $newToday = 0;
        $returningToday = 0;
        foreach ($todayIps as $ip) {
            $existed = DB::table('page_views')
                ->where('ip', $ip)
                ->whereDate('created_at', '<', Carbon::today())
                ->exists();
            if ($existed) {
                $returningToday++;
            } else {
                $newToday++;
            }
        }

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

        // Peak hours (24h distribution) — compatible with both SQLite and PostgreSQL
        $hourExpr = $driver === 'sqlite'
            ? DB::raw('strftime("%H", created_at) as hour')
            : DB::raw('TO_CHAR(created_at, \'HH24\') as hour');

        $hourly = PageView::select($hourExpr, DB::raw('COUNT(*) as count'))
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
            'day_of_week' => $dayOfWeek,
            'new_visitors_today' => $newToday,
            'returning_visitors_today' => $returningToday,
            'browsers' => $browsers,
            'devices' => ['mobile' => $mobileCount, 'desktop' => $desktopCount],
            'peak_hours' => $peakHours,
            'recent_views' => $recentViews,
        ]);
    }
}
