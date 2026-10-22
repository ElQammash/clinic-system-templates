<?php

namespace Database\Seeders;

use App\Models\LabTest;
use App\Models\Medicine;
use Illuminate\Database\Seeder;

/**
 * Starter medicines library (common in Egyptian pharmacies) and lab / imaging catalog.
 * Only runs on empty tables; clinics edit both lists from the app.
 */
class MedicalLibrarySeeder extends Seeder
{
    public function run(): void
    {
        if (Medicine::count() === 0) {
            foreach ($this->medicines() as [$name, $generic, $form, $strength, $dose, $frequency, $duration]) {
                Medicine::create([
                    'name' => $name, 'generic_name' => $generic, 'form' => $form, 'strength' => $strength,
                    'default_dose' => $dose, 'default_frequency' => $frequency, 'default_duration' => $duration,
                ]);
            }
        }

        if (LabTest::count() === 0) {
            foreach ($this->labTests() as [$name, $type]) {
                LabTest::create(['name' => $name, 'type' => $type]);
            }
        }
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: ?string, 4: ?string, 5: ?string, 6: ?string}> */
    protected function medicines(): array
    {
        return [
            ['Panadol', 'Paracetamol', 'tablet', '500mg', 'قرص واحد', 'كل 8 ساعات', '3 أيام'],
            ['Panadol Extra', 'Paracetamol + Caffeine', 'tablet', '500/65mg', 'قرص واحد', 'كل 8 ساعات', '3 أيام'],
            ['Cetal Syrup', 'Paracetamol', 'syrup', '250mg/5ml', '5 مل', 'كل 6 ساعات', '3 أيام'],
            ['Brufen', 'Ibuprofen', 'tablet', '400mg', 'قرص واحد', 'كل 8 ساعات', '5 أيام'],
            ['Brufen Syrup', 'Ibuprofen', 'syrup', '100mg/5ml', '5 مل', 'كل 8 ساعات', '3 أيام'],
            ['Cataflam', 'Diclofenac Potassium', 'tablet', '50mg', 'قرص واحد', '3 مرات يوميًا', '5 أيام'],
            ['Voltaren', 'Diclofenac Sodium', 'tablet', '75mg', 'قرص واحد', 'مرتين يوميًا', '5 أيام'],
            ['Augmentin', 'Amoxicillin + Clavulanate', 'tablet', '1g', 'قرص واحد', 'كل 12 ساعة', '7 أيام'],
            ['Augmentin Suspension', 'Amoxicillin + Clavulanate', 'suspension', '457mg/5ml', '5 مل', 'كل 12 ساعة', '7 أيام'],
            ['Amoxil', 'Amoxicillin', 'capsule', '500mg', 'كبسولة واحدة', 'كل 8 ساعات', '7 أيام'],
            ['Zithromax', 'Azithromycin', 'tablet', '500mg', 'قرص واحد', 'مرة يوميًا', '3 أيام'],
            ['Xithrone Suspension', 'Azithromycin', 'suspension', '200mg/5ml', '5 مل', 'مرة يوميًا', '3 أيام'],
            ['Ciprobay', 'Ciprofloxacin', 'tablet', '500mg', 'قرص واحد', 'كل 12 ساعة', '7 أيام'],
            ['Flagyl', 'Metronidazole', 'tablet', '500mg', 'قرص واحد', 'كل 8 ساعات', '7 أيام'],
            ['Klacid', 'Clarithromycin', 'tablet', '500mg', 'قرص واحد', 'كل 12 ساعة', '7 أيام'],
            ['Ceftriaxone', 'Ceftriaxone', 'injection', '1g', 'حقنة واحدة', 'مرة يوميًا', '5 أيام'],
            ['Nexium', 'Esomeprazole', 'tablet', '40mg', 'قرص واحد', 'مرة يوميًا', '14 يومًا'],
            ['Controloc', 'Pantoprazole', 'tablet', '40mg', 'قرص واحد', 'مرة يوميًا', '14 يومًا'],
            ['Antinal', 'Nifuroxazide', 'capsule', '200mg', 'كبسولة واحدة', 'كل 6 ساعات', '3 أيام'],
            ['Motilium', 'Domperidone', 'tablet', '10mg', 'قرص واحد', '3 مرات يوميًا', '5 أيام'],
            ['Buscopan', 'Hyoscine Butylbromide', 'tablet', '10mg', 'قرص واحد', '3 مرات يوميًا', 'عند اللزوم'],
            ['Spasmo-Digestin', null, 'tablet', null, 'قرص واحد', '3 مرات يوميًا', '7 أيام'],
            ['Rani 2', 'Oral rehydration salts', 'sachet', null, 'كيس في 200 مل ماء', 'بعد كل إسهال', '3 أيام'],
            ['Telfast', 'Fexofenadine', 'tablet', '180mg', 'قرص واحد', 'مرة يوميًا', '7 أيام'],
            ['Zyrtec', 'Cetirizine', 'tablet', '10mg', 'قرص واحد', 'مرة يوميًا', '7 أيام'],
            ['Claritine Syrup', 'Loratadine', 'syrup', '5mg/5ml', '5 مل', 'مرة يوميًا', '7 أيام'],
            ['Aerius', 'Desloratadine', 'tablet', '5mg', 'قرص واحد', 'مرة يوميًا', '10 أيام'],
            ['Ventolin Inhaler', 'Salbutamol', 'inhaler', '100mcg', 'بختين', 'عند اللزوم', 'مستمر'],
            ['Farcosolvin Syrup', 'Ambroxol + Salbutamol', 'syrup', null, '5 مل', '3 مرات يوميًا', '5 أيام'],
            ['Mucosol', 'Carbocisteine', 'syrup', null, '10 مل', '3 مرات يوميًا', '5 أيام'],
            ['Otrivin Drops', 'Xylometazoline', 'drops', '0.05%', 'نقطتين', 'مرتين يوميًا', '5 أيام'],
            ['Glucophage', 'Metformin', 'tablet', '1000mg', 'قرص واحد', 'مرتين يوميًا', 'مستمر'],
            ['Concor', 'Bisoprolol', 'tablet', '5mg', 'قرص واحد', 'مرة يوميًا', 'مستمر'],
            ['Norvasc', 'Amlodipine', 'tablet', '5mg', 'قرص واحد', 'مرة يوميًا', 'مستمر'],
            ['Capoten', 'Captopril', 'tablet', '25mg', 'قرص واحد', 'مرتين يوميًا', 'مستمر'],
            ['Aspocid', 'Aspirin', 'tablet', '75mg', 'قرص واحد', 'مرة يوميًا', 'مستمر'],
            ['Lipitor', 'Atorvastatin', 'tablet', '20mg', 'قرص واحد', 'قبل النوم', 'مستمر'],
            ['Neurobion', 'Vitamin B complex', 'tablet', null, 'قرص واحد', 'مرة يوميًا', 'شهر'],
            ['Ferrotron', 'Iron', 'capsule', null, 'كبسولة واحدة', 'مرة يوميًا', 'شهر'],
            ['Devarol-S', 'Vitamin D3', 'injection', '200000 IU', 'أمبول واحد', 'مرة واحدة', null],
            ['Fucidin Cream', 'Fusidic acid', 'cream', '2%', 'دهان طبقة رقيقة', 'مرتين يوميًا', '7 أيام'],
            ['Betaderm Cream', 'Betamethasone', 'cream', '0.1%', 'دهان طبقة رقيقة', 'مرتين يوميًا', '7 أيام'],
            ['Daktarin Cream', 'Miconazole', 'cream', '2%', 'دهان طبقة رقيقة', 'مرتين يوميًا', '14 يومًا'],
            ['Tobradex Eye Drops', 'Tobramycin + Dexamethasone', 'drops', null, 'نقطة', '4 مرات يوميًا', '7 أيام'],
            ['Dolo-D', 'Paracetamol + Pseudoephedrine', 'tablet', null, 'قرص واحد', 'كل 8 ساعات', '5 أيام'],
        ];
    }

    /** @return list<array{0: string, 1: string}> */
    protected function labTests(): array
    {
        return [
            ['صورة دم كاملة CBC', 'lab'], ['سكر صائم FBS', 'lab'], ['سكر فاطر PPBS', 'lab'], ['سكر تراكمي HbA1c', 'lab'],
            ['وظائف كبد ALT/AST', 'lab'], ['وظائف كلى Creatinine/Urea', 'lab'], ['دهون ثلاثية وكوليسترول Lipid profile', 'lab'],
            ['وظائف غدة درقية TSH', 'lab'], ['فيتامين د Vitamin D', 'lab'], ['مخزون الحديد Ferritin', 'lab'],
            ['سرعة الترسيب ESR', 'lab'], ['CRP', 'lab'], ['تحليل بول كامل Urine analysis', 'lab'], ['تحليل براز Stool analysis', 'lab'],
            ['حمض البوليك Uric acid', 'lab'], ['فيروس سي HCV Ab', 'lab'], ['فيروس بي HBsAg', 'lab'],
            ['أشعة سينية على الصدر Chest X-ray', 'imaging'], ['سونار على البطن Abdominal ultrasound', 'imaging'],
            ['سونار على الحوض Pelvic ultrasound', 'imaging'], ['أشعة بانوراما للأسنان Panoramic X-ray', 'imaging'],
            ['رسم قلب ECG', 'imaging'], ['إيكو على القلب Echocardiography', 'imaging'],
            ['أشعة مقطعية CT', 'imaging'], ['رنين مغناطيسي MRI', 'imaging'],
        ];
    }
}
