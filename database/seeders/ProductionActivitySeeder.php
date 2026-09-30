<?php

namespace Database\Seeders;

use App\Models\ProductionActivity;
use Illuminate\Database\Seeder;

class ProductionActivitySeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            ['name' => 'Material Purchase', 'sort_order' => 10, 'is_required' => false],
            ['name' => 'Material Preparation', 'sort_order' => 20, 'is_required' => false],
            ['name' => 'Material Receipt', 'sort_order' => 30, 'is_required' => false],
            ['name' => 'Cutting', 'sort_order' => 40, 'is_required' => false],
            ['name' => 'Sewing', 'sort_order' => 50, 'is_required' => false],
            ['name' => 'Embroidery', 'sort_order' => 60, 'is_required' => false],
            ['name' => 'Printing', 'sort_order' => 70, 'is_required' => false],
            ['name' => 'Branding', 'sort_order' => 80, 'is_required' => false],
            ['name' => 'Assembly', 'sort_order' => 90, 'is_required' => false],
            ['name' => 'Finishing', 'sort_order' => 100, 'is_required' => false],
            ['name' => 'Packaging', 'sort_order' => 110, 'is_required' => false],
            ['name' => 'Quality Control', 'sort_order' => 120, 'is_required' => true],
            ['name' => 'Delivery', 'sort_order' => 130, 'is_required' => true],
        ];

        foreach ($activities as $activity) {
            ProductionActivity::updateOrCreate(
                ['name' => $activity['name']],
                $activity
            );
        }
    }
}
