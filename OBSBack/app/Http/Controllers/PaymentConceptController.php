<?php

namespace App\Http\Controllers;

use App\Models\PaymentConcept;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class PaymentConceptController extends Controller
{
    public function index()
    {
        try {
            $concepts = PaymentConcept::where('status', 'active')->get();
            return response()->json($concepts);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Intenta nuevamente, no hay conexión en este momento. Te agradecemos por tu paciencia.',
                'error' => 'database_connection_error'
            ], 503);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numñeric|min:0',
            'type' => 'required|in:monthly,one_time,optional'
        ]);

        try {
            $concept = PaymentConcept::create($request->all());
            return response()->json($concept, 201);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Intenta nuevamente, no hay conexión en este momento. Te agradecemos por tu paciencia.',
                'error' => 'database_connection_error'
            ], 503);
        }
    }

    public function update(Request $request, PaymentConcept $concept)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|numeric|min:0',
            'type' => 'sometimes|in:monthly,one_time,optional',
            'status' => 'sometimes|in:active,inactive'
        ]);

        try {
            $concept->update($request->all());
            return response()->json($concept);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Intenta nuevamente, no hay conexión en este momento. Te agradecemos por tu paciencia.',
                'error' => 'database_connection_error'
            ], 503);
        }
    }

    public function destroy(PaymentConcept $concept)
    {
        try {
            $concept->delete();
            return response()->json(null, 204);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Intenta nuevamente, no hay conexión en este momento. Te agradecemos por tu paciencia.',
                'error' => 'database_connection_error'
            ], 503);
        }
    }
}
