@extends('layouts.app')

@section('title', 'Accès refusé – UniSync')

@section('content')
  <main class="login-page" id="principal">
    <div class="form-card">
      <h1>Accès refusé</h1>
      <p class="aide">Votre rôle ne vous permet pas d’ouvrir cette page.</p>
      <a class="btn" href="{{ route('space') }}">Retour à mon space</a>
    </div>
  </main>
@endsection
