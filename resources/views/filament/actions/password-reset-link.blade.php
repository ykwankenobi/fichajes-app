<div
    x-data="{
        url: @js($url),
        copied: false,
        copy() {
            navigator.clipboard.writeText(this.url)
            this.copied = true
            setTimeout(() => this.copied = false, 2000)
        },
    }"
    class="space-y-4"
>
    <div class="flex gap-2">
        <input
            type="text"
            x-model="url"
            readonly
            class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm"
        >

        <button
            type="button"
            x-on:click="copy"
            class="inline-flex shrink-0 items-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500"
        >
            Copiar
        </button>
    </div>

    <p x-show="copied" x-cloak class="text-sm font-medium text-green-600">
        Enlace copiado.
    </p>
</div>
