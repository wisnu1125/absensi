<?php
/**
 * Chart ringan berbasis SVG murni (bukan library JS) — dipakai Dashboard
 * Admin (donut "Analisis Penilaian" & line chart "Grafik Mingguan").
 * Sengaja tanpa dependency eksternal supaya tetap ringan sesuai prinsip
 * performa di spesifikasi refactor UI/UX.
 */

if (! function_exists('svg_donut_chart')) {
    /**
     * @param array $rincian [['jenis'=>string,'jumlah'=>int,'persen'=>int], ...] sudah terurut
     * @param array $warnaMap [jenis => warna_css] opsional, fallback ke palet siklis
     */
    function svg_donut_chart(array $rincian, int $total, array $warnaMap = [], int $size = 148): string
    {
        $radius   = ($size / 2) - 14;
        $cx       = $size / 2;
        $cy       = $size / 2;
        $circum   = 2 * M_PI * $radius;
        $palet    = ['var(--color-success)', 'var(--color-primary)', 'var(--color-substitute)', 'var(--color-warning)', 'var(--color-danger)', 'var(--teal-500)'];

        $svg  = "<svg viewBox=\"0 0 {$size} {$size}\" width=\"{$size}\" height=\"{$size}\" style=\"transform:rotate(-90deg)\">";
        $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$radius}\" fill=\"none\" stroke=\"var(--color-surface-alt)\" stroke-width=\"13\"/>";

        $offsetAcc = 0;
        foreach ($rincian as $i => $r) {
            if ($total <= 0 || $r['jumlah'] <= 0) {
                continue;
            }
            $warna   = $warnaMap[$r['jenis']] ?? $palet[$i % count($palet)];
            $panjang = ($r['jumlah'] / $total) * $circum;
            $dasharray  = round($panjang, 2) . ' ' . round($circum - $panjang, 2);
            $dashoffset = -round($offsetAcc, 2);
            $svg .= "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$radius}\" fill=\"none\" stroke=\"{$warna}\" stroke-width=\"13\" "
                  . "stroke-dasharray=\"{$dasharray}\" stroke-dashoffset=\"{$dashoffset}\" stroke-linecap=\"butt\"/>";
            $offsetAcc += $panjang;
        }
        $svg .= '</svg>';

        return $svg;
    }

    /** Palet warna default utk jenis penilaian, dipetakan konsisten. */
    function warna_jenis_penilaian(): array
    {
        return [
            'Keaktifan'       => 'var(--color-success)',
            'Bertanya'        => 'var(--color-info)',
            'Presentasi'      => 'var(--color-substitute)',
            'Praktik'         => 'var(--color-warning)',
            'Penugasan'       => 'var(--teal-500)',
            'Ulangan harian'  => 'var(--color-danger)',
            'Hafalan'         => 'var(--ocean-400)',
            'Sikap'           => 'var(--cyan-400)',
            'Kedisiplinan'    => 'var(--navy-600)',
        ];
    }
}

if (! function_exists('svg_line_chart')) {
    /**
     * @param array $data [['label'=>string,'jurnal'=>int,'penilaian'=>int], ...]
     */
    function svg_line_chart(array $data, int $width = 320, int $height = 150): string
    {
        $n = count($data);
        if ($n < 2) {
            return '<p class="text-soft" style="font-size:12.5px">Data belum cukup.</p>';
        }

        $padL = 26; $padR = 6; $padT = 10; $padB = 22;
        $plotW = $width - $padL - $padR;
        $plotH = $height - $padT - $padB;

        $maxVal = 1;
        foreach ($data as $d) {
            $maxVal = max($maxVal, $d['jurnal'], $d['penilaian']);
        }
        $maxVal = (int) ceil($maxVal / 5) * 5;
        $maxVal = max($maxVal, 5);

        $x = static fn ($i) => $padL + ($i / ($n - 1)) * $plotW;
        $y = static fn ($v) => $padT + $plotH - ($v / $maxVal) * $plotH;

        $ptsJurnal = []; $ptsPenilaian = [];
        foreach ($data as $i => $d) {
            $ptsJurnal[]    = round($x($i), 1) . ',' . round($y($d['jurnal']), 1);
            $ptsPenilaian[] = round($x($i), 1) . ',' . round($y($d['penilaian']), 1);
        }

        $svg = "<svg viewBox=\"0 0 {$width} {$height}\" width=\"100%\" height=\"{$height}\">";

        // Garis bantu horizontal (grid) tipis
        for ($g = 0; $g <= 4; $g++) {
            $gv = $maxVal * $g / 4;
            $gy = round($y($gv), 1);
            $svg .= "<line x1=\"{$padL}\" y1=\"{$gy}\" x2=\"" . ($width - $padR) . "\" y2=\"{$gy}\" stroke=\"var(--color-border)\" stroke-width=\"1\"/>";
            $svg .= "<text x=\"2\" y=\"" . ($gy + 3) . "\" font-size=\"9\" fill=\"var(--color-text-soft)\">{$gv}</text>";
        }

        // Label sumbu-X (tanggal), diselang biar tidak numpuk kalau 7 titik
        foreach ($data as $i => $d) {
            $svg .= "<text x=\"" . round($x($i), 1) . "\" y=\"" . ($height - 6) . "\" font-size=\"9\" fill=\"var(--color-text-soft)\" text-anchor=\"middle\">{$d['label']}</text>";
        }

        $svg .= '<polyline points="' . implode(' ', $ptsPenilaian) . '" fill="none" stroke="var(--color-success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>';
        $svg .= '<polyline points="' . implode(' ', $ptsJurnal) . '" fill="none" stroke="var(--color-primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>';

        foreach ($data as $i => $d) {
            $svg .= '<circle cx="' . round($x($i), 1) . '" cy="' . round($y($d['jurnal']), 1) . '" r="2.5" fill="var(--color-primary)"/>';
            $svg .= '<circle cx="' . round($x($i), 1) . '" cy="' . round($y($d['penilaian']), 1) . '" r="2.5" fill="var(--color-success)"/>';
        }

        $svg .= '</svg>';

        return $svg;
    }
}
