<?php

namespace App\Models;

use App\Http\Filters\V1\QueryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function scopeFilters(Builder $builder, QueryFilter $filters)
    {
        return $filters->apply($builder);
    }
}
