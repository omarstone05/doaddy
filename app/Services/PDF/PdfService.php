<?php

namespace App\Services\PDF;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class PdfService
{
    /**
     * Generate a PDF from a view template
     *
     * @param string $view The view template path
     * @param array $data Data to pass to the view
     * @param array $options PDF options (paper size, orientation, etc.)
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generate(string $view, array $data = [], array $options = [])
    {
        $defaultOptions = [
            'paper' => 'a4',
            'orientation' => 'portrait',
        ];

        $options = array_merge($defaultOptions, $options);

        return Pdf::loadView($view, $data)
            ->setPaper($options['paper'], $options['orientation']);
    }

    /**
     * Generate and download a PDF
     *
     * @param string $view The view template path
     * @param array $data Data to pass to the view
     * @param string $filename The filename for the download
     * @param array $options PDF options
     * @return \Illuminate\Http\Response
     */
    public function download(string $view, array $data = [], string $filename = 'document.pdf', array $options = [])
    {
        $pdf = $this->generate($view, $data, $options);
        return $pdf->download($filename);
    }

    /**
     * Generate and stream a PDF (for viewing in browser)
     *
     * @param string $view The view template path
     * @param array $data Data to pass to the view
     * @param string $filename The filename
     * @param array $options PDF options
     * @return \Illuminate\Http\Response
     */
    public function stream(string $view, array $data = [], string $filename = 'document.pdf', array $options = [])
    {
        $pdf = $this->generate($view, $data, $options);
        return $pdf->stream($filename);
    }

    /**
     * Generate PDF as string
     *
     * @param string $view The view template path
     * @param array $data Data to pass to the view
     * @param array $options PDF options
     * @return string
     */
    public function output(string $view, array $data = [], array $options = []): string
    {
        $pdf = $this->generate($view, $data, $options);
        return $pdf->output();
    }
}

