<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\PremiumPackge;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Slider;
use App\Models\Seller;
use App\Models\Shop;
use App\Models\Banner;
use App\Models\Product;
use App\Models\StockInfo;
use App\Models\Review;
use App\Models\RightBanner;
use App\Models\FlashSale;
use App\Models\ProductView;
use App\Models\ShockingDeal;
use App\Models\TopBanner;
use App\Models\MetaContent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{

	public function index(Request $request) {
		$sliderList=Slider::where('status',1)
		->whereNull('deleted_at')
		->orderBy('id','desc')
			->get();
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

					$shockingSliderList=ShockingDeal::inRandomOrder()
					->where('status',1)
						->whereNull('deleted_at')
							->get();
		$brandList=Brand::inRandomOrder()
		->where('status',1)
			->whereNull('deleted_at')
				->get();
		$recentViewProductList=Product::with(['recentView','stockInfo'=>function($q) use($request){
			$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
		}])
			->where('status',1)
				->whereNull('deleted_at')
					->where('published',1)

					->limit(20)
					->orderBy('id','desc')
								->get();
			$mostViewProductList=Product::with(['stockInfo'=>function($q) use($request){
				$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
			}])
				->where('status',1)
					->whereNull('deleted_at')
						->where('published',1)
							->orderBy('total_view','desc')
								->limit(20)
							
									->get();

			$bannerList=Banner::inRandomOrder()
					->where('status',1)
						->whereNull('deleted_at')
							->limit(2)
								->get();

								$date =Carbon::today()->subDays(7);
		$latestProductList=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
							->where('status',1)
								->whereNull('deleted_at')
									->where('published',1)
										->orderBy('id','desc')
										->where('created_at','>=',$date)
											->limit(20)
											->orderBy('id','desc')
												->get();
	
			$supperProductSliderList=RightBanner::inRandomOrder()
					->where('status',1)
					
							->limit(4)
								->get();

			$dualCategoryList=Category::with(['categoryProducts'=>function($q) use($request){
				$q->whereNull('deleted_at')
					->where('status',1)
						->where('published',1)
							->inRandomOrder()
								->limit(10);
			},'categoryProducts.stockInfo',
			'singleSubCategory'=>function($q) use($request){
				$q->whereNull('deleted_at')
					->where('status',1)
						->inRandomOrder();
			},
			'singleSubCategory.subCategoryProducts'=>function($q) use($request){
				$q->whereNull('deleted_at')
					->where('status',1)
						->where('published',1)
							->inRandomOrder()
								->limit(10);
			},'singleSubCategory.subCategoryProducts.stockInfo'

			])
			->where('look_type',1)
				->where('status',1)
					->whereNull('deleted_at')
						->inRandomOrder()
							->limit(3)
								->get();

				$toBannerList=TopBanner::
    					where('status',1)
    						->whereNull('deleted_at')
    							->get();
				$seodata  = MetaContent::first();	
							
			$responseData=[
				'recentViewProductList'=>$recentViewProductList,
				'mostViewProductList'=>$mostViewProductList,
				'bannerList'=>$bannerList,
				'latestProductList'=>$latestProductList,
				'brandList'=>$brandList,
				'shockingSliderList'=>$shockingSliderList,
				'supperProductSliderList'=>$supperProductSliderList,
				'sliderList'=>$sliderList,
				'categoryList'=>$categoryList,
				'dualCategoryList'=>$dualCategoryList,
				'toBannerList'=>$toBannerList,
				'seodata'=>$seodata,
			
			
				
				
			];
	
			return response()->json($responseData,200);
		
	}
    public function getProductInfo(Request $request)
    {
        $productInfo=Product::with(['deliveryCharge','stockInfo'=>function($q) use($request){
                                $q->select('product_id','size_id','color_id','size_attribute_id','quantity','sell_price','whole_sale_price')->where('status',1);
                            },
                            'brandInfo'=>function($q) use($request){
                                $q->select('name','id','slug');
                            },
						
                            'stockInfo.colorInfo'=>function($q) use($request){
                                $q->select('color','color_code','id');
                            },
                            'stockInfo.sizeInfo'=>function($q) use($request){
                                $q->select('size','id');
                            },
							'stockInfo.sizeVariantInfo'=>function($q) use($request){
                                $q->select('size_id','attribute','id');
                            },
                            'shopInfo',
							'stockSingleInfo.sizeInfo'=>function($q) use($request){
                                $q->select('size','id');
                            },
                            'productImages'=>function($q) use($request){
                                $q->select('product_id','base_url','product_image','color_id','alt_name','status');
                            }])
                            ->where('status',1)
                                ->whereNull('deleted_at')
                                    ->where('published',1)
                                        ->where('slug','like','%'.$request->slug.'%')
                                            ->first();
					$pageTitle =$productInfo->name;
					$metaDescription  =$productInfo->description;
					$slug  =$productInfo->slug;
											
					if(!empty($productInfo)){
						
						

						$productReview=Review::with(['images','customerInfo','stockInfo', 'stockInfo.colorInfo'=>function($q) use($request){
							$q->select('color','color_code','id');
						},
						'stockInfo.sizeInfo'=>function($q) use($request){
							$q->select('size','id');
						},
						
						])->where('product_id',$productInfo->id)->get();
						$ratingCount=Review::where('product_id',$productInfo->id)->count('id');
						
						$progress=Review::where('product_id',$productInfo->id)->sum('rating');
						$fiveStarRevieTotal=Review::where('product_id',$productInfo->id)->where('rating',5)->sum('rating');
						$fiveStarRevieTotalCount=Review::where('product_id',$productInfo->id)->where('rating',5)->count('id');
                        $fourStarRevieTotal=Review::where('product_id',$productInfo->id)->where('rating',4)->sum('rating');
						$fourStarRevieTotalCount=Review::where('product_id',$productInfo->id)->where('rating',4)->count('id');
						$threeStarRevieTotal=Review::where('product_id',$productInfo->id)->where('rating',3)->sum('rating');
						$threeStarRevieTotalCount=Review::where('product_id',$productInfo->id)->where('rating',3)->count('id');
						$twoStarRevieTotal=Review::where('product_id',$productInfo->id)->where('rating',2)->sum('rating');
						$twoStarRevieTotalCount=Review::where('product_id',$productInfo->id)->where('rating',2)->count('id');
						$oneStarRevieTotal=Review::where('product_id',$productInfo->id)->where('rating',1)->sum('rating');
						$oneStarRevieTotalCount=Review::where('product_id',$productInfo->id)->where('rating',1)->count('id');
						$totalrating = round($ratingCount ? ($progress*5)/ ($ratingCount*5) : 0);
						$category=Category::where('id',$productInfo->category_id)->first();
						//star rating system https://codesandbox.io/s/9846q4oz4r?file=/src/components/rating-stars.vue
						
						$subCategory=Category::where('id',$productInfo->subcategory_id)->first();
						
						$subSubCategory=Category::where('id',$productInfo->sub_subcategory_id)->first();
						
					}

        $relatedProducts=[];
        if(!empty($productInfo)){
                $relatedProducts=Product::with(['stockInfo'=>function($q) use($request){
                                    $q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
                                }])
                                    ->where('status',1)
									->where('id','!=',$productInfo->id)
                                        ->whereNull('deleted_at')
                                            ->where('published',1)
                                            ->where('category_id',$productInfo->category_id)
                                                ->inRandomOrder()
                                                    ->limit(20)
                                                        ->get();
        }

        $responseData=[
            'productInfo'=>$productInfo,
			'productReview'=>$productReview,
            'relatedProducts'=>$relatedProducts,
			'totalrating'=>$totalrating,
			'ratingCount'=>$ratingCount,
			'pageTitle'=>$pageTitle,
			'metaDescription'=>$metaDescription,
			'slug'=>$slug ,
			'fiveStarRevieTotal'=>$fiveStarRevieTotal,
			'fiveStarRevieTotalCount'=>$fiveStarRevieTotalCount,
			'fourStarRevieTotalCount'=>$fourStarRevieTotalCount,
			'threeStarRevieTotalCount'=>$threeStarRevieTotalCount,
			'twoStarRevieTotalCount'=>$twoStarRevieTotalCount,
			'oneStarRevieTotalCount'=>$oneStarRevieTotalCount,
			'category'=>$category,
			'subCategory'=>$subCategory,
			'subSubCategory'=>$subSubCategory,
			
			
        ];

        return response()->json($responseData,200);
    }
    public function getRandomDualCategory(Request $request)
    {
        $dataList=Category::with(['categoryProducts'=>function($q) use($request){
                                $q->whereNull('deleted_at')
                                    ->where('status',1)
                                        ->where('published',1)
                                            ->inRandomOrder()
                                                ->limit(10);
                            },'categoryProducts.stockInfo',
                            'singleSubCategory'=>function($q) use($request){
                                $q->whereNull('deleted_at')
                                    ->where('status',1)
                                        ->inRandomOrder();
                            },
                            'singleSubCategory.subCategoryProducts'=>function($q) use($request){
                                $q->whereNull('deleted_at')
                                    ->where('status',1)
                                        ->where('published',1)
                                            ->inRandomOrder()
                                                ->limit(10);
                            },'singleSubCategory.subCategoryProducts.stockInfo'

                            ])
                            ->where('look_type',1)
                                ->where('status',1)
                                    ->whereNull('deleted_at')
                                        ->inRandomOrder()
                                            ->limit(3)
                                                ->get();

         // return view('welcome',compact('dataList'));
        return response()->json($dataList,200);
    }
	public function getRecentViewedProduct(Request $request)
	{
		$dataList=Product::with(['recentView','stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
								->where('status',1)
									->whereNull('deleted_at')
										->where('published',1)
													->get();

		return response()->json($dataList,200);
	}
	public function getMostViewedProduct(Request $request)
	{
		$dataList=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
								->where('status',1)
									->whereNull('deleted_at')
										->where('published',1)
											->orderBy('total_view','desc')
												->limit(20)
													->get();

		return response()->json($dataList,200);
	}
	public function getLatestProduct(Request $request)
	{
		$date =Carbon::today()->subDays(7);
		$dataList=Product::with(['stockInfo'=>function($q) use($request){
								$q->select('product_id','size_id','color_id','quantity','sell_price','whole_sale_price')->where('status',1);
							}])
							->where('status',1)
								->whereNull('deleted_at')
									->where('published',1)
										->orderBy('id','desc')
										->where('created_at','>=',$date)
											->limit(20)
												->get();

		return response()->json($dataList,200);
	}
	public function getRandomLimitedBannerList(Request $request)
    {
    	$dataList=Banner::inRandomOrder()
    					->where('status',1)
    						->whereNull('deleted_at')
    							->limit(2)
    								->get();

    	return response()->json($dataList,200);
    }
    public function getBannerList(Request $request)
    {
    	$dataList=Banner::inRandomOrder()
    					->where('status',1)
    						->whereNull('deleted_at')
    							->get();

    	return response()->json($dataList,200);
    }
	 public function getRandomLimitedBrandList(Request $request)
    {
    	$dataList=Brand::inRandomOrder()
    					->where('status',1)
    						->whereNull('deleted_at')
    							->limit(10)
    								->get();

    	return response()->json($dataList,200);
    }
    public function getBrandList(Request $request)
    {
    	$dataList=Brand::inRandomOrder()
    					->where('status',1)
    						->whereNull('deleted_at')
    							->get();

    	return response()->json($dataList,200);
    }
	public function getCategoryList(Request $request)
    {
    	$dataList=Category::with(['subCategory'=>function($q) use($request){
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

    	return response()->json($dataList,200);
    }

	public function getCategoryTop(Request $request)
    {
    	$dataList=Category::with(['subCategory'=>function($q) use($request){
    								$q->where('status',1)->whereNull('deleted_at');
    							},
    							'subCategory.subCategory'=>function($q) use($request){
    								$q->where('status',1)->whereNull('deleted_at');
    							},
    							'categoryImage'])
    							->where('status',1)
    								->whereNull('deleted_at')
    									->where('look_type',1)
										->where('top',1)
    										->orderBy('title','asc')
    											->get();

    	return response()->json($dataList,200);
    }
	public function getSliderList(Request $request)
    {
    	$dataList=Slider::where('status',1)
    						->whereNull('deleted_at')
							->orderBy('id','desc')
    							->get();

    	return response()->json($dataList,200);
    }

     public function getRandomLimitedShopList(Request $request)
    {
    	$dataList=Shop::inRandomOrder()
    					->where('status',1)
    						->whereNull('deleted_at')
    							
    								->get();

    	return response()->json($dataList,200);
    }
	public function getRightBannerList(Request $request)
    {
    	$dataList=RightBanner::inRandomOrder()
    					->where('status',1)
    					
    							->limit(4)
    								->get();

    	return response()->json($dataList,200);
    }
    public function getShopList(Request $request)
    {
    	$dataList=Shop::inRandomOrder()
    					->where('status',1)
						->where('is_verify',1)
    						->whereNull('deleted_at')
    							->get();

    	return response()->json($dataList,200);
    }
    public function getRandomLimitedSellerList(Request $request)
    {
    	$dataList=Seller::inRandomOrder()
    					->where('status',1)
    						->whereNull('deleted_at')
    							->limit(10)
    								->get();

    	return response()->json($dataList,200);
    }
    public function getSellerList(Request $request)
    {
    	$dataList=Seller::inRandomOrder()
    					->where('status',1)
    						->whereNull('deleted_at')
    							->get();

    	return response()->json($dataList,200);
    }
	public function search(Request $request)
    {
		$query = $request->input('q');

		$results = DB::table('products')
		  ->where('name', 'like', '%'.$query.'%')
		  ->get();
	  
		return response()->json($results);
    }

	public function flashSaleTime(Request $request)
	{
		$flashsaletime = FlashSale::latest()->first();
		
		if(!empty($flashsaletime)){
			$endTimeDate=$flashsaletime->endDate->toDateString() .' '. $flashsaletime->endTime;
		}else{
			$endTimeDate=0;
		}

		$responseData=[
            'endTimeDate'=>$endTimeDate,
			
			
        ];

		return response()->json($responseData,200);
	}
	public function productView(Request $request)
	{
		$product=Product::where('slug','like','%'.$request->slug.'%')->first();
		if(!empty($product)){
		// 	$totalView = $product->total_view;
		//   $product->total_view=$totalView + 1;
		//    $product->save();

		$product->total_view=$product->total_view + 1;
		$product->save();
		$likeViewInfo=new ProductView();
		$likeViewInfo->product_id=$product->id;
		$likeViewInfo->user_ip=$request->ip();
		$likeViewInfo->view=1;
		$likeViewInfo->created_at=Carbon::now();
		$likeViewInfo->save();

		}
		
		return response()->json($product,200);
	}
	public function sizeWisePrice(Request $request){

		$product=Product::where('slug',$request->slug)->first();

		$stockInfo = StockInfo::where('product_id',$product->id)->where('size_attribute_id',$request->sizeAttribute)->first();

		return response()->json($stockInfo,200);

	}

	public function getPremiumPackge(Request $request)
    {
    	$dataList=PremiumPackge::inRandomOrder()
    					->where('status',1)
    						->whereNull('deleted_at')
    							->get();

    	return response()->json($dataList,200);
    }


	public function sitemapGenerate()
    {
        $shopList = Shop::where('status', 1)->get();
        $subCategoryList = Category::where('status', 1)->get();
      

        return response()->view('sitemap', [
            'shopList' => $shopList,
            'subCategoryList' => $subCategoryList,
         
        ])->header('Content-Type', 'text/xml');
    }

	
}