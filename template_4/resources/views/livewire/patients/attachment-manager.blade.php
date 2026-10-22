@php use App\Support\Format; @endphp
<div>
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <div class="flex flex-1 gap-1 overflow-x-auto">
            <button type="button" wire:click="$set('filter', '')" @class(['btn btn-sm shrink-0', 'btn-primary' => $filter === '', 'btn-ghost' => $filter !== ''])>{{ __('All') }}</button>
            @foreach ($categories as $value => $label)
                <button type="button" wire:click="$set('filter', '{{ $value }}')" @class(['btn btn-sm shrink-0', 'btn-primary' => $filter === $value, 'btn-ghost' => $filter !== $value])>{{ $label }}</button>
            @endforeach
        </div>
        @if ($canUpload)
            <button type="button" wire:click="$toggle('showUpload')" class="btn btn-primary"><x-icon name="upload" class="size-4" /> {{ __('Upload files') }}</button>
        @endif
    </div>

    @if ($canUpload && $showUpload)
        <form wire:submit="upload" class="card mb-6 p-5">
            <label x-data="{ drag: false }"
                   x-on:dragover.prevent="drag = true" x-on:dragleave.prevent="drag = false"
                   x-on:drop.prevent="drag = false; $refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change'))"
                   :class="drag ? 'border-primary-500 bg-primary-50' : 'border-neutral-300 bg-neutral-50/60'"
                   class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-none border-2 border-dashed px-6 py-8 text-center transition hover:border-primary-400">
                <span class="grid size-12 place-items-center rounded-full bg-white text-primary-600 shadow-none"><x-icon name="upload" class="size-6" /></span>
                <span class="text-sm font-semibold text-ink">{{ __('Drop files here or click to choose') }}</span>
                <span class="text-xs text-muted">{{ __('Images, PDF, DICOM or Word — up to :size MB each, 10 files at a time.', ['size' => (int) (config('clinic.attachments.max_kb') / 1024)]) }}</span>
                <input x-ref="input" type="file" wire:model="files" multiple accept="{{ $accept }}" class="sr-only">
            </label>
            <div wire:loading wire:target="files" class="mt-3 text-sm text-primary-700">{{ __('Uploading…') }}</div>
            @error('files') <p class="form-error">{{ $message }}</p> @enderror
            @error('files.*') <p class="form-error">{{ $message }}</p> @enderror

            @if ($files)
                <ul class="mt-4 divide-y divide-line rounded-none border border-line">
                    @foreach ($files as $index => $file)
                        <li class="flex items-center gap-3 px-4 py-2.5 text-sm" wire:key="upload-{{ $index }}">
                            <x-icon name="paperclip" class="size-4 text-neutral-400" />
                            <span class="min-w-0 flex-1 truncate" dir="ltr">{{ $file->getClientOriginalName() }}</span>
                            <span class="text-xs text-muted">{{ \Illuminate\Support\Number::fileSize($file->getSize(), 1) }}</span>
                            <button type="button" wire:click="removeFile({{ $index }})" class="text-neutral-400 hover:text-rose-600"><x-icon name="close" class="size-4" /></button>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <x-field :label="__('Type')" for="att-category" error="category">
                    <select wire:model="category" id="att-category" class="form-control">
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('Title')" for="att-title" error="title" :hint="count($files) > 1 ? __('File names are used when uploading several files.') : null">
                    <input wire:model="title" id="att-title" class="form-control" @disabled(count($files) > 1) placeholder="{{ __('e.g. Chest X-ray') }}">
                </x-field>
                <x-field :label="__('Date taken')" for="att-date" error="taken_at">
                    <input wire:model="taken_at" id="att-date" type="date" max="{{ now()->format('Y-m-d') }}" class="form-control">
                </x-field>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" wire:click="$set('showUpload', false)" class="btn btn-secondary">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="files,upload">{{ __('Save to patient file') }}</button>
            </div>
        </form>
    @endif

    @if ($attachments->isEmpty())
        <div class="card px-6 py-14 text-center">
            <div class="mx-auto grid size-14 place-items-center rounded-full bg-primary-50 text-primary-600"><x-icon name="paperclip" class="size-7" /></div>
            <p class="mt-3 font-semibold text-ink">{{ __('No files yet') }}</p>
            <p class="mt-1 text-sm text-muted">{{ __('X-rays, lab results and reports uploaded here appear in the patient timeline.') }}</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($attachments as $attachment)
                <div class="card group overflow-hidden" wire:key="att-{{ $attachment->id }}">
                    <div class="relative grid aspect-[4/3] place-items-center overflow-hidden bg-neutral-100">
                        @if ($canOpen && $attachment->isImage())
                            <img src="{{ route('attachments.show', $attachment) }}" alt="{{ $attachment->title }}" loading="lazy" class="size-full object-cover transition group-hover:scale-105">
                        @else
                            <div class="flex flex-col items-center gap-2 text-neutral-400">
                                <x-icon :name="$attachment->category->icon()" class="size-10" />
                                <span class="rounded-none bg-white px-1.5 py-0.5 font-mono text-[11px] font-semibold text-neutral-500">{{ $attachment->extension() }}</span>
                            </div>
                        @endif
                        <x-badge :color="$attachment->category->color()" class="absolute start-2 top-2">{{ $attachment->category->label() }}</x-badge>
                        @unless ($canOpen)
                            <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-neutral-900/60 py-1.5 text-xs text-white"><x-icon name="lock" class="size-3.5" /> {{ __('Medical file — doctors only') }}</span>
                        @endunless
                    </div>
                    <div class="p-4">
                        <p class="truncate font-semibold text-ink" title="{{ $attachment->title }}">{{ $attachment->title }}</p>
                        <p class="mt-1 text-xs text-muted">
                            {{ Format::date($attachment->taken_at ?? $attachment->created_at) }} · {{ $attachment->humanSize() }}
                            @if ($attachment->uploader) · {{ $attachment->uploader->name }} @endif
                        </p>
                        <div class="mt-3 flex items-center gap-1">
                            @if ($canOpen)
                                <a href="{{ route('attachments.show', $attachment) }}" target="_blank" class="btn btn-secondary btn-sm"><x-icon name="eye" class="size-4" /> {{ __('Open') }}</a>
                                <a href="{{ route('attachments.show', [$attachment, 'download' => 1]) }}" class="btn btn-ghost btn-sm" title="{{ __('Download') }}"><x-icon name="download" class="size-4" /></a>
                            @endif
                            @if ($canDelete)
                                <button type="button" wire:click="delete({{ $attachment->id }})" wire:confirm="{{ __('Delete this file permanently?') }}" class="btn btn-ghost btn-sm ms-auto text-rose-600" title="{{ __('Delete') }}"><x-icon name="trash" class="size-4" /></button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
