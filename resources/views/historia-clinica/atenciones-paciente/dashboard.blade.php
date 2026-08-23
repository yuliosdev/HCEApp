@extends('layouts.app')

@section('title', 'Dashboard del paciente')

@section('content')
    <livewire:historia-clinica.paciente-dashboard :paciente="$paciente" />
@endsection
