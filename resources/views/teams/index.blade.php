@extends('layouts.teams')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">My Teams</h1>
        <a href="{{ route('teams.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Create New Team
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <!-- Owned Teams -->
    @if($ownedTeams->count() > 0)
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Teams I Own</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($ownedTeams as $team)
                    <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $team->name }}</h3>
                            @if($team->is_active)
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Active</span>
                            @else
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Inactive</span>
                            @endif
                        </div>
                        
                        @if($team->description)
                            <p class="text-gray-600 text-sm mb-4">{{ Str::limit($team->description, 100) }}</p>
                        @endif
                        
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            {{ $team->users->count() }} members
                        </div>
                        
                        <div class="flex space-x-2">
                            <a href="{{ route('teams.show', $team) }}" class="flex-1 bg-blue-50 text-blue-700 text-center px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-100 transition-colors">
                                View
                            </a>
                            <a href="{{ route('teams.members', $team) }}" class="flex-1 bg-gray-50 text-gray-700 text-center px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors">
                                Members
                            </a>
                            <a href="{{ route('teams.edit', $team) }}" class="flex-1 bg-gray-50 text-gray-700 text-center px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors">
                                Edit
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Joined Teams -->
    @if($joinedTeams->count() > 0)
        <div>
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Teams I've Joined</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($joinedTeams as $team)
                    <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $team->name }}</h3>
                            @if($team->is_active)
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Active</span>
                            @else
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Inactive</span>
                            @endif
                        </div>
                        
                        @if($team->description)
                            <p class="text-gray-600 text-sm mb-4">{{ Str::limit($team->description, 100) }}</p>
                        @endif
                        
                        <div class="flex items-center text-sm text-gray-500 mb-2">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            Owner: {{ $team->owner->name }}
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            {{ $team->users->count() }} members
                        </div>
                        
                        <div class="flex space-x-2">
                            <a href="{{ route('teams.show', $team) }}" class="flex-1 bg-blue-50 text-blue-700 text-center px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-100 transition-colors">
                                View
                            </a>
                            <a href="{{ route('teams.members', $team) }}" class="flex-1 bg-gray-50 text-gray-700 text-center px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors">
                                Members
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($ownedTeams->count() === 0 && $joinedTeams->count() === 0)
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No teams</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new team.</p>
            <div class="mt-6">
                <a href="{{ route('teams.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Create New Team
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
