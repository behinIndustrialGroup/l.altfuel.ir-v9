<?php

namespace Behin\CrmClient;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class CrmClient
{
    protected ?string $baseUrl;
    protected ?string $username;
    protected ?string $password;
    protected ?string $apiPath;

    public function __construct(?string $baseUrl = null, ?string $username = null, ?string $password = null, ?string $apiPath = null)
    {
        $this->baseUrl = $this->normaliseBaseUrl($baseUrl ?? config('services.crm.base_url'));
        $this->username = $username ?? config('services.crm.username');
        $this->password = $password ?? config('services.crm.password');
        $this->apiPath = $this->normalisePath($apiPath ?? config('services.crm.path') ?? 'Main/api/data/v9.0');
    }

    public function configured(): bool
    {
        return $this->hasValue($this->baseUrl) && $this->hasValue($this->username) && $this->hasValue($this->password);
    }

    public function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('CRM credentials are not configured.');
        }
    }

    public function save(string $entity, array $attributes = [], array $options = []): Response
    {
        return $this->request($entity, 'POST', $attributes, $options);
    }

    public function request(string $endpoint, string $method = 'GET', array $parameters = [], array $options = []): Response
    {
        $this->ensureConfigured();

        $url = $this->resolveUrl($endpoint);
        $method = strtolower($method);

        $options = $this->buildOptions($options);
        $pendingRequest = Http::withOptions($options)->acceptJson();

        return match ($method) {
            'get' => $pendingRequest->get($url, $parameters),
            'delete' => $pendingRequest->delete($url, $parameters),
            'post', 'put', 'patch' => $pendingRequest->{$method}($url, $parameters),
            default => throw new InvalidArgumentException(sprintf('Unsupported CRM request method [%s].', strtoupper($method))),
        };
    }

    protected function buildOptions(array $options): array
    {
        $auth = [$this->username, $this->password, 'ntlm'];

        if (array_key_exists('auth', $options)) {
            $options['auth'] = $options['auth'] ?: $auth;
        } else {
            $options['auth'] = $auth;
        }

        return $options;
    }

    protected function resolveUrl(string $endpoint): string
    {
        if ($this->looksLikeUrl($endpoint)) {
            return $endpoint;
        }

        $endpoint = ltrim($endpoint, '/');

        if ($this->hasValue($this->apiPath)) {
            $endpoint = trim($this->apiPath, '/') . '/' . $endpoint;
        }

        return rtrim($this->baseUrl, '/') . '/' . $endpoint;
    }

    protected function looksLikeUrl(string $value): bool
    {
        return (bool) preg_match('/^https?:\/\//i', $value);
    }

    protected function normaliseBaseUrl(?string $baseUrl): ?string
    {
        if (! $this->hasValue($baseUrl)) {
            return null;
        }

        return rtrim($baseUrl, '/');
    }

    protected function normalisePath(?string $path): ?string
    {
        if (! $this->hasValue($path)) {
            return null;
        }

        return trim($path, '/');
    }

    protected function hasValue(?string $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        return trim($value) !== '';
    }
}
