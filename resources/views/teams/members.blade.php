@extends('layouts.teams')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Team Members</h1>
                <p class="mt-2 text-gray-600">Manage members and permissions for {{ $team->name }}</p>
            </div>
            <a href="{{ route('teams.show', $team) }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                ← Back to Team
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission:</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Members List -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Pending Invitations Section -->
            @php
                $pendingInvitations = $team->invitations()->where('status', 'pending')->where('expires_at', '>', now())->with('inviter')->get();
            @endphp

            @if($pendingInvitations->count() > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 bg-yellow-50">
                        <h2 class="text-lg font-medium text-gray-900 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Pending Invitations ({{ $pendingInvitations->count() }})
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($pendingInvitations as $invitation)
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-medium text-gray-900">{{ $invitation->email }}</h3>
                                            <p class="text-sm text-gray-500">
                                                Invited by {{ $invitation->inviter->name }} • {{ $invitation->created_at->diffForHumans() }}
                                            </p>
                                            <div class="flex items-center mt-1 space-x-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ ucfirst($invitation->role) }}
                                                </span>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                                @if($invitation->isExpired())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Expired
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-500">
                                                        Expires {{ $invitation->expires_at->diffForHumans() }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if($team->isTeamOwner(auth()->user()) || auth()->user()->hasTeamPermission($team, 'manage_team'))
                                        <div class="flex items-center space-x-2">
                                            <form action="{{ route('teams.invitations.resend', [$team, $invitation]) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="text-blue-600 hover:text-blue-900 text-sm font-medium flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                    Resend
                                                </button>
                                            </form>
                                            <form action="{{ route('teams.invitations.destroy', [$team, $invitation]) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this invitation?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">All Members ({{ $team->users->count() }})</h2>
                </div>

                <div class="divide-y divide-gray-200">
                    @foreach($team->users as $member)
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <img class="h-12 w-12 rounded-full" src="{{ $member->avatar_url }}" alt="{{ $member->name }}" loading="lazy">
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">{{ $member->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $member->email }}</p>
                                        @if($team->owner_id === $member->id)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                Team Owner
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($member->pivot->role === 'admin') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst($member->pivot->role) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($team->isTeamOwner(auth()->user()) || auth()->user()->hasTeamPermission($team, 'manage_team'))
                                    @if($team->owner_id !== $member->id)
                                        <div class="flex items-center space-x-2">
                                            <button onclick="editMember({{ $member->id }}, '{{ $member->name }}', '{{ $member->pivot->role }}', {{ $member->pivot->permissions ?? '[]' }})" 
                                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                Edit
                                            </button>
                                            <form action="{{ route('teams.members.remove', [$team, $member]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove {{ $member->name }} from the team?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500">Owner</span>
                                    @endif
                                @endif
                            </div>
                            
                            @if($member->pivot->permissions && count($member->pivot->permissions) > 0)
                                <div class="mt-3">
                                    <p class="text-xs text-gray-500 mb-1">Specific Permissions:</p>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($member->pivot->permissions as $permission)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                {{ \App\Models\TeamUser::PERMISSIONS[$permission] ?? $permission }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Add Member Form -->
        @if($team->isTeamOwner(auth()->user()) || auth()->user()->hasTeamPermission($team, 'manage_team'))
            <div class="lg:col-span-1 space-y-6">
                <!-- Invite by Email -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Invite by Email</h2>
                    </div>
                    
                    <form action="{{ route('teams.invitations.store', $team) }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        
                        <div>
                            <label for="invite_email" class="block text-sm font-medium text-gray-700">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                id="invite_email" 
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="user@example.com"
                            >
                            <p class="mt-1 text-xs text-gray-500">An invitation email will be sent to this address.</p>
                        </div>

                        <div>
                            <label for="invite_role" class="block text-sm font-medium text-gray-700">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="role" 
                                id="invite_role" 
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            >
                                @foreach(\App\Models\TeamUser::ROLES as $key => $label)
                                    @if($key !== 'owner')
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Specific Permissions
                            </label>
                            <p class="text-xs text-gray-500 mb-2">Only required for 'member' role. Admins and owners have full access.</p>
                            <div class="space-y-2">
                                @foreach(\App\Models\TeamUser::PERMISSIONS as $key => $label)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="permissions[]" value="{{ $key }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Send Invitation
                        </button>
                    </form>
                </div>

                <!-- Add Existing Member -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Add Existing Member</h2>
                    </div>
                    
                    <form action="{{ route('teams.members.add', $team) }}" method="POST" class="p-6 space-y-4">
                        @csrf
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="user@example.com"
                            >
                            <p class="mt-1 text-xs text-gray-500">User must already have an account.</p>
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="role" 
                                id="role" 
                                required
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            >
                                @foreach(\App\Models\TeamUser::ROLES as $key => $label)
                                    @if($key !== 'owner')
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Specific Permissions
                            </label>
                            <p class="text-xs text-gray-500 mb-2">Only required for 'member' role. Admins and owners have full access.</p>
                            <div class="space-y-2">
                                @foreach(\App\Models\TeamUser::PERMISSIONS as $key => $label)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="permissions[]" value="{{ $key }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Add Member Directly
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Edit Member Modal -->
<div id="editMemberModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Member Role</h3>
            <form id="editMemberForm" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <input type="hidden" id="editUserId" name="user_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Member</label>
                    <p id="editMemberName" class="mt-1 text-sm text-gray-900"></p>
                </div>

                <div>
                    <label for="editRole" class="block text-sm font-medium text-gray-700">
                        Role <span class="text-red-500">*</span>
                    </label>
                    <select 
                        name="role" 
                        id="editRole" 
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        @foreach(\App\Models\TeamUser::ROLES as $key => $label)
                            @if($key !== 'owner')
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Specific Permissions
                    </label>
                    <div class="space-y-2" id="editPermissions">
                        @foreach(\App\Models\TeamUser::PERMISSIONS as $key => $label)
                            <label class="flex items-center">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeEditModal()" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editMember(userId, userName, currentRole, currentPermissions) {
    document.getElementById('editUserId').value = userId;
    document.getElementById('editMemberName').textContent = userName;
    document.getElementById('editRole').value = currentRole;
    
    // Clear all checkboxes first
    const checkboxes = document.querySelectorAll('#editPermissions input[type="checkbox"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
    
    // Check current permissions
    if (currentPermissions && Array.isArray(currentPermissions)) {
        currentPermissions.forEach(permission => {
            const checkbox = document.querySelector(`#editPermissions input[value="${permission}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    document.getElementById('editMemberModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editMemberModal').classList.add('hidden');
}

// Set form action when editing
document.getElementById('editMemberForm').addEventListener('submit', function(e) {
    const userId = document.getElementById('editUserId').value;
    this.action = '{{ route("teams.members.update", [$team, ":user"]) }}'.replace(':user', userId);
});
</script>
@endsection
