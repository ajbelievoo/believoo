@extends('layouts.admin')

@section('title', isset($rule) ? 'Edit OVH Pricing Rule' : 'New OVH Pricing Rule')

@section('content')
<h1>{{ isset($rule) ? 'Edit' : 'New' }} OVH Pricing Rule</h1>

<form method="POST" action="{{ isset($rule) ? route('admin.ovh-pricing-rules.update', $rule) : route('admin.ovh-pricing-rules.store') }}" class="row g-3 mt-3">
  @csrf
  @isset($rule) @method('PUT') @endisset

  <div class="col-md-4">
    <label>Category</label>
    <input type="text" name="category" class="form-control" value="{{ old('category', $rule->category ?? 'all') }}" required>
  </div>

  <div class="col-md-4">
    <label>Plan Code</label>
    <input type="text" name="plan_code" class="form-control" value="{{ old('plan_code', $rule->plan_code ?? '*') }}" required>
  </div>

  <div class="col-md-4">
    <label>Currency</label>
    <input type="text" name="currency" class="form-control" value="{{ old('currency', $rule->currency ?? 'INR') }}" required maxlength="3">
  </div>

  <div class="col-md-3">
    <label>Margin %</label>
    <input type="number" step="0.0001" name="margin_percent" class="form-control" value="{{ old('margin_percent', $rule->margin_percent ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Fixed Markup</label>
    <input type="number" step="0.0001" name="fixed_markup" class="form-control" value="{{ old('fixed_markup', $rule->fixed_markup ?? 0) }}">
  </div>

  <div class="col-md-3">
    <label>Min Margin %</label>
    <input type="number" step="0.0001" name="min_margin_percent" class="form-control" value="{{ old('min_margin_percent', $rule->min_margin_percent ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Max Margin %</label>
    <input type="number" step="0.0001" name="max_margin_percent" class="form-control" value="{{ old('max_margin_percent', $rule->max_margin_percent ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Round To</label>
    <input type="number" name="round_to" class="form-control" value="{{ old('round_to', $rule->round_to ?? 2) }}" min="0" max="4">
  </div>

  <div class="col-md-3">
    <label>Priority</label>
    <input type="number" name="priority" class="form-control" value="{{ old('priority', $rule->priority ?? 0) }}">
  </div>

  <div class="col-md-3">
    <label>Active From</label>
    <input type="datetime-local" name="active_from" class="form-control" value="{{ old('active_from', $rule->active_from ?? '') }}">
  </div>

  <div class="col-md-3">
    <label>Active Until</label>
    <input type="datetime-local" name="active_until" class="form-control" value="{{ old('active_until', $rule->active_until ?? '') }}">
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
@endsection
