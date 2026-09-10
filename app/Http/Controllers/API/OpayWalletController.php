<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\UserResource;
use App\Services\OpayDigitalWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpayWalletController extends BaseController
{
    public function show()
    {
        if (!$this->user->opay_wallet_number) {
            return $this->sendError('OPay wallet has not been created.', [], 404);
        }
        return $this->sendResponse(new UserResource($this->user->fresh()), 'OPay wallet retrieved successfully.');
    }

    public function store(OpayDigitalWalletService $opay)
    {
        if (blank($this->user->bvn) && blank($this->user->nin)) {
            return $this->sendError(
                'Verification Required',
                'Add your BVN or NIN to create your personal OPay funding account.',
                422
            );
        }

        try {
            $user = Cache::lock('opay-wallet-user-'.$this->user->id, 30)->block(5, function () use ($opay) {
                $user = $this->user->fresh();
                if ($user->opay_wallet_number) {
                    return $user;
                }
                $refId = 'SWL'.str_pad((string) $user->id, 12, '0', STR_PAD_LEFT);
                $email = filter_var($user->email, FILTER_VALIDATE_EMAIL)
                    ? (string) $user->email
                    : null;
                if ($email === null) {
                    throw new \RuntimeException(
                        'A valid email address is required to create an OPay wallet.'
                    );
                }
                $parameters = [
                    'opayMerchantId' => $opay->branchId(),
                    'refId' => $refId,
                    'name' => trim($user->firstname.' '.$user->lastname)
                        ?: 'Swiftlink User '.$user->id,
                    'email' => $email,
                    'accountType' => (string) config('services.opay_wallet.account_type', 'Merchant'),
                    'sendPassWordFlag' => strtoupper((string) config('services.opay_wallet.send_password', 'N')),
                ];
                $response = $opay->createWallet(array_filter(
                    $parameters,
                    static fn ($value) => $value !== null && $value !== ''
                ));
                $data = $response['data'] ?? [];
                if (blank($data['depositCode'] ?? null)) {
                    throw new \RuntimeException('OPay did not return a wallet number.');
                }
                $user->opay_wallet_number = (string) $data['depositCode'];
                $user->opay_wallet_ref_id = (string) ($data['refId'] ?? $refId);
                $user->opay_wallet_name = (string) ($data['name'] ?? trim($user->firstname.' '.$user->lastname));
                $user->opay_wallet_account_type = (string) ($data['accountType'] ?? config('services.opay_wallet.account_type'));
                $user->opay_wallet_status = 'Active';
                $user->save();
                return $user;
            });
            return $this->sendResponse(new UserResource($user), 'OPay digital wallet created successfully.');
        } catch (Throwable $exception) {
            Log::error('OPay wallet creation failed.', ['user_id' => $this->user->id, 'error' => $exception->getMessage()]);
            return $this->sendError('Unable to create OPay wallet right now.', $exception->getMessage(), 502);
        }
    }

    public function balance(OpayDigitalWalletService $opay)
    {
        if (!$this->user->opay_wallet_number) {
            return $this->sendError('OPay wallet has not been created.', [], 404);
        }
        try {
            return $this->sendResponse($opay->walletBalance($this->user->opay_wallet_number)['data'], 'OPay wallet balance retrieved successfully.');
        } catch (Throwable $exception) {
            return $this->sendError('Unable to retrieve OPay wallet balance.', $exception->getMessage(), 502);
        }
    }

    public function transactions(Request $request, OpayDigitalWalletService $opay)
    {
        if (!$this->user->opay_wallet_number) {
            return $this->sendError('OPay wallet has not been created.', [], 404);
        }
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Ymd'], 'end_date' => ['nullable', 'date_format:Ymd'],
            'status' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);
        try {
            $response = $opay->transactionHistory([
                'depositCodeList' => [$this->user->opay_wallet_number],
                'orderStatus' => $validated['status'] ?? null,
                'startDate' => $validated['start_date'] ?? null,
                'endDate' => $validated['end_date'] ?? null,
                'pageIndex' => $validated['page'] ?? 1,
                'pageSize' => $validated['page_size'] ?? 20,
            ]);
            return $this->sendResponse($response['data'], 'OPay wallet transactions retrieved successfully.');
        } catch (Throwable $exception) {
            return $this->sendError('Unable to retrieve OPay wallet transactions.', $exception->getMessage(), 502);
        }
    }

    private function normalizePhone($phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        return str_starts_with($phone, '0') ? '234'.substr($phone, 1) : $phone;
    }
}
