@props(['label', 'name', 'rows' => 3, 'hint' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="block text-sm font-medium text-ink-900">{{ $label }}</label>
    <textarea {{ $attributes->except('class')->merge([
        'id' => $name,
        'name' => $name,
        'rows' => $rows,
        'class' => 'mt-1.5 w-full rounded-lg border-slate-200 px-3 py-2 text-sm focus:border-brand-500 focus:ring-brand-500'
    ]) }}></textarea>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
    @enderror
</div>