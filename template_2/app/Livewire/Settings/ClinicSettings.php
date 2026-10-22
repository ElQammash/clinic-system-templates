<?php

namespace App\Livewire\Settings;

use App\Actions\Settings\UpdateClinicSettings;
use App\Enums\ClinicSpecialty;
use App\Enums\Module;
use App\Enums\Permission;
use App\Livewire\Forms\ClinicSettingsForm;
use App\Services\Messaging\AppointmentReminders;
use App\Support\Branding;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Settings')]
class ClinicSettings extends Component
{
    use WithFileUploads;

    public ClinicSettingsForm $form;

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    public bool $removeLogo = false;

    #[Url]
    public string $tab = 'general';

    public function mount(): void
    {
        $this->authorize(Permission::SettingsManage->value);
        $this->form->fillFromSettings();
    }

    public function save(UpdateClinicSettings $action): void
    {
        $this->authorize(Permission::SettingsManage->value);

        $this->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:'.config('clinic.logo.max_kb')],
        ]);

        $changes = $action->handle($this->form->values(), $this->logo, $this->removeLogo);

        $this->reset('logo', 'removeLogo');

        // Language, color or logo changes need a full reload to take effect everywhere.
        if (array_intersect(array_keys($changes), ['default_locale', 'primary_color', 'logo_path', 'clinic_name', 'clinic_name_en', 'digits', 'module_lab', 'module_expenses', 'module_inventory'])) {
            session()->flash('toast', __('Settings saved.'));
            $this->redirectRoute('settings.clinic', ['tab' => $this->tab]);

            return;
        }

        $this->dispatch('toast', message: __('Settings saved.'));
    }

    public function resetColor(): void
    {
        $this->form->primary_color = null;
    }

    public function render()
    {
        return view('livewire.settings.clinic-settings', [
            'tabs' => [
                'general' => __('Clinic info'),
                'branding' => __('Branding'),
                'regional' => __('Language & currency'),
                'modules' => __('Specialty & modules'),
                'printing' => __('Printing'),
                'messages' => __('Messages'),
            ],
            'placeholders' => AppointmentReminders::PLACEHOLDERS,
            'specialties' => ClinicSpecialty::options(),
            'modules' => Module::cases(),
            'logoUrl' => Branding::logoUrl(),
        ]);
    }
}
