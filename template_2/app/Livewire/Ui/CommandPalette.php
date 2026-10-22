<?php

namespace App\Livewire\Ui;

use App\Models\Patient;
use App\Support\ArabicText;
use App\Support\Navigation;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Template 2 command palette (Ctrl/⌘ + K): jump to any page or open a patient file.
 * Presentation-only helper; it reuses Navigation and the Patient search scope.
 */
class CommandPalette extends Component
{
    public string $query = '';

    /** @return list<array{label: string, url: string, icon: string, hint: string}> */
    #[Computed]
    public function results(): array
    {
        $user = auth()->user();
        $term = ArabicText::normalize($this->query);

        $pages = collect(Navigation::flat($user))
            ->filter(fn ($item) => $term === '' || Str::contains(ArabicText::normalize($item['label']), $term) || Str::contains($item['route'], $term))
            ->take($term === '' ? 8 : 5)
            ->map(fn ($item) => ['label' => $item['label'], 'url' => $item['url'], 'icon' => $item['icon'], 'hint' => __('Page')]);

        $patients = collect();
        if (mb_strlen(trim($this->query)) >= 2 && $user->can('patients.view')) {
            $patients = Patient::query()->search($this->query)->limit(6)->get()
                ->map(fn (Patient $p) => [
                    'label' => $p->name,
                    'url' => route('patients.show', $p),
                    'icon' => 'user',
                    'hint' => '#'.$p->file_number.($p->phone ? ' · '.$p->phone : ''),
                ]);
        }

        return $patients->concat($pages)->values()->all();
    }

    public function render()
    {
        return view('livewire.ui.command-palette');
    }
}
