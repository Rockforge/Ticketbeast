<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Concert;
use Illuminate\Http\Response;
use App\Billing\PaymentGateway;
use App\Billing\FakePaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentGateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
    }

    public function orderTickets(Concert $concert, array $params)
    {
        return $this->json('POST', "/concerts/{$concert->id}/orders", $params);
    }

    public function assertValidationError($response, $field)
    {
        $response->assertStatus(422);
        $this->assertArrayHasKey($field, $response->getOriginalContent()['errors']);
    }

    /** @test */
    public function customer_can_purchase_tickets_to_a_published_concert()
    {
        // Arrange
        // Create a concert
        $concert = Concert::factory()->published()->create([
            'ticket_price' => 3250
        ]);
        $concert->addTickets(3);

        // Act
        // Purchase concert tickets
        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        // Assert
        $response->assertStatus(201);

        // Make sure customer was charged the correct amount
        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        // Make sure an order exists for this customer
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets()->count());
    }

    /** @test */
    public function cannot_purchase_tickets_to_an_unpublished_concert()
    {
        $concert = Concert::factory()->unpublished()->create();
        $concert->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, $concert->orders()->count());
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function cannot_purchase_more_tickets_than_remain()
    {
        $concert = Concert::factory()->published()->create();
        $concert->addTickets(50);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $response->assertStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNull($order);
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function an_order_is_not_created_if_payment_fails()
    {
        $concert = Concert::factory()->published()->create([
            'ticket_price' => 3250
        ]);
        $concert->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token'
        ]);

        $response->assertStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNull($order);
    }

    /** @test */
    public function email_is_required_to_purchase_tickets()
    {
        $concert = Concert::factory()->published()->create();

        $response = $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertValidationError($response, 'email');
    }

    /** @test */
    public function valid_email_is_required_to_purchase_tickets()
    {
        $concert = Concert::factory()->published()->create();

        $response = $this->orderTickets($concert, [
            'email' => 'not-a-valid-email',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertValidationError($response, 'email');
    }

    /** @test */
    public function ticket_quantity_is_required_to_purchase_tickets()
    {
        $concert = Concert::factory()->published()->create();

        $response = $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertValidationError($response, 'ticket_quantity');
    }

    /** @test */
    public function ticket_quantity_must_be_at_least_1_to_purchase_tickets()
    {
        $concert = Concert::factory()->published()->create();

        $response = $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken()
        ]);

        $this->assertValidationError($response, 'ticket_quantity');
    }

    /** @test */
    public function payment_token_is_required_to_purchase_tickets()
    {
        $concert = Concert::factory()->published()->create();

        $response = $this->orderTickets($concert, [
            'email' => 'jane@example.com',
            'ticket_quantity' => 3
        ]);

        $this->assertValidationError($response, 'payment_token');
    }
}
