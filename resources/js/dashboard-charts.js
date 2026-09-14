// Grafik di dashboard admin (Modul 6: Rekapitulasi) — dipakai di
// resources/views/admin/dashboard.blade.php, buat 3 grafik: "Keuntungan per
// Periode", "Anggota Paling Sering Belanja", dan "Distribusi per OPD".
//
// Data grafiknya DIKIRIM dari controller lewat atribut HTML data-chart (JSON)
// — file ini murni soal "cara gambar", bukan hitung-hitungan data (itu tugas
// RecapService di backend).
//
// PERBAIKAN PERFORMA: file ini di-import di app.js, yang dimuat di SEMUA
// halaman. Chart.js (~200KB) makanya di-import DINAMIS di dalam
// renderChart(), dan cuma kalau memang ada <canvas data-chart> di halaman itu
// — jadi Chart.js CUMA ke-download pas buka halaman dashboard.
//
// SATU SUMBU SAJA (15 Sep 2026): dua grafik tadinya memakai sumbu kedua
// (batang jumlah pesanan di kiri, garis rupiah di kanan). Dua skala di satu
// gambar membuat pembaca membandingkan hal yang tidak sebanding. Sekarang
// grafik seperti itu cuma berisi satu ukuran, dan ukuran pendampingnya muncul
// di tooltip lewat `keterangan`.
//
// Warna seri divalidasi untuk buta warna & kontras (panduan dataviz):
// hijau koperasi #059669, amber #b45309, biru #2a78d6.

const RUPIAH = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const RINGKAS = new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 });
const ANGKA = new Intl.NumberFormat('id-ID');

// Warna kerangka grafik: sengaja pudar supaya data yang menonjol.
const GARIS_BANTU = '#eef2ef';
const TINTA_SUMBU = '#6b7280';

/**
 * Legend berupa elemen HTML asli (bukan legend bawaan Chart.js yang digambar
 * di dalam <canvas>), supaya bisa ikut animasi pop-in seperti elemen kecil
 * lain. Grafik satu seri tidak memakai legend: judulnya sudah menamai serinya.
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

    if (items.length < 2) {
        return;
    }

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
 * Render satu grafik ke elemen <canvas data-chart="...">. Kalau elemennya
 * tidak ada di halaman, fungsi ini diam saja.
 *
 * Bentuk data-chart yang diharapkan:
 * {
 *   labels: string[],
 *   format: 'rupiah' | 'angka',        // cara menulis angka di sumbu & tooltip
 *   bars: [{ label: string, data: number[], color: string }],
 *   line?: { label: string, data: number[], color: string },  // satu sumbu dengan batang
 *   keterangan?: string[],             // baris tambahan di tooltip, per label
 *   mendatar?: boolean                 // batang mendatar, untuk label panjang (nama OPD/anggota)
 * }
 */
async function renderChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.dataset.chart) {
        return;
    }

    // Baru di-download di titik ini, dan cuma sekali.
    const { default: Chart } = await import('chart.js/auto');

    const data = JSON.parse(canvas.dataset.chart);
    const rupiah = data.format === 'rupiah';
    const tulisPenuh = (nilai) => (rupiah ? RUPIAH.format(nilai) : ANGKA.format(nilai));

    // Label panjang (nama OPD, nama anggota) terbaca utuh di batang mendatar;
    // di batang tegak labelnya dimiringkan dan terpotong.
    const sumbuNilai = data.mendatar ? 'x' : 'y';
    const sumbuKategori = data.mendatar ? 'y' : 'x';

    // Hormati setelan "kurangi animasi" di perangkat pemakai.
    const tanpaAnimasi = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const datasets = data.bars.map((bar) => ({
        type: 'bar',
        label: bar.label,
        data: bar.data,
        backgroundColor: bar.color,
        // Ujung batang membulat 4px, pangkalnya rata di garis nol.
        borderRadius: 4,
        borderSkipped: 'start',
        maxBarThickness: 44,
        order: 1,
    }));

    if (data.line) {
        datasets.push({
            type: 'line',
            label: data.line.label,
            data: data.line.data,
            borderColor: data.line.color,
            backgroundColor: data.line.color,
            borderWidth: 2,
            tension: 0.3,
            // Titik diberi cincin putih supaya terbaca di atas batang.
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            // Chart.js menggambar dataset ber-order kecil paling atas; tanpa
            // ini garisnya tertimbun batang.
            order: 0,
        });
    }

    new Chart(canvas, {
        data: { labels: data.labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: sumbuKategori,
            animation: tanpaAnimasi ? false : undefined,
            // Angka ditulis cara Indonesia (7.000.000, bukan 7,000,000).
            locale: 'id-ID',
            // axis: tanpa ini, di grafik mendatar tooltip mencari data lewat
            // sumbu nilai dan tidak pernah muncul.
            interaction: { mode: 'index', intersect: false, axis: sumbuKategori },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (konteks) => `${konteks.dataset.label}: ${tulisPenuh(konteks.parsed[sumbuNilai])}`,
                        afterBody: (item) => (data.keterangan && item.length ? data.keterangan[item[0].dataIndex] : ''),
                    },
                },
            },
            scales: {
                [sumbuKategori]: {
                    grid: { display: false },
                    ticks: { color: TINTA_SUMBU },
                },
                [sumbuNilai]: {
                    beginAtZero: true,
                    grid: { color: GARIS_BANTU },
                    border: { display: false },
                    ticks: {
                        color: TINTA_SUMBU,
                        // Jumlah pesanan tidak mungkin pecahan; rupiah diringkas (7 jt).
                        precision: 0,
                        callback: (nilai) => (rupiah ? `Rp${RINGKAS.format(nilai)}` : ANGKA.format(nilai)),
                    },
                },
            },
        },
    });

    renderLegend(`${canvasId}-legend`, datasets.map((d) => ({
        label: d.label,
        color: d.type === 'line' ? d.borderColor : d.backgroundColor,
    })));
}

document.addEventListener('DOMContentLoaded', () => {
    renderChart('revenue-chart');
    renderChart('top-members-chart');
    renderChart('opd-chart');
});
