<x-filament-panels::page>
    <form wire:submit.prevent="save" class="space-y-6">
        <x-filament::section heading="Webhook aplikasi ini">
            <div
                x-data="{
                    webhookUrl: @js(route('wa-gateway.webhook.message')),
                    copied: false,
                    copyWebhook() {
                        if (navigator.clipboard && window.isSecureContext) {
                            navigator.clipboard.writeText(this.webhookUrl)
                                .then(() => this.showCopiedState())
                                .catch(() => this.fallbackCopy());
                            return;
                        }

                        this.fallbackCopy();
                    },
                    fallbackCopy() {
                        this.$refs.webhookInput.focus();
                        this.$refs.webhookInput.select();
                        document.execCommand('copy');
                        this.showCopiedState();
                    },
                    showCopiedState() {
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2000);
                    }
                }"
                class="space-y-2"
            >
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    Webhook aplikasi ini
                </label>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <input
                        x-ref="webhookInput"
                        type="text"
                        :value="webhookUrl"
                        readonly
                        class="w-full rounded-lg border-none bg-gray-100/70 p-3 text-sm text-gray-900 dark:bg-white/5 dark:text-white"
                    />

                    <x-filament::button type="button" color="gray" x-on:click="copyWebhook()">
                        <span x-show="! copied">Copy</span>
                        <span x-show="copied" x-cloak>Tersalin</span>
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{ $this->form }}

        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-filament::button type="button" color="gray" wire:click="testConnection">
                Test Koneksi
            </x-filament::button>
            <x-filament::button type="button" color="warning" wire:click="restartQueueWorkers">
                Restart Worker/Queue
            </x-filament::button>
            <x-filament::button type="submit" color="primary">
                Simpan Pengaturan WA Gateway
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
