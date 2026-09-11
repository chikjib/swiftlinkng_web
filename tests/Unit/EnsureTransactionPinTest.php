<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureTransactionPin;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class EnsureTransactionPinTest extends TestCase
{
    /**
     * @dataProvider unattendedUserLevels
     */
    public function test_unattended_transaction_channels_bypass_the_pin_challenge(int $userLevel): void
    {
        $user = new User();
        $user->userlevel = $userLevel;

        $request = Request::create('/api/purchase/data', 'POST');
        $request->setUserResolver(static fn () => $user);

        $response = (new EnsureTransactionPin())->handle(
            $request,
            static fn () => 'request-continued'
        );

        $this->assertSame('request-continued', $response);
    }

    public function unattendedUserLevels(): array
    {
        return [
            'WhatsApp bot user' => [2],
            'API integration user' => [3],
        ];
    }
}
