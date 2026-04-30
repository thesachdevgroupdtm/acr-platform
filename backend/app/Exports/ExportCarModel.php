<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\Models\CarModel;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportCarModel implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    // public function collection()
    // {
    //     return CarModel::all();
    // }

    public function view(): View
    {
        return view('backend.carmodel.export-csv');
    }
}
