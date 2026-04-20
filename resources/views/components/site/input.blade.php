@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'autocomplete' => null,
    'autofocus' => false,
    'placeholder' => null,
])
<div>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-slate-300">
            {{ $label }}
        </label>
    @endif
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @if ($required) required @endif
        @if ($autofocus) autofocus @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->class([
            'mt-2 block w-full rounded-md bg-white/5 border border-white/10 px-3.5 py-2.5 text-white placeholder:text-slate-500',
            'focus:outline-none focus:border-white/30 focus:bg-white/[0.07]',
            'border-rose-400/40' => $errors->has($name),
        ]) }}
    />
    @error($name)
        <p class="mt-1.5 text-xs text-rose-300">{{ $message }}</p>
    @enderror
</div>
