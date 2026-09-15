@extends('bconnect.layout')
@section('title', 'New Project')
@section('content')
<div class="max-w-xl bc-card p-8">
    <form method="POST" action="{{ route('bconnect.projects.store') }}" class="space-y-5">@csrf
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Project Name</label><input type="text" name="name" required class="bc-input"></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Description</label><textarea name="description" rows="3" class="bc-input"></textarea></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Client (optional)</label><select name="client_id" class="bc-input"><option value="">—</option>@foreach(\App\Models\Bconnect\Member::with('user')->where('company_id', request()->input('bconnect_company_id'))->where('role', 'client')->get() as $c)<option value="{{ $c->id }}">{{ $c->user->name }}</option>@endforeach</select></div>
        <button type="submit" class="bc-btn bc-btn-primary"><i class="fas fa-plus"></i>Create Project</button>
    </form>
</div>
@endsection
