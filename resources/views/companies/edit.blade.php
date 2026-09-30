@extends('layout.app')
@section('title', 'Edit Company')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user-create.css') }}">
@endpush
@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.companies.index') }}">Companies</a><span>&rsaquo;</span><a href="{{ route('web.companies.show', $company) }}">{{ $company->name }}</a><span>&rsaquo;</span><span>Edit</span></nav>
    <div class="page-heading"><div><h1>Edit Company</h1><p>Update company information and contact details.</p></div></div>
    @if ($errors->any())
        <div role="alert" class="user-form-errors"><strong>Please check the following:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('web.companies.update', $company) }}">
        @csrf
        @method('PUT')
        <section class="user-form-card">
            <div class="user-fields">
                @foreach (['name' => ['Company Name', 'text', 150], 'email' => ['Email', 'email', 150], 'mobile' => ['Phone', 'tel', 20], 'contact_person' => ['Contact Person', 'text', 150], 'city' => ['City', 'text', 100], 'state' => ['State', 'text', 100]] as $field => [$label, $type, $max])
                    <label>{{ $label }} <em>*</em><span class="user-input"><input name="{{ $field }}" type="{{ $type }}" required maxlength="{{ $max }}" value="{{ old($field, $company->$field) }}"></span></label>
                @endforeach
                <label>Address <em>*</em><span class="user-input"><input name="address" required maxlength="500" value="{{ old('address', $company->address) }}"></span></label>
                <label>Status<span class="user-input"><select name="status"><option value="active" @selected(old('status', $company->status) === 'active')>Active</option><option value="inactive" @selected(old('status', $company->status) === 'inactive')>Inactive</option></select></span></label>
                <label>Company Code<span class="user-input"><input readonly value="{{ $company->company_code }}"></span></label>
            </div>
        </section>
        <div class="save-user-bar"><a class="secondary-button" href="{{ route('web.companies.show', $company) }}">Cancel</a><button class="primary-button" type="submit">Save Changes</button></div>
    </form>
@endsection
