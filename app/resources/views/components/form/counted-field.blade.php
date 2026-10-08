@props(['name', 'label', 'max', 'value' => '', 'rows' => null, 'help' => null, 'disabled' => false, 'labelClass' => ''])
@php $length = mb_strlen((string) $value); @endphp
<div class="mb-4">
    <div class="mb-1 flex items-baseline justify-between">
        <label for="{{ $name }}" class="block text-sm font-medium {{ $labelClass }}">{{ $label }} <span aria-hidden="true">*</span></label>
        <span class="text-xs {{ $length > $max ? 'text-red-800' : 'text-ink-500' }}" aria-live="polite">{{ $length }} / {{ $max }}</span>
    </div>
    @if ($rows)
        <textarea id="{{ $name }}" wire:model.live.debounce.300ms="{{ $name }}" rows="{{ $rows }}" maxlength="{{ $max + 50 }}" @disabled($disabled)
            @if($help) aria-describedby="{{ $name }}-aide" @endif @error($name) aria-invalid="true" @enderror
            class="block w-full rounded border border-ink-300 bg-white px-3 py-2 disabled:bg-ink-100"></textarea>
    @else
        <input id="{{ $name }}" type="text" wire:model.live.debounce.300ms="{{ $name }}" maxlength="{{ $max + 50 }}" @disabled($disabled)
            @if($help) aria-describedby="{{ $name }}-aide" @endif @error($name) aria-invalid="true" @enderror
            class="block w-full rounded border border-ink-300 bg-white px-3 py-2 disabled:bg-ink-100">
    @endif
    @if ($help) <p id="{{ $name }}-aide" class="mt-1 text-sm text-ink-500">{{ $help }}</p> @endif
    @error($name) <p class="mt-1 text-sm text-red-800">{{ $message }}</p> @enderror
</div>
