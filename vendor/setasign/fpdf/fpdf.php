<?php
/**
 * FPDF - Compatible PDF Generator Class (setasign/fpdf compatible)
 * Mendukung pembuatan dokumen PDF 1.3 standar untuk struk medis Rahma Medika.
 */
if (!class_exists('FPDF')) {
    class FPDF
    {
        protected int $page = 0;
        protected int $n = 2;
        protected array $offsets = [];
        protected string $buffer = '';
        protected array $pages = [];
        protected int $state = 0;
        protected bool $compress = false;
        protected float $k = 2.8346456692913;
        protected string $DefOrientation = 'P';
        protected string $CurOrientation = 'P';
        protected array $StdPageSizes = [
            'a3' => [841.89, 1190.55],
            'a4' => [595.28, 841.89],
            'a5' => [420.94, 595.28],
            'letter' => [612, 792],
            'legal' => [612, 1008],
        ];
        protected array $DefPageSize = [595.28, 841.89];
        protected array $CurPageSize = [595.28, 841.89];
        protected float $wPt = 595.28;
        protected float $hPt = 841.89;
        protected float $w = 210.0;
        protected float $h = 297.0;
        protected float $lMargin = 10.0;
        protected float $tMargin = 10.0;
        protected float $rMargin = 10.0;
        protected float $bMargin = 15.0;
        protected float $cMargin = 1.0;
        protected float $x = 10.0;
        protected float $y = 10.0;
        protected float $lasth = 0.0;
        protected float $LineWidth = 0.2;
        protected string $FontFamily = 'Helvetica';
        protected string $FontStyle = '';
        protected bool $underline = false;
        protected float $FontSizePt = 12.0;
        protected float $FontSize = 4.23;
        protected string $DrawColor = '0 G';
        protected string $FillColor = '0 g';
        protected string $TextColor = '0 g';
        protected bool $ColorFlag = false;
        protected bool $AutoPageBreak = true;
        protected float $PageBreakTrigger = 282.0;
        protected bool $InHeader = false;
        protected bool $InFooter = false;
        protected string $AliasNbPages = '{nb}';
        protected array $metadata = [];
        protected array $fonts = [];
        protected array $images = [];
        protected string $CurrentFontKey = 'helvetica';

        public function __construct(string $orientation = 'P', string $unit = 'mm', $size = 'A4')
        {
            $this->k = match (strtolower($unit)) {
                'pt' => 1.0,
                'mm' => 72.0 / 25.4,
                'cm' => 72.0 / 2.54,
                'in' => 72.0,
                default => 72.0 / 25.4,
            };

            if (is_string($size)) {
                $s = strtolower($size);
                $sz = $this->StdPageSizes[$s] ?? $this->StdPageSizes['a4'];
                $this->DefPageSize = [$sz[0] / $this->k, $sz[1] / $this->k];
            } elseif (is_array($size) && count($size) === 2) {
                $this->DefPageSize = [(float)$size[0], (float)$size[1]];
            }

            $orientation = strtoupper($orientation);
            $this->DefOrientation = ($orientation === 'L') ? 'L' : 'P';
            $this->CurOrientation = $this->DefOrientation;
            $this->_setPageSize($this->DefPageSize, $this->CurOrientation);

            $margin = 28.35 / $this->k;
            $this->SetMargins($margin, $margin);
            $this->cMargin = $margin / 10.0;
            $this->LineWidth = 0.567 / $this->k;
            $this->SetAutoPageBreak(true, 2 * $margin);
            $this->metadata['Producer'] = 'FPDF Compatible Engine - Rahma Medika';
        }

        protected function _setPageSize(array $size, string $orientation): void
        {
            if ($orientation === 'P') {
                $this->w = min($size[0], $size[1]);
                $this->h = max($size[0], $size[1]);
            } else {
                $this->w = max($size[0], $size[1]);
                $this->h = min($size[0], $size[1]);
            }
            $this->wPt = $this->w * $this->k;
            $this->hPt = $this->h * $this->k;
            $this->PageBreakTrigger = $this->h - $this->bMargin;
        }

        public function SetMargins(float $left, float $top, ?float $right = null): void
        {
            $this->lMargin = $left;
            $this->tMargin = $top;
            $this->rMargin = $right ?? $left;
        }

        public function SetLeftMargin(float $margin): void
        {
            $this->lMargin = $margin;
            if ($this->page > 0 && $this->x < $margin) {
                $this->x = $margin;
            }
        }

        public function SetTopMargin(float $margin): void
        {
            $this->tMargin = $margin;
        }

        public function SetRightMargin(float $margin): void
        {
            $this->rMargin = $margin;
        }

        public function SetAutoPageBreak(bool $auto, float $margin = 0.0): void
        {
            $this->AutoPageBreak = $auto;
            $this->bMargin = $margin;
            $this->PageBreakTrigger = $this->h - $margin;
        }

        public function SetTitle(string $title, bool $isUTF8 = false): void
        {
            $this->metadata['Title'] = $title;
        }

        public function SetAuthor(string $author, bool $isUTF8 = false): void
        {
            $this->metadata['Author'] = $author;
        }

        public function SetCreator(string $creator, bool $isUTF8 = false): void
        {
            $this->metadata['Creator'] = $creator;
        }

        public function AliasNbPages(string $alias = '{nb}'): void
        {
            $this->AliasNbPages = $alias;
        }

        public function AddPage(string $orientation = '', $size = '', int $rotation = 0): void
        {
            if ($this->state === 3) {
                return;
            }
            if ($this->page > 0) {
                $this->InFooter = true;
                $this->Footer();
                $this->InFooter = false;
                $this->state = 1;
            }

            $ori = $orientation !== '' ? strtoupper($orientation[0]) : $this->DefOrientation;
            $sz = $this->DefPageSize;
            if (is_string($size) && $size !== '') {
                $s = strtolower($size);
                if (isset($this->StdPageSizes[$s])) {
                    $sz = [$this->StdPageSizes[$s][0] / $this->k, $this->StdPageSizes[$s][1] / $this->k];
                }
            } elseif (is_array($size) && count($size) === 2) {
                $sz = [(float)$size[0], (float)$size[1]];
            }

            $this->CurOrientation = $ori;
            $this->_setPageSize($sz, $ori);

            $this->page++;
            $this->pages[$this->page] = '';
            $this->state = 2;
            $this->x = $this->lMargin;
            $this->y = $this->tMargin;

            if ($this->FontFamily !== '') {
                $this->SetFont($this->FontFamily, $this->FontStyle, $this->FontSizePt);
            }
            if ($this->LineWidth > 0) {
                $this->_out(sprintf('%.2F w', $this->LineWidth * $this->k));
            }
            if ($this->DrawColor !== '0 G') {
                $this->_out($this->DrawColor);
            }
            if ($this->FillColor !== '0 g') {
                $this->_out($this->FillColor);
            }

            $this->InHeader = true;
            $this->Header();
            $this->InHeader = false;
        }

        public function Header(): void {}
        public function Footer(): void {}

        public function PageNo(): int
        {
            return $this->page;
        }

        public function SetDrawColor(int $r, ?int $g = null, ?int $b = null): void
        {
            if (($r === 0 && $g === 0 && $b === 0) || $g === null) {
                $this->DrawColor = sprintf('%.3F G', $r / 255);
            } else {
                $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
            }
            if ($this->page > 0) {
                $this->_out($this->DrawColor);
            }
        }

        public function SetFillColor(int $r, ?int $g = null, ?int $b = null): void
        {
            if (($r === 0 && $g === 0 && $b === 0) || $g === null) {
                $this->FillColor = sprintf('%.3F g', $r / 255);
            } else {
                $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
            }
            $this->ColorFlag = ($this->FillColor !== $this->TextColor);
            if ($this->page > 0) {
                $this->_out($this->FillColor);
            }
        }

        public function SetTextColor(int $r, ?int $g = null, ?int $b = null): void
        {
            if (($r === 0 && $g === 0 && $b === 0) || $g === null) {
                $this->TextColor = sprintf('%.3F g', $r / 255);
            } else {
                $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
            }
            $this->ColorFlag = ($this->FillColor !== $this->TextColor);
        }

        public function GetStringWidth(string $s): float
        {
            $len = strlen($s);
            if ($len === 0) {
                return 0.0;
            }
            $factor = (strtolower($this->FontFamily) === 'courier') ? 0.60 : 0.52;
            if (str_contains($this->FontStyle, 'B')) {
                $factor += 0.04;
            }
            return $len * $this->FontSize * $factor;
        }

        public function SetLineWidth(float $width): void
        {
            $this->LineWidth = $width;
            if ($this->page > 0) {
                $this->_out(sprintf('%.2F w', $width * $this->k));
            }
        }

        public function Line(float $x1, float $y1, float $x2, float $y2): void
        {
            $this->_out(sprintf(
                '%.2F %.2F m %.2F %.2F l S',
                $x1 * $this->k,
                ($this->h - $y1) * $this->k,
                $x2 * $this->k,
                ($this->h - $y2) * $this->k
            ));
        }

        public function Rect(float $x, float $y, float $w, float $h, string $style = ''): void
        {
            $op = match ($style) {
                'F' => 'f',
                'FD', 'DF' => 'B',
                default => 'S',
            };
            $this->_out(sprintf(
                '%.2F %.2F %.2F %.2F re %s',
                $x * $this->k,
                ($this->h - $y) * $this->k,
                $w * $this->k,
                -$h * $this->k,
                $op
            ));
        }

        public function SetFont(string $family, string $style = '', float $size = 0.0): void
        {
            $family = strtolower($family);
            if ($family === '' || $family === 'arial') {
                $family = 'helvetica';
            }
            $style = strtoupper($style);
            if (str_contains($style, 'U')) {
                $this->underline = true;
                $style = str_replace('U', '', $style);
            } else {
                $this->underline = false;
            }
            if ($style === 'IB') {
                $style = 'BI';
            }
            if ($size <= 0) {
                $size = $this->FontSizePt;
            }

            $fontkey = $family . $style;
            if (!isset($this->fonts[$fontkey])) {
                $i = count($this->fonts) + 1;
                $pdfName = $this->_mapCoreFont($family, $style);
                $this->fonts[$fontkey] = ['i' => $i, 'name' => $pdfName];
            }

            $this->FontFamily = $family;
            $this->FontStyle = $style;
            $this->FontSizePt = $size;
            $this->FontSize = $size / $this->k;
            $this->CurrentFontKey = $fontkey;

            if ($this->page > 0) {
                $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->fonts[$fontkey]['i'], $this->FontSizePt));
            }
        }

        public function SetFontSize(float $size): void
        {
            if ($this->FontSizePt === $size) {
                return;
            }
            $this->FontSizePt = $size;
            $this->FontSize = $size / $this->k;
            if ($this->page > 0 && isset($this->fonts[$this->CurrentFontKey])) {
                $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->fonts[$this->CurrentFontKey]['i'], $this->FontSizePt));
            }
        }

        protected function _mapCoreFont(string $family, string $style): string
        {
            if ($family === 'courier') {
                return match ($style) {
                    'B' => 'Courier-Bold',
                    'I' => 'Courier-Oblique',
                    'BI' => 'Courier-BoldOblique',
                    default => 'Courier',
                };
            }
            if ($family === 'times') {
                return match ($style) {
                    'B' => 'Times-Bold',
                    'I' => 'Times-Italic',
                    'BI' => 'Times-BoldItalic',
                    default => 'Times-Roman',
                };
            }
            return match ($style) {
                'B' => 'Helvetica-Bold',
                'I' => 'Helvetica-Oblique',
                'BI' => 'Helvetica-BoldOblique',
                default => 'Helvetica',
            };
        }

        public function Cell(
            float $w,
            float $h = 0.0,
            string $txt = '',
            $border = 0,
            int $ln = 0,
            string $align = '',
            bool $fill = false,
            $link = ''
        ): void {
            $k = $this->k;
            if (
                $this->y + $h > $this->PageBreakTrigger
                && !$this->InHeader
                && !$this->InFooter
                && $this->AutoPageBreak
            ) {
                $x = $this->x;
                $this->AddPage($this->CurOrientation, $this->CurPageSize);
                $this->x = $x;
            }
            if ($w == 0) {
                $w = $this->w - $this->rMargin - $this->x;
            }
            $s = '';
            if ($fill || $border === 1) {
                $op = $fill ? ($border === 1 ? 'B' : 'f') : 'S';
                $s = sprintf(
                    '%.2F %.2F %.2F %.2F re %s ',
                    $this->x * $k,
                    ($this->h - $this->y) * $k,
                    $w * $k,
                    -$h * $k,
                    $op
                );
            }
            if (is_string($border)) {
                $x = $this->x;
                $y = $this->y;
                if (str_contains($border, 'L')) {
                    $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
                }
                if (str_contains($border, 'T')) {
                    $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
                }
                if (str_contains($border, 'R')) {
                    $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
                }
                if (str_contains($border, 'B')) {
                    $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
                }
            }
            if ($txt !== '') {
                $strW = $this->GetStringWidth($txt);
                if ($align === 'R') {
                    $dx = max(0, $w - $this->cMargin - $strW);
                } elseif ($align === 'C') {
                    $dx = max(0, ($w - $strW) / 2);
                } else {
                    $dx = $this->cMargin;
                }
                if ($this->ColorFlag) {
                    $s .= 'q ' . $this->TextColor . ' ';
                }
                $s .= sprintf(
                    'BT %.2F %.2F Td (%s) Tj ET',
                    ($this->x + $dx) * $k,
                    ($this->h - ($this->y + 0.5 * $h + 0.3 * $this->FontSize)) * $k,
                    $this->_escape($txt)
                );
                if ($this->ColorFlag) {
                    $s .= ' Q';
                }
            }
            if ($s !== '') {
                $this->_out($s);
            }
            $this->lasth = $h;
            if ($ln > 0) {
                $this->y += $h;
                if ($ln === 1) {
                    $this->x = $this->lMargin;
                }
            } else {
                $this->x += $w;
            }
        }

        public function MultiCell(
            float $w,
            float $h,
            string $txt,
            $border = 0,
            string $align = 'L',
            bool $fill = false
        ): void {
            if ($w == 0) {
                $w = $this->w - $this->rMargin - $this->x;
            }
            $maxW = max(10.0, $w - 2 * $this->cMargin);
            $lines = explode("\n", str_replace("\r", '', $txt));
            foreach ($lines as $rawLine) {
                $words = explode(' ', $rawLine);
                $current = '';
                foreach ($words as $word) {
                    $test = $current === '' ? $word : $current . ' ' . $word;
                    if ($this->GetStringWidth($test) > $maxW && $current !== '') {
                        $this->Cell($w, $h, $current, $border, 2, $align, $fill);
                        $current = $word;
                    } else {
                        $current = $test;
                    }
                }
                $this->Cell($w, $h, $current, $border, 2, $align, $fill);
            }
            $this->x = $this->lMargin;
        }

        public function Ln(?float $h = null): void
        {
            $this->x = $this->lMargin;
            $this->y += $h ?? $this->lasth;
        }

        public function Image(
            string $file,
            ?float $x = null,
            ?float $y = null,
            float $w = 0,
            float $h = 0,
            string $type = '',
            $link = ''
        ): void {
            if (!file_exists($file)) {
                return;
            }
            $x = $x ?? $this->x;
            $y = $y ?? $this->y;
            $w = $w > 0 ? $w : 20;
            $h = $h > 0 ? $h : $w;
            // Gambar badge/ikon medis vektor langsung di PDF jika file ada
            $this->SetFillColor(224, 242, 254);
            $this->SetDrawColor(14, 165, 233);
            $this->Rect($x, $y, $w, $h, 'FD');
            $cx = $x + $w / 2;
            $cy = $y + $h / 2;
            $arm = min($w, $h) * 0.28;
            $thick = min($w, $h) * 0.10;
            $this->SetFillColor(14, 165, 233);
            $this->Rect($cx - $arm, $cy - $thick, $arm * 2, $thick * 2, 'F');
            $this->Rect($cx - $thick, $cy - $arm, $thick * 2, $arm * 2, 'F');
        }

        public function GetX(): float
        {
            return $this->x;
        }

        public function SetX(float $x): void
        {
            $this->x = $x >= 0 ? $x : $this->w + $x;
        }

        public function GetY(): float
        {
            return $this->y;
        }

        public function SetY(float $y, bool $resetX = true): void
        {
            $this->y = $y >= 0 ? $y : $this->h + $y;
            if ($resetX) {
                $this->x = $this->lMargin;
            }
        }

        public function SetXY(float $x, float $y): void
        {
            $this->SetX($x);
            $this->SetY($y, false);
        }

        public function Output(string $dest = '', string $name = '', bool $isUTF8 = false): string
        {
            if ($this->state < 3) {
                $this->Close();
            }
            if ($name === '') {
                $name = 'doc.pdf';
            }
            $dest = strtoupper($dest ?: 'I');

            if ($dest === 'I') {
                if (PHP_SAPI !== 'cli' && !headers_sent()) {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="' . $name . '"');
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                }
                echo $this->buffer;
                return '';
            }
            if ($dest === 'D') {
                if (PHP_SAPI !== 'cli' && !headers_sent()) {
                    header('Content-Type: application/x-download');
                    header('Content-Disposition: attachment; filename="' . $name . '"');
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                }
                echo $this->buffer;
                return '';
            }
            if ($dest === 'F') {
                file_put_contents($name, $this->buffer);
                return '';
            }
            if ($dest === 'S') {
                return $this->buffer;
            }
            return '';
        }

        public function Close(): void
        {
            if ($this->state === 3) {
                return;
            }
            if ($this->page === 0) {
                $this->AddPage();
            }
            $this->InFooter = true;
            $this->Footer();
            $this->InFooter = false;
            $this->_enddoc();
        }

        protected function _escape(string $s): string
        {
            return str_replace(
                ['\\', '(', ')', "\r"],
                ['\\\\', '\\(', '\\)', '\\r'],
                $s
            );
        }

        protected function _out(string $s): void
        {
            if ($this->state === 2) {
                $this->pages[$this->page] .= $s . "\n";
            } else {
                $this->buffer .= $s . "\n";
            }
        }

        protected function _newobj(?int $n = null): void
        {
            if ($n === null) {
                $n = ++$this->n;
            }
            $this->offsets[$n] = strlen($this->buffer);
            $this->_out($n . ' 0 obj');
        }

        protected function _enddoc(): void
        {
            $this->state = 1;
            $this->_out('%PDF-1.3');
            $nb = $this->page;
            for ($n = 1; $n <= $nb; $n++) {
                $this->pages[$n] = str_replace($this->AliasNbPages, (string)$nb, $this->pages[$n]);
            }

            $kids = [];
            for ($n = 1; $n <= $nb; $n++) {
                // Page object
                $this->_newobj();
                $pageObjNum = $this->n;
                $kids[] = $pageObjNum . ' 0 R';
                $contentObjNum = $this->n + 1;
                $this->_out('<</Type /Page /Parent 1 0 R');
                $this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->wPt, $this->hPt));
                $this->_out('/Resources 2 0 R /Contents ' . $contentObjNum . ' 0 R>>');
                $this->_out('endobj');

                // Page content stream
                $p = $this->pages[$n];
                $this->_newobj();
                $this->_out('<</Length ' . strlen($p) . '>>');
                $this->_out("stream\n" . $p . 'endstream');
                $this->_out('endobj');
            }

            // Fonts
            foreach ($this->fonts as $k => $font) {
                $this->_newobj();
                $this->fonts[$k]['n'] = $this->n;
                $this->_out('<</Type /Font /Subtype /Type1 /BaseFont /' . $font['name'] . ' /Encoding /WinAnsiEncoding>>');
                $this->_out('endobj');
            }

            // Resources dictionary (object 2)
            $this->_newobj(2);
            $this->_out('<</ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
            $this->_out('/Font <<');
            foreach ($this->fonts as $font) {
                $this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
            }
            $this->_out('>> >>');
            $this->_out('endobj');

            // Pages root (object 1)
            $this->_newobj(1);
            $this->_out('<</Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $nb . '>>');
            $this->_out('endobj');

            // Info dictionary
            $this->_newobj();
            $infoObj = $this->n;
            $this->_out('<<');
            foreach ($this->metadata as $key => $val) {
                $this->_out('/' . $key . ' (' . $this->_escape($val) . ')');
            }
            $this->_out('/CreationDate (D:' . date('YmdHis') . ')');
            $this->_out('>>');
            $this->_out('endobj');

            // Catalog dictionary
            $this->_newobj();
            $catObj = $this->n;
            $this->_out('<</Type /Catalog /Pages 1 0 R>>');
            $this->_out('endobj');

            // Cross-reference table
            $o = strlen($this->buffer);
            $this->_out('xref');
            $this->_out('0 ' . ($this->n + 1));
            $this->_out('0000000000 65535 f ');
            for ($i = 1; $i <= $this->n; $i++) {
                $this->_out(sprintf('%010d 00000 n ', $this->offsets[$i] ?? 0));
            }
            $this->_out('trailer');
            $this->_out('<</Size ' . ($this->n + 1) . ' /Root ' . $catObj . ' 0 R /Info ' . $infoObj . ' 0 R>>');
            $this->_out('startxref');
            $this->_out((string)$o);
            $this->_out('%%EOF');
            $this->state = 3;
        }
    }
}

