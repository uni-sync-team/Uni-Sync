@extends('layouts.app')

@section('title', 'Connexion – UniSync')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@push('scripts')
  <script src="{{ asset('script/login.js') }}" defer></script>
@endpush

{{-- this page has no footer --}}
@section('no_footer', '1')

@section('content')
<main class="auth" id="principal">

  <div class="auth-brand">
    <a class="logo" href="{{ route('home') }}"><i aria-hidden="true"></i>UniSync</a>

    <div>
      <p class="auth-lead">Notes, réclamations et résultats, au même endroit.</p>
      <ul class="auth-roles">
        <li><strong>Étudiant</strong><span>Consulte ses notes et dépose une réclamation.</span></li>
        <li><strong>Enseignant</strong><span>Saisit les notes de ses modules.</span></li>
        <li><strong>Administration</strong><span>Gère les départements, les filières et les comptes.</span></li>
      </ul>
    </div>
  </div>

  <div class="auth-panel">
    <div class="box">
      <h1>Connexion</h1>
      <p class="intro">Votre espace s’ouvre selon votre rôle.</p>

      @error('login')
        <p class="alert" role="alert">{{ $message }}</p>
      @enderror
      @error('password')
        <p class="alert" role="alert">{{ $message }}</p>
      @enderror

      <form action="{{ route('login') }}" method="post">
        @csrf

        <label for="login">Login</label>
        <div class="field">
          <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" required autofocus>
        </div>

        <label for="mdp">Mot de passe</label>
        <div class="field">
          <input id="mdp" name="password" type="password" autocomplete="current-password" required>
          <button class="reveal" id="reveal" type="button" aria-pressed="false" aria-label="Afficher le mot de passe" hidden>Afficher</button>
        </div>

        <button class="btn" type="submit">Se connecter</button>
      </form>

      <p class="aide">Compte oublié ? Contactez l’administrateur du département.</p>
      <p class="aide"><a href="{{ route('home') }}">Retour à l’accueil</a></p>
    </div>
  </div>

</main>
@endsection
