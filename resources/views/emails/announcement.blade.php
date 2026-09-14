@extends('emails.layout')

@section('content')
<h1 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 18px;">{{ $announcement->title }}</h1>

<div style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">
    {!! $body !!}
</div>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">For questions, simply reply to this email or contact support.</p>

<p style="font-size:15px; line-height:1.65; margin:0; color:#475569;">Regards,<br><strong style="color:#0f172a;">Team Believoo</strong></p>

<div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #94a3b8;">
    <p style="margin: 0;">You are receiving this because you have an account with Believoo.</p>
    <p style="margin: 8px 0 0;">
        <a href="{{ $unsubscribeUrl }}" style="color: #0077cc; text-decoration: underline;">Unsubscribe</a> &middot;
        <a href="{{ str_replace('/unsubscribe/', '/resubscribe/', $unsubscribeUrl) }}" style="color: #0077cc; text-decoration: underline;">Resubscribe</a>
    </p>
</div>
@endsection
