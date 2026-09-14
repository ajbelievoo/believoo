@extends('layouts.teams')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Create New Team</h1>
        <p class="mt-2 text-gray-600">Build a team to collaborate and manage access to your resources.</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('teams.store') }}" method="POST" class="space-y-6 p-6">
            @csrf
            
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-md p-4">
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

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                    Team Name <span class="text-red-500">*</span>
                </label>
                <div class="mt-1">
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        value="{{ old('name') }}"
                        required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Enter team name"
                    >
                </div>
                <p class="mt-1 text-sm text-gray-500">Choose a descriptive name for your team.</p>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">
                    Description
                </label>
                <div class="mt-1">
                    <textarea 
                        name="description" 
                        id="description" 
                        rows="4"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Describe the purpose of this team (optional)"
                    >{{ old('description') }}</textarea>
                </div>
                <p class="mt-1 text-sm text-gray-500">Help team members understand what this team is for.</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Team Owner</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>You will be automatically set as the team owner with full permissions to manage the team, add/remove members, and control access to resources.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invite Members Section -->
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-900">Invite Team Members (Optional)</h3>
                    <button type="button" onclick="toggleInviteSection()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <span id="inviteToggleText">Add Members</span>
                        <svg id="inviteToggleIcon" class="inline-block ml-1 w-4 h-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
                
                <div id="inviteSection" class="hidden space-y-4">
                    <p class="text-xs text-gray-600">Add team members by email. They will receive an invitation to join your team.</p>
                    
                    <div id="inviteEmailsContainer" class="space-y-3">
                        <!-- First email input -->
                        <div class="invite-email-row flex items-center space-x-2">
                            <input type="email" name="invite_emails[]" placeholder="Enter email address" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <select name="invite_roles[]" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                @foreach(\App\Models\TeamUser::ROLES as $key => $label)
                                    @if($key !== 'owner')
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="button" onclick="removeInviteEmail(this)" class="text-red-600 hover:text-red-800">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="button" onclick="addInviteEmailField()" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        + Add Another Email
                    </button>
                </div>
            </div>

            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('teams.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Create Team
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleInviteSection() {
    const section = document.getElementById('inviteSection');
    const toggleText = document.getElementById('inviteToggleText');
    const toggleIcon = document.getElementById('inviteToggleIcon');
    
    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        toggleText.textContent = 'Hide Members';
        toggleIcon.classList.add('rotate-180');
    } else {
        section.classList.add('hidden');
        toggleText.textContent = 'Add Members';
        toggleIcon.classList.remove('rotate-180');
    }
}

function addInviteEmailField() {
    const container = document.getElementById('inviteEmailsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'invite-email-row flex items-center space-x-2';
    newRow.innerHTML = `
        <input type="email" name="invite_emails[]" placeholder="Enter email address" 
               class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        <select name="invite_roles[]" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            @foreach(\App\Models\TeamUser::ROLES as $key => $label)
                @if($key !== 'owner')
                    <option value="{{ $key }}">{{ $label }}</option>
                @endif
            @endforeach
        </select>
        <button type="button" onclick="removeInviteEmail(this)" class="text-red-600 hover:text-red-800">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    `;
    container.appendChild(newRow);
}

function removeInviteEmail(button) {
    const row = button.closest('.invite-email-row');
    const container = document.getElementById('inviteEmailsContainer');
    
    // Don't remove if it's the last row
    if (container.children.length > 1) {
        row.remove();
    }
}
</script>
@endpush
