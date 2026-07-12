<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="@lang('shop::app.customers.login-form.page-title')"/>
    <meta name="keywords" content="@lang('shop::app.customers.login-form.page-title')"/>
@endPush

@push('styles')
<style>
    body { background: #f0f4f8 !important; font-family: 'Poppins', sans-serif !important; }

    .gm-login-page {
        display: flex;
        flex-direction: column;
        background: #f0f4f8;
    }

    /* ── Hero ── */
    .gm-hero {
        background: linear-gradient(135deg, #0b1f5e 0%, #1565C0 100%);
        padding: 44px 24px 64px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        position: relative;
    }
    .gm-hero::after {
        content: '';
        position: absolute;
        bottom: -1px; left: 0; right: 0;
        height: 48px;
        background: #f0f4f8;
        border-radius: 50% 50% 0 0 / 100% 100% 0 0;
    }
    .gm-hero-logo { height: 38px; width: auto; filter: brightness(0) invert(1); }
    .gm-hero-sub { color: rgba(255,255,255,.75); font-size: 14px; font-family: 'Poppins', sans-serif; font-weight: 400; letter-spacing: 0.2px; }

    /* ── Card ── */
    .gm-card {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 4px 32px rgba(11,31,94,.10);
        padding: 28px 24px 24px;
        margin: -24px 16px 24px;
        position: relative;
        z-index: 1;
    }
    .gm-card-title {
        font-size: 22px;
        font-weight: 700;
        color: #0b1f5e;
        font-family: 'Poppins', sans-serif;
        letter-spacing: -0.3px;
        line-height: 1.3;
        margin: 0 0 4px;
    }
    .gm-card-sub {
        font-size: 13px;
        color: #9e9e9e;
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        line-height: 1.5;
        margin: 0 0 22px;
    }

    /* ── Form controls ── */
    .gm-label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #3a3a4c;
        font-family: 'Poppins', sans-serif;
        margin-bottom: 7px;
        letter-spacing: 0.1px;
    }
    .gm-label .req { color: #e53935; margin-left: 2px; }
    .gm-field {
        width: 100% !important;
        border: 1.5px solid #e0e7ef !important;
        border-radius: 12px !important;
        padding: 13px 16px !important;
        font-size: 15px !important;
        font-family: 'Poppins', sans-serif !important;
        font-weight: 400 !important;
        color: #1a1a2e !important;
        background: #f9fafc !important;
        outline: none !important;
        transition: border-color .2s, background .2s;
        box-sizing: border-box;
        line-height: 1.5 !important;
    }
    .gm-field::placeholder { color: #b0b8c8 !important; font-weight: 400; }
    .gm-field:focus { border-color: #1565C0 !important; background: #fff !important; }
    .gm-group { margin-bottom: 16px; }

    /* Show pass / forgot */
    .gm-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 4px 0 18px;
    }
    .gm-show-label {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        color: #666;
        cursor: pointer;
        user-select: none;
    }
    .gm-forgot {
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        color: #1565C0;
        text-decoration: none;
        letter-spacing: 0.1px;
    }

    /* Submit */
    .gm-submit {
        width: 100%;
        background: linear-gradient(135deg, #0b1f5e 0%, #1565C0 100%);
        color: #fff;
        border: none;
        border-radius: 12px;
        padding: 15px;
        font-size: 16px;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: opacity .2s;
        margin-bottom: 4px;
    }
    .gm-submit:active { opacity: .85; }

    /* Social login wrapper (overrides the default row style) */
    .gm-social-wrapper .flex { flex-wrap: wrap; gap: 10px !important; justify-content: center; }
    .gm-social-wrapper a {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 48px !important;
        height: 48px !important;
        border: 1.5px solid #e0e7ef;
        border-radius: 12px;
        background: #f9fafc;
        transition: background .15s, border-color .15s;
        text-decoration: none;
    }
    .gm-social-wrapper a:active { background: #e8f0fe; border-color: #1565C0; }

    /* Divider */
    .gm-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 18px 0 14px;
        font-size: 12px;
        font-family: 'Poppins', sans-serif;
        font-weight: 500;
        color: #bbb;
        letter-spacing: 0.3px;
    }
    .gm-divider::before, .gm-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #eee;
    }

    /* Register */
    .gm-register {
        text-align: center;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        color: #888;
        margin-top: 18px;
        line-height: 1.6;
    }
    .gm-register a {
        color: #1565C0;
        font-weight: 600;
        text-decoration: none;
    }

    /* Footer */
    .gm-footer {
        text-align: center;
        font-size: 11px;
        font-family: 'Poppins', sans-serif;
        font-weight: 400;
        color: #bbb;
        padding: 18px 16px 24px;
        line-height: 1.6;
    }
</style>
@endpush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <x-slot:title>
        @lang('shop::app.customers.login-form.page-title')
    </x-slot>

    <div class="gm-login-page">

        {!! view_render_event('bagisto.shop.customers.login.logo.before') !!}

        <!-- Hero -->
        <div class="gm-hero">
            <a href="{{ route('shop.home.index') }}" aria-label="{{ config('app.name') }}">
                <img
                    class="gm-hero-logo"
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                >
            </a>
            <span class="gm-hero-sub">Masuk ke akun Anda</span>
        </div>

        {!! view_render_event('bagisto.shop.customers.login.logo.after') !!}

        <!-- Card -->
        <div class="gm-card">
            <h1 class="gm-card-title">@lang('shop::app.customers.login-form.page-title')</h1>
            <p class="gm-card-sub">@lang('shop::app.customers.login-form.form-login-text')</p>

            {!! view_render_event('bagisto.shop.customers.login.before') !!}

            <x-shop::form :action="route('shop.customer.session.create')">

                {!! view_render_event('bagisto.shop.customers.login_form_controls.before') !!}

                <!-- Email -->
                <div class="gm-group">
                    <label class="gm-label" for="gm-email">
                        @lang('shop::app.customers.login-form.email')<span class="req">*</span>
                    </label>
                    <x-shop::form.control-group.control
                        type="email"
                        class="gm-field"
                        name="email"
                        id="gm-email"
                        rules="required|email"
                        value=""
                        :label="trans('shop::app.customers.login-form.email')"
                        placeholder="email@example.com"
                        :aria-label="trans('shop::app.customers.login-form.email')"
                        aria-required="true"
                    />
                    <x-shop::form.control-group.error control-name="email" />
                </div>

                <!-- Password -->
                <div class="gm-group">
                    <label class="gm-label" for="password">
                        @lang('shop::app.customers.login-form.password')<span class="req">*</span>
                    </label>
                    <x-shop::form.control-group.control
                        type="password"
                        class="gm-field"
                        id="password"
                        name="password"
                        rules="required|min:6"
                        value=""
                        :label="trans('shop::app.customers.login-form.password')"
                        :placeholder="trans('shop::app.customers.login-form.password')"
                        :aria-label="trans('shop::app.customers.login-form.password')"
                        aria-required="true"
                    />
                    <x-shop::form.control-group.error control-name="password" />
                </div>

                <!-- Show password + Forgot -->
                <div class="gm-row">
                    <label class="gm-show-label">
                        <input
                            type="checkbox"
                            id="show-password"
                            class="peer hidden"
                            onchange="switchVisibility()"
                        />
                        <span class="icon-uncheck peer-checked:icon-check-box text-xl text-navyBlue peer-checked:text-navyBlue"></span>
                        @lang('shop::app.customers.login-form.show-password')
                    </label>
                    <a class="gm-forgot" href="{{ route('shop.customers.forgot_password.create') }}">
                        @lang('shop::app.customers.login-form.forgot-pass')
                    </a>
                </div>

                <!-- Captcha -->
                @if (core()->getConfigData('customer.captcha.credentials.status'))
                    <x-shop::form.control-group class="mb-4">
                        {!! \Webkul\Customer\Facades\Captcha::render() !!}
                        <x-shop::form.control-group.error control-name="recaptcha_token" />
                    </x-shop::form.control-group>
                @endif

                <!-- Sign In button -->
                <button type="submit" class="gm-submit">
                    @lang('shop::app.customers.login-form.button-title')
                </button>

                <!-- Social login injected here by SocialLogin package -->
                <div class="gm-social-wrapper">
                    @php
                        $hasSocial = false;
                        foreach(['enable_facebook','enable_twitter','enable_google','enable_github'] as $s) {
                            if (core()->getConfigData('customer.settings.social_login.' . $s)) { $hasSocial = true; break; }
                        }
                    @endphp
                    @if($hasSocial)
                        <div class="gm-divider">atau masuk dengan</div>
                    @endif
                    {!! view_render_event('bagisto.shop.customers.login_form_controls.after') !!}
                </div>

            </x-shop::form>

            {!! view_render_event('bagisto.shop.customers.login.after') !!}

            <!-- Resend verification -->
            @if (request()->cookie('enable-resend') && request()->cookie('email-for-resend'))
                <p style="text-align:center; font-size:13px; color:#888; margin-top:12px;">
                    <a style="color:#1565C0; font-weight:600;" href="{{ route('shop.customers.resend.verification_email', urlencode(request()->cookie('email-for-resend'))) }}">
                        @lang('shop::app.customers.login-form.resend-verification')
                    </a>
                </p>
            @endif

            <!-- Register -->
            <div class="gm-register">
                @lang('shop::app.customers.login-form.new-customer')
                <a href="{{ route('shop.customers.register.index') }}">
                    @lang('shop::app.customers.login-form.create-your-account')
                </a>
            </div>
        </div>

        <p class="gm-footer">
            @lang('shop::app.customers.login-form.footer', ['current_year' => date('Y')])
        </p>

    </div>

    @push('scripts')
        {!! \Webkul\Customer\Facades\Captcha::renderJS() !!}
        <script>
            function switchVisibility() {
                let f = document.getElementById("password");
                f.type = f.type === "password" ? "text" : "password";
            }
        </script>
    @endpush

</x-shop::layouts>
