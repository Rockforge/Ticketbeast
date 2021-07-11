<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\NotEnoughTicketsException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Concert extends Model
{
    use HasFactory;

    /**
     * Don't protect any fields
     * @var array
     */
    protected $guarded = [];

    /**
     * Dates to be casted
     * @var array
     */
    protected $dates = [
        'date'
    ];

    /**
     * Get the formatted date
     *
     * @return \Carbon\Carbon
     */
    public function getFormattedDateAttribute()
    {
        return $this->date->format('F j, Y');
    }

    /**
     * Get the formatted start time
     *
     * @return \Carbon\Carbon
     */
    public function getFormattedStartTimeAttribute()
    {
        return $this->date->format('g:ia');
    }

    /**
     * Get the ticket price in dollars
     *
     * @return string
     */
    public function getTicketPriceInDollarsAttribute()
    {
        return number_format($this->ticket_price/ 100, 2);
    }

    /**
     * Published at scope
     *
     * @return QueryBuilder
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Get all of the orders for the Concert
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all of the tickets for the Concert
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function orderTickets($email, $ticketQuantity)
    {
        $tickets = $this->tickets()->available()->take($ticketQuantity)->get();

        if ($tickets->count() < $ticketQuantity) {
            throw new NotEnoughTicketsException;
        }

        $order = $this->orders()->create([
            'email' => $email
        ]);

        foreach ($tickets as $ticket) {
            $order->tickets()->save($ticket);
        }

        return $order;
    }

    public function addTickets($quantity)
    {
        foreach (range(1, $quantity) as $i) {
            $this->tickets()->create([]);
        }
    }

    public function ticketsRemaining()
    {
        return $this->tickets()->available()->count();
    }
}
