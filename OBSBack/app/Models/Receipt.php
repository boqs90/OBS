<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'receipt_number',
        'total_amount',
        'payment_method',
        'payer_name',
        'payer_email',
        'payer_phone',
        'details',
        'pdf_path'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2'
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public static function generateReceiptNumber()
    {
        $latest = self::latest()->first();
        if (!$latest) {
            return 'RCP-000001';
        }
        
        $number = intval(substr($latest->receipt_number, 4)) + 1;
        return 'RCP-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
