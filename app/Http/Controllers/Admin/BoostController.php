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

        return response()->json([
            'mcp_enabled' => $mcpEnabled,
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function browserLogs(): JsonResponse
    {
        $browserLogs = $this->logLinesFromFile($this->browserLogPath());
        $mcpEvents = $this->logLinesFromFile($this->mcpEventLogPath());

        $logs = array_slice(array_merge($browserLogs, $mcpEvents), -250);

        return response()->json([
            'logs' => array_values($logs),
            'checked_at' => now()->toIso8601String(),
        ]);
    }

    public function clearBrowserLogs(): JsonResponse
    {
        File::put($this->browserLogPath(), '');
        File::put($this->mcpEventLogPath(), '');

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
        File::append(
            $this->mcpEventLogPath(),
            sprintf(
                '[%s] mcp.%s status-check user=%s ip=%s',
                now()->format('Y-m-d H:i:s'),
                $mcpEnabled ? 'enabled' : 'disabled',
                (string) ($request->user()?->id ?? 'guest'),
                (string) ($request->ip() ?? 'unknown')
            ).PHP_EOL
        );
    }
}
