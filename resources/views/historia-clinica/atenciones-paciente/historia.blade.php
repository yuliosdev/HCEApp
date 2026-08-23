@extends('layouts.app')

@section('title', 'Historia Clinica del paciente')

@section('content')
    <livewire:historia-clinica.historia-clinica-paciente :paciente="$paciente" />
@endsection
