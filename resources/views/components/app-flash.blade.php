<div class="mb-3">
{{--    <flux:callout variant="secondary" icon="information-circle" heading="Your account has been successfully created." />
    <flux:callout variant="success" icon="check-circle" heading="Your account is verified and ready to use." />
    <flux:callout variant="warning" icon="exclamation-circle" heading="Please verify your account to unlock all features." />
    <flux:callout variant="danger" icon="x-circle" heading="Something went wrong. Try again or contact support." />--}}
    @if(session()->has('success'))
        <flux:callout variant="success" icon="check-circle" heading="{{ session('success') }}" />
    @endif
    @if(session()->has('error'))
        <flux:callout variant="danger" icon="exclamation-circle" heading="{{ session('error') }}" />
    @endif
    @if(session()->has('info'))
        <flux:callout variant="secondary" icon="information-circle" heading="{{ session('info') }}" />
    @endif
    @if(session()->has('warning'))
        <flux:callout variant="warning" icon="exclamation-triangle" heading="{{ session('warning') }}" />
    @endif
</div>
