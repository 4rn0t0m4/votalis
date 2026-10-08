@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'autocomplete' => null, 'help' => null, 'maxlength' => null, 'bag' => 'default'])
@php $errorBag = $errors->getBag($bag); @endphp
<div {{ $attributes->merge(['class' => 'mb-4']) }}>
    <label for="{{ $name }}" class="mb-1.5 block font-bold">{{ $label }}@if($required) <span aria-hidden="true">*</span>@endif</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        @if($required) required aria-required="true" @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if($maxlength) maxlength="{{ $maxlength }}" @endif
        @if($errorBag->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-erreur" @elseif($help) aria-describedby="{{ $name }}-aide" @endif
        class="field"
    >
    @if ($help && ! $errorBag->has($name))
        <p id="{{ $name }}-aide" class="mt-1 text-sm text-ink-700">{{ $help }}</p>
    @endif
    @if ($errorBag->has($name))
        <p id="{{ $name }}-erreur" class="mt-1 text-sm text-red-800">{{ $errorBag->first($name) }}</p>
    @endif
</div>
