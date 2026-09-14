@extends('layouts.teams')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Edit Team</h1>
        <p class="mt-2 text-gray-600">Update team information and settings.</p>
    </div>

    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('teams.update', $team) }}" method="POST" class="space-y-6 p-6">
            @csrf
            @method('PUT')
            
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
                        value="{{ old('name', $team->name) }}"
                        required
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Enter team name"
                    >
                </div>
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
                    >{{ old('description', $team->description) }}</textarea>
                </div>
                <p class="mt-1 text-sm text-gray-500">Help team members understand what this team is for.</p>
            </div>

            <div>
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        id="is_active" 
                        value="1"
                        {{ old('is_active', $team->is_active) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    >
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Team is active
                    </label>
                </div>
                <p class="mt-1 text-sm text-gray-500">Inactive teams cannot be accessed by members.</p>
            </div>

            <!-- Team Information -->
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
                <h3 class="text-sm font-medium text-gray-900 mb-3">Team Information</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Team ID:</span>
                        <span class="font-mono text-gray-900">{{ $team->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Slug:</span>
                        <span class="font-mono text-gray-900">{{ $team->slug }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Owner:</span>
                        <span class="text-gray-900">{{ $team->owner->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Members:</span>
                        <span class="text-gray-900">{{ $team->users->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Created:</span>
                        <span class="text-gray-900">{{ $team->created_at->format('M j, Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between space-x-3 pt-6 border-t border-gray-200">
                <div class="flex space-x-3">
                    <a href="{{ route('teams.show', $team) }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    @if($team->isTeamOwner(auth()->user()))
                        <form action="{{ route('teams.destroy', $team) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this team? This action cannot be undone and will remove all member access.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Delete Team
                            </button>
                        </form>
                    @endif
                </div>
                <button type="submit" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Update Team
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
