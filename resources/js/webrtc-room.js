/**
 * Salle de réunion WebRTC native (maillage pair-à-pair).
 *
 * Signalisation : canal de présence Reverb (`presence-meeting.{id}`), les
 * offres/réponses SDP et les candidats ICE circulent via whisper() —
 * directement de navigateur à navigateur par le serveur WebSocket, sans
 * jamais passer par le backend Laravel.
 *
 * Limite connue : sans serveur TURN (non fourni ici, nécessiterait une
 * infrastructure dédiée), la connexion peut échouer entre deux réseaux
 * avec NAT strict/symétrique (ex. certains réseaux d'entreprise). Ça
 * fonctionne bien dans l'immense majorité des cas (réseaux domestiques,
 * 4G/5G, la plupart des NAT). Adapté à de petits groupes (jusqu'à 6-8
 * personnes) : au-delà, le maillage pair-à-pair devient coûteux en
 * bande passante pour chaque participant.
 */
export default function webrtcRoom(config) {
    return {
        meetingId: config.meetingId,
        userId: config.userId,
        userName: config.userName,
        isOrganizer: config.isOrganizer,

        localStream: null,
        peers: {}, // { [userId]: { connection, stream, name } }
        channel: null,

        micOn: true,
        camOn: true,
        screenSharing: false,
        screenTrack: null,

        chatOpen: false,
        chatMessages: [],
        chatInput: '',
        participantsOpen: false,

        joining: true,
        joinError: null,

        iceServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' },
        ],

        async init() {
            // Attendre le premier rendu
            await this.$nextTick();

            // Rendre visible la partie vidéo (le template x-if s'affiche)
            this.joining = false;

            // Attendre que le DOM soit mis à jour avec l'élément vidéo
            await this.$nextTick();

            // Maintenant, l'élément <video> est présent
            try {
                this.localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        
                if (this.$refs.localVideo) {
                    this.$refs.localVideo.srcObject = this.localStream;
                } else {
                    console.error('Élément vidéo local introuvable');
                    this.joinError = "Erreur interne : caméra non trouvée.";
                    return;
                }
            } catch (e) {
                console.error(e);
                this.joinError = "Impossible d'accéder à la caméra/micro. Vérifie les autorisations de ton navigateur.";
                return;
            }

            this.setupSignaling();
        },

        setupSignaling() {
            if (!window.Echo) {
                this.joinError = 'Connexion temps réel indisponible.';
                return;
            }

            this.channel = window.Echo.join(`meeting.${this.meetingId}`)
                .here((users) => {
                    // Rien à initier ici : les membres déjà présents nous
                    // enverront une offre via l'événement "joining" ci-dessous.
                    users.forEach((u) => {
                        if (u.id !== this.userId && !this.peers[u.id]) {
                            this.peers[u.id] = { connection: null, stream: null, name: u.name };
                        }
                    });
                })
                .joining((user) => {
                    if (user.id === this.userId) return;
                    this.createPeerConnection(user, true);
                })
                .leaving((user) => {
                    this.removePeer(user.id);
                })
                .listenForWhisper('signal', (payload) => this.handleSignal(payload))
                .listenForWhisper('chat', (payload) => {
                    this.chatMessages.push(payload);
                    this.$nextTick(() => this.scrollChat());
                });
        },

        createPeerConnection(user, isInitiator) {
            const pc = new RTCPeerConnection({ iceServers: this.iceServers });

            this.peers[user.id] = { connection: pc, stream: null, name: user.name };

            this.localStream.getTracks().forEach((track) => pc.addTrack(track, this.localStream));

            pc.ontrack = (event) => {
                if (this.peers[user.id]) {
                    this.peers[user.id].stream = event.streams[0];
                    this.$nextTick(() => {
                        const el = this.$refs[`remote-${user.id}`];
                        if (el) el.srcObject = event.streams[0];
                    });
                }
            };

            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    this.sendSignal(user.id, 'ice-candidate', event.candidate);
                }
            };

            if (isInitiator) {
                pc.createOffer()
                    .then((offer) => pc.setLocalDescription(offer))
                    .then(() => this.sendSignal(user.id, 'offer', pc.localDescription));
            }

            return pc;
        },

        async handleSignal(payload) {
            if (payload.to !== this.userId) return;

            const fromId = payload.from;
            let peer = this.peers[fromId];

            if (payload.type === 'offer') {
                if (!peer || !peer.connection) {
                    const pc = this.createPeerConnection({ id: fromId, name: peer?.name || '...' }, false);
                    peer = this.peers[fromId];
                    peer.connection = pc;
                }
                await peer.connection.setRemoteDescription(new RTCSessionDescription(payload.data));
                const answer = await peer.connection.createAnswer();
                await peer.connection.setLocalDescription(answer);
                this.sendSignal(fromId, 'answer', peer.connection.localDescription);
            } else if (payload.type === 'answer' && peer?.connection) {
                await peer.connection.setRemoteDescription(new RTCSessionDescription(payload.data));
            } else if (payload.type === 'ice-candidate' && peer?.connection) {
                try {
                    await peer.connection.addIceCandidate(new RTCIceCandidate(payload.data));
                } catch (e) {
                    console.warn('ICE candidate error', e);
                }
            }
        },

        sendSignal(toUserId, type, data) {
            this.channel?.whisper('signal', {
                from: this.userId,
                to: toUserId,
                type,
                data,
            });
        },

        removePeer(userId) {
            const peer = this.peers[userId];
            if (peer?.connection) {
                peer.connection.close();
            }
            delete this.peers[userId];
        },

        get remotePeers() {
            return Object.entries(this.peers).map(([id, p]) => ({ id, ...p }));
        },

        // ==================== Contrôles ====================

        toggleMic() {
            this.micOn = !this.micOn;
            this.localStream.getAudioTracks().forEach((t) => (t.enabled = this.micOn));
        },

        toggleCam() {
            this.camOn = !this.camOn;
            this.localStream.getVideoTracks().forEach((t) => (t.enabled = this.camOn));
        },

        async toggleScreenShare() {
            if (this.screenSharing) {
                this.stopScreenShare();
                return;
            }

            try {
                const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                this.screenTrack = screenStream.getVideoTracks()[0];
                this.screenTrack.onended = () => this.stopScreenShare();

                Object.values(this.peers).forEach((peer) => {
                    const sender = peer.connection?.getSenders().find((s) => s.track?.kind === 'video');
                    if (sender) sender.replaceTrack(this.screenTrack);
                });

                this.$refs.localVideo.srcObject = screenStream;
                this.screenSharing = true;
            } catch (e) {
                console.error(e);
            }
        },

        stopScreenShare() {
            const camTrack = this.localStream.getVideoTracks()[0];

            Object.values(this.peers).forEach((peer) => {
                const sender = peer.connection?.getSenders().find((s) => s.track?.kind === 'video');
                if (sender && camTrack) sender.replaceTrack(camTrack);
            });

            if (this.screenTrack) this.screenTrack.stop();
            this.screenTrack = null;
            this.$refs.localVideo.srcObject = this.localStream;
            this.screenSharing = false;
        },

        sendChatMessage() {
            const message = this.chatInput.trim();
            if (!message) return;

            const payload = {
                from: this.userId,
                name: this.userName,
                message,
                at: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }),
            };

            this.chatMessages.push(payload);
            this.channel?.whisper('chat', payload);
            this.chatInput = '';
            this.$nextTick(() => this.scrollChat());
        },

        scrollChat() {
            const el = this.$refs.chatContainer;
            if (el) el.scrollTop = el.scrollHeight;
        },

        leave(redirect = true) {
            Object.keys(this.peers).forEach((id) => this.removePeer(id));
            this.localStream?.getTracks().forEach((t) => t.stop());
            if (this.channel) window.Echo.leave(`meeting.${this.meetingId}`);

            if (redirect) {
                window.location.href = `/meetings/${this.meetingId}`;
            }
        },
    };
}
