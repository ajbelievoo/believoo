@extends('layouts.admin')

@section('title', isset($rule) ? 'Edit OVH Pricing Rule' : 'New OVH Pricing Rule')

@section('content')
<div class="page-header"><div><h1 class="page-title">{{ isset($rule) ? 'Edit' : 'New' }} OVH Pricing Rule</h1><p class="page-subtitle">Configure catalog pricing behavior</p></div></div>
<div class="card"><div class="card-header"><div class="card-title"><i class="fas fa-sliders-h"></i>Rule Details</div></div><div class="card-body">
<form method="POST" action="{{ isset($rule) ? route('admin.ovh-pricing-rules.update', $rule) : route('admin.ovh-pricing-rules.store') }}">
  @csrf
  @isset($rule) @method('PUT') @endisset

  <div class="col-md-4">
    <label>Category</label>
    <input type="text" name="category" class="form-input" value="{{ old('category', $rule->category ?? 'all') }}" required>
  </div>

  <div class="col-md-4">
    <label>Plan Code</label>
    <input type="text" name="plan_code" class="form-input" value="{{ old('plan_code', $rule->plan_code ?? '*') }}" required>
  </div>

  <div class="col-md-4">
    <label>Currency</label>
    <input type="text" name="currency" class="form-input" value="{{ old('currency', $rule->currency ?? 'INR') }}" required maxlength="3">
  </div>

  <div class="col-md-3">
    <label>Margin %</label>
    <input type="number" step="0.0001" name="margin_percent" class="form-input" value="{{ old('margin_percent', $rule->margin_percent ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Fixed Markup</label>
    <input type="number" step="0.0001" name="fixed_markup" class="form-input" value="{{ old('fixed_markup', $rule->fixed_markup ?? 0) }}">
  </div>

  <div class="col-md-3">
    <label>Min Margin %</label>
    <input type="number" step="0.0001" name="min_margin_percent" class="form-input" value="{{ old('min_margin_percent', $rule->min_margin_percent ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Max Margin %</label>
    <input type="number" step="0.0001" name="max_margin_percent" class="form-input" value="{{ old('max_margin_percent', $rule->max_margin_percent ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Round To</label>
    <input type="number" name="round_to" class="form-input" value="{{ old('round_to', $rule->round_to ?? 2) }}" min="0" max="4">
  </div>

  <div class="col-md-3">
    <label>Priority</label>
    <input type="number" name="priority" class="form-input" value="{{ old('priority', $rule->priority ?? 0) }}">
  </div>

  <div class="col-md-3">
    <label>Active From</label>
    <input type="datetime-local" name="active_from" class="form-input" value="{{ old('active_from', $rule->active_from ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Active Until</label>
    <input type="datetime-local" name="active_until" class="form-input" value="{{ old('active_until', $rule->active_until ?? '') }}">
  </div>

  <div class="col-12">
    <div class="form-check">
      <input type="checkbox" name="is_active" class="form-check-input" value="1" {{ old('is_active', $rule->is_active ?? true) ? 'checked' : '' }}>
      <label class="form-check-label">Active</label>
    </div>
  </div>

  <div class="col-12">
    <button class="btn btn-primary" type="submit">Save</button>
  </div>
</form>
</div></div>
@endsection
