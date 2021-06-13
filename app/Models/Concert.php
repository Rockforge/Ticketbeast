<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
