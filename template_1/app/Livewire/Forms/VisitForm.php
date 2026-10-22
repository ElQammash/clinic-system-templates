<?php

namespace App\Livewire\Forms;

use App\Enums\CustomFieldEntity;
use App\Models\Visit;
use App\Services\CustomFields;
use App\Support\Format;
use Livewire\Form;

class VisitForm extends Form
{
    public ?string $chief_complaint = null;

    public ?string $examination = null;

    public ?string $diagnosis = null;

    public ?string $notes = null;

    public ?string $bp_systolic = null;

    public ?string $bp_diastolic = null;

    public ?string $pulse = null;

    public ?string $temperature = null;

    public ?string $spo2 = null;

    public ?string $weight = null;

    public ?string $height = null;

    public ?string $follow_up_date = null;

    /** @var array<string, mixed> */
    public array $custom = [];

    public const VITALS = ['bp_systolic', 'bp_diastolic', 'pulse', 'temperature', 'spo2', 'weight', 'height'];

    public function setVisit(Visit $visit): void
    {
        foreach (['chief_complaint', 'examination', 'diagnosis', 'notes'] as $key) {
            $this->{$key} = $visit->{$key};
        }

        foreach (self::VITALS as $key) {
            $this->{$key} = $visit->{$key} !== null ? (string) (float) $visit->{$key} : null;
        }

        $this->follow_up_date = $visit->follow_up_date?->toDateString();
        $this->custom = $visit->custom_data ?? [];
    }

    public function rules(): array
    {
        return [
            'chief_complaint' => ['nullable', 'string', 'max:2000'],
            'examination' => ['nullable', 'string', 'max:5000'],
            'diagnosis' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'bp_systolic' => ['nullable', 'integer', 'between:40,300', 'required_with:bp_diastolic'],
            'bp_diastolic' => ['nullable', 'integer', 'between:20,200', 'required_with:bp_systolic', 'lt:bp_systolic'],
            'pulse' => ['nullable', 'integer', 'between:20,250'],
            'temperature' => ['nullable', 'numeric', 'between:30,45'],
            'spo2' => ['nullable', 'integer', 'between:50,100'],
            'weight' => ['nullable', 'numeric', 'between:0.3,400'],
            'height' => ['nullable', 'numeric', 'between:20,250'],
            'follow_up_date' => ['nullable', 'date', 'after:today'],
            ...app(CustomFields::class)->rules(CustomFieldEntity::Visit),
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'bp_systolic' => __('Systolic pressure'),
            'bp_diastolic' => __('Diastolic pressure'),
            'pulse' => __('Pulse'),
            'temperature' => __('Temperature'),
            'spo2' => __('Oxygen saturation'),
            'weight' => __('Weight'),
            'height' => __('Height'),
            'follow_up_date' => __('Follow-up date'),
            ...app(CustomFields::class)->attributes(CustomFieldEntity::Visit),
        ];
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    public function payload(): array
    {
        foreach (self::VITALS as $key) {
            $this->{$key} = filled($this->{$key}) ? Format::normalizeDigits(str_replace(',', '.', trim($this->{$key}))) : null;
        }

        $data = $this->validate();
        $custom = $data['custom'] ?? [];
        unset($data['custom']);

        foreach (self::VITALS as $key) {
            $data[$key] = $data[$key] === null ? null : (float) $data[$key];
        }

        return [$data, $custom];
    }

    /** Live BMI preview while typing. */
    public function bmi(): ?float
    {
        return Visit::calculateBmi(
            is_numeric($this->weight) ? (float) $this->weight : null,
            is_numeric($this->height) ? (float) $this->height : null,
        );
    }
}
