<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }} — Growth Scale Partner. {{ __('All rights reserved.') }}

[Website]({{ config('app.url') }}) &middot; [Support](mailto:support@believoo.com) &middot; [Services]({{ config('app.url') }}/services)

You received this email because you have an account or subscribed at {{ config('app.name') }}.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
