<?php

namespace App\Services\Kasabazaar;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KasabazaarClient
{
    public function get(string $path, array $query = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->send(fn () => $this->request($headers)->get($path, $query)));
    }

    public function post(string $path, array $data = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->send(fn () => $this->request($headers)->post($path, $data)));
    }

    public function put(string $path, array $data = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->send(fn () => $this->request($headers)->put($path, $data)));
    }

    public function patch(string $path, array $data = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->send(fn () => $this->request($headers)->patch($path, $data)));
    }

    public function delete(string $path, array $data = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->send(fn () => $this->request($headers)->delete($path, $data)));
    }

    /**
     * @param  array<int, array{name: string, contents: mixed, filename?: string}>  $files
     */
    public function postMultipart(string $path, array $data, array $files, array $headers = []): KasabazaarResponse
    {
        $request = $this->request($headers);

        foreach ($files as $file) {
            $request = $request->attach($file['name'], $file['contents'], $file['filename'] ?? null);
        }

        return $this->parse($this->send(fn () => $request->asMultipart()->post($path, $this->toMultipartFields($data))));
    }

    /**
     * Public URL of a file the backend stored on its own public disk (vendor
     * logos, banners...). Those live on the backend's host, not this one, so
     * asset() would point at the wrong server.
     */
    public static function storageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        $origin = Str::before(rtrim((string) config('services.kasabazaar.base_url'), '/'), '/api/');

        return $origin.'/storage/'.ltrim($path, '/');
    }

    private function request(array $headers = []): PendingRequest
    {
        $request = Http::baseUrl(rtrim(config('services.kasabazaar.base_url'), '/'))
            ->timeout((int) config('services.kasabazaar.timeout', 10))
            ->acceptJson()
            ->withHeaders($headers)
            ->retry(2, 200, fn ($exception) => $exception instanceof ConnectionException, throw: false);

        if ($token = $this->token()) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    /**
     * Runs a request, turning a network failure (DNS, refused connection,
     * timeout) into the same exception every caller already handles, so an
     * unreachable backend degrades a page instead of returning a 500.
     *
     * @param  callable(): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            return $request();
        } catch (ConnectionException $e) {
            report($e);

            throw new KasabazaarApiException(
                'The marketplace service is temporarily unavailable. Please try again in a moment.',
                503,
            );
        }
    }

    private function token(): ?string
    {
        $user = Auth::user();

        return $user?->kasabazaar_token;
    }

    private function parse(Response $response): KasabazaarResponse
    {
        $body = $response->json() ?? [];

        if (! $response->successful() || ($body['success'] ?? false) === false) {
            throw new KasabazaarApiException(
                $body['message'] ?? 'The marketplace service returned an unexpected error.',
                $response->status(),
                $body['errors'] ?? []
            );
        }

        return new KasabazaarResponse(
            success: true,
            data: $body['data'] ?? null,
            message: $body['message'] ?? null,
            meta: $body['meta'] ?? null,
        );
    }

    /**
     * Http::asMultipart() expects each field as its own ['name' => ..., 'contents' => ...]
     * array rather than a flat associative array.
     */
    private function toMultipartFields(array $data): array
    {
        $fields = [];

        foreach ($data as $name => $contents) {
            $fields[] = ['name' => $name, 'contents' => $contents];
        }

        return $fields;
    }
}
