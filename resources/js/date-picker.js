// Date picker (flatpickr) dengan rentang terkunci, mis. hanya 1 Jan - 31 Des tahun usulan.
// Input asli tetap mengirim format Y-m-d; yang terlihat user adalah input tampilan "3 Maret 2026"
// yang tidak bisa diketik manual, jadi tanggal di luar rentang tidak bisa dimasukkan sama sekali.
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import 'flatpickr/dist/flatpickr.min.css';

export function tanggalPicker(el, { min, max, onChange } = {}) {
    return flatpickr(el, {
        locale: Indonesian,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'j F Y',
        minDate: min,
        maxDate: max,
        disableMobile: true, // jangan ganti ke picker native HP, yang tidak menghormati batas tahun
        onChange: (_, tanggal) => onChange?.(tanggal),
        onReady: (_, __, fp) => {
            // Satu tahun saja: kolom tahun di header kalender dikunci (lihat .fp-tahun-terkunci di app.css).
            if (min && max && String(min).slice(0, 4) === String(max).slice(0, 4)) {
                fp.calendarContainer.classList.add('fp-tahun-terkunci');
            }
        },
    });
}
