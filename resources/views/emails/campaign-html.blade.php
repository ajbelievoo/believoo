@extends('emails.layout')

@section('content')
{!! $content !!}

<p style="font-size:13px; color:#64748b; margin:24px 0 0;">
    You received this email because you are subscribed to Believoo updates.<br>
    @isset($unsubscribeUrl)
        <a href="{{ $unsubscribeUrl }}" style="color:#0077cc; text-decoration:underline;">Unsubscribe</a> &middot;
    @endisset
    <a href="{{ config('app.url') }}" style="color:#0077cc; text-decoration:underline;">Believoo</a>
</p>
@endsection
