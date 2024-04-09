<?php

namespace App\Admin\Controllers;
use App\Models\SubCategory;
use App\Models\Vendor;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Facades\Admin;

class GeneralController extends Controller
{
    use ModelForm;

    public function GetSubCategory()
    {
        return SubCategory::where('category_id', $_GET['q'])->get(['id', DB::raw('sub_category_name')]);
    }

      public function GetVendorSubCategory()
    {
        $vendor_id = Vendor::where('admin_user_id',Admin::user()->id)->value('id');
        return SubCategory::where('category_id', $_GET['q'])->where('vendor_id', $vendor_id)->get(['id', DB::raw('sub_category_name')]);
    }
} 
