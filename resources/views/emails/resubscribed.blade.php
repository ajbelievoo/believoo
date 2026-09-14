@extends('layouts.app')

@section('title', 'Resubscribed')

@section('content')
<div class="container" style="max-width: 600px; margin: 60px auto; text-align: center; padding: 40px; background: #ffffff; border-radius: 14px; box-shadow: 0 8px 30px rgba(15,23,42,0.09);">
    <h1 style="color: #0f172a; font-size: 24px; margin-bottom: 16px;">Resubscribed</h1>
    <p style="color: #475569; font-size: 16px; line-height: 1.65;">
        <strong>{{ $email }}</strong> is now subscribed to Believoo announcements again.
    </p>
    <a href="{{ config('app.url') }}" class="btn btn-primary" style="margin-top: 24px;">Go to Believoo</a>
</div>
@endsection
