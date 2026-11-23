@extends('layouts.app')

@section('title', 'UniSync')

@push('scripts')
  <script src="{{ asset('script/slide.js') }}" defer></script>
@endpush

@php
  $total = $publications->count();
@endphp

@section('content')
  <header class="top">
    <div class="topbar">
      <div class="wrap">
        <span>{{ $annee ? 'Année universitaire ' . $annee : 'UniSync' }}</span>
        <a href="mailto:{{ config('unisync.contact.email') }}">{{ config('unisync.contact.email') }}</a>
      </div>
    </div>
    <div class="wrap">
      <nav aria-label="Navigation principale">
        <a class="logo" href="{{ url('/') }}"><i aria-hidden="true"></i>UniSync</a>
        <ul>
          <li><a href="#departements">Départements</a></li>
          <li><a href="#filieres">Filières</a></li>
          <li><a href="#services">Services</a></li>
        </ul>
        <div class="navbtns">
          <a class="btn line small" href="#contact">Contact</a>
          <a class="btn sage small" href="{{ route('login') }}">Se connecter</a>
        </div>
      </nav>

      <div class="hero">
        <div>
          <h1>Bienvenue sur votre plateforme web universitaire.</h1>
          <p>Un seul space pour l'ensemble de votre communauté universitaire.</p>
          <div class="actions">
            <a class="btn sage" href="{{ route('login') }}">Se connecter</a>
            <a class="btn line" href="#departements">Nos départements</a>
          </div>
        </div>

        <div class="pubs" role="region" aria-roledescription="carrousel" aria-label="Publications du département">
          <header>
            <h2>Publications</h2><span>Dernières annonces</span>
          </header>
          <div id="slides" aria-live="off">
            @forelse ($publications as $p)
              <article class="slide" aria-label="{{ $loop->iteration }} sur {{ $total }}" @if (!$loop->first) hidden @endif>
                <time datetime="{{ $p->date_publication->format('Y-m-d') }}">{{ $p->date_publication->locale('fr')->translatedFormat('j F Y') }}</time>
                <h3>{{ $p->titre }}</h3>
                <p>{{ \Illuminate\Support\Str::limit($p->contenu, 160, '…') }}</p>
              </article>
            @empty
              <article class="slide">
                <h3>Aucune publication pour le moment</h3>
                <p>Les annonces du département apparaîtront ici.</p>
              </article>
            @endforelse
          </div>
          @if ($total > 1)
            <div class="ctrl">
              <button type="button" id="pause" aria-label="Mettre le diaporama en pause">Pause</button>
              <div class="dots" id="dots"></div>
              <div>
                <button type="button" id="prev" aria-label="Publication précédente">‹</button>
                <button type="button" id="next" aria-label="Publication suivante">›</button>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </header>

  <main id="principal">
    <section class="chiffres" aria-label="Chiffres clés">
      <div class="wrap">
        <dl>
          @foreach (['etudiants' => 'Étudiants', 'enseignants' => 'Enseignants', 'filieres' => 'Filières', 'formations' => 'Formations'] as $k => $label)
            <div>
              <dd>{{ (int) $stats[$k] }}</dd>
              <dt>{{ $label }}</dt>
            </div>
          @endforeach
        </dl>
      </div>
    </section>

    <section id="departements">
      <div class="wrap">
        <h2 class="big">Nos départements</h2>
        <p class="lead">Chaque département regroupe des filières et des formations, de la licence au master.</p>
        <div class="cards">
          @forelse ($departements as $d)
            @php $nbFor = $d->filieres->sum(fn ($f) => $f->formations->count()); @endphp
            <article class="card">
              <h3>{{ $d->nom }}</h3>
              <p>{{ $d->description }}</p>
              <p class="meta">{{ $d->filieres->count() }} filière(s) · {{ $nbFor }} formation(s)</p>
              <ul class="tags">
                @foreach ($d->filieres as $f)
                  <li>{{ $f->nom }}</li>
                @endforeach
              </ul>
            </article>
          @empty
            <p>Aucun département pour le moment.</p>
          @endforelse
        </div>
      </div>
    </section>

    <section id="filieres">
      <div class="wrap">
        <h2 class="big">Filières et formations</h2>
        <p class="lead">Retrouvez les filières de chaque département et les formations qu’elles proposent.</p>

        <div class="dep-grid">
          @foreach ($departements as $d)
            @continue($d->filieres->isEmpty())
            <div class="dep-col">
              <h3 class="dep">{{ $d->nom }}</h3>
              <div class="stack">
                @foreach ($d->filieres as $f)
                  <article class="card">
                    <h4>{{ $f->nom }}</h4>
                    <p>{{ $f->description }}</p>
                    <ul class="formations">
                      @foreach ($f->formations as $fo)
                        <li>{{ $fo->intitule }}<span>{{ $fo->niveau }}{{ $fo->duree ? ' · ' . (int) $fo->duree . ' an(s)' : '' }}</span></li>
                      @endforeach
                    </ul>
                  </article>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <section class="band" id="services">
      <div class="wrap cols">
        <div>
          <h2 class="big">Des services près de vous</h2>
          <p class="lead">Pour toutes vos démarches et votre vie d’étudiant, tout au long de l’année.</p>
        </div>
        <ul class="regles">
          <li><strong>Secrétariat pédagogique</strong><span>Inscriptions, attestations et certificats de scolarité : adressez-vous au secrétariat du département.</span></li>
          <li><strong>Bibliothèque universitaire</strong><span>Ouvrages, salles de travail et ressources en ligne pour préparer vos cours et vos examens.</span></li>
          <li><strong>Vie étudiante</strong><span>Clubs, associations et événements pour animer le campus.</span></li>
          <li><strong>Assistance informatique</strong><span>Un souci de connexion ou de mot de passe ? Le service informatique vous répond.</span></li>
        </ul>
      </div>
    </section>

    <section class="cta" id="connexion">
      <div class="wrap">
        <div>
          <h2>Accédez à votre space.</h2>
          <p>Connectez-vous avec votre login et votre mot de passe. Votre space s’ouvre selon votre rôle.</p>
        </div>
        <a class="btn" href="{{ route('login') }}">Se connecter</a>
      </div>
    </section>
  </main>
@endsection
