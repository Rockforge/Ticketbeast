<?php

namespace App\Billing;

class FakePaymentGateway implements PaymentGateway
{

    private $charges;

    public function __construct()
    {
        $this->charges = collect();
    }

    /**
     * Get valid test token
     *
     * @return string
     */
    public function getValidTestToken()
    {
        return 'valid-token';
    }

    public function charge($amount, $token)
    {
        if ($token !== $this->getValidTestToken()) {
            throw new PaymentFailedException;
        }
        $this->charges[] = $amount;
    }

    public function totalCharges()
    {
        return $this->charges->sum();
    }
}