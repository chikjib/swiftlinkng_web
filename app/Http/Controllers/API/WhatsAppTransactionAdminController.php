<?php

namespace App\Http\Controllers\API;

use App\Models\WhatsAppOrder;
use App\Models\WhatsAppTransactionAdminLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WhatsAppTransactionAdminController extends BaseController
{
    private const STATUSES = [
        'PENDING',
        'PENDING_CONFIRMATION',
        'WAITING_PAYMENT',
        'PAID',
        'FAILED',
        'COMPLETED',
        'CANCELLED',
        'EXPIRED',
    ];

    public function index(Request $request)
    {
        $query = WhatsAppOrder::with(['customer', 'payment', 'plan.provider']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('ref', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customer) use ($search) {
                        $customer->where('whatsapp_number', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($service = $request->input('service_type')) {
            $query->where('service_type', $service);
        }

        $summary = [
            'total' => WhatsAppOrder::count(),
            'pending' => WhatsAppOrder::whereIn('status', [
                'PENDING',
                'PENDING_CONFIRMATION',
                'WAITING_PAYMENT',
                'PAID',
            ])->count(),
            'completed' => WhatsAppOrder::where('status', 'COMPLETED')->count(),
            'failed' => WhatsAppOrder::whereIn('status', [
                'FAILED',
                'CANCELLED',
                'EXPIRED',
            ])->count(),
            'completed_value' => (float) WhatsAppOrder::where(
                'status',
                'COMPLETED'
            )->sum('amount'),
        ];

        return $this->sendResponse([
            'summary' => $summary,
            'statuses' => self::STATUSES,
            'service_types' => WhatsAppOrder::query()
                ->distinct()
                ->orderBy('service_type')
                ->pluck('service_type'),
            'orders' => $query->latest()->paginate(25),
        ], 'WhatsApp transactions retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $order = WhatsAppOrder::with('payment')->findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $validated['status'];
        $baseUrl = rtrim((string) config('services.whatsapp_bot_admin.base_url'), '/');
        $secret = (string) config('services.whatsapp_bot_admin.secret');

        if ($baseUrl === '' || $secret === '') {
            return $this->sendError(
                'WhatsApp status notification service is not configured.',
                'Set WHATSAPP_BOT_URL and the same SWIFTLINK_ADMIN_API_SECRET on both applications.',
                503
            );
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-Swiftlink-Admin-Secret' => $secret])
                ->timeout(20)
                ->put("{$baseUrl}/internal/admin/orders/{$order->id}/status", [
                    'status' => $newStatus,
                    'reason' => $validated['note'],
                ]);
        } catch (\Throwable $exception) {
            Log::error('WhatsApp admin status update request failed.', [
                'order_id' => $order->id,
                'new_status' => $newStatus,
                'error' => $exception->getMessage(),
            ]);

            return $this->sendError(
                'The bot status service could not be reached. Nothing was changed.',
                'The bot status service could not be reached. Nothing was changed.',
                503
            );
        }

        if (!$response->successful()) {
            return $this->sendError(
                $response->json('message') ?: 'The transaction status could not be updated.',
                $response->json('errors') ?: $response->json('message'),
                in_array($response->status(), [401, 422, 503], true)
                    ? $response->status()
                    : 502
            );
        }

        $order->refresh();

        WhatsAppTransactionAdminLog::create([
            'admin_user_id' => $request->user()->id,
            'bot_order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $order->status,
            'note' => $validated['note'],
        ]);

        return $this->sendResponse(
            [
                'order' => $order->fresh(['customer', 'payment', 'plan.provider']),
                'refunded_amount' => (float) $response->json('refunded_amount', 0),
            ],
            $response->json('message')
        );
    }

    public function refund(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $order = WhatsAppOrder::with('payment')->findOrFail($id);
        $oldStatus = $order->status;
        $baseUrl = rtrim((string) config('services.whatsapp_bot_admin.base_url'), '/');
        $secret = (string) config('services.whatsapp_bot_admin.secret');

        if ($baseUrl === '' || $secret === '') {
            return $this->sendError(
                'WhatsApp refund service is not configured.',
                'Set WHATSAPP_BOT_URL and the same SWIFTLINK_ADMIN_API_SECRET on both applications.',
                503
            );
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['X-Swiftlink-Admin-Secret' => $secret])
                ->timeout(20)
                ->post("{$baseUrl}/internal/admin/orders/{$order->id}/refund", [
                    'reason' => $validated['note'],
                ]);
        } catch (\Throwable $exception) {
            Log::error('WhatsApp admin refund request failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->sendError(
                'The bot refund service could not be reached. Nothing was changed.',
                'The bot refund service could not be reached. Nothing was changed.',
                503
            );
        }

        if (!$response->successful()) {
            return $this->sendError(
                $response->json('message') ?: 'The transaction could not be refunded.',
                $response->json('errors') ?: $response->json('message'),
                in_array($response->status(), [401, 422, 503], true)
                    ? $response->status()
                    : 502
            );
        }

        $order->refresh();
        WhatsAppTransactionAdminLog::create([
            'admin_user_id' => $request->user()->id,
            'bot_order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $order->status,
            'note' => 'REFUND: '.$validated['note'],
        ]);

        return $this->sendResponse([
            'order' => $order->fresh(['customer', 'payment', 'plan.provider']),
            'refunded_amount' => (float) $response->json('refunded_amount', 0),
        ], $response->json('message'));
    }
}
