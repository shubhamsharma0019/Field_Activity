@extends('layout.app')
@section('title', 'Add Company')
@push('styles')<link rel="stylesheet" href="{{ asset('css/companies.css') }}">@endpush
@section('content')
<nav class="breadcrumbs" aria-label="Breadcrumb"><a href="{{ route('web.dashboard') }}"><svg class="icon"><use href="#home"/></svg>Dashboard</a><span>&rsaquo;</span><a href="{{ route('web.companies.index') }}">Companies</a><span>&rsaquo;</span><span aria-current="page">Add Company</span></nav>
<div class="page-heading create-heading"><div><h1>Add Company</h1><p>Create a new company in the system.</p></div></div>
<form class="create-company-form" id="create-company-form">
<section class="create-panel company-details-panel"><div class="create-fields">
<label>Company Name <em>*</em><span class="field-input"><svg class="icon"><use href="#building"/></svg><input name="company_name" required maxlength="100" placeholder="Enter company name"></span></label>
<label>Short Name<span class="field-input"><svg class="icon"><use href="#building"/></svg><input name="short_name" maxlength="40" placeholder="Enter short name (optional)"></span></label>
<label>Email <em>*</em><span class="field-input"><svg class="icon"><use href="#mail"/></svg><input name="email" type="email" required maxlength="150" placeholder="Enter email address"></span></label>
<label>Phone <em>*</em><span class="field-input"><svg class="icon"><use href="#phone"/></svg><input name="phone" type="tel" required maxlength="20" placeholder="Enter phone number"></span></label>
<label>Contact Person <em>*</em><span class="field-input"><svg class="icon"><use href="#users"/></svg><input name="contact_person" required maxlength="100" placeholder="Enter contact person name"></span></label>
<label>GST Number<span class="field-input"><svg class="icon"><use href="#file"/></svg><input name="gst_number" maxlength="30" placeholder="Enter GST number (optional)"></span></label>
<label>PAN Number<span class="field-input"><svg class="icon"><use href="#file"/></svg><input name="pan_number" maxlength="20" placeholder="Enter PAN number (optional)"></span></label>
<label>Licence Number<span class="field-input"><svg class="icon"><use href="#file"/></svg><input name="licence_number" maxlength="40" placeholder="Enter licence number (optional)"></span></label>
<label class="full-width">Address <em>*</em><span class="field-input"><svg class="icon"><use href="#pin"/></svg><input name="address" required maxlength="255" placeholder="Enter complete address"></span></label>
<label>State <em>*</em><select name="state" required><option value="" selected disabled>Select state</option><option>Gujarat</option><option>Maharashtra</option><option>Rajasthan</option><option>Delhi</option></select></label>
<label>City <em>*</em><select name="city" required><option value="" selected disabled>Select city</option><option>Ahmedabad</option><option>Mumbai</option><option>Jaipur</option><option>New Delhi</option></select></label>
<label>Pincode <em>*</em><span class="field-input"><svg class="icon"><use href="#pin"/></svg><input name="pincode" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Enter pincode"></span></label>
<label>Status<select name="status"><option>Active</option><option>Inactive</option></select></label>
<label class="notes-field">Notes<span class="field-input textarea-input"><svg class="icon"><use href="#file"/></svg><textarea name="notes" id="company-notes" maxlength="500" placeholder="Enter any additional notes (optional)"></textarea><small id="notes-count">0/500</small></span></label>
</div></section>
<aside class="company-create-aside"><section class="create-panel logo-panel"><h2>Company Logo</h2><label class="upload-box" for="company-logo"><span id="logo-placeholder"><svg class="upload-icon" viewBox="0 0 24 24"><path d="M12 16V4m0 0-5 5m5-5 5 5M5 16a4 4 0 1 0 1 7h12a4 4 0 0 0 1-7"/></svg><strong>Upload Company Logo</strong><small>PNG, JPG, JPEG (Max 2MB)</small><b>Choose File</b></span><img id="logo-preview" alt="Selected company logo preview" hidden></label><input id="company-logo" name="logo" type="file" accept="image/png,image/jpeg" hidden><p class="upload-error" id="upload-error" role="alert"></p></section><section class="company-info"><span>i</span><div><h2>Company Information</h2><p>Company can have multiple projects, workers and field activities. Make sure to enter correct details for better tracking and management.</p></div></section></aside>
<div class="create-form-actions"><a class="secondary-button" href="{{ route('web.companies.index') }}">Cancel</a><button class="primary-button" type="submit">Save Company</button></div>
</form><div class="company-toast" id="create-company-toast" role="status" hidden></div>
@endsection
@push('scripts')<script src="{{ asset('js/company-create.js') }}"></script>@endpush
