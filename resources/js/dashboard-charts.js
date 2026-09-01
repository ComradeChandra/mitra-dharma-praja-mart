// Grafik batang+garis buat dashboard admin (Modul 6: Rekapitulasi) — dipakai
// di resources/views/admin/dashboard.blade.php, buat 3 grafik: "Keuntungan
// per Periode", "Anggota Paling Sering Belanja", dan "Distribusi per OPD".
//
// Data grafiknya DIKIRIM dari controller lewat atribut HTML data-chart (JSON,
// di-encode aman pakai Illuminate\Support\Js::from() di sisi Blade) — file
// ini murni soal "cara gambar", bukan hitung-hitungan data (itu tugas
// RecapService di backend).
//
// PERBAIKAN PERFORMA: file ini di-import di app.js, yang dimuat di SEMUA
// halaman (login, katalog, tiap halaman CRUD admin, dst) — bukan cuma
// dashboard. Kalau Chart.js (~200KB) di-import statis kayak biasa di baris
// paling atas, browser bakal download-parse-jalankan library segede itu di
// SETIAP halaman, padahal cuma dashboard yang benar-benar butuh. Makanya
// import-nya dipindah jadi DINAMIS (`await import(...)`) di dalam
// renderComboChart(), dan cuma dieksekusi kalau memang ada <canvas data-chart>
// di halaman itu — jadi Chart.js CUMA ke-download pas buka halaman dashboard.

/**
 * Bikin legend manual berupa elemen HTML asli (bukan legend bawaan Chart.js
 * yang digambar di dalam <canvas>) — soalnya isi <canvas> itu piksel gambar,
 * bukan elemen DOM, jadi nggak bisa dikasih animasi CSS (mis. pop-in) kayak
 * elemen HTML biasa. Tiap item legend di sini muncul gantian berurutan
 * (animation-delay bertingkat) pakai @keyframes pop-in yang sama dengan
 * yang dipakai tab kategori/status (lihat resources/css/app.css) — biar
 * konsisten sama elemen kecil lain di seluruh halaman.
 *
 * @param {string} containerId id kotak tempat legend dipasang
 * @param {{label: string, color: string}[]} items
 */
function renderLegend(containerId, items) {
    const container = document.getElementById(containerId);
    if (!container) {
        return;
    }

    container.innerHTML = '';

    items.forEach((item, index) => {
        const pill = document.createElement('span');
        pill.className = 'inline-flex items-center gap-1.5 text-xs text-gray-600';
        pill.style.animation = 'pop-in 0.3s ease-out backwards';
        pill.style.animationDelay = `${index * 60}ms`;

        const dot = document.createElement('span');
        dot.className = 'h-2.5 w-2.5 rounded-full shrink-0';
        dot.style.backgroundColor = item.color;

        pill.appendChild(dot);
        pill.append(item.label);
        container.appendChild(pill);
    });
}

/**
 * Render 1 grafik kombinasi batang (bisa lebih dari satu seri) + 1 garis ke
 * elemen <canvas data-chart="...">. Kalau elemennya tidak ada di halaman
 * (mis. di halaman lain yang tidak butuh grafik), fungsi ini diam saja.
 *
 * Bentuk data-chart yang diharapkan:
 * {
 *   labels: string[],
 *   bars: [{ label: string, data: number[], color: string }],
 *   line: { label: string, data: number[], color: string, axis?: 'y' | 'y1' }
 * }
 *
 * `line.axis: 'y1'` dipakai kalau skala garisnya beda jauh dari batang
 * (contoh: batang = jumlah pesanan/satuan kecil, garis = nilai rupiah/besar)
 * — biar dua-duanya tetap kebaca jelas, tidak ada yang keliatan rata/hilang.
 */
async function renderComboChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.dataset.chart) {
        return;
    }

    // Baru di-download di titik ini, dan cuma sekali (import kedua otomatis
    // dibaca dari cache modul oleh browser, bukan download ulang).
    const { default: Chart } = await import('chart.js/auto');

    const data = JSON.parse(canvas.dataset.chart);
    const pakaiSumbuKedua = data.line.axis === 'y1';

    new Chart(canvas, {
        data: {
            labels: data.labels,
            datasets: [
                ...data.bars.map((bar) => ({
                    type: 'bar',
                    label: bar.label,
                    data: bar.data,
                    backgroundColor: bar.color,
                    borderRadius: 6,
                    yAxisID: 'y',
                })),
                {
                    type: 'line',
                    label: data.line.label,
                    data: data.line.data,
                    borderColor: data.line.color,
                    backgroundColor: data.line.color,
                    tension: 0.3,
                    yAxisID: pakaiSumbuKedua ? 'y1' : 'y',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            // Legend bawaan Chart.js dimatikan — diganti legend HTML manual
            // (lihat renderLegend()) biar bisa dikasih animasi pop-in.
            plugins: {
                legend: { display: false },
            },
            scales: pakaiSumbuKedua
                ? {
                    y: { beginAtZero: true, position: 'left' },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } },
                }
                : {
                    y: { beginAtZero: true },
                },
        },
    });

    renderLegend(`${canvasId}-legend`, [
        ...data.bars.map((bar) => ({ label: bar.label, color: bar.color })),
        { label: data.line.label, color: data.line.color },
    ]);
}

document.addEventListener('DOMContentLoaded', () => {
    renderComboChart('revenue-chart');
    renderComboChart('top-members-chart');
    renderComboChart('opd-chart');
});
