@props(['label', 'name', 'error' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-ink-900">{{ $label }}</label>
    <input {{ $attributes->except('class')->merge([
        'id' => $name,
        'name' => $name,
        'class' => 'mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500'
    ]) }}>
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>