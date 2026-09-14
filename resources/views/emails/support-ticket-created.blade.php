@extends('emails.layout')

@section('content')
    <h2 style="color: #00b7ff; margin-bottom: 16px;">New Support Ticket</h2>
    <p style="color: #e0e0e0; font-size: 16px;">A new support ticket has been created.</p>
    <div style="background: #0f1623; padding: 16px; border-radius: 12px; margin: 20px 0; border: 1px solid #1a2332;">
        <p style="margin: 4px 0; color: #ffffff;"><strong>Ticket ID:</strong> {{ $ticket->ticket_id }}</p>
        <p style="margin: 4px 0; color: #ffffff;"><strong>From:</strong> {{ $ticket->name }} ({{ $ticket->email }})</p>
        <p style="margin: 4px 0; color: #ffffff;"><strong>Platform:</strong> {{ $ticket->category }}</p>
        <p style="margin: 4px 0; color: #ffffff;"><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</p>
        <p style="margin: 4px 0; color: #ffffff;"><strong>Subject:</strong> {{ $ticket->subject }}</p>
        <p style="margin: 4px 0; color: #e0e0e0;"><strong>Message:</strong> {{ $ticket->messages->first()->message ?? '' }}</p>
    </div>
    <a href="{{ route('admin.tickets.index') }}" style="display: inline-block; background: #00b7ff; color: #000000; padding: 14px 28px; border-radius: 8px; font-weight: bold; text-decoration: none;">View Ticket</a>
@endsection
