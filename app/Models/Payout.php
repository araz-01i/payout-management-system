<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = ['employee_id', 'task', 'amount', 'status'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'IQD '.number_format($this->amount, 0, '.', ',');
    }
}
