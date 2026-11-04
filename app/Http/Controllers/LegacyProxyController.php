<?php

namespace App\Http\Controllers;

use App\Models\LegacyUser;
use App\Services\LegacyBridge;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Meneruskan endpoint yang belum dimigrasi ke aplikasi Flask lama.
 * Flask hanya mendengarkan di localhost dan memverifikasi HMAC dari Laravel.
 */
class LegacyProxyController extends Controller
{
    public function __construct(private readonly LegacyBridge $bridge)
    {
    }

    public function forward(Request $request, string $path = ''): Response
    {
        $base = rtrim((string) config('migration.python_api_url'), '/');
        $target = $base.'/api/'.$path;
        $query = $request->getQueryString();

        if ($query) {
            $target .= '?'.$query;
        }

        $user = $request->attributes->get('legacy_user');
        if (! $user instanceof LegacyUser) {
            $userId = $request->session()->get('user_id');
            $user = $userId ? LegacyUser::query()->find($userId) : null;
        }

        $contentType = (string) $request->header('Content-Type');
        $isMultipart = str_starts_with(strtolower($contentType), 'multipart/form-data');

        $headers = [
            'Accept' => $request->header('Accept', '*/*'),
        ];

        if ($contentType !== '' && ! $isMultipart) {
            $headers['Content-Type'] = $contentType;
        }

        $headers = array_merge($headers, $this->bridge->headers($user));

        try {
            $pending = Http::withHeaders($headers)
                ->timeout(600)
                ->withOptions(['allow_redirects' => false]);

            $method = strtoupper($request->method());

            if ($isMultipart) {
                $response = $pending->asMultipart()->send($method, $target, [
                    'multipart' => $this->multipartParts($request),
                ]);
            } elseif (in_array($method, ['GET', 'DELETE'], true)) {
                $response = $pending->send($method, $target);
            } else {
                $response = $pending->withBody(
                    $request->getContent(),
                    $contentType !== '' ? $contentType : 'application/octet-stream'
                )->send($method, $target);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Layanan Flask tidak dapat dihubungi: '.$e->getMessage(),
            ], 502);
        }

        $responseHeaders = [];
        foreach (['Content-Type', 'Content-Disposition', 'Cache-Control', 'ETag', 'Last-Modified'] as $name) {
            if ($response->header($name)) {
                $responseHeaders[$name] = $response->header($name);
            }
        }

        return response($response->body(), $response->status())
            ->withHeaders($responseHeaders);
    }

    /**
     * Susun ulang multipart karena php://input kosong untuk form-data.
     *
     * @return array<int, array{name:string, contents:mixed, filename?:string, headers?:array}>
     */
    private function multipartParts(Request $request): array
    {
        $parts = [];

        $this->tambahFile($request->allFiles(), '', $parts);

        foreach ($request->request->all() as $key => $value) {
            $this->tambahField($parts, (string) $key, $value);
        }

        return $parts;
    }

    private function tambahFile(array $files, string $prefix, array &$parts): void
    {
        foreach ($files as $key => $value) {
            $name = $prefix === '' ? (string) $key : $prefix.'['.$key.']';

            if (is_array($value)) {
                $this->tambahFile($value, $name, $parts);
                continue;
            }

            if (! $value instanceof UploadedFile || ! $value->isValid()) {
                continue;
            }

            $stream = fopen($value->getRealPath(), 'r');
            if ($stream === false) {
                continue;
            }

            $parts[] = [
                'name' => $name,
                'contents' => $stream,
                'filename' => $value->getClientOriginalName(),
                'headers' => [
                    'Content-Type' => $value->getMimeType() ?: 'application/octet-stream',
                ],
            ];
        }
    }

    private function tambahField(array &$parts, string $name, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $this->tambahField($parts, $name.'['.$key.']', $item);
            }

            return;
        }

        if ($value instanceof UploadedFile) {
            return;
        }

        $parts[] = [
            'name' => $name,
            'contents' => $value === null ? '' : (string) $value,
        ];
    }
}
