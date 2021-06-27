<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Billing\PaymentGateway;
use App\Models\Concert;

class ConcertsOrderController extends Controller
{

    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($concertId)
    {
        $concert = Concert::find($concertId);

        $ticketQuantity = request('ticket_quantity');

        $amount = $ticketQuantity * $concert->ticket_price;

        $token = request('token');

        $this->paymentGateway->charge($amount, $token);

        return response()->json([], 201);
    }
}
