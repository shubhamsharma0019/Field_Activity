@extends('layout.app')
@section('title', 'Company Details')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user-create.css') }}">
@endpush
@section('content')
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.companies.index') }}">Companies</a><span>&rsaquo;</span><span>Company Details</span></nav>
    <div class="page-heading"><div><h1>{{ $company->name }}</h1><p>{{ $company->company_code }}</p></div><a class="primary-button" href="{{ route('web.companies.edit', $company) }}">Edit Company</a></div>
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    <section class="user-form-card">
        <h2>Company Information</h2>
        <dl class="user-fields user-detail-fields">
            @foreach (['Company Name' => $company->name, 'Code' => $company->company_code, 'Email' => $company->email, 'Phone' => $company->mobile, 'Contact Person' => $company->contact_person, 'Address' => $company->address, 'City' => $company->city, 'State' => $company->state, 'Status' => ucfirst($company->status), 'Projects' => $company->projects_count, 'Users' => $company->users_count, 'Workers' => $company->workers_count, 'Created' => $company->created_at?->format('d M Y'), 'Last Updated' => $company->updated_at?->format('d M Y')] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value ?? '-' }}</dd></div>
            @endforeach
        </dl>
    </section>
    <div class="save-user-bar"><a class="secondary-button" href="{{ route('web.companies.index') }}">Back to Companies</a></div>
@endsection
