@props(['checked' => false, 'disabled' => false])

<label class="inline-flex cursor-pointer items-center">
    <input type="checkbox" @checked($checked) @disabled($disabled)
        {{ $attributes->merge(['class' => 'peer sr-only']) }} />
    <span aria-hidden="true"
        class="relative h-6 w-11 shrink-0 rounded-full bg-line transition-colors after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-focus-visible:ring-offset-2 peer-disabled:cursor-not-allowed peer-disabled:opacity-50"></span>
</label>
