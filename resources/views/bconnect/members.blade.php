@php
$limitLabel = $memberLimit === null ? 'Unlimited' : $memberLimit;
@endphp
@extends('bconnect.layout')
@section('title', 'Members')
@section('content')
@if(!$canAdd)
<div class="bc-card p-4 mb-6 border-l-4 border-amber-500">
    <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-circle text-amber-400 mt-1"></i>
        <div>
            <p class="font-bold text-amber-400">Member limit reached</p>
            <p class="text-sm text-slate-400">You have used {{ $memberUsage }} of {{ $limitLabel }} members. <a href="{{ route('bconnect.billing.upgrade') }}" class="text-cyan-400 hover:underline">Upgrade plan</a> to invite more.</p>
        </div>
    </div>
</div>
@endif

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bc-card p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold">Team Members</h3>
            <span class="bc-badge bc-badge-slate">{{ $memberUsage }} / {{ $limitLabel }}</span>
        </div>
        <table class="bc-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($members as $m)
                <tr>
                    <td class="font-medium">{{ $m->user->name }}</td>
                    <td class="text-slate-400">{{ $m->user->email }}</td>
                    <td><span class="bc-badge bc-badge-slate">{{ ucfirst(str_replace('_', ' ', $m->role)) }}</span></td>
                    <td>{!! $m->is_active ? '<span class="bc-badge bc-badge-green">Active</span>' : '<span class="bc-badge bc-badge-red">Inactive</span>' !!}</td>
                    <td>
                        <form method="POST" action="{{ route('bconnect.members.update', $m->id) }}" class="inline">@csrf @method('PUT')
                            <select name="role" onchange="this.form.submit()" class="bg-slate-800 border border-slate-600 rounded text-xs p-1 text-white">
                                @foreach($roles as $role)<option value="{{ $role }}" {{ $m->role == $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$role)) }}</option>@endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('bconnect.members.destroy', $m->id) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 text-xs ml-2 hover:text-red-300"><i class="fas fa-trash"></i></button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        {{ $members->links() }}
    </div>
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">Add Member</h3>
        <form method="POST" action="{{ route('bconnect.members.store') }}" class="space-y-4">@csrf
            <input type="text" name="name" placeholder="Full Name" required class="bc-input" @if(!$canAdd) disabled @endif>
            <input type="email" name="email" placeholder="Email" required class="bc-input" @if(!$canAdd) disabled @endif>
            <select name="role" class="bc-input" @if(!$canAdd) disabled @endif>
                @foreach($roles as $role)<option value="{{ $role }}">{{ ucfirst(str_replace('_',' ',$role)) }}</option>@endforeach
            </select>
            <button type="submit" class="bc-btn bc-btn-primary w-full" @if(!$canAdd) disabled @endif><i class="fas fa-plus"></i>Add Member</button>
        </form>
    </div>
</div>
@endsection
