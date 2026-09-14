@extends('layouts.teams')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Invitation Declined
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                You have declined the team invitation.
            </p>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">What's Next?</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        The team inviter has been notified that you declined the invitation. If you change your mind, you'll need to ask them to send a new invitation.
                    </p>
                </div>
                
                <div class="pt-4 border-t border-gray-200">
                    <a href="{{ route('login') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
