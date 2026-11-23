@extends('layouts.app')

@section('title', 'Mon space – UniSync')

{{-- Temporary page: it will be replaced by one dashboard per role. --}}
@section('content')
  <main class="login-page" id="principal">
    <div class="form-card">
      <h1>Bonjour, {{ auth()->user()->login }}</h1>
      <p class="aide">Rôle : {{ auth()->user()->primaryRole() ?? 'aucun' }}</p>

      <form method="post" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn">Se déconnecter</button>
      </form>
    </div>
  </main>
@endsection
