<?php

namespace App\Services;

use App\Models\Personil;
use App\Models\Spj;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class KwitansiDinasGenerator
{
    protected string $templatePath;

    public function __construct(?string $templatePath = null)
    {
        $this->templatePath = $templatePath ?: public_path('template/kwitansi-dinas.docx');
    }

    /**
     * @return array{path:string,url:string}
     */
    public function generate(Spj $spj): array
    {
        if (! file_exists($this->templatePath)) {
            throw new \RuntimeException('Template kwitansi dinas tidak ditemukan.');
        }

        $data = $this->buildData($spj);
        $output = $this->buildOutputPath($spj);

        copy($this->templatePath, $output['path']);

        $zip = new ZipArchive();
        if ($zip->open($output['path']) !== true) {
            throw new \RuntimeException('Gagal membuka template kwitansi dinas.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            throw new \RuntimeException('Template kwitansi dinas tidak valid.');
        }

        $documentXml = $this->fillMergeFields($documentXml, $data);

        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        return $output;
    }

    /**
     * @return array<string, string|int>
     */
    protected function buildData(Spj $spj): array
    {
        $jumlah = (int) ($spj->jumlah ?? 0);
        $totalPajak = $spj->total_pajak;
        $jumlahBersih = $spj->jumlah_bersih;

        $terbilang = $this->formatTerbilang($jumlah);
        $nomFormatted = number_format($jumlah, 0, ',', '.');
        $bersihFormatted = number_format($jumlahBersih > 0 ? $jumlahBersih : $jumlah, 0, ',', '.');

        return [
            'nom' => $nomFormatted,
            'bersih' => $bersihFormatted,
            'terbilang' => $terbilang,
            'bayar' => $spj->uraian ?: '-',
            // Tax values formatted as "X.XXX,00"
            'ppn_val' => number_format((int) $spj->ppn, 0, ',', '.') . ',00',
            'pph21_val' => number_format((int) $spj->pph21, 0, ',', '.') . ',00',
            'pph22_val' => number_format((int) $spj->pph22, 0, ',', '.') . ',00',
            'pph23_val' => number_format((int) $spj->pph23, 0, ',', '.') . ',00',
            'total_pajak_val' => number_format($totalPajak, 0, ',', '.') . ',00',
            // Middle column integer (without ,00 — the ,00 is literal in the template)
            'total_pajak_int' => number_format($totalPajak, 0, ',', '.'),
        ];
    }

    /**
     * Replace MERGEFIELD display values «field» in document XML,
     * and inject tax values into their hardcoded placeholder positions.
     *
     * Template structure:
     *   «nom»          = jumlah kotor (appears 3×: header, 3-col row gross, 3-col row net)
     *   <w:t>0,00</w:t> × 5 = PPn, PPh21, PPh22, PPh23, total pajak (in order)
     *   <w:t>0</w:t>   (1st occurrence) = total pajak integer in 3-col row middle
     *
     * @param  array<string, string|int>  $data
     */
    protected function fillMergeFields(string $documentXml, array $data): string
    {
        // 1. Replace first 2 occurrences of «nom» with gross amount (header + 3-col gross)
        $documentXml = $this->replaceNthOccurrence($documentXml, '«nom»', (string) $data['nom'], 1);
        $documentXml = $this->replaceNthOccurrence($documentXml, '«nom»', (string) $data['nom'], 1);

        // 2. Replace 3rd (now 1st remaining) «nom» with net amount
        $documentXml = $this->replaceNthOccurrence($documentXml, '«nom»', (string) $data['bersih'], 1);

        // 3. Replace other MERGEFIELD markers
        foreach (['terbilang', 'bayar'] as $field) {
            $documentXml = str_replace(
                '«' . $field . '»',
                htmlspecialchars((string) $data[$field], ENT_XML1, 'UTF-8'),
                $documentXml
            );
        }

        // 4. Replace 5 × <w:t>0,00</w:t> in order: PPn, PPh21, PPh22, PPh23, total
        //    Use unique placeholders first so that a "0,00" value doesn't confuse the next slot.
        $taxSlots = [
            '__SLOT_PPn__'         => 'ppn_val',
            '__SLOT_PPh21__'       => 'pph21_val',
            '__SLOT_PPh22__'       => 'pph22_val',
            '__SLOT_PPh23__'       => 'pph23_val',
            '__SLOT_TOTAL_PAJAK__' => 'total_pajak_val',
        ];
        foreach (array_keys($taxSlots) as $slot) {
            $documentXml = $this->replaceNthOccurrence($documentXml, '<w:t>0,00</w:t>', "<w:t>{$slot}</w:t>", 1);
        }
        foreach ($taxSlots as $slot => $field) {
            $documentXml = str_replace(
                "<w:t>{$slot}</w:t>",
                '<w:t>' . htmlspecialchars((string) $data[$field], ENT_XML1, 'UTF-8') . '</w:t>',
                $documentXml
            );
        }

        // 5. Replace 1st <w:t>0</w:t> with total pajak integer (middle column in 3-col row)
        $documentXml = $this->replaceNthOccurrence(
            $documentXml,
            '<w:t>0</w:t>',
            '<w:t>' . htmlspecialchars((string) $data['total_pajak_int'], ENT_XML1, 'UTF-8') . '</w:t>',
            1
        );

        return $documentXml;
    }

    /**
     * Replace the nth occurrence of $search with $replace in $subject.
     */
    protected function replaceNthOccurrence(string $subject, string $search, string $replace, int $nth): string
    {
        $pos = -1;
        for ($i = 0; $i < $nth; $i++) {
            $pos = strpos($subject, $search, $pos + 1);
            if ($pos === false) {
                return $subject;
            }
        }

        return substr_replace($subject, $replace, $pos, strlen($search));
    }

    protected function formatTerbilang(?int $amount): string
    {
        if (! $amount) {
            return 'nol';
        }

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('id', \NumberFormatter::SPELLOUT);
            $result = $formatter->format($amount);

            return is_string($result) && $result !== '' ? $result : (string) $amount;
        }

        return (string) $amount;
    }

    /**
     * @return array{path:string,url:string}
     */
    protected function buildOutputPath(Spj $spj): array
    {
        $filename = 'kwitansi_' . $spj->id . '_' . now()->format('Ymd_His') . '_' . Str::random(5) . '.docx';
        $relative = 'spj-kwitansi/' . $filename;

        Storage::disk('public')->makeDirectory('spj-kwitansi');

        return [
            'path' => Storage::disk('public')->path($relative),
            'url' => Storage::disk('public')->url($relative),
        ];
    }
}
