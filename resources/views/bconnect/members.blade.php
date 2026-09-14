@extends('bconnect.layout')
@section('title', 'Members')
@section('content')
<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">Team Members</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($members as $m)
                <tr class="border-b border-slate-800">
                    <td class="py-3">{{ $m->user->name }}</td>
                    <td>{{ $m->user->email }}</td>
                    <td><span class="px-2 py-1 rounded bg-slate-700 text-xs">{{ ucfirst(str_replace('_', ' ', $m->role)) }}</span></td>
                    <td>{!! $m->is_active ? '<span class="text-green-400">Active</span>' : '<span class="text-red-400">Inactive</span>' !!}</td>
                    <td>
                        <form method="POST" action="{{ route('bconnect.members.update', $m->id) }}" class="inline">@csrf @method('PUT')
                            <select name="role" onchange="this.form.submit()" class="bg-slate-800 border border-slate-600 rounded text-xs p-1">
                                @foreach($roles as $role)<option value="{{ $role }}" {{ $m->role == $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$role)) }}</option>@endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('bconnect.members.destroy', $m->id) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="text-red-400 text-xs ml-2"><i class="fas fa-trash"></i></button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">Add Member</h3>
        <form method="POST" action="{{ route('bconnect.members.store') }}" class="space-y-4">@csrf
            <input type="text" name="name" placeholder="Full Name" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
            <input type="email" name="email" placeholder="Email" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
            <select name="role" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
                @foreach($roles as $role)<option value="{{ $role }}">{{ ucfirst(str_replace('_',' ',$role)) }}</option>@endforeach
            </select>
            <button type="submit" class="w-full py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">Add Member</button>
        </form>
    </div>
</div>
@endsection
