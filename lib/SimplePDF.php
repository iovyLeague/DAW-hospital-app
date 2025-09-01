<?php
// lib/SimplePDF.php

class SimplePDF {
    private $lines = array();
    private $title;

    public function __construct($title = 'Report') {
        $this->title = $title;
    }

    public function addLine($text) {
        $this->lines[] = (string)$text;
    }

    private function escape($s) {
        return str_replace(
            array('\\', '(', ')', "\r", "\n"),
            array('\\\\', '\\(', '\\)', '', ''),
            (string)$s
        );
    }

    public function output($filename = 'report.pdf') {
        $w = 595.28; // format A4 L
        $h = 841.89; // format A4 H

        $content  = "BT\n/F1 16 Tf 50 " . ($h - 60) . " Td (" . $this->escape($this->title) . ") Tj\nET\n";
        $y = $h - 90;
        $content .= "BT\n/F1 12 Tf\n";
        foreach ($this->lines as $line) {
            $safe = $this->escape($line);
            $content .= "1 0 0 1 50 {$y} Tm ({$safe}) Tj\n";
            $y -= 16;
            if ($y < 60) break; // doar pe o pag
        }
        $content .= "ET\n";
        $content_len = strlen($content);

        $pdf = "%PDF-1.4\n";
        $objects = array(
            
            "<< /Type /Catalog /Pages 2 0 R >>\n",
           
            "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n",
           
            "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$w} {$h}] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\n",
            
            "<< /Length {$content_len} >>\nstream\n{$content}\nendstream\n",
           
            "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n",
        );

        $offsets = array(0);
        $out = $pdf;
        foreach ($objects as $i => $obj) {
            $offsets[] = strlen($out);
            $n = $i + 1;
            $out .= "{$n} 0 obj\n{$obj}endobj\n";
        }
        $xref_pos = strlen($out);
        $count = count($objects);
        $out .= "xref\n0 " . ($count + 1) . "\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $count; $i++) {
            $off = $offsets[$i];
            $out .= sprintf("%010d 00000 n \n", $off);
        }
        $out .= "trailer\n<< /Size " . ($count + 1) . " /Root 1 0 R >>\nstartxref\n{$xref_pos}\n%%EOF";

        // Output
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($out));
        echo $out;
    }
}
