<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class BoostController extends Controller
{
    private const MAX_MCP_EVENT_LOG_LINES = 5000;

    public function index(): Response
    {
        $boostConfig = $this->boostConfig();

        return Inertia::render('Admin/Boost/Index', [
            'boost' => [
                'installed' => File::exists(base_path('boost.json')),
                'guidelines' => (bool) ($boostConfig['guidelines'] ?? false),
                'mcp' => (bool) ($boostConfig['mcp'] ?? false),
                'sail' => (bool) ($boostConfig['sail'] ?? false),
                'agents' => array_values(array_filter($boostConfig['agents'] ?? [], 'is_string')),
                'skills' => array_values(array_filter($boostConfig['skills'] ?? [], 'is_string')),
                'commands' => [
                    'docker compose exec app php artisan test --testsuite=Unit',
                    'docker compose exec app php artisan test --testsuite=Feature',
                    'docker compose exec app composer lint',
                    'docker compose exec app composer analyse',
                ],
            ],
        ]);
    }

    public function mcpStatus(Request $request): JsonResponse
    {
        $boostConfig = $this->boostConfig();
        $mcpEnabled = (bool) ($boostConfig['mcp'] ?? false);

        $this->recordMcpEvent($mcpEnabled, $request);
        $this->recordMcpStateTransition($mcpEnabled);

        return response()->json([
            'mcp_enabled' => $mcpEnabled,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function mcpStats(): JsonResponse
    {
        $lines = $this->logLinesFromFile($this->mcpEventLogPath());
        $now = now();
        $oneHourAgo = $now->copy()->subHour();
        $oneDayAgo = $now->copy()->subDay();

        $totalChecks = 0;
        $checksLastHour = 0;
        $checksLast24h = 0;
        $enabledCount = 0;
        $lastCheckAt = null;
        $activityBuckets = array_fill(0, 24, 0);
        $allIps = [];
        $allUsers = [];

        foreach ($lines as $line) {
            if (! preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] mcp\.(enabled|disabled) status-check user=(\S+) ip=(\S+)/', $line, $m)) {
                continue;
            }

            $timestamp = \Carbon\Carbon::parse($m[1]);
            $status = $m[2];
            $userId = $m[3];
            $ip = $m[4];

            $totalChecks++;
            $lastCheckAt = $timestamp->toIso8601String();

            if ($status === 'enabled') {
                $enabledCount++;
            }

            if ($timestamp->gte($oneHourAgo)) {
                $checksLastHour++;
            }

            if ($timestamp->gte($oneDayAgo)) {
                $checksLast24h++;
                $hour = (int) $timestamp->format('G');
                $activityBuckets[$hour]++;
            }

            $allIps[] = $ip;
            $allUsers[] = $userId;
        }

        $uptimePercent = $totalChecks > 0
            ? round(($enabledCount / $totalChecks) * 100, 1)
            : 0;

        $labelMap = [0 => '12am', 6 => '6am', 12 => '12pm', 18 => '6pm'];
        $activityTimeline = [];
        for ($i = 0; $i < 24; $i++) {
            $activityTimeline[] = [
                'hour' => $i,
                'label' => $labelMap[$i] ?? '',
                'count' => $activityBuckets[$i],
            ];
        }

        $recentIps = [];
        foreach (array_reverse($allIps) as $ip) {
            if (! in_array($ip, $recentIps, true)) {
                $recentIps[] = $ip;
                if (count($recentIps) >= 5) {
                    break;
                }
            }
        }

        $recentUsers = [];
        foreach (array_reverse($allUsers) as $userId) {
            if (! in_array($userId, $recentUsers, true)) {
                $recentUsers[] = $userId;
                if (count($recentUsers) >= 5) {
                    break;
                }
            }
        }

        return response()->json([
            'total_checks' => $totalChecks,
            'checks_last_hour' => $checksLastHour,
            'checks_last_24h' => $checksLast24h,
            'uptime_percent' => $uptimePercent,
            'last_check_at' => $lastCheckAt,
            'activity_timeline' => $activityTimeline,
            'recent_ips' => $recentIps,
            'recent_users' => $recentUsers,
        ]);
    }

    public function browserLogs(): JsonResponse
    {
        $logs = array_slice($this->logLinesFromFile($this->browserLogPath()), -250);

        return response()->json([
            'logs' => array_values($logs),
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function clearBrowserLogs(): JsonResponse
    {
        File::put($this->browserLogPath(), '');
        File::put($this->mcpEventLogPath(), '');
        File::put($this->mcpStatePath(), json_encode(['mcp_enabled' => null]));

        return response()->json([
            'cleared' => true,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    private function boostConfig(): array
    {
        $boostConfigPath = base_path('boost.json');

        if (! File::exists($boostConfigPath)) {
            return [];
        }

        $decodedConfig = json_decode(File::get($boostConfigPath), true);

        return is_array($decodedConfig) ? $decodedConfig : [];
    }

    private function browserLogPath(): string
    {
        return storage_path('logs/browser.log');
    }

    private function mcpEventLogPath(): string
    {
        return storage_path('logs/mcp-events.log');
    }

    private function mcpStatePath(): string
    {
        return storage_path('app/mcp-state.json');
    }

    /**
     * @return array<int, string>
     */
    private function logLinesFromFile(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', File::get($path)) ?: [];

        return array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));
    }

    private function recordMcpEvent(bool $mcpEnabled, Request $request): void
    {
        $path = $this->mcpEventLogPath();

        File::append(
            $path,
            sprintf(
                '[%s] mcp.%s status-check user=%s ip=%s',
                now()->format('Y-m-d H:i:s'),
                $mcpEnabled ? 'enabled' : 'disabled',
                (string) ($request->user()?->id ?? 'guest'),
                (string) ($request->ip() ?? 'unknown')
            ).PHP_EOL
        );

        $this->pruneLogFile($path, self::MAX_MCP_EVENT_LOG_LINES);
    }

    private function recordMcpStateTransition(bool $mcpEnabled): void
    {
        $statePath = $this->mcpStatePath();
        $stateDir = dirname($statePath);

        if (! File::isDirectory($stateDir)) {
            File::makeDirectory($stateDir, 0755, true);
        }

        $previous = null;
        if (File::exists($statePath)) {
            $decoded = json_decode(File::get($statePath), true);
            if (is_array($decoded) && array_key_exists('mcp_enabled', $decoded)) {
                $previous = $decoded['mcp_enabled'];
            }
        }

        File::put($statePath, json_encode(['mcp_enabled' => $mcpEnabled]));

        if ($previous === null || $previous === $mcpEnabled) {
            return;
        }

        if ($mcpEnabled) {
            File::append(
                $this->browserLogPath(),
                sprintf('[%s] mcp.ONLINE MCP server came online', now()->format('Y-m-d H:i:s')).PHP_EOL
            );
        } else {
            File::append(
                $this->browserLogPath(),
                sprintf('[%s] mcp.OFFLINE MCP server went offline', now()->format('Y-m-d H:i:s')).PHP_EOL
            );
        }
    }

    private function pruneLogFile(string $path, int $maxLines): void
    {
        $lines = $this->logLinesFromFile($path);

        if (count($lines) <= $maxLines) {
            return;
        }

        File::put($path, implode(PHP_EOL, array_slice($lines, -$maxLines)).PHP_EOL);
    }
}
