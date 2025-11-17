<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\QRCodeService;
use App\Models\User;
use App\Models\Advantage;
use App\Models\QrCode;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QRCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QRCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QRCodeService();
    }

    /** @test */
    public function it_generates_unique_qr_code()
    {
        $user = User::factory()->create();
        $advantage = Advantage::factory()->create();

        $qrCode1 = $this->service->generateQRCode($user, $advantage);
        $qrCode2 = $this->service->generateQRCode($user, $advantage);

        $this->assertNotEquals($qrCode1->code, $qrCode2->code);
    }

    /** @test */
    public function it_creates_valid_signature()
    {
        $user = User::factory()->create();
        $advantage = Advantage::factory()->create();

        $qrCode = $this->service->generateQRCode($user, $advantage);

        // Verify signature is not empty
        $this->assertNotEmpty($qrCode->signature);

        // Verify signature is 64 characters (SHA-256)
        $this->assertEquals(64, strlen($qrCode->signature));
    }

    /** @test */
    public function it_validates_qr_code_correctly()
    {
        $user = User::factory()->create();
        $advantage = Advantage::factory()->create();

        $qrCode = $this->service->generateQRCode($user, $advantage);

        // Valid QR code should pass validation
        $isValid = $this->service->validateQRCode($qrCode->code, $advantage->merchant_id);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_rejects_expired_qr_code()
    {
        $user = User::factory()->create();
        $advantage = Advantage::factory()->create();

        $qrCode = QrCode::create([
            'code' => 'EXPIRED-CODE',
            'user_id' => $user->id,
            'advantage_id' => $advantage->id,
            'valid_from' => now()->subHours(3),
            'valid_until' => now()->subHours(1),
            'status' => 'expired',
        ]);

        $isValid = $this->service->validateQRCode($qrCode->code, $advantage->merchant_id);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_rejects_already_used_qr_code()
    {
        $user = User::factory()->create();
        $advantage = Advantage::factory()->create();

        $qrCode = QrCode::create([
            'code' => 'USED-CODE',
            'user_id' => $user->id,
            'advantage_id' => $advantage->id,
            'valid_from' => now(),
            'valid_until' => now()->addHours(2),
            'status' => 'used',
        ]);

        $isValid = $this->service->validateQRCode($qrCode->code, $advantage->merchant_id);

        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_enforces_two_hour_validity()
    {
        $user = User::factory()->create();
        $advantage = Advantage::factory()->create();

        $qrCode = $this->service->generateQRCode($user, $advantage);

        $validFrom = \Carbon\Carbon::parse($qrCode->valid_from);
        $validUntil = \Carbon\Carbon::parse($qrCode->valid_until);

        $differenceInHours = $validFrom->diffInHours($validUntil);

        $this->assertEquals(2, $differenceInHours);
    }
}
