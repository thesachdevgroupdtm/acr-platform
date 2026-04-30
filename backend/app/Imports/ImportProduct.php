<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ShopCategory;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportProduct implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $category = ShopCategory::select('id')->where('name','=',$row[0])->first();
        
        if(!empty($category))
        {
            $category_id= $category->id;
        }
        else {
            $category = ShopCategory::create([
                'name' => $row[0],
                'slug' => strtolower($row[0]),
                // 'image' => NULL,
                'is_archive' => 1,
                'status' => 1,
            ]);
            $category_id = $category->id;
        }

        return new Product([
            'slug' => strtolower($row[8]),
            'shop_category_id' => $category_id,
            'name' => $row[1],
            'sku' => $row[7],
            'description' => $row[2],
            'specification' => $row[3],
            'price' => $row[6],
            'amazon_link' => $row[4],
            'flipcart_link' => $row[5],
            'meta_title' => $row[9],
            'meta_keywords' => $row[10],
            'meta_description' => $row[11],
            'is_archive' => 1,
            'status' => 1,
        ]);
    }
}
