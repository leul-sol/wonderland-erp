<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * @param  array<int, array<int, string>>  $rows
     */
    public function download(string $title, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($title, $rows) {
            echo $this->spreadsheetXml($title, $rows);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function spreadsheetXml(string $title, array $rows): string
    {
        $escapedTitle = htmlspecialchars($title, ENT_XML1);
        $xml = '<?xml version="1.0"?>';
        $xml .= '<?mso-application progid="Excel.Sheet"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        $xml .= 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Worksheet ss:Name="'.$escapedTitle.'"><Table>';

        foreach ($rows as $row) {
            $xml .= '<Row>';
            foreach ($row as $cell) {
                $xml .= '<Cell><Data ss:Type="String">'.htmlspecialchars((string) $cell, ENT_XML1).'</Data></Cell>';
            }
            $xml .= '</Row>';
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return $xml;
    }
}
