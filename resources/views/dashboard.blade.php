@extends('layouts.main')
@section('title', 'Dashboard')

@section('content')
    {{-- El acceso se vuelve a comprobar en el navegador usando el token guardado. --}}
    <script>
        if (!localStorage.getItem('auth_token')) {
            window.location.replace('/');
        }
    </script>
    <dashboard></dashboard>
@endsection
