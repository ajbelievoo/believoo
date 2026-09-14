@extends('layouts.teams')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $team->name }}</h1>
                <p class="mt-2 text-gray-600">{{ $team->description ?? 'No description provided' }}</p>
                <div class="mt-4 flex items-center space-x-4 text-sm text-gray-500">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        Owner: {{ $team->owner->name }}
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                        </svg>
                        {{ $team->users->count() }} members
                    </span>
                    <span class="flex items-center">
                        @if($team->is_active)
                            <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Active</span>
                        @else
                            <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Inactive</span>
                        @endif
                    </span>
                </div>
            </div>
            <div class="flex space-x-2">
                @if($team->isTeamOwner(auth()->user()) || auth()->user()->hasTeamPermission($team, 'manage_team'))
                    <a href="{{ route('teams.edit', $team) }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                        Edit Team
                    </a>
                @endif
                <a href="{{ route('teams.members', $team) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Manage Members
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Your Role & Permissions -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Your Role & Permissions</h2>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm font-medium text-gray-700">Role:</span>
                        <span class="ml-2 px-2.5 py-0.5 rounded text-xs font-medium 
                            @if($userRole === 'owner') bg-purple-100 text-purple-800
                            @elseif($userRole === 'admin') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($userRole) }}
                        </span>
                    </div>
                    
                    @if($userRole !== 'owner' && $userRole !== 'admin' && !empty($userPermissions))
                        <div>
                            <span class="text-sm font-medium text-gray-700">Specific Permissions:</span>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($userPermissions as $permission)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">
                                        {{ \App\Models\TeamUser::PERMISSIONS[$permission] ?? $permission }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @elseif($userRole === 'owner' || $userRole === 'admin')
                        <p class="text-sm text-gray-600">You have full access to all team resources and permissions.</p>
                    @else
                        <p class="text-sm text-gray-600">You have limited permissions. Contact your team owner for access to specific features.</p>
                    @endif
                </div>
            </div>

            <!-- Team Statistics -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Team Overview</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $team->users->count() }}</div>
                        <div class="text-sm text-gray-600">Total Members</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ $team->users()->wherePivot('role', 'owner')->count() }}</div>
                        <div class="text-sm text-gray-600">Owners</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ $team->users()->wherePivot('role', 'admin')->count() }}</div>
                        <div class="text-sm text-gray-600">Admins</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity (Placeholder) -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h2>
                <div class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="mt-2 text-sm">No recent activity to show.</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            @if($team->isTeamOwner(auth()->user()) || auth()->user()->hasTeamPermission($team, 'manage_team'))
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('teams.members', $team) }}" class="block w-full text-center bg-blue-50 text-blue-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-100 transition-colors">
                            Add Members
                        </a>
                        <a href="{{ route('teams.edit', $team) }}" class="block w-full text-center bg-gray-50 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-100 transition-colors">
                            Edit Team Settings
                        </a>
                        @if($team->isTeamOwner(auth()->user()))
                            <form action="{{ route('teams.destroy', $team) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this team? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full bg-red-50 text-red-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-red-100 transition-colors">
                                    Delete Team
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Team Members Preview -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Team Members</h3>
                <div class="space-y-3">
                    @foreach($team->users->take(5) as $member)
                        <div class="flex items-center space-x-3">
                            <img class="h-8 w-8 rounded-full" src="{{ $member->avatar_url }}" alt="{{ $member->name }}" loading="lazy">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $member->name }}</p>
                                <p class="text-sm text-gray-500">{{ ucfirst($member->pivot->role) }}</p>
                            </div>
                        </div>
                    @endforeach
                    
                    @if($team->users->count() > 5)
                        <div class="pt-2 border-t border-gray-200">
                            <a href="{{ route('teams.members', $team) }}" class="text-sm text-blue-600 hover:text-blue-500">
                                View all {{ $team->users->count() }} members →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
