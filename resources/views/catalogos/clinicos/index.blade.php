@extends('layouts.app')

@section('title', $titulo.' - HCEApp')

@section('content')
    <livewire:catalogos.catalogo-clinico-panel :catalogo="$catalogo" />
@endsection
