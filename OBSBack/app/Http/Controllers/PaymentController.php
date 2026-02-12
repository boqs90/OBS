<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentConcept;
use App\Models\Receipt;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['student', 'paymentConcept', 'receipt']);
        
        // Filtros
        if ($request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->student_id) {
            $query->where('student_id', $request->student_id);
        }
        
        if ($request->date_from) {
            $query->whereDate('due_date', '>=', $request->date_from);
        }
        
        if ($request->date_to) {
            $query->whereDate('due_date', '<=', $request->date_to);
        }
        
        $payments = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json($payments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'payment_concept_id' => 'required|exists:payment_concepts,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        $payment = Payment::create($request->all());
        
        return response()->json($payment->load(['student', 'paymentConcept']), 201);
    }

    public function show(Payment $payment)
    {
        return response()->json($payment->load(['student', 'paymentConcept', 'receipt']));
    }

    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,paid,partial,overdue',
            'notes' => 'nullable|string'
        ]);

        $payment->update($request->all());
        
        return response()->json($payment->load(['student', 'paymentConcept', 'receipt']));
    }

    public function processPayment(Request $request, Payment $payment)
    {
        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payer_name' => 'required|string',
            'payer_email' => 'nullable|email',
            'payer_phone' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            // Actualizar pago
            $payment->paid_amount = $request->paid_amount;
            $payment->payment_method = $request->payment_method;
            $payment->paid_date = now();
            $payment->notes = $request->notes;
            
            if ($request->paid_amount >= $payment->amount) {
                $payment->status = 'paid';
            } else {
                $payment->status = 'partial';
            }
            
            $payment->save();

            // Crear recibo
            $receipt = Receipt::create([
                'payment_id' => $payment->id,
                'receipt_number' => Receipt::generateReceiptNumber(),
                'total_amount' => $request->paid_amount,
                'payment_method' => $request->payment_method,
                'payer_name' => $request->payer_name,
                'payer_email' => $request->payer_email,
                'payer_phone' => $request->payer_phone,
                'details' => $request->notes
            ]);

            DB::commit();

            return response()->json([
                'payment' => $payment->load(['student', 'paymentConcept', 'receipt']),
                'receipt' => $receipt
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error processing payment: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->json(null, 204);
    }

    public function getDashboardStats()
    {
        $totalIncome = Payment::where('status', 'paid')->sum('paid_amount');
        $pendingPayments = Payment::where('status', 'pending')->count();
        $overduePayments = Payment::where('status', 'pending')->where('due_date', '<', now())->count();
        $monthlyIncome = Payment::where('status', 'paid')
            ->whereMonth('paid_date', now()->month)
            ->sum('paid_amount');

        return response()->json([
            'total_income' => $totalIncome,
            'pending_payments' => $pendingPayments,
            'overdue_payments' => $overduePayments,
            'monthly_income' => $monthlyIncome
        ]);
    }

    public function getRecentTransactions()
    {
        $transactions = Payment::with(['student', 'paymentConcept'])
            ->whereNotNull('paid_date')
            ->orderBy('paid_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json($transactions);
    }
}
