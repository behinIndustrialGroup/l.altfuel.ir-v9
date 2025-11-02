<?php

namespace App\Http\Controllers;

use App\Models\User;
use Behin\CrmClient\CrmClient;

class CrmContactSyncController extends Controller
{
    public function __construct(private CrmClient $crmClient)
    {
    }

    public function __invoke()
    {
        if (! $this->crmClient->configured()) {
            return response()->json([
                'message' => 'CRM credentials are not configured.',
            ], 500);
        }

        $users = User::skip(2410)->take(600)->get();

        $results = [];

        foreach ($users as $user) {
            [$firstName, $lastName] = $this->splitName($user->display_name ?? $user->name ?? '');

            $payload = array_filter([
                'firstname' => $firstName,
                'lastname' => $lastName,
                'telephone1' => $user->email,
                'mobilephone' => $user->email,
                'emailaddress1' => $user->email,
            ], fn ($value) => filled($value));

            try {
                $response = $this->crmClient->save('contacts', $payload);

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
