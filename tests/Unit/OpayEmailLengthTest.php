<?php
namespace Tests\Unit;
use App\Services\OpayDigitalWalletService;
use PHPUnit\Framework\TestCase;
class OpayEmailLengthTest extends TestCase {
    private function service(string $error): OpayDigitalWalletService {
        return new class($error) extends OpayDigitalWalletService {
            public array $calls=[];
            public function __construct(private string $error) {}
            protected function request(string $path,array $parameters): array {
                $this->calls[]=$parameters;
                if (count($this->calls)===1) throw new \RuntimeException($this->error);
                return ['data'=>['depositCode'=>'1234567890']];
            }
        };
    }
    private function parameters(): array { return ['opayMerchantId'=>'branch','refId'=>'SWL000000000001','name'=>'Email Test','email'=>'abubakreabideenadetunji@gmail.com','accountType'=>'Merchant','sendPassWordFlag'=>'N']; }
    public function test_explicit_email_length_rejection_retries_with_same_reference_and_no_email(): void {
        $s=$this->service('OPay rejected the request (ERR): email length exceeds maximum');
        $s->createWallet($this->parameters());
        $this->assertSame('abubakreabideenadetunji@gmail.com',$s->calls[0]['email']);
        $this->assertArrayNotHasKey('email',$s->calls[1]);
        $this->assertSame($s->calls[0]['refId'],$s->calls[1]['refId']);
    }
    public function test_decrypted_business_email_rejection_also_uses_the_fallback(): void {
        $s=new class extends OpayDigitalWalletService {
            public array $calls=[];
            protected function request(string $path,array $parameters): array {
                $this->calls[]=$parameters;
                return count($this->calls)===1 ? ['code'=>'EMAIL_ERROR','message'=>'email length exceeds maximum'] : ['code'=>'00000','data'=>['depositCode'=>'1234567890']];
            }
        };
        $s->createWallet($this->parameters());
        $this->assertCount(2,$s->calls);
        $this->assertArrayNotHasKey('email',$s->calls[1]);
    }
    public function test_ip_rejection_does_not_retry_or_blame_email_length(): void {
        $s=$this->service('OPay rejected the request (C_1113): not within ip white list');
        try { $s->createWallet($this->parameters()); $this->fail('Expected rejection'); } catch(\RuntimeException $e) { $this->assertCount(1,$s->calls); }
        $this->assertFalse(OpayDigitalWalletService::isEmailLengthRejection('OPay rejected the request (C_1113): not within ip white list'));
    }
    public function test_timeout_does_not_retry_wallet_creation(): void {
        $s=$this->service('Operation timed out');
        try { $s->createWallet($this->parameters()); $this->fail('Expected timeout'); } catch(\RuntimeException $e) { $this->assertCount(1,$s->calls); }
    }
}
