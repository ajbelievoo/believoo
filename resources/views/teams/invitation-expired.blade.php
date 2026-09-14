@extends('layouts.teams')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Invitation Expired
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                This invitation has expired and is no longer valid.
            </p>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6">
            <div class="space-y-4">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">What Happened?</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Team invitations expire after 7 days for security reasons. You'll need to contact the team owner to send a new invitation.
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
