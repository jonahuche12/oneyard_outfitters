<?php

namespace App\Actions\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SendWhatsAppTemplate
{
    /**
     * Send an approved WhatsApp template message through the Cloud API.
     *
     * @param  array<int, string>  $bodyParameters  Values for {{1}}, {{2}}, ... in order.
     * @return array<string, mixed>
     *
     * @throws RuntimeException            When the integration is not configured.
     * @throws \Illuminate\Http\Client\RequestException  When the API rejects the request.
     */
    public function __invoke(string $to, string $template, array $bodyParameters): array
    {
        $config = config('services.whatsapp');

        if (blank($config['token']) || blank($config['phone_number_id'])) {
            throw new RuntimeException('WhatsApp Cloud API is not configured.');
        }

        $url = rtrim($config['base_url'], '/')
            . '/' . $config['api_version']
            . '/' . $config['phone_number_id']
            . '/messages';

        $response = Http::withToken($config['token'])
            ->acceptJson()
            ->timeout(15)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($to, '+'),
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => $config['template_language']],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => array_map(
                                fn ($value) => [
                                    'type' => 'text',
                                    'text' => $this->clean((string) $value),
                                ],
                                array_values($bodyParameters)
                            ),
                        ],
                    ],
                ],
            ]);

        return $response->throw()->json() ?? [];
    }

    /**
     * Template values cannot contain newlines/tabs or be empty.
     */
    private function clean(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/', ' ', $value));

        return $value === '' ? '-' : Str::limit($value, 900, '');
    }
}
