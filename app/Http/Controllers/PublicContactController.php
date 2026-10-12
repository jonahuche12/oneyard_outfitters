<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'category' => [
                'required',
                'string',
                'in:Fabrics / Materials,Clothing,Uniforms,Institutional Supplies,Procurement / Sourcing,Other Requirement',
            ],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $lead = Lead::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'organization_name' => $validated['organization'] ?? null,
            'phone' => $validated['phone'],
            'category' => $validated['category'],
            'message' => $validated['message'],
            'source' => Lead::SOURCE_WEBSITE,
            'status' => Lead::STATUS_NEW,
        ]);

        return response()->json([
            'message' => 'Your enquiry has been received.',
            'lead_id' => $lead->id,
        ], 201);
    }
}
