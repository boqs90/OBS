<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'payment_concept_id',
        'amount',
        'status',
        'due_date',
        'paid_date',
        'payment_method',
        'notes',
        'paid_amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function paymentConcept()
    {
        return $this->belongsTo(PaymentConcept::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }

    public function isPaid()
    {
        return $this->status === 'paid' || $this->paid_amount >= $this->amount;
    }

    public function isOverdue()
    {
        return $this->status === 'pending' && $this->due_date < now();
    }
}
