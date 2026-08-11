@extends('layouts.app')

@section('title', $titulo.' - HCEApp')

@section('content')
    <livewire:gestion-clinica.registro-clinico-panel :registro-clinico="$registroClinico" />
@endsection
