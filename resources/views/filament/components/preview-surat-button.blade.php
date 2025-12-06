@props(['url'])

<div class="fi-fo-file-upload-preview">
    <x-filament::button
        type="button"
        color="gray"
        icon="heroicon-o-eye"
        x-data="{}"
        x-on:click="
            const popup = window.open({{ \Illuminate\Support\Js::from($url) }}, 'preview-surat', 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,fullscreen=yes');
            if (popup) {
                popup.moveTo(0, 0);
                popup.resizeTo(screen.width, screen.height);
                popup.focus();
            }
        "
    >
        Lihat Berkas Surat (PDF)
    </x-filament::button>

    <p class="mt-2 text-sm text-gray-500">
        Dokumen akan terbuka di jendela penuh sehingga Anda dapat membaca isi surat untuk mengisi data secara manual.
    </p>
</div>
