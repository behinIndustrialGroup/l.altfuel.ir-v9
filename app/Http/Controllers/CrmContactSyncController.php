<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CrmContactSyncController extends Controller
{
    public function __invoke(Request $request)
    {
        $baseUrl = rtrim(config('services.crm.base_url'), '/');
        $username = config('services.crm.username');
        $password = config('services.crm.password');

        if (! $baseUrl || ! $username || ! $password) {
            return response()->json([
                'message' => 'CRM credentials are not configured.',
            ], 500);
        }

        $users = User::all();

        $results = [];

        foreach ($users as $user) {
            [$firstName, $lastName] = $this->splitName($user->display_name ?? $user->name ?? '');

            $payload = array_filter([
                'firstname' => $firstName,
                'lastname' => $lastName,
                'telephone1' => $user->ext_num,
                'mobilephone' => $this->resolvePhoneNumber($user),
                'emailaddress1' => $user->email,
            ], fn ($value) => filled($value));

            try {
                $response = Http::withOptions([
                    'auth' => [$username, $password, 'ntlm'],
                ])->acceptJson()->post(
                    $baseUrl . '/Main/api/data/v9.0/contacts',
                    $payload
                );

                $results[] = [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'successful' => $response->successful(),
                    'payload' => $payload,
                    'response' => $this->decodeResponse($response->body()),
                ];
            } catch (\Throwable $exception) {
                $results[] = [
                    'user_id' => $user->id,
                    'status' => null,
                    'successful' => false,
                    'payload' => $payload,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return response()->json([
            'total' => $users->count(),
            'results' => $results,
        ]);
    }

    private function splitName(?string $fullName): array
    {
        if (blank($fullName)) {
            return [null, null];
        }

        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];

        $firstName = array_shift($parts) ?: null;
        $lastName = $parts ? implode(' ', $parts) : null;

        return [$firstName, $lastName];
    }

    private function resolvePhoneNumber(User $user): ?string
    {
        foreach (['mobile', 'mobilephone', 'phone', 'telephone', 'telephone1'] as $attribute) {
            $value = $user->getAttribute($attribute);

            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function decodeResponse(string $body)
    {
        $decoded = json_decode($body, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $body;
    }
}
