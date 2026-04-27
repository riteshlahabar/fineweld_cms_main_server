@extends('layouts.app')
@section('title', 'Tally Integration')

@section('content')
<div class="page-wrapper">
    <div class="page-content">
        <x-breadcrumb :langArray="[
                                'app.settings',
                                'Tally Integration',
                            ]"/>

        <div class="card">
            <div class="card-header px-4 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 text-uppercase">Tally Integration</h5>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-0">Tally integration settings will be configured here.</p>
            </div>
        </div>
    </div>
</div>
@endsection