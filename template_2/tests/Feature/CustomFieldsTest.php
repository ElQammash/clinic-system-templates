<?php

use App\Enums\Role;
use App\Livewire\Settings\CustomFields;
use App\Models\CustomField;
use Livewire\Livewire;

it('lets the admin create a custom field with options', function () {
    actingAsRole(Role::Admin);

    Livewire::test(CustomFields::class)
        ->set('entity', 'visit')
        ->call('create')
        ->set('label_ar', 'رقم السن')
        ->set('label_en', 'Tooth number')
        ->set('type', 'select')
        ->set('optionsText', "11\n12\n12\n\n13")
        ->set('specialty', 'dental')
        ->call('save')
        ->assertHasNoErrors();

    $field = CustomField::firstOrFail();

    expect($field->key)->toBe('tooth_number')
        ->and($field->entity->value)->toBe('visit')
        ->and($field->options)->toBe(['11', '12', '13'])
        ->and($field->specialty->value)->toBe('dental');
});

it('requires options for choice fields', function () {
    actingAsRole(Role::Admin);

    Livewire::test(CustomFields::class)
        ->call('create')
        ->set('label_ar', 'نوع الجلد')
        ->set('type', 'multiselect')
        ->call('save')
        ->assertHasErrors('optionsText');
});

it('generates unique keys and keeps them when the label changes', function () {
    actingAsRole(Role::Admin);
    $make = fn () => Livewire::test(CustomFields::class)->set('entity', 'patient')->call('create')->set('label_ar', 'ملاحظة')->set('label_en', 'Remark')->call('save');

    $make();
    $make();

    expect(CustomField::pluck('key')->all())->toBe(['remark', 'remark_2']);

    $field = CustomField::first();
    Livewire::test(CustomFields::class)->call('edit', $field->id)->set('label_en', 'Comment')->call('save');
    expect($field->fresh()->key)->toBe('remark');
});

it('forbids non-admins from managing custom fields', function () {
    actingAsRole(Role::Doctor);
    $this->get(route('settings.custom-fields'))->assertForbidden();
});
