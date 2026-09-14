@component('emails.bconnect-layout', ['bconnectBrand' => $bconnectBrand])
<h1 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 18px;">{{ $heading }}</h1>

@foreach ($lines as $line)
<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">{!! $line !!}</p>
@endforeach

@if ($url && $button)
<p style="text-align:center; margin:28px 0;">
    <a href="{{ $url }}" style="display:inline-block; padding:14px 32px; border-radius:999px; background:linear-gradient(135deg,#00b7ff,#0066ff); color:#ffffff; font-weight:700; text-decoration:none; font-size:15px;">{{ $button }}</a>
</p>
@endif
@endcomponent
