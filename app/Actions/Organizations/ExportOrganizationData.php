<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use Illuminate\Support\Carbon;

class ExportOrganizationData
{
    /**
     * Build a structured, AI-friendly snapshot of an organization.
     *
     * This action only reads application data. It does not modify
     * the organization or any of its related records.
     */
    public function execute(Organization $organization): array
    {
        $organization->load([
            'contacts' => function ($query) {
                $query->orderByDesc('is_primary')
                    ->orderBy('first_name')
                    ->orderBy('last_name');
            },

            'assessments' => function ($query) {
                $query->latest('created_at');
            },

            'followUps' => function ($query) {
                $query->latest('created_at');
            },

            'productSpecifications' => function ($query) {
                $query->latest('specification_date')
                    ->latest('id');
            },
        ]);

        return [
            'export_type' => 'oneyard_organization_intelligence',
            'export_version' => '1.0',
            'generated_at' => Carbon::now()->toIso8601String(),

            'organization' => [
                'code' => $organization->organization_code,
                'name' => $organization->name,
                'type' => $organization->type,
                'ownership' => $organization->ownership,
                'phone' => $organization->phone,
                'email' => $organization->email,
                'address' => $organization->address,
                'city' => $organization->city,
                'area' => $organization->area,
                'lga' => $organization->lga,
                'state' => $organization->state,
                'country' => $organization->country,
                'website' => $organization->website,
                'notes' => $organization->notes,
                'is_active' => $organization->is_active,
                'created_at' => $organization->created_at?->toIso8601String(),
                'updated_at' => $organization->updated_at?->toIso8601String(),
            ],

            'contacts' => $organization->contacts
                ->map(fn ($contact) => [
                    'id' => $contact->id,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'middle_name' => $contact->middle_name,
                    'position' => $contact->position,
                    'phone' => $contact->phone,
                    'alternate_phone' => $contact->alternate_phone,
                    'email' => $contact->email,
                    'whatsapp' => $contact->whatsapp,
                    'is_primary' => $contact->is_primary,
                    'is_active' => $contact->is_active,
                    'notes' => $contact->notes,
                    'created_at' => $contact->created_at?->toIso8601String(),
                    'updated_at' => $contact->updated_at?->toIso8601String(),
                ])
                ->values()
                ->all(),

            'assessments' => $organization->assessments
                ->map(fn ($assessment) => [
                    'id' => $assessment->id,
                    'created_at' => $assessment->created_at?->toIso8601String(),
                    'updated_at' => $assessment->updated_at?->toIso8601String(),
                    'data' => $this->modelAttributes($assessment),
                ])
                ->values()
                ->all(),

            'follow_ups' => $organization->followUps
                ->map(fn ($followUp) => [
                    'id' => $followUp->id,
                    'created_at' => $followUp->created_at?->toIso8601String(),
                    'updated_at' => $followUp->updated_at?->toIso8601String(),
                    'data' => $this->modelAttributes($followUp),
                ])
                ->values()
                ->all(),

            'product_specifications' => $organization->productSpecifications
                ->map(fn ($specification) => [
                    'id' => $specification->id,
                    'specification_date' => $specification->specification_date?->toDateString(),
                    'item_name' => $specification->item_name,
                    'product_type' => $specification->product_type,
                    'description' => $specification->description,
                    'unit' => $specification->unit,
                    'unit_price' => $specification->unit_price,
                    'price_updated_at' => $specification->price_updated_at?->toDateString(),
                    'material' => $specification->material,
                    'material_details' => $specification->material_details,
                    'design_details' => $specification->design_details,
                    'size_details' => $specification->size_details,
                    'branding_details' => $specification->branding_details,
                    'quality_requirements' => $specification->quality_requirements,
                    'special_instructions' => $specification->special_instructions,
                    'status' => $specification->status,
                    'notes' => $specification->notes,
                    'created_at' => $specification->created_at?->toIso8601String(),
                    'updated_at' => $specification->updated_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Return model attributes while removing internal infrastructure fields.
     *
     * This keeps the generic export useful for Assessment and Follow-up
     * records without exposing deleted timestamps or internal bookkeeping.
     */
    private function modelAttributes(object $model): array
    {
        return collect($model->getAttributes())
            ->except([
                'deleted_at',
            ])
            ->map(function ($value) {
                if ($value instanceof \DateTimeInterface) {
                    return $value->format(DATE_ATOM);
                }

                return $value;
            })
            ->all();
    }
}
