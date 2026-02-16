@props([
    'options' => [],
    'valueKey' => 'id',
    'labelKey' => 'name',
    'label' => null,
    'placeholder' => 'Select an option',
])

@if($label)
    <label for="{{ $attributes->get('id') }}"
           class="inline-flex items-center text-sm font-medium [:where(&)]:text-zinc-800 [:where(&)]:dark:text-white [&:has([data-flux-label-trailing])]:flex mb-1">
        {{ $label }}
    </label>
@endif

<select id="{{ $attributes->get('id') }}"
    {{ $attributes->merge(['class' => 'w-full border rounded-lg block text-base sm:text-sm py-2 h-10 ps-3 pe-3 bg-white dark:bg-white/10 text-zinc-700 dark:text-zinc-300 shadow-xs border-zinc-200 dark:border-white/10']) }}>
    <option value="">{{ $placeholder }}</option>
    @foreach($options as $item)
        <option value="{{ $item[$valueKey] }}">{{ $item[$labelKey] }}</option>
    @endforeach
</select>
