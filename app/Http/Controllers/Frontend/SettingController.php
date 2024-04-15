<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use App\Models\MetaContent;

class SettingController extends Controller
{
    public function getInformation()
   {
    $globalData  = GeneralSetting::first();
    return response()->json($globalData ,200);
   
   }
   public function getSeoInformation()
   {
    $seodata  = MetaContent::first();
    return response()->json($seodata ,200);
   
   }

  
}
