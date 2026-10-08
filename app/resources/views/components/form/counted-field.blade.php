@props(['name', 'label', 'max', 'value' => '', 'rows' => null, 'help' => null, 'disabled' => false, 'labelClass' => ''])
@php $length = mb_strlen((string) $value); @endphp
<div class="mb-4">
    <div class="mb-1.5 flex items-baseline justify-between">
        <label for="{{ $name }}" class="block font-bold {{ $labelClass }}">{{ $label }} <span aria-hidden="true">*</span></label>
        <span class="text-xs font-semibold {{ $length > $max ? 'text-red-800' : 'text-ink-700' }}" aria-live="polite">{{ $length }} / {{ $max }}</span>
    </div>
    @if ($rows)
        <textarea id="{{ $name }}" wire:model.live.debounce.300ms="{{ $name }}" rows="{{ $rows }}" maxlength="{{ $max + 50 }}" @disabled($disabled)
            @if($help) aria-describedby="{{ $name }}-aide" @endif @error($name) aria-invalid="true" @enderror
            class="field"></textarea>
    @else
        <input id="{{ $name }}" type="text" wire:model.live.debounce.300ms="{{ $name }}" maxlength="{{ $max + 50 }}" @disabled($disabled)
            @if($help) aria-describedby="{{ $name }}-aide" @endif @error($name) aria-invalid="true" @enderror
            class="field">
    @endif
    @if ($help) <p id="{{ $name }}-aide" class="mt-1 text-sm text-ink-700">{{ $help }}</p> @endif
    @error($name) <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
</div>
