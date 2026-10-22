@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null, 'required' => false])
@php $error ??= $for; @endphp
<div {{ $attributes }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="form-label">
            {{ $label }}
            @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($error)
        @error($error)
            <p class="form-error">{{ $message }}</p>
        @enderror
    @endif

    @if ($hint)
        <p class="form-hint">{{ $hint }}</p>
    @endif
</div>
