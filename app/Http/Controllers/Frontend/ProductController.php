<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\SizeAttribute;
use App\Models\Brand;
use App\Models\Color;
use App\Models\Size;
use App\Models\Product;
use App\Models\District;
use App\Models\Country;
use App\Models\Shop;
use App\Models\RightBanner;
use App\Models\ShockingDeal;
use App\Models\GeneralSetting;
use App\Models\SellerBrand;
use App\Models\SupperSlider;
use Carbon\Carbon;
use DB;

class ProductController extends Controller
{
    public function categoryWiseProduct(Request $request)
    {
    	$categoryInfoCount = Category::where('slug', $request->slug)->count('id');


		if ($categoryInfoCount > 0) {
			$brandId = json_decode($request->input('brandId'));
			$colorId = json_decode($request->input('colorId'));
			$attributeId = json_decode($request->input('attributeId'));
			$sortBy=$request->input('sortBy');
			$categoryInfo = Category::with('parentInfo')->where('slug', $request->slug)->first();
			$parentCat= Category::where('id',$categoryInfo->parent_id)->first();
			$sellerBrand = SellerBrand::with('brands')->where('category_id',$parentCat->id)->get();
			$query = Product::with([
				'stockInfo' => function ($q) use ($request) {
					$q->select('product_id', 'size_id', 'color_id', 'quantity', 'sell_price', 'whole_sale_price')->where('status', 1);
				}
			])
			->where('subcategory_id', $categoryInfo->id)
			
				->where('status', 1)
				->whereNull('deleted_at')
				->where('published', 1);

				if (!empty($brandId)) {
					$query->whereIn('brand_id', $brandId);
				}

				if (!empty($colorId)) {
					$query->whereIn('color_id', $colorId);
				}
				if (!empty($attributeId)) {
					$query->whereIn('attribute_id', $attributeId);
				}

				if (!empty($request->price)) {
                    $value = $request->price;

					$priceRange = explode('-', $value);
					$minPrice = intval($priceRange[0]); 
					$maxPrice = intval($priceRange[1]); 

				
					$query->whereBetween('sell_price', [$minPrice, $maxPrice]);
					
				
				}
				if (!empty($sortBy)) {
					if($sortBy=='new'){
						$query->orderBy('id','DESC');

					}elseif($sortBy=='old'){
						$query->orderBy('id','ASC');
					}elseif($sortBy=='max'){
						$query->orderBy('sell_price','DESC');

					}elseif($sortBy=='min'){
						$query->orderBy('sell_price','ASC');

					}
					
				}
		

				$dataList = $query->paginate($request->numOfData);
	         	$pageTitle =$categoryInfo->title;
				$metaDescription  =$categoryInfo->meta_details;
				$slug  =$categoryInfo->slug;
				$categoryList=Category::with(['subCategory'=>function($q) use($request){
					$q->where('status',1)->whereNull('deleted_at');
				},
				'subCategory.subCategory'=>function($q) use($request){
					$q->where('status',1)->whereNull('deleted_at');
				},
				])
				->where('status',1)
					->whereNull('deleted_at')
						->where('look_type',1)
							->orderBy('serial','asc')
								->get();
								$brandList=Brand::where('status',1)
								->whereNull('deleted_at')
									->get();
	
		   $colorList=Color::where('status',1)
								->whereNull('deleted_at')
									->get();
									
		   $sizeList=Size::where('status',1)
		   ->whereNull('deleted_at')
			   ->get();

				$data = [
					'errMsgFlag' => false,
					'msgFlag' => true,
					'msg' => true,
					'errMsg' => true,
					'pageTitle'=>$pageTitle,
					'metaDescription'=>$metaDescription,
					'slug'=>$slug ,
					'dataList' => $dataList,
					'categoryInfo' => $categoryInfo,
					'sellerBrand'=>$sellerBrand,
					'categoryList'=>$categoryList,
					'brandList'=>$brandList,
					'colorList'=>$colorList,
					'sizeList'=>$sizeList,
					
				];
	
				return response()->json($data, 200);

		} else {
			

		}
    }
     public function sizeAttribute(Request $request){
		$query =SizeAttribute::whereNull('deleted_at');
		if(isset($request->sizeId) &&!is_null($request->sizeId))
		$query->where('size_id',$request->sizeId);
	
	$dataList=$query->get();
	return response()->json($dataList,200);

       }
	public function subCategoryWiseProduct(Request $request)
    {
    	$categoryInfoCount = Category::where('slug', $request->slug)->count('id');


		if ($categoryInfoCount > 0) {
			$brandId = json_decode($request->input('brandId'));
			$colorId = json_decode($request->input('colorId'));
			$sortBy=$request->input('sortBy');
			$categoryInfo = Category::with('parentInfo')->where('slug', $request->slug)->first();
			$subCat= Category::where('id',$categoryInfo->parent_id)->first();
			$parentCat= Category::where('id',$subCat->parent_id)->first();
			$sellerBrand = SellerBrand::with('brands')->where('category_id',$parentCat->id)->get();
			$query = Product::with([
				'stockInfo' => function ($q) use ($request) {
					$q->select('product_id', 'size_id', 'color_id', 'quantity', 'sell_price', 'whole_sale_price')->where('status', 1);
				}
			])
			->where('sub_subcategory_id', $categoryInfo->id)
			
				->where('status', 1)
				->whereNull('deleted_at')
				->where('published', 1);

				if (!empty($brandId)) {
					$query->whereIn('brand_id', $brandId);
				}

				if (!empty($colorId)) {
					$query->whereIn('color_id', $colorId);
				}
				if (!empty($sortBy)) {
					if($sortBy=='new'){
						$query->orderBy('id','DESC');

					}elseif($sortBy=='old'){
						$query->orderBy('id','ASC');
					}elseif($sortBy=='max'){
						$query->orderBy('sell_price','DESC');

					}elseif($sortBy=='min'){
						$query->orderBy('sell_price','ASC');

					}
					
				}
			
 				$dataList = $query->paginate($request->numOfData);
               	$pageTitle =$categoryInfo->title;
				$metaDescription  =$categoryInfo->meta_details;
				$slug  =$categoryInfo->slug;
				
				$colorList=Color::where('status',1)
				->whereNull('deleted_at')
					->get();
				$data = [
					'errMsgFlag' => false,
					'msgFlag' => true,
					'msg' => true,
					'errMsg' => true,
					'pageTitle'=>$pageTitle,
					'metaDescription'=>$metaDescription,
					'slug'=>$slug ,
					'dataList' => $dataList,
					'categoryInfo' => $categoryInfo,
					'sellerBrand'=>$sellerBrand,
					'colorList'=>$colorList,
					'parentCat'=>$parentCat,
				];
	
				return response()->json($data, 200);

		} else {
			

		}
    }
	public function comboWiseProduct(Request $request)
    {
    	// $query=Product::join('stock_infos','products.id', '=','stock_infos.product_id')
	
		// 	->where('products.status',1)
		// 	->where('products.combo_offer','true')
		// 		->whereNull('products.deleted_at')
		// 			->where('products.published',1);
		
		if($request->has('sort_by')){
			
					
	
		switch ($request->sort_by) {
			case 'old':
				$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->orderBy('id','asc');
				break;
			case 'new':
				$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->orderBy('id','desc');
				break;
			case 'max':
				$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->orderBy('sell_price','desc');
				break;
			case 'min':
				$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->orderBy('sell_price','asc');
				break;
			default:
			$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->orderBy('id','desc');
				break;
		}

		}else{
			$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->orderBy('id','desc');
		}
	
		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			
			'dataList'=>$dataList,
			
		];

		return response()->json($data,200);
    }

	public function mostViewProduct(Request $request)
    {
    	// $query=Product::join('stock_infos','products.id', '=','stock_infos.product_id')
	
		// 	->where('products.status',1)
		// 	->where('products.combo_offer','true')
		// 		->whereNull('products.deleted_at')
		// 			->where('products.published',1);
		$gs = GeneralSetting::find(1);
		
		if($request->has('sort_by')){
			
					
	
		switch ($request->sort_by) {
			case 'old':
				$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->where('total_view' ,'>',$gs->most_view)
							->orderBy('id','asc');
				break;
			case 'new':
				$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->where('total_view','>',$gs->most_view)
							->orderBy('id','desc');
				break;
			case 'max':
				$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1)->orderBy('sell_price','desc');
				}])
					->where('status',1)
						->whereNull('deleted_at')
						->where('total_view' ,'>',$gs->most_view)
							->where('published',1);
				break;
			case 'min':
				$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1)->orderBy('sell_price','asc');
				}])
					->where('status',1)
						->whereNull('deleted_at')
						->where('total_view' ,'>',$gs->most_view)
							->where('published',1);
				break;
			default:
			$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->where('total_view' ,'>',$gs->most_view)
							->orderBy('id','desc');
				break;
		}

		}else{
			$query = Product::with(['stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->where('total_view' ,'>',$gs->most_view)
							->orderBy('id','desc');
		}
	
		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataList'=>$dataList,
			
		];

		return response()->json($data,200);
    }

	public function recentViewProduct(Request $request)
    {
    	// $query=Product::join('stock_infos','products.id', '=','stock_infos.product_id')
	
		// 	->where('products.status',1)
		// 	->where('products.combo_offer','true')
		// 		->whereNull('products.deleted_at')
		// 			->where('products.published',1);
		
		
		if($request->has('sort_by')){
			
					
	
		switch ($request->sort_by) {
			case 'old':
				$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							
							->orderBy('id','asc');
				break;
			case 'new':
				$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
				
							->orderBy('id','desc');
				break;
			case 'max':
				$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
					->orderBy('sell_price','desc')
						->whereNull('deleted_at')
						
							->where('published',1);
				break;
			case 'min':
				$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
						->orderBy('sell_price','asc')
							->where('published',1);
				break;
			default:
			$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							
							->orderBy('id','desc');
				break;
		}

		}else{
			$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							
							->orderBy('id','desc');
		}
	
		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataList'=>$dataList,
			
		];

		return response()->json($data,200);
    }
	public function latestProduct(Request $request)
    {
    	// $query=Product::join('stock_infos','products.id', '=','stock_infos.product_id')
	
		// 	->where('products.status',1)
		// 	->where('products.combo_offer','true')
		// 		->whereNull('products.deleted_at')
		// 			->where('products.published',1);
		
		$date =Carbon::today()->subDays(7);
		if($request->has('sort_by')){
			
					
	
		switch ($request->sort_by) {
			case 'old':
				$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->where('created_at','>=',$date)
							->orderBy('id','asc');
				break;
			case 'new':
				$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->where('created_at','>=',$date)
							->orderBy('id','desc');
				break;
			case 'max':
				$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
						->where('created_at','>=',$date)
							->where('published',1)
							->orderBy('sell_price','desc');
				break;
			case 'min':
				$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
						->where('created_at','>=',$date)
							->where('published',1)
							->orderBy('sell_price','asc');
				break;
			default:
			$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->where('created_at','>=',$date)
							->orderBy('id','desc');
				break;
		}

		}else{
			$query = Product::with(['recentView','stockInfo'=>function($q) use($request){
					$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
				}])
					->where('status',1)
						->whereNull('deleted_at')
							->where('published',1)
							->where('created_at','>=',$date)
							->orderBy('id','desc');
		}
	
		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataList'=>$dataList,
			
		];

		return response()->json($data,200);
    }

	public function comboWiseProductSort(Request $request)
    {
    	$categoryInfo=Category::where('slug','like','%'.$request->slug.'%')->first();

    	$query=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
								->where('status',1)
								->where('combo_offer','true')
									->whereNull('deleted_at')
										->where('published',1);
		// if(!empty($categoryInfo)){
		// 	$query->where(function($q) use($categoryInfo){
		// 		$q->where('category_id',$categoryInfo->id)
		// 			->orWhere('subcategory_id',$categoryInfo->id)
		// 				->orWhere('sub_subcategory_id',$categoryInfo->id);
		// 	});
		// }

		

		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataList'=>$dataList,
			'categoryInfo'=>$categoryInfo,
		];

		return response()->json($data,200);
    }


	public function brandWiseProduct(Request $request)
    {
		$brandInfoCount = Brand::where('slug', $request->slug)->count('id');


		if ($brandInfoCount > 0) {
			$brandInfo=Brand::where('slug', $request->slug)->first();
			$sortBy=$request->input('sortBy');
			$query = Product::with([
				'stockInfo' => function ($q) use ($request) {
					$q->select('product_id', 'size_id', 'color_id', 'quantity', 'sell_price', 'whole_sale_price')->where('status', 1);
				}
			])
			->where('brand_id', $brandInfo->id)
			
				->where('status', 1)
				->whereNull('deleted_at')
				->where('published', 1);

				if (!empty($sortBy)) {
					if($sortBy=='new'){
						$query->orderBy('id','DESC');

					}elseif($sortBy=='old'){
						$query->orderBy('id','ASC');
					}elseif($sortBy=='max'){
						$query->orderBy('sell_price','DESC');

					}elseif($sortBy=='min'){
						$query->orderBy('sell_price','ASC');

					}
					
				}

				$dataList = $query->paginate($request->numOfData);

				$data = [
					'errMsgFlag' => false,
					'msgFlag' => true,
					'msg' => true,
					'errMsg' => true,
					'dataList'=>$dataList,
			        'brandInfo'=>$brandInfo,
				];
	
				return response()->json($data, 200);

		} else {
			

		}





    	// $brandInfo=Brand::where('slug','like','%'.$request->slug.'%')->first();

    	// $query=Product::with(['stockInfo'=>function($q) use($request){
		// 						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
		// 					}])
		// 						->where('status',1)
		// 							->whereNull('deleted_at')
		// 							->where('published',1);
		// if(!empty($brandInfo)){
		// 	$query->where(function($q) use($brandInfo){
		// 		$q->where('brand_id',$brandInfo->id);
					
		// 	});
		// }

		// $dataList=$query->paginate($request->numOfData);

		// $data=[
		// 	'errMsgFlag'=>false,
		// 	'msgFlag'=>true,
		// 	'msg'=>true,
		// 	'errMsg'=>true,
		// 	'dataList'=>$dataList,
		// 	'brandInfo'=>$brandInfo,
		// ];

		// return response()->json($data,200);
    }

	public function vandorWiseProduct(Request $request)
    {
    	$vandorInfo=Shop::with('sellerInfo','followingInfo')->where('slug','like','%'.$request->slug.'%')->first();

    	$query=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
								->where('status',1)
									->whereNull('deleted_at')
									->where('published',1);
		if(!empty($vandorInfo)){
			$query->where(function($q) use($vandorInfo){
				$q->where('shop_id',$vandorInfo->id);
					
			});
		}

		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataList'=>$dataList,
			'vandorInfo'=>$vandorInfo,
		];

		return response()->json($data,200);
    }


	public function supperWiseProduct(Request $request)
    {
    	$supper=RightBanner::where('slug','like','%'.$request->slug.'%')->first();
		$supperSlider=SupperSlider::where('supper_id',$supper->id)->get();
		if($supper->id == 1){
			$districts = Product::distinct('districtId')->pluck('districtId');
			$districtNames = District::whereIn('id', $districts)->get();

		}else{
			$districts = Product::distinct('countryId')->pluck('countryId');
			$districtNames = Country::whereIn('id', $districts)->get();

		}
	

    	$query=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							},
							'districtInfo'=>function($q) use($request){
								
							}
							
							])
								->where('status',1)
									->whereNull('deleted_at')
									->where('published',1);
		if(!empty($supper)){
			$query->where(function($q) use($supper){
				$q->where('supper_product_id',$supper->id);
					
			});
		}

		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataList'=>$dataList,
			'supper'=>$supper,
			'supperSlider'=>$supperSlider,
			'districtNames'=>$districtNames,
		];

		return response()->json($data,200);
    }

	public function supperProduct(Request $request)
    {
    	$supperProduct=Product::where('districtId',$request->dataId)->first();
		if($supperProduct->supper_product_id==1){

		

			$query=Product::with(['stockInfo'=>function($q) use($request){
				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
			},
			'districtInfo'=>function($q) use($request){
				
			}
			
			])
				->where('status',1)
					->whereNull('deleted_at')
					->where('published',1)
					->where('districtId',$request->dataId);
					
			// $query->where(function($q) use($supper){
			// 			$q->where('districtId',$request->dataId);
							
			// 			});
					

		}else{
		
			$query=Product::with(['stockInfo'=>function($q) use($request){
				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
			},
			'districtInfo'=>function($q) use($request){
				
			}
			
			])
				->where('status',1)
					->whereNull('deleted_at')
					->where('published',1)
					->where('countryId',$request->dataId);
				

		}
	

    

		$dataInfo=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataInfo'=>$dataInfo,
		
	
		];

		return response()->json($data,200);
    }



	public function shockinDealWiseProduct(Request $request)
    {
    	$shockingDeal=ShockingDeal::where('slug','like','%'.$request->slug.'%')->first();

    	$query=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
								->where('status',1)
									->whereNull('deleted_at')
									->where('published',1);
		if(!empty($shockingDeal)){
			$query->where(function($q) use($shockingDeal){
				$q->where('shocking_deal_id',$shockingDeal->id);
					
			});
		}

		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataList'=>$dataList,
			'shockingDeal'=>$shockingDeal,
		];

		return response()->json($data,200);
    }

	public function wholesaleWiseProduct(Request $request)
    {
    	

    	// $query=Product::with(['stockInfo'=>function($q) use($request){
		// 						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
		// 					}])
		// 						->where('status',1)
		// 							->whereNull('deleted_at')
		// 							->where('published',1)
		// 							->where('wholesale','true');
		
	

		// $dataList=$query->paginate($request->numOfData);

		// $data=[
		// 	'errMsgFlag'=>false,
		// 	'msgFlag'=>true,
		// 	'msg'=>true,
		// 	'errMsg'=>true,
		// 	'dataList'=>$dataList,
			
		// ];

		// return response()->json($data,200);



		if($request->has('sort_by')){
			
					
	
			switch ($request->sort_by) {
				case 'old':
					$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
								->where('published',1)
								->where('wholesale','true')
								->orderBy('id','asc');
					break;
				case 'new':
					$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
								->where('published',1)
								->where('wholesale','true')
								->orderBy('id','desc');
					break;
				case 'max':
					$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
							->where('wholesale','true')
								->where('published',1)
								->orderBy('sell_price','desc');
					break;
				case 'min':
					$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
							->where('wholesale','true')
								->where('published',1)
								->orderBy('sell_price','asc');
					break;
				default:
				$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
								->where('published',1)
								->where('wholesale','true')
								->orderBy('id','desc');
					break;
			}
	
			}else{
				$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
								->where('published',1)
								->where('wholesale','true')
								->orderBy('id','desc');
			}
		
			$dataList=$query->paginate($request->numOfData);
	
			$data=[
				'errMsgFlag'=>false,
				'msgFlag'=>true,
				'msg'=>true,
				'errMsg'=>true,
				'dataList'=>$dataList,
				
			];
	
			return response()->json($data,200);
    }
	public function featureWiseProduct(Request $request)
    {
    	

    	// $query=Product::with(['stockInfo'=>function($q) use($request){
		// 						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
		// 					}])
		// 						->where('status',1)
		// 							->whereNull('deleted_at')
		// 							->where('published',1)
		// 							->where('feature_product','true');
		
	

		// $dataList=$query->paginate($request->numOfData);

		// $data=[
		// 	'errMsgFlag'=>false,
		// 	'msgFlag'=>true,
		// 	'msg'=>true,
		// 	'errMsg'=>true,
		// 	'dataList'=>$dataList,
			
		// ];

		// return response()->json($data,200);

		if($request->has('sort_by')){
			
					
	
			switch ($request->sort_by) {
				case 'old':
					$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
								->where('published',1)
								->where('feature_product','true')
								->orderBy('id','asc');
					break;
				case 'new':
					$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
								->where('published',1)
								->where('feature_product','true')
								->orderBy('id','desc');
					break;
				case 'max':
					$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
							->where('feature_product','true')
								->where('published',1)
								->orderBy('sell_price','desc');
					break;
				case 'min':
					$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
							->where('feature_product','true')
								->where('published',1)
								->orderBy('sell_price','asc');
					break;
				default:
				$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
								->where('published',1)
								->where('feature_product','true')
								->orderBy('id','desc');
					break;
			}
	
			}else{
				$query = Product::with(['stockInfo'=>function($q) use($request){
						$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
					}])
						->where('status',1)
							->whereNull('deleted_at')
								->where('published',1)
								->where('feature_product','true')
								->orderBy('id','desc');
			}
		
			$dataList=$query->paginate($request->numOfData);
	
			$data=[
				'errMsgFlag'=>false,
				'msgFlag'=>true,
				'msg'=>true,
				'errMsg'=>true,
				'dataList'=>$dataList,
				
			];
	
			return response()->json($data,200);
    }
	public function todayDealWiseProduct(Request $request)
    {
    	
		$now = Carbon::now();
    	$query=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
								->where('status',1)
									->whereNull('deleted_at')
									->where('published',1)
									->where('discount_start', '<=', $now)
									->where('discount_end', '>=', $now);
		
	

		$dataList=$query->paginate($request->numOfData);

		$data=[
			'errMsgFlag'=>false,
			'msgFlag'=>true,
			'msg'=>true,
			'errMsg'=>true,
			'dataList'=>$dataList,
			
		];

		return response()->json($data,200);

		// if($request->has('sort_by')){
			
					
	
		// 	switch ($request->sort_by) {
		// 		case 'old':
		// 			$query = Product::with(['stockInfo'=>function($q) use($request){
		// 				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
		// 			}])
		// 				->where('status',1)
		// 					->whereNull('deleted_at')
		// 						->where('published',1)
		// 						->where('feature_product','true')
		// 						->orderBy('id','asc');
		// 			break;
		// 		case 'new':
		// 			$query = Product::with(['stockInfo'=>function($q) use($request){
		// 				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
		// 			}])
		// 				->where('status',1)
		// 					->whereNull('deleted_at')
		// 						->where('published',1)
		// 						->where('feature_product','true')
		// 						->orderBy('id','desc');
		// 			break;
		// 		case 'max':
		// 			$query = Product::with(['stockInfo'=>function($q) use($request){
		// 				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1)->orderBy('sell_price','desc');
		// 			}])
		// 				->where('status',1)
		// 					->whereNull('deleted_at')
		// 					->where('feature_product','true')
		// 						->where('published',1);
		// 			break;
		// 		case 'min':
		// 			$query = Product::with(['stockInfo'=>function($q) use($request){
		// 				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1)->orderBy('sell_price','asc');
		// 			}])
		// 				->where('status',1)
		// 					->whereNull('deleted_at')
		// 					->where('feature_product','true')
		// 						->where('published',1);
		// 			break;
		// 		default:
		// 		$query = Product::with(['stockInfo'=>function($q) use($request){
		// 				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
		// 			}])
		// 				->where('status',1)
		// 					->whereNull('deleted_at')
		// 						->where('published',1)
		// 						->where('feature_product','true')
		// 						->orderBy('id','desc');
		// 			break;
		// 	}
	
		// 	}else{
		// 		$query = Product::with(['stockInfo'=>function($q) use($request){
		// 				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
		// 			}])
		// 				->where('status',1)
		// 					->whereNull('deleted_at')
		// 						->where('published',1)
		// 						->where('feature_product','true')
		// 						->orderBy('id','desc');
		// 	}
		
		// 	$dataList=$query->paginate($request->numOfData);
	
		// 	$data=[
		// 		'errMsgFlag'=>false,
		// 		'msgFlag'=>true,
		// 		'msg'=>true,
		// 		'errMsg'=>true,
		// 		'dataList'=>$dataList,
				
		// 	];
	
		// 	return response()->json($data,200);
    }

	public function flashSaleProduct(Request $request)
    {
    	
		$now = Carbon::now();
    	$dataList=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
								->where('status',1)
									->whereNull('deleted_at')
									->where('published',1)
									->where('discount_start', '<=', $now)
									->where('discount_end', '>=', $now)
									->get();
		
	

		return response()->json($dataList,200);
}
}