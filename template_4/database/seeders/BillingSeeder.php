<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Service;
use App\Models\VisitType;
use Illuminate\Database\Seeder;

/**
 * Starter services, expense categories, and links between visit types and services.
 * Only fills empty tables.
 */
class BillingSeeder extends Seeder
{
    public function run(): void
    {
        if (Service::count() === 0) {
            $services = [
                ['كشف', 'Consultation', 'كشف', 300],
                ['استشارة', 'Follow-up visit', 'كشف', 150],
                ['كشف مستعجل', 'Urgent consultation', 'كشف', 450],
                ['جلسة نيبولايزر', 'Nebulizer session', 'إجراءات', 100],
                ['حقنة', 'Injection', 'إجراءات', 50],
                ['غيار على جرح', 'Wound dressing', 'إجراءات', 120],
                ['رسم قلب ECG', 'ECG', 'فحوصات', 150],
                ['سونار', 'Ultrasound', 'فحوصات', 350],
                ['تقرير طبي', 'Medical report', 'أخرى', 100],
            ];

            foreach ($services as $i => [$ar, $en, $category, $price]) {
                Service::create(['name_ar' => $ar, 'name_en' => $en, 'category' => $category, 'price' => $price, 'sort' => $i]);
            }
        }

        // Link visit types to the service with the same name so invoices pick the right line.
        VisitType::query()->whereNull('service_id')->get()->each(function (VisitType $type) {
            $service = Service::query()->where('name_ar', $type->name_ar)->first();
            $type->update(['service_id' => $service?->id]);
        });

        if (ExpenseCategory::count() === 0) {
            foreach ([
                ['إيجار', 'Rent'], ['كهرباء ومياه وإنترنت', 'Utilities'], ['مرتبات', 'Salaries'],
                ['مستلزمات طبية', 'Medical supplies'], ['أدوات مكتبية ونظافة', 'Office & cleaning'],
                ['صيانة', 'Maintenance'], ['دعاية وتسويق', 'Marketing'], ['أخرى', 'Other'],
            ] as [$ar, $en]) {
                ExpenseCategory::create(['name_ar' => $ar, 'name_en' => $en]);
            }
        }
    }
}
