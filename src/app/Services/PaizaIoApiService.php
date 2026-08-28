<?php

namespace App\Services;

use App\Exceptions\PaizaIoApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaizaIoApiService
{
    private const CREATE_RUNNER_URI = 'https://api.paiza.io/runners/create.json';

    private const GET_STATUS_URI = 'https://api.paiza.io/runners/get_status.json';

    private const GET_DETAILS_URI = 'https://api.paiza.io/runners/get_details.json';

    private const MAX_POLL_ATTEMPTS = 30;

    private const POLL_INTERVAL_MICROSECONDS = 100_000;

    private const HTTP_CONNECT_TIMEOUT = 10;

    private const HTTP_TIMEOUT = 30;

    private readonly string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.paiza_io.api_key');
    }

    /**
     * @return array<string, mixed>
     */
    public function runSource(string $source, string $input = '', string $language = 'ruby'): array
    {
        $id = $this->createRunner($source, $language, $input);
        $status = 'running';
        $attempts = 0;

        while ($status === 'running') {
            if ($attempts >= self::MAX_POLL_ATTEMPTS) {
                throw new PaizaIoApiException('PaizaIO polling timeout');
            }

            usleep(self::POLL_INTERVAL_MICROSECONDS);
            $status = $this->getStatus($id);
            $attempts++;
        }

        return $this->getDetails($id);
    }

    public function createRunner(string $source, string $language, string $input): string
    {
        $response = $this->client()->post(self::CREATE_RUNNER_URI, [
            'api_key' => $this->apiKey,
            'source_code' => $source,
            'language' => $language,
            'input' => $input,
        ]);

        if (! $response->successful()) {
            throw new PaizaIoApiException('リクエストに失敗しました');
        }

        $body = $response->json();

        if (! is_array($body) || ! array_key_exists('id', $body)) {
            Log::error('PaizaIO create_runner unexpected response body', ['body' => $body]);

            throw new PaizaIoApiException('コードが実行できませんでした');
        }

        return (string) $body['id'];
    }

    public function getStatus(string $id): string
    {
        $response = $this->client()->get(self::GET_STATUS_URI, [
            'api_key' => $this->apiKey,
            'id' => $id,
        ]);

        if (! $response->successful()) {
            throw new PaizaIoApiException('リクエストに失敗しました');
        }

        $body = $response->json();

        if (! is_array($body) || ! array_key_exists('status', $body)) {
            throw new PaizaIoApiException('idが無効です');
        }

        return (string) $body['status'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(string $id): array
    {
        $response = $this->client()->get(self::GET_DETAILS_URI, [
            'api_key' => $this->apiKey,
            'id' => $id,
        ]);

        if (! $response->successful()) {
            throw new PaizaIoApiException('リクエストに失敗しました');
        }

        $body = $response->json();

        if (! is_array($body) || ! array_key_exists('stdout', $body)) {
            throw new PaizaIoApiException('idが無効です');
        }

        return $body;
    }

    private function client(): PendingRequest
    {
        return Http::connectTimeout(self::HTTP_CONNECT_TIMEOUT)->timeout(self::HTTP_TIMEOUT);
    }
}
