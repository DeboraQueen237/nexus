@extends('layouts.app')

@section('title', $meeting->title . ' - Salle - NEXUS')

@section('content')
<div
    x-data="webrtcRoom({
        meetingId: {{ $meeting->id }},
        userId: {{ auth()->id() }},
        userName: @js(auth()->user()->name),
        isOrganizer: {{ auth()->id() === $meeting->user_id ? 'true' : 'false' }},
    })"
     x-init="$nextTick(() => init())"
    class="flex h-[calc(100vh-4rem)] flex-col bg-gray-900"
>
    {{-- États de chargement / erreur --}}
    <template x-if="joining">
        <div class="flex flex-1 flex-col items-center justify-center text-white">
            <svg class="mb-3 h-8 w-8 animate-spin text-primary-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            Connexion à la réunion...
        </div>
    </template>

    <template x-if="joinError">
        <div class="flex flex-1 flex-col items-center justify-center px-4 text-center text-white">
            <p class="text-red-400" x-text="joinError"></p>
            <a href="{{ route('meetings.show', $meeting) }}" class="btn-secondary mt-4">Retour</a>
        </div>
    </template>

    <template x-if="!joining && !joinError">
        <div class="flex flex-1 overflow-hidden">
            {{-- Grille vidéo --}}
            <div class="flex flex-1 flex-col overflow-hidden">
                <div class="grid flex-1 auto-rows-fr gap-2 overflow-y-auto p-3"
                     :class="remotePeers.length === 0 ? 'grid-cols-1' : remotePeers.length === 1 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-2 lg:grid-cols-3'">

                    {{-- Ma vidéo --}}
                    <div class="relative overflow-hidden rounded-xl bg-gray-800">
                        <video x-ref="localVideo" autoplay muted playsinline class="h-full w-full object-cover"></video>
                        <span class="absolute bottom-2 left-2 rounded-md bg-black/50 px-2 py-0.5 text-xs text-white">Moi</span>
                        <span x-show="!camOn" class="absolute inset-0 flex items-center justify-center bg-gray-800 text-3xl text-white">{{ Str::of(auth()->user()->name)->substr(0,1)->upper() }}</span>
                    </div>

                    {{-- Vidéos distantes --}}
                    <template x-for="peer in remotePeers" :key="peer.id">
                        <div class="relative overflow-hidden rounded-xl bg-gray-800">
                            <video :x-ref="'remote-' + peer.id" autoplay playsinline class="h-full w-full object-cover"></video>
                            <span class="absolute bottom-2 left-2 rounded-md bg-black/50 px-2 py-0.5 text-xs text-white" x-text="peer.name"></span>
                            <template x-if="!peer.stream">
                                <span class="absolute inset-0 flex items-center justify-center text-sm text-gray-400">Connexion...</span>
                            </template>
                        </div>
                    </template>
                </div>

                {{-- Barre de contrôle --}}
                <div class="flex items-center justify-center gap-3 border-t border-gray-800 bg-gray-900 p-4">
                    <button @click="toggleMic()" class="btn-icon h-11 w-11 text-white" :class="!micOn && 'bg-red-600 hover:bg-red-700'">
                        <svg x-show="micOn" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-14 0M12 18v3m-4 0h8M12 15a3 3 0 003-3V6a3 3 0 10-6 0v6a3 3 0 003 3z"/></svg>
                        <svg x-show="!micOn" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M12 18v3m-4 0h8M9 9v3a3 3 0 004.24 2.73M15 9.34V6a3 3 0 00-5.94-.6"/></svg>
                    </button>
                    <button @click="toggleCam()" class="btn-icon h-11 w-11 text-white" :class="!camOn && 'bg-red-600 hover:bg-red-700'">
                        <svg x-show="camOn" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="!camOn" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M15 10l4.553-2.276A1 1 0 0121 8.618v6.764M5 18h8a2 2 0 002-2v-2M5 5h8a2 2 0 012 2v2"/></svg>
                    </button>
                    <button @click="toggleScreenShare()" class="btn-icon h-11 w-11 text-white" :class="screenSharing && 'bg-primary-600 hover:bg-primary-700'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </button>
                    <button @click="chatOpen = !chatOpen; participantsOpen = false" class="btn-icon h-11 w-11 text-white" :class="chatOpen && 'bg-primary-600 hover:bg-primary-700'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </button>
                    <button @click="participantsOpen = !participantsOpen; chatOpen = false" class="btn-icon h-11 w-11 text-white" :class="participantsOpen && 'bg-primary-600 hover:bg-primary-700'">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                    <button @click="leave()" class="btn h-11 rounded-full bg-red-600 px-6 text-white hover:bg-red-700">
                        Quitter
                    </button>
                </div>
            </div>

            {{-- Panneau latéral : chat --}}
            <div x-show="chatOpen" x-cloak class="flex w-80 shrink-0 flex-col border-l border-gray-800 bg-gray-900" style="display:none;">
                <div class="border-b border-gray-800 p-3 text-sm font-semibold text-white">Discussion</div>
                <div class="flex-1 space-y-2 overflow-y-auto p-3" x-ref="chatContainer">
                    <template x-for="(msg, i) in chatMessages" :key="i">
                        <div class="text-sm">
                            <span class="font-semibold text-primary-400" x-text="msg.name + ':'"></span>
                            <span class="text-gray-200" x-text="msg.message"></span>
                            <span class="ml-1 text-[10px] text-gray-500" x-text="msg.at"></span>
                        </div>
                    </template>
                    <p x-show="chatMessages.length === 0" class="text-center text-xs text-gray-500">Aucun message pour l'instant.</p>
                </div>
                <form @submit.prevent="sendChatMessage()" class="flex gap-2 border-t border-gray-800 p-3">
                    <input type="text" x-model="chatInput" placeholder="Message..." class="input-field flex-1 bg-gray-800 text-white text-sm">
                    <button class="btn-primary !px-3">→</button>
                </form>
            </div>

            {{-- Panneau latéral : participants --}}
            <div x-show="participantsOpen" x-cloak class="w-72 shrink-0 border-l border-gray-800 bg-gray-900 p-3" style="display:none;">
                <p class="mb-3 text-sm font-semibold text-white">Participants (<span x-text="remotePeers.length + 1"></span>)</p>
                <div class="space-y-2 text-sm text-gray-200">
                    <div class="flex items-center gap-2">
                        <div class="avatar h-7 w-7 text-xs">{{ Str::of(auth()->user()->name)->substr(0,1)->upper() }}</div>
                        <span>Moi</span>
                    </div>
                    <template x-for="peer in remotePeers" :key="'p-' + peer.id">
                        <div class="flex items-center gap-2">
                            <div class="avatar h-7 w-7 text-xs" x-text="peer.name.charAt(0).toUpperCase()"></div>
                            <span x-text="peer.name"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
