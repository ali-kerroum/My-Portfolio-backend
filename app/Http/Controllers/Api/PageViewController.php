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

        // Estimate sessions: group consecutive views from same IP within 30 min
        $viewsByIp = PageView::orderBy('created_at')
            ->get(['ip', 'created_at'])
            ->groupBy('ip');

        $sessions = [];
        foreach ($viewsByIp as $ip => $views) {
            $sessionStart = null;
            $sessionEnd = null;
            $sessionPages = 0;

            foreach ($views as $view) {
                $viewTime = Carbon::parse($view->created_at);

                if ($sessionStart === null) {
                    $sessionStart = $viewTime;
                    $sessionEnd = $viewTime;
                    $sessionPages = 1;
                } elseif ($viewTime->diffInMinutes($sessionEnd) <= 30) {
                    $sessionEnd = $viewTime;
                    $sessionPages++;
                } else {
                    $sessions[] = [
                        'duration' => $sessionStart->diffInSeconds($sessionEnd),
                        'pages' => $sessionPages,
                    ];
                    $sessionStart = $viewTime;
                    $sessionEnd = $viewTime;
                    $sessionPages = 1;
                }
            }
            if ($sessionStart) {
                $sessions[] = [
                    'duration' => $sessionStart->diffInSeconds($sessionEnd),
                    'pages' => $sessionPages,
                ];
            }
        }

        $totalSessions = count($sessions);
        $sessionsCollection = collect($sessions);
        $avgSessionDuration = $totalSessions > 0 ? round($sessionsCollection->avg('duration')) : 0;
        $avgPagesPerSession = $totalSessions > 0 ? round($sessionsCollection->avg('pages'), 1) : 0;
        $bounceRate = $totalSessions > 0
            ? round($sessionsCollection->where('pages', 1)->count() / $totalSessions * 100)
            : 0;
        $longestSession = $totalSessions > 0 ? $sessionsCollection->max('duration') : 0;

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
        $driver = DB::getDriverName();
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

        // OS Breakdown (parse user_agent)
        $osBreakdown = PageView::select('user_agent', DB::raw('COUNT(*) as count'))
            ->whereNotNull('user_agent')
            ->groupBy('user_agent')
            ->get()
            ->groupBy(function ($item) {
                $ua = $item->user_agent;
                if (str_contains($ua, 'Windows')) return 'Windows';
                if (str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS')) return 'macOS';
                if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
                if (str_contains($ua, 'Android')) return 'Android';
                if (str_contains($ua, 'Linux')) return 'Linux';
                if (str_contains($ua, 'CrOS')) return 'ChromeOS';
                return 'Other';
            })
            ->map(function ($group, $name) {
                return ['name' => $name, 'count' => $group->sum('count')];
            })
            ->sortByDesc('count')
            ->values();

        // Engagement funnel: % of unique IPs that viewed each section
        $totalUniqueIps = DB::table('page_views')->distinct()->count('ip') ?: 1;
        $funnelSections = ['/', '/#hero', '/#about', '/#experience', '/#services', '/#projects', '/#contact'];
        $funnel = [];
        foreach ($funnelSections as $section) {
            $count = DB::table('page_views')
                ->where('page', $section)
                ->distinct()
                ->count('ip');
            $funnel[] = [
                'section' => $section,
                'visitors' => $count,
                'percent' => round(($count / $totalUniqueIps) * 100),
            ];
        }

        // Weekly comparison: this week vs last week
        $lastWeekStart = Carbon::now()->startOfWeek()->subWeek();
        $lastWeekEnd = Carbon::now()->startOfWeek()->subSecond();
        $lastWeek = PageView::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();
        $lastWeekUnique = DB::table('page_views')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->distinct()->count('ip');
        $thisWeekUnique = DB::table('page_views')
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->distinct()->count('ip');
        $weekViewsChange = $lastWeek > 0 ? round((($thisWeek - $lastWeek) / $lastWeek) * 100) : ($thisWeek > 0 ? 100 : 0);
        $weekVisitorsChange = $lastWeekUnique > 0 ? round((($thisWeekUnique - $lastWeekUnique) / $lastWeekUnique) * 100) : ($thisWeekUnique > 0 ? 100 : 0);

        // Views per month (last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->startOfMonth()->subMonths($i);
            $monthEnd = (clone $monthStart)->endOfMonth();
            $monthlyTrend[] = [
                'month' => $monthStart->format('M Y'),
                'short' => $monthStart->format('M'),
                'count' => PageView::whereBetween('created_at', [$monthStart, $monthEnd])->count(),
            ];
        }

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
            'session_insights' => [
                'avg_duration' => $avgSessionDuration,
                'avg_pages' => $avgPagesPerSession,
                'bounce_rate' => $bounceRate,
                'total_sessions' => $totalSessions,
                'longest_session' => $longestSession,
            ],
            'browsers' => $browsers,
            'devices' => ['mobile' => $mobileCount, 'desktop' => $desktopCount],
            'peak_hours' => $peakHours,
            'recent_views' => $recentViews,
            'os_breakdown' => $osBreakdown,
            'engagement_funnel' => $funnel,
            'weekly_comparison' => [
                'this_week_views' => $thisWeek,
                'last_week_views' => $lastWeek,
                'views_change' => $weekViewsChange,
                'this_week_visitors' => $thisWeekUnique,
                'last_week_visitors' => $lastWeekUnique,
                'visitors_change' => $weekVisitorsChange,
            ],
            'monthly_trend' => $monthlyTrend,
        ]);
    }
}
