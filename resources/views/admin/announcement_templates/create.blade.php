@extends('layouts.admin')

@section('title', 'Create Template')

@section('content')
<div class="page-header"><h1 class="page-title">Create Template</h1></div>

<div class="data-table" style="max-width: 900px;">
    <div class="table-header"><h3 class="table-title">Template Details</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.announcement-templates.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 18px;">
                <label style="font-size: 0.85rem; font-weight: 600;">Template Name</label>
                <input type="text" name="name" required style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-top: 6px;">
            </div>
            <div style="margin-bottom: 18px;">
                <label style="font-size: 0.85rem; font-weight: 600;">Title</label>
                <input type="text" name="title" required style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-top: 6px;">
            </div>
            <div style="margin-bottom: 18px;">
                <label style="font-size: 0.85rem; font-weight: 600;">Message (English)</label>
                <textarea name="message" rows="6" required style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-top: 6px; resize: vertical;"></textarea>
            </div>
            <div style="margin-bottom: 18px;">
                <label style="font-size: 0.85rem; font-weight: 600;">Message (Hindi)</label>
                <textarea name="message_hi" rows="4" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-top: 6px; resize: vertical;"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600;">Type</label>
                    <select name="type" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-top: 6px;">
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="important">Important</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600;">Language</label>
                    <select name="locale" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-top: 6px;">
                        <option value="en">English</option>
                        <option value="hi">Hindi</option>
                        <option value="both">Both</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Template</button>
        </form>
    </div>
</div>
@endsection
