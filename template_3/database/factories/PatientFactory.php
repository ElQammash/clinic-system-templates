<?php

namespace Database\Factories;

use App\Actions\Patients\SavePatient;
use App\Enums\BloodType;
use App\Enums\Gender;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public const MALE_NAMES = ['محمد', 'أحمد', 'محمود', 'مصطفى', 'عمر', 'يوسف', 'علي', 'حسن', 'خالد', 'إبراهيم', 'كريم', 'عبد الله', 'مازن', 'زياد', 'طارق', 'هشام', 'سيف', 'آدم', 'ياسين', 'تامر'];

    public const FEMALE_NAMES = ['فاطمة', 'مريم', 'نور', 'سارة', 'منى', 'هبة', 'آية', 'دينا', 'ريم', 'سلمى', 'ملك', 'جنى', 'ياسمين', 'نادين', 'رحمة', 'شهد', 'إيمان', 'هالة', 'أسماء', 'نهى'];

    public const FAMILY_NAMES = ['عبد الرحمن', 'السيد', 'حسن', 'إبراهيم', 'محمود', 'عبد العزيز', 'الشافعي', 'النجار', 'المصري', 'فهمي', 'سليمان', 'رمضان', 'عثمان', 'شوقي', 'الجمال', 'منصور', 'عبد الحميد', 'زكي', 'فؤاد', 'البنا'];

    public const ADDRESSES = ['مدينة نصر، القاهرة', 'المعادي، القاهرة', 'الدقي، الجيزة', 'المهندسين، الجيزة', 'شبرا، القاهرة', 'مصر الجديدة، القاهرة', 'التجمع الخامس، القاهرة الجديدة', 'سموحة، الإسكندرية', 'المنصورة، الدقهلية', 'طنطا، الغربية', '6 أكتوبر، الجيزة', 'حلوان، القاهرة'];

    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());
        $first = fake()->randomElement($gender === Gender::Male ? self::MALE_NAMES : self::FEMALE_NAMES);

        return [
            'name' => $first.' '.fake()->randomElement(self::MALE_NAMES).' '.fake()->randomElement(self::FAMILY_NAMES),
            'phone' => fake()->randomElement(['010', '011', '012', '015']).fake()->numerify('########'),
            'gender' => $gender,
            'birth_date' => fake()->dateTimeBetween('-80 years', '-1 month')->format('Y-m-d'),
            'address' => fake()->randomElement(self::ADDRESSES),
            'blood_type' => fake()->optional(0.6)->randomElement(BloodType::cases()),
            'allergies' => fake()->optional(0.15)->randomElement(['بنسلين', 'أسبرين', 'سلفا', 'البيض', 'الفول السوداني']),
            'chronic_diseases' => fake()->optional(0.2)->randomElement(['سكر', 'ضغط', 'ربو', 'سكر، ضغط', 'حساسية صدر']),
            'file_number' => fn () => app(SavePatient::class)->nextFileNumber(),
        ];
    }
}
