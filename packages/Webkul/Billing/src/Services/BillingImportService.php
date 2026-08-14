<?php

namespace Webkul\Billing\Services;

use Illuminate\Support\Facades\Storage;
use Webkul\Billing\Jobs\ProcessBillingJob;

class BillingImportService
{

    public function lerCsv()
    {
        $caminho = 'billing_import_sample.csv';

        if (!Storage::exists($caminho)) {
            abort(404, 'Arquivo não encontrado');
        }


        $arquivo = fopen(Storage::path($caminho), 'r');

        $cabecalho = fgetcsv($arquivo, 1000, ',');
        $dadosFormatados = [];

        while (($linha = fgetcsv($arquivo, 1000, ',')) !== false) {

            $linha = array_combine($cabecalho, $linha);

            $pname = $linha['person_name'];
            $pemail = $linha['person_email'];
            $inumber = $linha['installment_number'];
            $parcel_number = $linha['parcel_number'];
            $original_value = $linha['original_value'];
            $delinquent_value = $linha['delinquent_value'];
            $paid_value = $linha['paid_value'];
            $interest_value = $linha['interest_value'];
            $penalty_value = $linha['penalty_value'];
            $due_date = $linha['due_date'];
            $paid_date = $linha['paid_date'];
            $status = $linha['status'];

            $dadosFormatados[$inumber]['persons'] = [
                    'name' => $pname,
                    'email' => $pemail
            ];

            $dadosFormatados[$inumber]['installments']['number'] = $inumber;
            $dadosFormatados[$inumber]['installments']['original_value'] = ($dadosFormatados[$inumber]['installments']['original_value'] ?? 0) + $original_value;
            $dadosFormatados[$inumber]['installments']['delinquent_value'] = ($dadosFormatados[$inumber]['installments']['delinquent_value'] ?? 0) + $delinquent_value;
            $dadosFormatados[$inumber]['installments']['paid_value'] = ($dadosFormatados[$inumber]['installments']['paid_value'] ?? 0) + $paid_value;
            $dadosFormatados[$inumber]['installments']['interest_value'] = ($dadosFormatados[$inumber]['installments']['interest_value'] ?? 0) + $interest_value;
            $dadosFormatados[$inumber]['installments']['penalty_value'] = ($dadosFormatados[$inumber]['installments']['penalty_value'] ?? 0) + $penalty_value;
            $dadosFormatados[$inumber]['installments']['due_date'] = $linha['due_date'];
            $dadosFormatados[$inumber]['installments']['paid_date'] = $linha['paid_date'];
            $dadosFormatados[$inumber]['installments']['status'] = $linha['status'];

            $dadosFormatados[$inumber]['billings'][$parcel_number] = [
                'parcel_number' => $parcel_number,
                'original_value' => $original_value,
                'delinquent_value' => $delinquent_value,
                'paid_value' => $paid_value,
                'interest_value' => $interest_value,
                'penalty_value' => $penalty_value,
                'due_date' => $due_date,
                'paid_date' => $paid_date,
                'status' => $status,
            ];

        };

        foreach ($dadosFormatados as $dado) {
            ProcessBillingJob::dispatch($dado);
        }

        fclose($arquivo);
        return $dadosFormatados;
    }


}
