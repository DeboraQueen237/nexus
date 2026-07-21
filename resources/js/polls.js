/**
 * Composant Alpine.js pour une carte de sondage : vote (simple/multiple),
 * mise à jour des résultats en temps réel via Reverb, façon post de réseau
 * social.
 */
export default function pollCard(poll) {
    return {
        poll,
        selectedOptions: [],
        voting: false,
        justVoted: false,

        init() {
            if (!window.Echo) return;

            window.Echo.channel(`poll.${this.poll.id}`).listen('.poll.voted', (payload) => {
                if (payload.poll_id !== this.poll.id) return;

                this.poll.total_votes = payload.total_votes;
                payload.options.forEach((updated) => {
                    const option = this.poll.options.find((o) => o.id === updated.id);
                    if (option) {
                        option.vote_count = updated.vote_count;
                        option.percentage = updated.percentage;
                    }
                });
            });
        },

        toggleOption(optionId) {
            if (this.poll.is_expired || this.voting) return;

            if (this.poll.type === 'single') {
                this.vote([optionId]);
                return;
            }

            const idx = this.selectedOptions.indexOf(optionId);
            if (idx === -1) {
                this.selectedOptions.push(optionId);
            } else {
                this.selectedOptions.splice(idx, 1);
            }
        },

        submitMultiple() {
            if (this.selectedOptions.length === 0) return;
            this.vote(this.selectedOptions);
        },

        async vote(optionIds) {
            this.voting = true;

            const body = this.poll.type === 'single'
                ? { option_id: optionIds[0] }
                : { option_ids: optionIds };

            try {
                const res = await fetch(`/polls/${this.poll.id}/vote`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(body),
                });

                if (!res.ok) throw new Error('Vote impossible');
                const data = await res.json();

                this.poll = { ...this.poll, ...data };
                this.justVoted = true;
                setTimeout(() => (this.justVoted = false), 1200);
            } catch (e) {
                console.error(e);
            } finally {
                this.voting = false;
            }
        },

        isSelected(optionId) {
            return this.poll.type === 'single'
                ? this.poll.options.find((o) => o.id === optionId)?.voted_by_me
                : this.selectedOptions.includes(optionId);
        },

        async copyLink() {
            const url = `${window.location.origin}/polls/${this.poll.id}`;
            try {
                await navigator.clipboard.writeText(url);
                this.linkCopied = true;
                setTimeout(() => (this.linkCopied = false), 1500);
            } catch (e) {
                console.error(e);
            }
        },

        linkCopied: false,
    };
}
