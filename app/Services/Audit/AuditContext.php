<?php

namespace App\Services\Audit;

use App\Models\Branch;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Answers "where is this change coming from?" for every audit entry: the channel (a Filament
 * panel, the mobile API, a webhook, a queued job, an artisan command), the request (IP, agent,
 * URL), the staff member impersonating someone, and the user a background job acts for.
 *
 * Contexts are a stack: a sync job dispatched inside a request, or a command run from a test,
 * pushes its own frame and pops it again. Request data is only ever read while an HTTP frame is
 * on the stack — never from runningInConsole(), which is also true under PHPUnit.
 *
 * @phpstan-type Frame array{channel: ?string, source: ?string, request: ?Request, causer_id: ?string, impersonator_id: ?string, branch_id: ?string, origin_channel: ?string, suppress_auth: bool, opened_batch: bool}
 */
class AuditContext
{
    public const PANELS = ['admin', 'client', 'investor'];

    /** @var array<int, array<string, mixed>> */
    private array $stack = [];

    public static function instance(): self
    {
        return app(self::class);
    }

    /**
     * @param  array<string, mixed>  $frame
     */
    public function push(array $frame): void
    {
        $this->stack[] = array_merge([
            'channel' => null,
            'source' => null,
            'request' => null,
            'causer_id' => null,
            'impersonator_id' => null,
            'branch_id' => null,
            'origin_channel' => null,
            'suppress_auth' => false,
            'opened_batch' => false,
        ], $frame);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pop(): ?array
    {
        return array_pop($this->stack);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function current(): ?array
    {
        return $this->stack === [] ? null : $this->stack[array_key_last($this->stack)];
    }

    public function depth(): int
    {
        return count($this->stack);
    }

    public function enterHttp(Request $request): void
    {
        $this->push(['request' => $request]);
    }

    /**
     * Run $callback with a fixed channel/source, e.g. a Paystack webhook job or a guest
     * payment callback where neither the route nor the user says where the change came from.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function as(string $channel, ?string $source, Closure $callback): mixed
    {
        $context = self::instance();
        $inherited = $context->current() ?? [];

        $context->push([
            'channel' => $channel,
            'source' => $source,
            'request' => $inherited['request'] ?? null,
            'causer_id' => $inherited['causer_id'] ?? null,
            'impersonator_id' => $inherited['impersonator_id'] ?? null,
            'branch_id' => $inherited['branch_id'] ?? null,
            'suppress_auth' => $inherited['suppress_auth'] ?? false,
        ]);

        try {
            return $callback();
        } finally {
            $context->pop();
        }
    }

    /**
     * Keep the auth-event listener quiet while $callback swaps logins on purpose
     * (impersonation, logging an inactive user straight back out). The caller logs
     * the real event itself.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function suppressAuthEvents(Closure $callback): mixed
    {
        $context = self::instance();
        $inherited = $context->current() ?? [];

        $context->push(array_merge($inherited, ['suppress_auth' => true, 'opened_batch' => false]));

        try {
            return $callback();
        } finally {
            $context->pop();
        }
    }

    public function authEventsSuppressed(): bool
    {
        foreach ($this->stack as $frame) {
            if ($frame['suppress_auth']) {
                return true;
            }
        }

        return false;
    }

    public function request(): ?Request
    {
        for ($i = count($this->stack) - 1; $i >= 0; $i--) {
            if ($this->stack[$i]['request'] instanceof Request) {
                return $this->stack[$i]['request'];
            }
        }

        return null;
    }

    public function isHttp(): bool
    {
        return $this->request() !== null;
    }

    public function channel(): string
    {
        $frame = $this->current();

        if (filled($frame['channel'] ?? null)) {
            return $frame['channel'];
        }

        $request = $this->request();

        if ($request === null) {
            return 'console';
        }

        return $this->channelForRequest($request);
    }

    public function source(): ?string
    {
        return $this->current()['source'] ?? null;
    }

    /**
     * The id of the user a queued job was dispatched by, when the job itself runs without one.
     */
    public function backgroundCauserId(): ?string
    {
        for ($i = count($this->stack) - 1; $i >= 0; $i--) {
            if (filled($this->stack[$i]['causer_id'])) {
                return (string) $this->stack[$i]['causer_id'];
            }
        }

        return null;
    }

    /**
     * Who is responsible for a write right now: the signed-in user, else the user a queued job acts for.
     */
    public function causerId(): ?string
    {
        $userId = auth()->id();

        return $userId !== null ? (string) $userId : $this->backgroundCauserId();
    }

    public function impersonatorId(): ?string
    {
        for ($i = count($this->stack) - 1; $i >= 0; $i--) {
            if (filled($this->stack[$i]['impersonator_id'])) {
                return (string) $this->stack[$i]['impersonator_id'];
            }
        }

        $request = $this->request();

        if ($request !== null && $request->hasSession()) {
            $original = $request->session()->get('impersonate.original_id');

            return filled($original) ? (string) $original : null;
        }

        return null;
    }

    /**
     * The branch an entry belongs to: the record's own branch, else the Filament tenant, else
     * the branch the API caller picked, else the branch the job was dispatched from, else the
     * acting user's home branch.
     */
    public function branchIdFor(?Model $subject = null, ?Model $scope = null, ?Model $causer = null): ?string
    {
        foreach ([$subject, $scope] as $model) {
            $branchId = $model?->getAttribute('branch_id');

            if (filled($branchId) && Str::isUuid((string) $branchId)) {
                return (string) $branchId;
            }
        }

        $tenant = Filament::getTenant();

        if ($tenant instanceof Branch) {
            return (string) $tenant->getKey();
        }

        $header = $this->request()?->header('X-Branch-Id');

        if (filled($header) && Str::isUuid($header)) {
            return $header;
        }

        for ($i = count($this->stack) - 1; $i >= 0; $i--) {
            if (filled($this->stack[$i]['branch_id'])) {
                return (string) $this->stack[$i]['branch_id'];
            }
        }

        $causerBranch = $causer?->getAttribute('branch_id');

        return filled($causerBranch) ? (string) $causerBranch : null;
    }

    /**
     * @return array{ip_address: ?string, user_agent: ?string, url: ?string, http_method: ?string, route_name: ?string}
     */
    public function requestData(): array
    {
        $request = $this->request();

        if ($request === null) {
            return ['ip_address' => null, 'user_agent' => null, 'url' => null, 'http_method' => null, 'route_name' => null];
        }

        $url = $request->fullUrl();

        if ($this->isLivewireUpdate($request) && filled($request->header('referer'))) {
            $url = (string) $request->header('referer');
        }

        return [
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'url' => $this->stripSignature($url),
            'http_method' => $request->method(),
            'route_name' => $request->route()?->getName(),
        ];
    }

    /**
     * What a job should remember about the request that dispatched it.
     *
     * @return array{causer_id: ?string, impersonator_id: ?string, branch_id: ?string, origin_channel: string, batch_uuid: ?string}
     */
    public function payloadForJob(?string $batchUuid): array
    {
        return [
            'causer_id' => $this->causerId(),
            'impersonator_id' => $this->impersonatorId(),
            'branch_id' => $this->branchIdFor(causer: auth()->user()),
            'origin_channel' => $this->channel(),
            'batch_uuid' => $batchUuid,
        ];
    }

    private function channelForRequest(Request $request): string
    {
        $path = $request->path();

        if ($this->isLivewireUpdate($request) && filled($request->header('referer'))) {
            $path = ltrim((string) parse_url((string) $request->header('referer'), PHP_URL_PATH), '/');
        }

        $firstSegment = Str::before($path, '/');

        if (in_array($firstSegment, self::PANELS, true)) {
            return $firstSegment;
        }

        if (Str::contains($path, 'webhook') || Str::contains((string) $request->route()?->getName(), 'webhook')) {
            return 'webhook';
        }

        if ($firstSegment === 'api') {
            return 'api';
        }

        // A panel on a custom path or domain, or a Livewire request without a referer.
        $panel = Filament::getCurrentPanel()?->getId();

        if ($this->isLivewireUpdate($request) && in_array($panel, self::PANELS, true)) {
            return $panel;
        }

        return 'web';
    }

    private function isLivewireUpdate(Request $request): bool
    {
        return Str::endsWith($request->path(), 'livewire/update');
    }

    private function stripSignature(string $url): string
    {
        return (string) preg_replace('/([?&])signature=[^&]*/', '$1signature=…', $url);
    }
}
