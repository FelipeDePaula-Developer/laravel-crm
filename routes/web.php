<?php

use Webkul\Billing\Services\BillingImportService;

Route::get('/test-billing-csv', function () {
    $csv = array_map('str_getcsv', file(
        base_path('packages/Webkul/Billing/tests/billing_import_sample.csv')
    ));

    $header = array_shift($csv);

    $dados = array_map(function($row) use ($header) {
        return array_combine($header, $row);
    }, $csv);

    return response()->json([
        'total_registros' => count($dados),
        'primeiro_registro' => $dados[0],
        'ultimo_registro' => end($dados),
        'todos' => $dados,
    ]);
});

Route::get('/test-billing', function () {
    return response()->json((new BillingImportService())->lerCsv());
});
