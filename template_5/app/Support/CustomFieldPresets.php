<?php

namespace App\Support;

use App\Enums\ClinicSpecialty;
use App\Models\CustomField;

/**
 * Suggested visit-form fields per clinic type. Applied on install and from
 * Settings → Custom fields. Existing fields with the same key are never touched.
 */
class CustomFieldPresets
{
    /** @return list<array<string, mixed>> */
    public static function for(ClinicSpecialty $specialty): array
    {
        $teeth = array_merge(range(11, 18), range(21, 28), range(31, 38), range(41, 48));

        return match ($specialty) {
            ClinicSpecialty::Dental => [
                ['key' => 'teeth', 'label_ar' => 'الأسنان', 'label_en' => 'Teeth (FDI)', 'type' => 'multiselect', 'options' => array_map('strval', $teeth)],
                ['key' => 'procedure', 'label_ar' => 'الإجراء', 'label_en' => 'Procedure', 'type' => 'select', 'options' => ['كشف', 'حشو', 'علاج عصب', 'خلع', 'تنظيف جير', 'تركيب', 'تقويم', 'زراعة']],
                ['key' => 'next_session', 'label_ar' => 'الجلسة القادمة', 'label_en' => 'Next session plan', 'type' => 'textarea'],
            ],
            ClinicSpecialty::Pediatrics => [
                ['key' => 'head_circumference', 'label_ar' => 'محيط الرأس (سم)', 'label_en' => 'Head circumference (cm)', 'type' => 'number'],
                ['key' => 'feeding', 'label_ar' => 'الرضاعة / التغذية', 'label_en' => 'Feeding', 'type' => 'select', 'options' => ['طبيعية', 'صناعية', 'مختلطة', 'أكل عادي']],
                ['key' => 'vaccines_up_to_date', 'label_ar' => 'التطعيمات مكتملة', 'label_en' => 'Vaccines up to date', 'type' => 'checkbox'],
            ],
            ClinicSpecialty::Ophthalmology => [
                ['key' => 'va_right', 'label_ar' => 'حدة الإبصار - يمين', 'label_en' => 'Visual acuity - right', 'type' => 'text'],
                ['key' => 'va_left', 'label_ar' => 'حدة الإبصار - يسار', 'label_en' => 'Visual acuity - left', 'type' => 'text'],
                ['key' => 'iop_right', 'label_ar' => 'ضغط العين - يمين', 'label_en' => 'IOP right (mmHg)', 'type' => 'number'],
                ['key' => 'iop_left', 'label_ar' => 'ضغط العين - يسار', 'label_en' => 'IOP left (mmHg)', 'type' => 'number'],
            ],
            ClinicSpecialty::Dermatology => [
                ['key' => 'skin_type', 'label_ar' => 'نوع البشرة (فيتزباتريك)', 'label_en' => 'Skin type (Fitzpatrick)', 'type' => 'select', 'options' => ['I', 'II', 'III', 'IV', 'V', 'VI']],
                ['key' => 'affected_area', 'label_ar' => 'المنطقة المصابة', 'label_en' => 'Affected area', 'type' => 'text'],
                ['key' => 'session_type', 'label_ar' => 'نوع الجلسة', 'label_en' => 'Session type', 'type' => 'select', 'options' => ['كشف', 'ليزر', 'بيلينج', 'ميزوثيرابي', 'بوتوكس', 'فيلر', 'بلازما']],
            ],
            ClinicSpecialty::Gynecology => [
                ['key' => 'lmp', 'label_ar' => 'تاريخ آخر دورة', 'label_en' => 'Last menstrual period', 'type' => 'date'],
                ['key' => 'gravida', 'label_ar' => 'عدد مرات الحمل', 'label_en' => 'Gravida', 'type' => 'number'],
                ['key' => 'para', 'label_ar' => 'عدد الولادات', 'label_en' => 'Para', 'type' => 'number'],
            ],
            ClinicSpecialty::Orthopedics, ClinicSpecialty::Physiotherapy => [
                ['key' => 'side', 'label_ar' => 'الجهة', 'label_en' => 'Side', 'type' => 'select', 'options' => ['يمين', 'يسار', 'الجهتين']],
                ['key' => 'pain_score', 'label_ar' => 'شدة الألم (0-10)', 'label_en' => 'Pain score (0-10)', 'type' => 'number'],
            ],
            default => [],
        };
    }

    /** @return int number of fields added */
    public static function apply(ClinicSpecialty $specialty): int
    {
        $added = 0;
        $sort = (int) CustomField::where('entity', 'visit')->max('sort');

        foreach (self::for($specialty) as $field) {
            $created = CustomField::firstOrCreate(
                ['entity' => 'visit', 'key' => $field['key']],
                $field + ['specialty' => $specialty->value, 'sort' => ++$sort, 'is_required' => false, 'is_active' => true],
            );

            $added += (int) $created->wasRecentlyCreated;
        }

        return $added;
    }
}
