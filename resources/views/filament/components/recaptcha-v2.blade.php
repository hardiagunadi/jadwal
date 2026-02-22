<div
    x-data="{
        init() {
            window.onRecaptchaSuccess = (token) => {
                $wire.set('data.g_recaptcha_response', token);
            };
            window.onRecaptchaExpired = () => {
                $wire.set('data.g_recaptcha_response', '');
            };
        }
    }"
    class="flex justify-center py-1"
>
    <div
        class="g-recaptcha"
        data-sitekey="{{ config('services.recaptcha.site_key') }}"
        data-callback="onRecaptchaSuccess"
        data-expired-callback="onRecaptchaExpired"
    ></div>
</div>

@once
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endonce
