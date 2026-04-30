<?php

namespace App\Imports;

use App\Models\CarModel;
use App\Models\CarBrand;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportCarModel implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $brand = CarBrand::select('id')->where('title','=',$row[0])->first();
        
        if(!empty($brand)) {
            $brand_id= $brand->id;
        } else {
            $brand = CarBrand::create([
                'title' => $row[0],
                'slug' => strtolower($row[0]),
                // 'image' => NULL,
                'is_archive' => 1,
                'status' => 1,
            ]);
            $brand_id = $brand->id;
        }

        $url = $row[2];
        // Get the filename with extension from the URL
        $filename = pathinfo($url, PATHINFO_BASENAME);

        //get public path of you project
        // $dir = public_path().'/uploads/carmodel';
        // Combine the target folder and filename to get the full path
        // $destinationPath = $dir . '/' . $filename;
        //print_r($destinationPath);exit;

        // Download the file from the URL and save it to the destination folder
        // $fileContent = file_get_contents($url);
        // file_put_contents($destinationPath, $fileContent);
       // print_r($filename);exit;

        $model = CarModel::where('title','=',$row[1])->first();
        if(!empty($model)) {
            $model->update([
                'slug' => strtolower($row[1]),
                'carbrand_id' => $brand_id,
                'title' => $row[1],
                // 'image' => "https://drive.google.com/uc?export=view&id=".$filename,
                // 'image' => $url,
                'is_archive' => 1,
                'status' => 1,
            ]);
        } else {
            return new CarModel([
                'slug' => strtolower($row[1]),
                'carbrand_id' => $brand_id,
                'title' => $row[1],
                // 'image' => "https://drive.google.com/uc?export=view&id=".$filename,
                // 'image' => $url,
                'is_archive' => 1,
                'status' => 1,
            ]);
        }
    }
}
