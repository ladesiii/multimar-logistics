@extends('layouts.main')
@section('title', 'Dashboard')

@section('content')
    <script>
        if (!localStorage.getItem('auth_token')) {
            window.location.replace('/');
        }
    </script>
    <dashboard></dashboard>
@endsection
