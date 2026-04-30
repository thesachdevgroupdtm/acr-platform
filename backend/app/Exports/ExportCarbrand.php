<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use App\Models\CarBrand;
use Maatwebsite\Excel\Concerns\FromCollection;

class ExportCarbrand implements FromView 
{
    /**
    * @return \Illuminate\Support\Collection
    */

    //class ExportCarbrand implements FromCollection
    // public function collection() 
    // {
    //     //return CarBrand::all(); //all data return
    //     //return view('backend.carbrand.export-csv');
    // }

     public function view(): View
    {
        return view('backend.carbrand.export-csv');
    }
}
