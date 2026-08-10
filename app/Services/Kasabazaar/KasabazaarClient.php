<?php

namespace App\Services\Kasabazaar;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class KasabazaarClient
{
    public function get(string $path, array $query = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->request($headers)->get($path, $query));
    }

    public function post(string $path, array $data = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->request($headers)->post($path, $data));
    }

    public function put(string $path, array $data = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->request($headers)->put($path, $data));
    }

    public function patch(string $path, array $data = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->request($headers)->patch($path, $data));
    }

    public function delete(string $path, array $data = [], array $headers = []): KasabazaarResponse
    {
        return $this->parse($this->request($headers)->delete($path, $data));
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

        return $this->parse($request->asMultipart()->post($path, $this->toMultipartFields($data)));
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
