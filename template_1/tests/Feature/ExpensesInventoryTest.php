<?php

use App\Actions\Inventory\AdjustStock;
use App\Enums\Role;
use App\Livewire\Expenses\Index as Expenses;
use App\Models\Expense;
use App\Models\InventoryItem;
use Database\Seeders\BillingSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(BillingSeeder::class));

it('records expenses and totals them per month', function () {
    actingAsRole(Role::Accountant);

    Livewire::test(Expenses::class)
        ->call('edit')
        ->set('expense.description', 'إيجار أكتوبر')
        ->set('expense.amount', '5000.50')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('إيجار أكتوبر');

    expect(Expense::first()->amount)->toBe(5000.5)
        ->and(Expense::first()->getRawOriginal('amount'))->toBe(500050);
});

it('keeps reception and doctors out of expenses', function (Role $role) {
    actingAsRole($role);

    $this->get(route('expenses.index'))->assertForbidden();
})->with([Role::Receptionist, Role::Doctor]);

it('hides expenses when the module is off', function () {
    settings()->set(['module_expenses' => false]);
    actingAsRole(Role::Admin);

    $this->get(route('expenses.index'))->assertNotFound();
});

it('moves stock in and out and flags low stock', function () {
    settings()->set(['module_inventory' => true]);
    $user = actingAsRole(Role::Admin);
    $item = InventoryItem::create(['name' => 'جوانتي', 'unit' => 'علبة', 'reorder_level' => 5]);
    $stock = app(AdjustStock::class);

    $stock->handle($item, 'in', 20, 'توريد', $user);
    $stock->handle($item, 'out', 16, null, $user);

    expect($item->fresh()->quantity)->toBe(4)
        ->and($item->fresh()->isLow())->toBeTrue()
        ->and(InventoryItem::lowStock()->count())->toBe(1);

    $stock->handle($item, 'adjust', 10, 'جرد', $user);
    expect($item->fresh()->quantity)->toBe(10)
        ->and($item->movements()->first()->quantity)->toBe(6);

    expect(fn () => $stock->handle($item, 'out', 11, null, $user))->toThrow(ValidationException::class);

    $this->get(route('inventory.index'))->assertOk()->assertSee('جوانتي');
});

it('hides inventory by default', function () {
    actingAsRole(Role::Admin);

    $this->get(route('inventory.index'))->assertNotFound();
});
