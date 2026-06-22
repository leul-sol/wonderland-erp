<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['sku' => 'BEEF-001', 'name' => 'Beef (kg)', 'unit' => 'kg', 'unit_cost' => 450, 'quantity_on_hand' => 0],
            ['sku' => 'BUN-001', 'name' => 'Burger Bun', 'unit' => 'each', 'unit_cost' => 15, 'quantity_on_hand' => 0],
            ['sku' => 'COLA-330', 'name' => 'Cola 330ml', 'unit' => 'each', 'unit_cost' => 25, 'quantity_on_hand' => 0],
        ];

        foreach ($items as $row) {
            InventoryItem::query()->updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                    'unit_cost' => $row['unit_cost'],
                    'quantity_on_hand' => $row['quantity_on_hand'],
                    'reorder_level' => 10,
                    'is_active' => true,
                ]
            );
        }

        $burger = MenuItem::query()->updateOrCreate(
            ['code' => 'BURGER-CL'],
            ['name' => 'Classic Burger', 'price' => 350, 'category' => 'food', 'is_active' => true]
        );

        $cola = MenuItem::query()->updateOrCreate(
            ['code' => 'DRINK-COLA'],
            ['name' => 'Cola', 'price' => 80, 'category' => 'beverage', 'is_active' => true]
        );

        $beefId = InventoryItem::query()->where('sku', 'BEEF-001')->value('id');
        $bunId = InventoryItem::query()->where('sku', 'BUN-001')->value('id');
        $colaId = InventoryItem::query()->where('sku', 'COLA-330')->value('id');

        DB::table('menu_item_ingredients')->updateOrInsert(
            ['menu_item_id' => $burger->id, 'inventory_item_id' => $beefId],
            ['quantity' => 0.2]
        );
        DB::table('menu_item_ingredients')->updateOrInsert(
            ['menu_item_id' => $burger->id, 'inventory_item_id' => $bunId],
            ['quantity' => 1]
        );
        DB::table('menu_item_ingredients')->updateOrInsert(
            ['menu_item_id' => $cola->id, 'inventory_item_id' => $colaId],
            ['quantity' => 1]
        );
    }
}
