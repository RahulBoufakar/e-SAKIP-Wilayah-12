import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.store('workflowStatus', {
        status: 'draft',
        catatanRevisi: null,
        canOverrideLock: false, // true kalau user login role super_admin

        init(status, catatanRevisi = null, canOverrideLock = false) {
            this.status = status;
            this.catatanRevisi = catatanRevisi;
            this.canOverrideLock = canOverrideLock;
        },

        get isLocked() {
            if (this.status === 'menunggu_validasi') return true;
            if (this.status === 'disetujui') return !this.canOverrideLock;
            return false;
        },

        get showRevisionBanner() {
            return this.status === 'ditolak' && !!this.catatanRevisi;
        },

        get badgeLabel() {
            return {
                draft: 'Draft',
                menunggu_validasi: 'Menunggu Validasi',
                disetujui: 'Disetujui',
                ditolak: 'Ditolak',
            }[this.status] ?? this.status;
        },
    });
});