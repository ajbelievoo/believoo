@extends('bconnect.layout')
@section('title', 'New Project')
@section('content')
<div class="max-w-xl bg-slate-900 rounded-xl border border-slate-800 p-8">
    <form method="POST" action="{{ route('bconnect.projects.store') }}" class="space-y-5">@csrf
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Project Name</label><input type="text" name="name" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Description</label><textarea name="description" rows="3" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></textarea></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Client (optional)</label><select name="client_id" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="">—</option>@foreach(\App\Models\Bconnect\Member::with('user')->where('company_id', request()->input('bconnect_company_id'))->where('role', 'client')->get() as $c)<option value="{{ $c->id }}">{{ $c->user->name }}</option>@endforeach</select></div>
        <button type="submit" class="px-6 py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">Create Project</button>
    </form>
</div>
@endsection
