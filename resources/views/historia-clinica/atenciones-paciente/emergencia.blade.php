@extends('layouts.app')

@section('title', 'Historia clinica de emergencia')

@section('content')
    <livewire:historia-clinica.hc-emergencia-panel :paciente="$paciente" :atencion="$atencion" :modo="$modo" />
@endsection
