<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NEXUS — Collaborez, communiquez, créez</title>
        <meta name="description" content="NEXUS réunit messagerie instantanée, sondages en temps réel, documentation collaborative et réunions vidéo dans une seule plateforme.">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface-50 text-gray-800">

        <div class="relative overflow-hidden">
            <div class="pointer-events-none absolute -top-32 left-1/2 h-[36rem] w-[36rem] -translate-x-1/2 rounded-full bg-gradient-to-br from-primary-200/50 to-secondary-200/50 blur-3xl"></div>

            <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-primary-600 to-secondary-600 text-white font-bold shadow-soft">N</div>
                    <span class="text-xl font-bold gradient-text">NEXUS</span>
                </div>
                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary">Ouvrir l'application</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost">Se connecter</a>
                        <a href="{{ route('register') }}" class="btn-primary">Créer un compte</a>
                    @endauth
                </nav>
            </header>

            <section class="relative z-10 mx-auto max-w-4xl px-6 pb-20 pt-12 text-center lg:px-8 lg:pt-20">
                <span class="badge-primary mx-auto mb-6 inline-flex">✨ Une seule plateforme pour toute l'équipe</span>
                <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-6xl">
                    Collaborez, communiquez,
                    <span class="gradient-text">créez ensemble</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600">
                    NEXUS réunit messagerie instantanée, sondages en temps réel, base de connaissances
                    et réunions vidéo dans une expérience fluide et intuitive.
                </p>
                <div class="mt-10 flex items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="btn-primary px-6 py-3 text-base">Commencer gratuitement</a>
                    <a href="#fonctionnalites" class="btn-secondary px-6 py-3 text-base">Découvrir les fonctionnalités</a>
                </div>
            </section>
        </div>

        <section id="fonctionnalites" class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="card animate-fade-in">
                    <div class="card-body">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">Messagerie instantanée</h3>
                        <p class="mt-2 text-sm text-gray-500">Discussions individuelles et de groupe, indicateur de frappe, réactions et partage de fichiers en temps réel.</p>
                    </div>
                </div>
                <div class="card animate-fade-in">
                    <div class="card-body">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 text-purple-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">Sondages en temps réel</h3>
                        <p class="mt-2 text-sm text-gray-500">Créez, partagez et visualisez les résultats instantanément, comme un post sur un réseau social.</p>
                    </div>
                </div>
                <div class="card animate-fade-in">
                    <div class="card-body">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">Knowledge Base</h3>
                        <p class="mt-2 text-sm text-gray-500">Documentation hiérarchisée en Markdown, avec permissions fines par rôle et par catégorie.</p>
                    </div>
                </div>
                <div class="card animate-fade-in">
                    <div class="card-body">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900">Réunions vidéo</h3>
                        <p class="mt-2 text-sm text-gray-500">Planifiez, invitez et rejoignez des réunions avec appel vidéo, vocal, chat et partage d'écran.</p>
                    </div>
                </div>
            </div>
        </section>

        <footer class="border-t border-gray-100 py-8 text-center text-sm text-gray-400">
            © {{ date('Y') }} NEXUS — Plateforme de collaboration.
        </footer>
    </body>
</html>
