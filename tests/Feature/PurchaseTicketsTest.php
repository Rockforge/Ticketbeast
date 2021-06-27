<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Concert;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function customer_can_purchase_concert_tickets()
    {
        $this->withoutExceptionHandling();
        $paymentGateway = new FakePaymentGateway;

        $this->app->instance(PaymentGateway::class, $paymentGateway);

        // Arrange
        // Create a concert
        $concert = Concert::factory()->create([
            'ticket_price' => 3250
        ]);

        // Act
        // Purchase concert tickets
        $response = $this->json('POST', "/concerts/{$concert->id}/orders", [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $paymentGateway->getValidTestToken()
        ]);

        // Assert
        $response->assertStatus(201);

        // Make sure customer was charged the correct amount
        $this->assertEquals(9750, $paymentGateway->totalCharges());

        // Make sure an order exists for this customer
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets->count());
    }
}
