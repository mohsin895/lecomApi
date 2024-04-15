<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\GeneralController;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Seller;
use App\Models\SizeAttribute;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;
use App\Models\Size;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\StockInfo;
use App\Models\DeliveryCharge;
use App\Models\ShockingDeal;
use App\Models\RightBanner;
use App\Models\Account;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\SellerBrand;
use Exception;
use Carbon\Carbon;




class ProductController extends Controller
{
    public $image_files = array();
    //upload product images
    public function getProductList(Request $request)
    { 

      

        if($request->dataId==1){
          
            $query=Product::withSum('orderItems', 'quantity')->withSum('stockItems', 'quantity')->with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockSingleInfo')->where('seller_id',Auth::guard('seller-api')->user()->id);
        
            if(isset($request->productId) && !is_null($request->productId))
                $query->where('id',$request->productId);
    
            if(isset($request->productName) && !is_null($request->productName))
                $query->where('name','like',$request->productName.'%');
    
            if(isset($request->seller) && !is_null($request->seller))
                $query->where('seller_id',$request->seller);
            if($request->categoryId=='all'){
                $query->where('deleted_at',NULL);

            }else{
                if(isset($request->categoryId) && !is_null($request->categoryId)){
                    $query->where(function($q) use($request){
                        $q->where('category_id',$request->categoryId    )
                                ->orWhere('subcategory_id',$request->categoryId)
                                    ->orWhere('sub_subcategory_id',$request->categoryId);
                    });
                }
            }
     
    
           
    
            $query->orderBy('id','desc');
            
            $dataList=$query->paginate($request->numOfData);

        }elseif($request->dataId==2){
            $query=Product::withSum('orderItems', 'quantity')->withSum('stockItems', 'quantity')->with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockSingleInfo')->where('seller_id',Auth::guard('seller-api')->user()->id)->whereNull('deleted_at')->where(['status'=>1,'published'=>1]);
        
             
            if(isset($request->productId) && !is_null($request->productId))
                $query->where('id',$request->productId);
    
            if(isset($request->productName) && !is_null($request->productName))
                $query->where('name','like',$request->productName.'%');
    
            if(isset($request->seller) && !is_null($request->seller))
                $query->where('seller_id',$request->seller);
 
    
                if($request->categoryId=='all'){
                    $query->where('deleted_at',NULL);
    
                }else{
                    if(isset($request->categoryId) && !is_null($request->categoryId)){
                        $query->where(function($q) use($request){
                            $q->where('category_id',$request->categoryId    )
                                    ->orWhere('subcategory_id',$request->categoryId)
                                        ->orWhere('sub_subcategory_id',$request->categoryId);
                        });
                    }
                }
    
            $query->orderBy('id','desc');
            
            $dataList=$query->paginate($request->numOfData);

        }elseif($request->dataId==3){
            $query=Product::withSum('orderItems', 'quantity')->withSum('stockItems', 'quantity')->with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockSingleInfo')->where('seller_id',Auth::guard('seller-api')->user()->id)->whereNull('deleted_at')->where(['status'=>1,'published'=>0]);
        
            
            if(isset($request->productId) && !is_null($request->productId))
                $query->where('id',$request->productId);
    
            if(isset($request->productName) && !is_null($request->productName))
                $query->where('name','like',$request->productName.'%');
    
            if(isset($request->seller) && !is_null($request->seller))
                $query->where('seller_id',$request->seller);
 
                if($request->categoryId=='all'){
                    $query->where('deleted_at',NULL);
    
                }else{
                    if(isset($request->categoryId) && !is_null($request->categoryId)){
                        $query->where(function($q) use($request){
                            $q->where('category_id',$request->categoryId    )
                                    ->orWhere('subcategory_id',$request->categoryId)
                                        ->orWhere('sub_subcategory_id',$request->categoryId);
                        });
                    }
                }
    
            $query->orderBy('id','desc');
            
            $dataList=$query->paginate($request->numOfData);

        }elseif($request->dataId==4){
            $query=Product::withSum('orderItems', 'quantity')->withSum('stockItems', 'quantity')->with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockSingleInfo')->where('seller_id',Auth::guard('seller-api')->user()->id)->whereNull('deleted_at')->where(['status'=>0]);
        
          
            if(isset($request->productId) && !is_null($request->productId))
                $query->where('id',$request->productId);
    
            if(isset($request->productName) && !is_null($request->productName))
                $query->where('name','like',$request->productName.'%');
    
            if(isset($request->seller) && !is_null($request->seller))
                $query->where('seller_id',$request->seller);
 
    
                if($request->categoryId=='all'){
                    $query->where('deleted_at',NULL);
    
                }else{
                    if(isset($request->categoryId) && !is_null($request->categoryId)){
                        $query->where(function($q) use($request){
                            $q->where('category_id',$request->categoryId    )
                                    ->orWhere('subcategory_id',$request->categoryId)
                                        ->orWhere('sub_subcategory_id',$request->categoryId);
                        });
                    }
                }
    
            $query->orderBy('id','desc');
            
            $dataList=$query->paginate($request->numOfData);

        }elseif($request->dataId==5){
            $query=Product::withSum('orderItems', 'quantity')->withSum('stockItems', 'quantity')->with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockSingleInfo')->where('seller_id',Auth::guard('seller-api')->user()->id)->whereNull('deleted_at')->where(['suspended'=>1]);
        
           
            if(isset($request->productId) && !is_null($request->productId))
                $query->where('id',$request->productId);
    
            if(isset($request->productName) && !is_null($request->productName))
                $query->where('name','like',$request->productName.'%');
    
            if(isset($request->seller) && !is_null($request->seller))
                $query->where('seller_id',$request->seller);
 
                if($request->categoryId=='all'){
                    $query->where('deleted_at',NULL);
    
                }else{
                    if(isset($request->categoryId) && !is_null($request->categoryId)){
                        $query->where(function($q) use($request){
                            $q->where('category_id',$request->categoryId    )
                                    ->orWhere('subcategory_id',$request->categoryId)
                                        ->orWhere('sub_subcategory_id',$request->categoryId);
                        });
                    }
                }
    
            $query->orderBy('id','desc');
            
            $dataList=$query->paginate($request->numOfData);

        }elseif($request->dataId==6){
            $query=Product::withSum('orderItems', 'quantity')->withSum('stockItems', 'quantity')->with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockSingleInfo')->where('seller_id',Auth::guard('seller-api')->user()->id)->whereNull('deleted_at')->where(['rejacted'=>1]);
        
           
            if(isset($request->productId) && !is_null($request->productId))
                $query->where('id',$request->productId);
    
            if(isset($request->productName) && !is_null($request->productName))
                $query->where('name','like',$request->productName.'%');
    
            if(isset($request->seller) && !is_null($request->seller))
                $query->where('seller_id',$request->seller);
 
    
                if($request->categoryId=='all'){
                    $query->where('deleted_at',NULL);
    
                }else{
                    if(isset($request->categoryId) && !is_null($request->categoryId)){
                        $query->where(function($q) use($request){
                            $q->where('category_id',$request->categoryId    )
                                    ->orWhere('subcategory_id',$request->categoryId)
                                        ->orWhere('sub_subcategory_id',$request->categoryId);
                        });
                    }
                }
    
            $query->orderBy('id','desc');
            
            $dataList=$query->paginate($request->numOfData);

        }elseif($request->dataId==7){
            $query=Product::withSum('orderItems', 'quantity')->withSum('stockItems', 'quantity')->with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockSingleInfo')->where('seller_id',Auth::guard('seller-api')->user()->id)->where('deleted_at','!=', NULL);
        
           
            if(isset($request->productId) && !is_null($request->productId))
                $query->where('id',$request->productId);
    
            if(isset($request->productName) && !is_null($request->productName))
                $query->where('name','like',$request->productName.'%');
    
            if(isset($request->seller) && !is_null($request->seller))
                $query->where('seller_id',$request->seller);
 
    
                if($request->categoryId=='all'){
                    $query->where('deleted_at','!=',NULL);
    
                }else{
                    if(isset($request->categoryId) && !is_null($request->categoryId)){
                        $query->where(function($q) use($request){
                            $q->where('category_id',$request->categoryId    )
                                    ->orWhere('subcategory_id',$request->categoryId)
                                        ->orWhere('sub_subcategory_id',$request->categoryId);
                        });
                    }
                }
    
            $query->orderBy('id','desc');
            
            $dataList=$query->paginate($request->numOfData);

        }
        else{
          
            $query=Product::withSum('orderItems', 'quantity')->withSum('stockItems', 'quantity')->with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockSingleInfo')->where('seller_id',Auth::guard('seller-api')->user()->id)->where(['status'=>1,'published'=>1])->whereNull('deleted_at');
        
            // if(isset($request->status) && !is_null($request->status))
            //     $query->where('status',$request->status);
    
            // if(isset($request->name) && !is_null($request->name))
            //     $query->where('name','like',$request->name.'%');
    
            // if(isset($request->seller) && !is_null($request->seller))
            //     $query->where('seller_id',$request->seller);
    
            // if(isset($request->brand) && !is_null($request->brand))
            //     $query->where('brand_id',$request->brand);
    
            // if(isset($request->published) && !is_null($request->published))
            //     $query->where('published',$request->published);
    
            // if(isset($request->category) && !is_null($request->category)){
            //     $query->where(function($q) use($request){
            //         $q->where('category_id',$request->category)
            //                 ->orWhere('subcategory_id',$request->category)
            //                     ->orWhere('sub_subcategory_id',$request->category);
            //     });
            // }
    

             
            if(isset($request->productId) && !is_null($request->productId))
                $query->where('id',$request->productId);
    
            if(isset($request->productName) && !is_null($request->productName))
                $query->where('name','like',$request->productName.'%');
    
            if(isset($request->seller) && !is_null($request->seller))
                $query->where('seller_id',$request->seller);
 
    
            if(isset($request->categoryId) && !is_null($request->categoryId)){
                $query->where(function($q) use($request){
                    $q->where('category_id',$request->categoryId    )
                            ->orWhere('subcategory_id',$request->categoryId)
                                ->orWhere('sub_subcategory_id',$request->categoryId);
                });
            }
            $query->orderBy('id','desc');
            
            $dataList=$query->paginate($request->numOfData);

        }
    

        $onlineTotalProduct=Product::where('seller_id',Auth::guard('seller-api')->user()->id)->where(['status'=>1,'published'=>1])->whereNull('deleted_at')->count('id');
        $inactiveTotalProduct=Product::where('seller_id',Auth::guard('seller-api')->user()->id)->where(['status'=>0])->whereNull('deleted_at')->count('id');
        $requestQCProduct=Product::where('seller_id',Auth::guard('seller-api')->user()->id)->where(['status'=>1,'published'=>0])->whereNull('deleted_at')->count('id');
        $deleteTotalProduct=Product::where('seller_id',Auth::guard('seller-api')->user()->id)->where('deleted_at','!=', NULL)->count('id');
        $suspendedTotalProduct=Product::where('seller_id',Auth::guard('seller-api')->user()->id)->where(['suspended'=>1])->whereNull('deleted_at')->count('id');
        $rejectedTotalProduct=Product::where('seller_id',Auth::guard('seller-api')->user()->id)->where(['rejacted'=>1])->whereNull('deleted_at')->count('id');
        $TotalProduct=Product::where('seller_id',Auth::guard('seller-api')->user()->id)->count('id');
        $category=Category::where(['status'=>1,'look_type'=>1])->get();
        $data=[
            'dataList'=>$dataList,
            'onlineTotalProduct'=>$onlineTotalProduct,
            'TotalProduct'=>$TotalProduct,
            'inactiveTotalProduct'=>$inactiveTotalProduct,
            'requestQCProduct'=>$requestQCProduct,
            'deleteTotalProduct'=>$deleteTotalProduct,
            'suspendedTotalProduct'=>$suspendedTotalProduct,
            'rejectedTotalProduct'=>$rejectedTotalProduct,
         
            'category'=>$category,
        ];

        return response()->json($data,200);
    }

    public function getStock(Request $request){
        $stockList=StockInfo::withSum('orderItems', 'quantity')->with([ 'sizeInfo', 'colorInfo','sizeVariantInfo'])->where('product_id', $request->dataId)->get();
         $productList=Product::where('id',$request->dataId)->first();

        $data=[
            'stockList'=>$stockList,
            'productList'=>$productList,
           
           
        ];
           return response()->json($data,200);
        
       

    }

    public function getProductDetailInfo(Request $request)
    {
       $dataInfo=Product::with('shopInfo','brandInfo','megaCategory','subCategory','normalCategory', 'stockInfo', 'productImages','stockInfo.colorInfo','stockInfo.sizeInfo','rejectedInfo','suspendedInfo','reviewInfo','reviewInfo.images')->where('id',$request->dataId)->first();
       $productReview=Review::with(['images','customerInfo','stockInfo', 'stockInfo.colorInfo'=>function($q) use($request){
        $q->select('color','color_code','id');
    },
    'stockInfo.sizeInfo'=>function($q) use($request){
        $q->select('size','id');
    },
    
    ])->where('product_id',$dataInfo->id)->get();
      
       $totalProductPrice = StockInfo::where('product_id',$dataInfo->id)->sum('sell_price');
        $totalOrderQty=OrderItem::where('product_id',$dataInfo->id)->sum('quantity');
        $totalQty = StockInfo::where('product_id',$dataInfo->id)->sum('quantity');
        $totalSellingPrice = OrderItem::where('product_id',$dataInfo->id)->sum('quantity');
        $totalReview=Review::where('product_id',$dataInfo->id)->count('id');
        $fiveStarRevieTotal=Review::where('product_id',$dataInfo->id)->where('rating',5)->sum('rating');
        $fiveStarRevieTotalCount=Review::where('product_id',$dataInfo->id)->where('rating',5)->count('id');
        $fourStarRevieTotal=Review::where('product_id',$dataInfo->id)->where('rating',4)->sum('rating');
        $fourStarRevieTotalCount=Review::where('product_id',$dataInfo->id)->where('rating',4)->count('id');
        $threeStarRevieTotal=Review::where('product_id',$dataInfo->id)->where('rating',3)->sum('rating');
        $threeStarRevieTotalCount=Review::where('product_id',$dataInfo->id)->where('rating',3)->count('id');
        $twoStarRevieTotal=Review::where('product_id',$dataInfo->id)->where('rating',2)->sum('rating');
        $twoStarRevieTotalCount=Review::where('product_id',$dataInfo->id)->where('rating',2)->count('id');
        $oneStarRevieTotal=Review::where('product_id',$dataInfo->id)->where('rating',1)->sum('rating');
        $oneStarRevieTotalCount=Review::where('product_id',$dataInfo->id)->where('rating',1)->count('id');
        $ratingCount=Review::where('product_id',$dataInfo->id)->count('id');
        $progress=Review::where('product_id',$dataInfo->id)->sum('rating');
        $totalrating = $ratingCount ? ($progress*5)/ ($ratingCount*5) : 0;
        $totalFiveStar=$ratingCount ? ($fiveStarRevieTotalCount/$ratingCount)*100 : 0;
        $totalFoureStar=$ratingCount ? ($fourStarRevieTotalCount/$ratingCount)*100 : 0;
        $totalThreeStar=$ratingCount ? ($threeStarRevieTotalCount/$ratingCount)*100 : 0;
        $totalTwoStar=$ratingCount ? ($twoStarRevieTotalCount/$ratingCount)*100 : 0;
        $totalOneStar=$ratingCount ? ($oneStarRevieTotalCount/$ratingCount)*100 : 0;
     

       $responseData=[
        'dataInfo'=>$dataInfo,
        'totalFiveStar'=>$totalFiveStar,
        'totalFoureStar'=>$totalFoureStar,
        'totalThreeStar'=>$totalThreeStar,
        'totalTwoStar'=>$totalTwoStar,
        'totalOneStar'=>$totalOneStar,
        'productReview'=>$productReview,
        'totalProductPrice'=>$totalProductPrice,
        'totalOrderQty'=>$totalOrderQty,
        'totalQty'=>$totalQty,
        'totalReview'=>$totalReview,
        'fiveStarRevieTotalCount'=>$fiveStarRevieTotalCount,
        'fourStarRevieTotalCount'=>$fourStarRevieTotalCount,
        'threeStarRevieTotalCount'=>$threeStarRevieTotalCount,
		'twoStarRevieTotalCount'=>$twoStarRevieTotalCount,
		'oneStarRevieTotalCount'=>$oneStarRevieTotalCount,
        'totalrating'=>$totalrating,
       
    ];
       return response()->json($responseData,200);
    }
    public function getProductInfo(Request $request)
    {
       $dataInfo=Product::find($request->dataId);

       if(!empty($dataInfo)) {
          $responseData=[
                'errMsgFlag'=>false,
                'msgFlag'=>true,
                'errMsg'=>null,
                'msg'=>null,
                'dataInfo'=>$dataInfo
          ];  
       }
       else{
            $responseData=[
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'errMsg'=>'Requested Data Not Found.',
                'msg'=>null,
                'dataInfo'=>$dataInfo
          ];
       }

       return response()->json($responseData,200);
    }

    public function addProduct(Request $request)
    {
        try{
            DB::beginTransaction();

         

            $sellerInfo=Seller::with('shopInfo')
                              ->where('id',Auth::guard('seller-api')->user()->id)
                                   ->first();
           //  $sellerInfo = null;
            $quantity=0;

            $dataInfo=new Product();
            $dataInfo->category_id = $request->category_id;
            $dataInfo->subcategory_id = $request->subcategory_id;
            $dataInfo->sub_subcategory_id = $request->sub_subcategory_id;
            $dataInfo->name = $request->name;
            $dataInfo->added_by = 1;
            $dataInfo->staff_id = null;
            $dataInfo->seller_id = isset($sellerInfo) ? $sellerInfo->id : null;
            $dataInfo->shop_id = (isset($sellerInfo) && !is_null($sellerInfo->shopInfo)) ? $sellerInfo->shopInfo->id : null;
            $dataInfo->brand_id = $request->brand_id;
            $dataInfo->refundable = $request->refundable ? 1 : 0;
            $dataInfo->tags = $request->tags;
            $dataInfo->height = $request->height;
            $dataInfo->weight = $request->weight;
            $dataInfo->length = $request->length;
            $dataInfo->width = $request->width;
            $dataInfo->video_link = $request->video_link;
            $dataInfo->description = $request->description;
            $dataInfo->min_qty = $request->min_qty;
            $dataInfo->max_qty = $request->max_qty;
            $dataInfo->quantity = array_sum($request->quantity);
            $dataInfo->has_warranty = $request->has_warranty ? 1 : 0;
            $dataInfo->warranty_type = $request->warranty_type;
            $dataInfo->warranty_period = $request->warranty_period;
            $dataInfo->slug = Str::slug($request->name) .'-'.uniqid();
            $dataInfo->sku = $request->sku;
   
            $dataInfo->published = 0; //$request->published;

            // $dataInfo->is_b_to_b=$request->is_b_to_b;
            $dataInfo->array_color=json_encode($request->color_id);
            $dataInfo->array_size=json_encode($request->size_attribute_id);
            $dataInfo->is_b_to_c = 1;//$request->is_b_to_c;
            $dataInfo->status = 1;
            $dataInfo->created_at = Carbon::now();

            if(isset($request->thumbnail_img) && $request->thumbnail_img!='')
            {

                $image = $request->thumbnail_img;

                 $imageName = $this->nameGenerate($image);

                if (!Storage::disk('public')->exists('products')) {
                    Storage::disk('public')->makeDirectory('products');
                }

                $note_img = Image::make($image)->stream();
                Storage::disk('public')->put('products/' . $imageName, $note_img);
                $path = "/storage/app/public/products/".$imageName;
                $dataInfo->thumbnail_img=$path;
            }
        // return response()->json($dataInfo->save());
            
            if($dataInfo->save()){
                $pro_img = new ProductImage();
                $pro_img->product_id =$dataInfo->id;

                $pro_img->alt_name= $request->name;
                $pro_img->status=1;
                if(isset($request->thumbnail_img) && $request->thumbnail_img!='')
             {
 
                 $image = $request->thumbnail_img;
 
                  $imageName = $this->nameGenerate($image);
 
                 if (!Storage::disk('public')->exists('products')) {
                     Storage::disk('public')->makeDirectory('products');
                 }
 
                 $note_img = Image::make($image)->stream();
                 Storage::disk('public')->put('products/' . $imageName, $note_img);
                 $path = "/storage/app/public/products/".$imageName;
                 $pro_img->product_image=$path;
             }

             $pro_img->save();
          
                if($this->saveProductImage($request,$sellerInfo,$dataInfo) && $this->storeStock($request,$sellerInfo,$dataInfo) && $this->storeDeliveryCharge($request,$sellerInfo,$dataInfo)){
                 
                    DB::commit();
                    $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>"Successfully Added Product.",
                        'errMsg'=>null,
                     ];

                    return response()->json($responseData,200);
                }
                else{

                    DB::rollBack();

                    $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Failed To Add Product.',
                     ];

                    return response()->json($responseData,200);
                }
            }
            else{
                
                DB::rollBack();

                $responseData=[
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>null,
                    'errMsg'=>'Failed To Add Product.',
                 ];

                return response()->json($responseData,200);
            }
      

        }
        catch(Exception $err){
           $responseData=[
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>null,
                    'errMsg'=>'Something Went Wrong.Please Try Again.',
                 ];

            return response()->json($responseData,200); 
        }
    }

    public function updateProduct(Request $request)
    {
       
            DB::beginTransaction();
            $sellerInfo=Seller::with('shopInfo')
            ->where('id',Auth::guard('seller-api')->user()->id)
                 ->first();
            $sellerInfo = null;
            $quantity=0;

            $dataInfo= Product::where('id',$request->dataId)->first();
            $dataInfo->category_id = $request->category_id;
            $dataInfo->subcategory_id = $request->subcategory_id;
            $dataInfo->sub_subcategory_id = $request->sub_subcategory_id;
            $dataInfo->name = $request->name;
            $dataInfo->added_by = 1;
            $dataInfo->staff_id = null;
            $dataInfo->height = $request->height;
            $dataInfo->weight = $request->weight;
            $dataInfo->length = $request->length;
            $dataInfo->width = $request->width;
            
             //$dataInfo->seller_id = isset($sellerInfo) ? $sellerInfo->id : null;
            // $dataInfo->shop_id = (isset($sellerInfo) && !is_null($sellerInfo->shopInfo)) ? $sellerInfo->shopInfo->id : null;
            $dataInfo->brand_id = $request->brand_id;
            $dataInfo->refundable = $request->refundable ? 1 : 0;
            $dataInfo->tags = $request->tags;
            $dataInfo->product_type =  0;
            $dataInfo->video_link = $request->video_link;
            $dataInfo->description = $request->description;
            $dataInfo->min_qty = $request->min_qty;
            $dataInfo->max_qty = $request->max_qty;
            $dataInfo->quantity = array_sum($request->quantity);
            $dataInfo->has_warranty = $request->has_warranty ? 1 : 0;
            $dataInfo->warranty_type = $request->warranty_type;
            $dataInfo->warranty_period = $request->warranty_period;
            $dataInfo->slug = Str::slug($request->name) .'-'.uniqid();
            $dataInfo->sku = $request->sku;
            // $dataInfo->has_discount = (isset($request->has_discount) && ($request->has_discount != 'false') && $request->has_discount != 'null') ? 1 : 0;
            // if ($dataInfo->has_discount) {
            //     $dataInfo->discount = $request->discount;
            //     $dataInfo->discount_start = $request->startDate;
            //     $dataInfo->discount_end = $request->endDate;
            
            // }
            $dataInfo->published = 0;
            $dataInfo->array_color=json_encode($request->color_id);
            $dataInfo->array_size=json_encode($request->size_attribute_id);
             //$request->published;

            // $dataInfo->is_b_to_b=$request->is_b_to_b;

            $dataInfo->is_b_to_c = 1;//$request->is_b_to_c;
            $dataInfo->status = 1;

            $dataInfo->created_at = Carbon::now();

            // if(isset($request->thumbnail_img) && $request->thumbnail_img!='')
            // {

            //     $image = $request->thumbnail_img;

            //      $imageName = $this->nameGenerate($image);

            //     if (!Storage::disk('public')->exists('products')) {
            //         Storage::disk('public')->makeDirectory('products');
            //     }

            //     $note_img = Image::make($image)->stream();
            //     Storage::disk('public')->put('products/' . $imageName, $note_img);
            //     $path = "/storage/app/public/products/".$imageName;
            //     $dataInfo->thumbnail_img=$path;
            // }
    
            
            if($dataInfo->save()){
             

                // $oldData = ProductImage::where('product_id', $dataInfo->id)->get();

                // foreach ($oldData as $data) {
                   
                //     $deleteImage = ProductImage::find($data->id);
                
                //     if ($deleteImage) {
                //         $deleteImage->delete();
                //     }
                // }


            //     $pro_img = new ProductImage();
            //     $pro_img->product_id =$dataInfo->id;

            //     $pro_img->alt_name= $request->name;
            //     $pro_img->status=1;
            //     if(isset($request->thumbnail_img) && $request->thumbnail_img!='')
            //  {
 
            //      $image = $request->thumbnail_img;
 
            //       $imageName = $this->nameGenerate($image);
 
            //      if (!Storage::disk('public')->exists('products')) {
            //          Storage::disk('public')->makeDirectory('products');
            //      }
 
            //      $note_img = Image::make($image)->stream();
            //      Storage::disk('public')->put('products/' . $imageName, $note_img);
            //      $path = "/storage/app/public/products/".$imageName;
            //      $pro_img->product_image=$path;
            //  }

            //  $pro_img->save();
               
           
                    if( $this->storeDeliveryCharge($request,$sellerInfo,$dataInfo)){
                    DB::commit();
                    $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>"Successfully Updated Product.",
                        'errMsg'=>null,
                     ];

                    return response()->json($responseData,200);
                }
                else{

                    DB::rollBack();

                    $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Failed To Update Product.',
                     ];

                    return response()->json($responseData,200);
                }
            }
            else{
                
                DB::rollBack();

                $responseData=[
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>null,
                    'errMsg'=>'Failed To Add Product.',
                 ];

                return response()->json($responseData,200);
            }
      
    }
    protected function nameGenerate($file)
    {
        $name = base64_encode(rand(10000, 99999) . time());
        $name = preg_replace('/[^A-Za-z0-9\-]/', '', $name);
        return strtolower($name) . '.' . explode('/', explode(':', substr($file, 0, strpos($file, ';')))[1])[1];
    }
    public function productImages($image, $count)
    {
        if (isset($image)) {
         
            $imageName = $this->nameGenerate($image);
            if (!Storage::disk('public')->exists('products')) {
                Storage::disk('public')->makeDirectory('products');
            }
            
            $note_img = Image::make($image)->stream();
            Storage::disk('public')->put('products/' . $imageName, $note_img);
            $path = "/storage/app/public/products/".$imageName;
            array_push($this->image_files, $path);
        }
    }
    public function saveProductImage($request,$sellerInfo,$productInfo)
    {

        foreach ($request->images as $i => $image) {

            $this->productImages($image, $i);
       
             $productimage = new ProductImage;
            
       
              $productimage->product_image =$this->image_files[$i];
              $productimage->product_id = $productInfo->id;
              $productimage->status = 1;
              $productimage->save();

      }

       return  true;
   
    }
   
  
    public function storeStock($request,$sellerInfo,$productInfo)
    {
        foreach ($request->quantity as $key => $quantity) {
            $stockInfo=new StockInfo();
            if(isset($sellerInfo) && !is_null($sellerInfo->shopInfo))  $stockInfo->shop_id= $sellerInfo->shopInfo->id;
            $stockInfo->product_id=$productInfo->id;
            if(isset($sellerInfo)) $stockInfo->seller_id = $sellerInfo->id;
            $stockInfo->size_id=$request->size_id[$key];
            $stockInfo->color_id=$request->color_id[$key];
            $stockInfo->size_attribute_id=$request->size_attribute_id[$key];
            $stockInfo->quantity=$request->quantity[$key];
            $stockInfo->sell_price=$request->sell_price[$key];
            if($request->special_price[$key]== 'NaN'){
                $stockInfo->startDate = NULL;
                $stockInfo->endDate = NULL;
              
                 $stockInfo->special_price=NULL;

            }else{
                $stockInfo->startDate = $request->startDate;
                $stockInfo->endDate = $request->endDate;
              
                 $stockInfo->special_price=$request->special_price[$key];

            }
          
         
        
     
            $stockInfo->total_purches_price=$request->quantity[$key]*$request->sell_price[$key];
            $stockInfo->status=1;
            $stockInfo->created_at=Carbon::now();
            $stockInfo->save();
 
            if($stockInfo->save()){
             Account::where('product_id',$productInfo->id)->delete();
             $totalQty=StockInfo::where('product_id',$productInfo->id)->sum('quantity');
             $totalCost=StockInfo::where('product_id',$productInfo->id)->sum('total_purches_price');
             $account=new Account();
             $account->product_id=$productInfo->id;
             $account->total_qty=$totalQty;
             $account->debit=$totalCost;
             $account->seller_id=1;
             $account->save();
 
             $stockId=StockInfo::where('product_id',$productInfo->id)->first();
             $updateProductInfo=Product::where('id',$productInfo->id)->first();
             $updateProductInfo->sell_price=$stockId->sell_price;
             $updateProductInfo->color_id=$stockId->color_id;
           
             $updateProductInfo->special_price=$stockId->special_price;
             $updateProductInfo->startDate = $stockId->startDate;
             $updateProductInfo->endDate = $stockId->endDate;
            
 
             $updateProductInfo->save();
            }
        }
       return true;
    }

    public function updateStock($request,$sellerInfo,$productInfo)
    {
        foreach ($request->quantity as $key => $quantity) {
            if($request->stockId[$key] == 'NaN'){
                $stockInfo=new StockInfo();
                if(isset($sellerInfo) && !is_null($sellerInfo->shopInfo))  $stockInfo->shop_id= $sellerInfo->shopInfo->id;
                $stockInfo->product_id=$request->dataId;
                if(isset($sellerInfo)) $stockInfo->seller_id = $sellerInfo->id;
                $stockInfo->size_id=$request->size_id[$key];
                $stockInfo->color_id=$request->color_id[$key];
                $stockInfo->size_attribute_id=$request->size_attribute_id[$key];
                $stockInfo->quantity=$request->quantity[$key];
                $stockInfo->sell_price=$request->sell_price[$key];
    
                if($request->special_price[$key]== 'NaN'){
                    $stockInfo->startDate = NULL;
                    $stockInfo->endDate = NULL;
                  
                     $stockInfo->special_price=NULL;
    
                }else{
                    $stockInfo->startDate = $request->startDate;
                    $stockInfo->endDate = $request->endDate;
                  
                     $stockInfo->special_price=$request->special_price[$key];
    
                }
            //    $stockInfo->startDate= $request->startDate[$key];
            //     $stockInfo->endDate=$request->endDate[$key];
            //    $stockInfo->special_price=$request->special_price[$key];
              
                $stockInfo->total_purches_price=$request->quantity[$key]*$request->sell_price[$key];
                $stockInfo->status=1;
                $stockInfo->created_at=Carbon::now();
                $stockInfo->save();

                $stockId=StockInfo::where('product_id',$productInfo->id)->first();
                $updateProductInfo=Product::where('id',$productInfo->id)->first();
                $updateProductInfo->sell_price=$stockId->sell_price;
               
                $updateProductInfo->color_id=$stockId->color_id;
                 $updateProductInfo->special_price=$stockId->special_price;
                 $updateProductInfo->startDate = $stockId->startDate;
                 $updateProductInfo->endDate = $stockId->endDate;
    
                $updateProductInfo->save();
    
            }else{
                $stockInfo=StockInfo::where('id',$request->stockId[$key])->first();
                $stockInfo->seller_id=1;
                $stockInfo->shop_id=1;
                $stockInfo->product_id=$productInfo->id;
                if(isset($sellerInfo)) $stockInfo->seller_id = $sellerInfo->id;
                $stockInfo->size_id=$request->size_id[$key];
                $stockInfo->color_id=$request->color_id[$key];
                $stockInfo->size_attribute_id=$request->size_attribute_id[$key];
                $stockInfo->quantity=$request->quantity[$key];
                $stockInfo->sell_price=$request->sell_price[$key];
                if($request->special_price[$key]== 'NaN'){
                    $stockInfo->startDate = NULL;
                    $stockInfo->endDate = NULL;
                  
                     $stockInfo->special_price=NULL;
    
                }else{
                    $stockInfo->startDate = $request->startDate;
                    $stockInfo->endDate = $request->endDate;
                  
                     $stockInfo->special_price=$request->special_price[$key];
    
                }
            //     $stockInfo->startDate= $request->startDate[$key];
            //     $stockInfo->endDate=$request->endDate[$key];
            //    $stockInfo->special_price=$request->special_price[$key];
              
                // $stockInfo->whole_sale_price=$request->whole_sale_price[$key];
                // $stockInfo->note=$request->note[$key];
                $stockInfo->total_purches_price=$request->quantity[$key]*$request->sell_price[$key];
                $stockInfo->status=1;
                $stockInfo->created_at=Carbon::now();
                $stockInfo->save();
     
                if($stockInfo->save()){
                 Account::where('product_id',$productInfo->id)->delete();
                 $totalQty=StockInfo::where('product_id',$productInfo->id)->sum('quantity');
                 $totalCost=StockInfo::where('product_id',$productInfo->id)->sum('total_purches_price');
                 $account=new Account();
                 $account->product_id=$productInfo->id;
                 $account->total_qty=$totalQty;
                 $account->debit=$totalCost;
                 $account->seller_id=1;
                 $account->save();
                 
                 $stockId=StockInfo::where('product_id',$productInfo->id)->first();
                 $updateProductInfo=Product::where('id',$productInfo->id)->first();
                 $updateProductInfo->sell_price=$stockId->sell_price;
                
                 $updateProductInfo->color_id=$stockId->color_id;
                $updateProductInfo->special_price=$stockId->special_price;
                $updateProductInfo->startDate = $stockId->startDate;
                 $updateProductInfo->endDate = $stockId->endDate;
     
                 $updateProductInfo->save();
                
     
                 
                }
    
            }
              
           }




        // array_sum($request->quantity)
       //StockInfo::where('product_id', $productInfo->id)->delete();
    //    foreach ($request->quantity as $key => $quantity) {
    //        $stockInfo=StockInfo::where('id',$request->stockId[$key])->first();
    //        $stockInfo->seller_id=1;
    //        $stockInfo->shop_id=1;
    //        $stockInfo->product_id=$productInfo->id;
    //        if(isset($sellerInfo)) $stockInfo->seller_id = $sellerInfo->id;
    //        $stockInfo->size_id=$request->size_id[$key];
    //        $stockInfo->color_id=$request->color_id[$key];
    //        $stockInfo->size_attribute_id=$request->size_attribute_id[$key];
    //        $stockInfo->quantity=$request->quantity[$key];
    //        $stockInfo->sell_price=$request->sell_price[$key];


    //        if($request->special_price[$key]== 'NaN'){
    //         $stockInfo->startDate = NULL;
    //         $stockInfo->endDate = NULL;
          
    //          $stockInfo->special_price=NULL;

    //     }else{
    //         $stockInfo->startDate = $request->startDate;
    //         $stockInfo->endDate = $request->endDate;
          
    //          $stockInfo->special_price=$request->special_price[$key];

    //     }
    //      $stockInfo->startDate= $request->startDate[$key];
    //        $stockInfo->endDate=$request->endDate[$key];
    //          $stockInfo->special_price=$request->special_price[$key];
        
    //        $stockInfo->whole_sale_price=$request->whole_sale_price[$key];
    //         $stockInfo->note=$request->note[$key];
    //       $stockInfo->total_purches_price=$request->quantity[$key]*$request->sell_price[$key];
    //        $stockInfo->status=1;
    //        $stockInfo->created_at=Carbon::now();
    //        $stockInfo->save();

    //        if($stockInfo->save()){
    //         Account::where('product_id',$productInfo->id)->delete();
    //         $totalQty=StockInfo::where('product_id',$productInfo->id)->sum('quantity');
    //         $totalCost=StockInfo::where('product_id',$productInfo->id)->sum('total_purches_price');
    //         $account=new Account();
    //         $account->product_id=$productInfo->id;
    //         $account->total_qty=$totalQty;
    //         $account->debit=$totalCost;
    //         $account->seller_id=1;
    //         $account->save();
            
    //         $stockId=StockInfo::where('product_id',$productInfo->id)->first();
    //         $updateProductInfo=Product::where('id',$productInfo->id)->first();
    //         $updateProductInfo->sell_price=$stockId->sell_price;
          
    //         $updateProductInfo->special_price=$stockId->special_price;
             

    //         $updateProductInfo->save();
           

            
    //        }
    //    }
       return true;
    }
    public function stockUpdateQuantity(Request $request)
    {
        // array_sum($request->quantity)
       //StockInfo::where('product_id', $productInfo->id)->delete();
       foreach ($request->newQuantity as $key => $newQuantity) {
           $stockInfo=StockInfo::where('id',$request->stockId[$key])->first();
           if($request->incDec[$key] =='yes'){
            $stockInfo->quantity=$stockInfo->quantity + $request->newQuantity[$key];
            $stockInfo->incDec=$request->incDec[$key];

           }elseif($request->incDec[$key] =='no'){
            $stockInfo->quantity=$stockInfo->quantity - $request->newQuantity[$key];
            $stockInfo->incDec=$request->incDec[$key];

           }else{
           

           }

       
           $stockInfo->created_at=Carbon::now();
           $stockInfo->save();

   

          
       }

       if($stockInfo) $responseData=[
        'errMsgFlag'=>false,
        'msgFlag'=>true,
        'msg'=> 'Product Price Update Successfully.',
        'errMsg'=> null,
    ];
else $responseData=[
        'errMsgFlag'=>true,
        'msgFlag'=>false,
        'msg'=>null,
        'errMsg'=>'Failed To Update Product Price.',
     ];
return response()->json($responseData,200);
      
    }

    public function stockUpdateQuantityPrice(Request $request)
    {
        // array_sum($request->quantity)
       //StockInfo::where('product_id', $productInfo->id)->delete();
       foreach ($request->stockId as $key => $stockId) {
           $stockInfo=StockInfo::where('id',$request->stockId[$key])->first();
         
            $stockInfo->startDate= $request->startDate[$key];
            $stockInfo->endDate=$request->endDate[$key];

       
            $stockInfo->sell_price= $request->sell_price[$key];
            $stockInfo->special_price=$request->special_price[$key];

            $stockInfo->created_at=Carbon::now();
            $stockInfo->save();

            $stockId=StockInfo::where('product_id',$request->dataId)->first();
            $updateProductInfo=Product::where('id',$request->dataId)->first();

            $updateProductInfo->sell_price=$stockId->sell_price;
            $updateProductInfo->save();
          

          
       }

       if($stockInfo) $responseData=[
        'errMsgFlag'=>false,
        'msgFlag'=>true,
        'msg'=> 'Product Price Update Successfully.',
        'errMsg'=> null,
    ];
else $responseData=[
        'errMsgFlag'=>true,
        'msgFlag'=>false,
        'msg'=>null,
        'errMsg'=>'Failed To Update Product Price.',
     ];
return response()->json($responseData,200);
      
    }
    public function storeDeliveryCharge($request,$sellerInfo,$productInfo)
    {
      
        DeliveryCharge::where('product_id', $productInfo->id)->delete();
       

            $deliveryCharge=new DeliveryCharge();

            $deliveryCharge->thana_id=1;

            $deliveryCharge->product_id=$productInfo->id;
           

                $deliveryCharge->interCityDeliveryCharge=60;

                $deliveryCharge->interCityDeliveryChargeMax=80; 

           

            $deliveryCharge->outCityDeliveryCharge=100;

           $deliveryCharge->outCityDeliveryChargeMax=120;
           

            $deliveryCharge->created_at=Carbon::now();

            $deliveryCharge->save();
            
 
        return true;
    }
  
  
    public function deleteProduct($id)
    {
        $deleteProduct = Product::find($id);
        $stock = StockInfo::where('product_id',$deleteProduct->id)->get();
       
            foreach($stock as $stPro){
             
                $stPro->delete();
            }

       
            $deleteProduct->delete();
        if($deleteProduct) $responseData=[
                'errMsgFlag'=>false,
                'msgFlag'=>true,
                'msg'=> 'Product Deleted Successfully.',
                'errMsg'=> null,
            ];
        else $responseData=[
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>null,
                'errMsg'=>'Failed To Delete Product.',
             ];
        return response()->json($responseData,200);
    }

    public function deleteProductQuantity($id)
    {
        $deleteProduct = StockInfo::find($id);
        // $deleteProduct->deleted_at=Carbon::now();
        $deleteProduct->delete();
        if($deleteProduct) $responseData=[
                'errMsgFlag'=>false,
                'msgFlag'=>true,
                'msg'=> 'Product Quantity Deleted Successfully.',
                'errMsg'=> null,
            ];
        else $responseData=[
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>null,
                'errMsg'=>'Failed To Delete Product Quantity.',
             ];
        return response()->json($responseData,200);
    }
    public function deleteAll(Request $request){

        DB::beginTransaction();

        try{
        $selectedProductsIds = json_decode($request->input('selectedProductsId'));
       
      $deleteAllProducts=  Product::whereIn('id', $selectedProductsIds)->update(['deleted_at' => Carbon::now(), 'published'=>0,]);


        if($deleteAllProducts)
        {
          
            DB::commit();

            $responseData=[
                    'errMsgFlag'=>false,
                    'msgFlag'=>true,
                    'msg'=>'Product Published  Successfully.',
                    'errMsg'=>null,
                ];
        }
        else
        {
           DB::rollBack();

           $responseData=[
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>null,
                    'errMsg'=>'Failed To Change Product Published.'
                ];
        }

    return response()->json($responseData,200);
}
catch(Exception $err)
{
    DB::rollBack();
    
    GeneralController::storeSystemErrorLog("Backends\BrandController@changeBrandStatus",$err);
    
    DB::commit();

    $responseData=[
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>null,
                'errMsg'=>'Something Went Wrong.Please Try Again.'
    ];

    return response()->json($responseData,200);
}
        
    }
    public function publishedAll(Request $request){

        DB::beginTransaction();

        try{
        $selectedProductsIds = json_decode($request->input('selectedProductsId'));
       
       $publishProduct= Product::whereIn('id', $selectedProductsIds)->update(['deleted_at' => NULL,'published'=>1]);


      
        
        if($publishProduct)
        {
          
            DB::commit();

            $responseData=[
                    'errMsgFlag'=>false,
                    'msgFlag'=>true,
                    'msg'=>'Product Published  Successfully.',
                    'errMsg'=>null,
                ];
        }
        else
        {
           DB::rollBack();

           $responseData=[
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>null,
                    'errMsg'=>'Failed To Change Product Published.'
                ];
        }

    return response()->json($responseData,200);
}
catch(Exception $err)
{
    DB::rollBack();
    
    GeneralController::storeSystemErrorLog("Backends\BrandController@changeBrandStatus",$err);
    
    DB::commit();

    $responseData=[
                'errMsgFlag'=>true,
                'msgFlag'=>false,
                'msg'=>null,
                'errMsg'=>'Something Went Wrong.Please Try Again.'
    ];

    return response()->json($responseData,200);
}
        
    }

    public function changeProductPublished(Request $request)
    {
    	DB::beginTransaction();

        try{
                $dataInfo=Product::find($request->dataId);

                $dataInfo->published=$request->published;

                $dataInfo->updated_at=Carbon::now();

                if($dataInfo->save())
                {
                    $dataId=$dataInfo->id;

                    $tableName='brands';

                    $userId=1;

                    $userType=1;

                    $dataType=2;

                    $comment=$dataInfo->id.'=>'.$dataInfo->title.' Brand Status Changed By ';
                    // $comment=$dataInfo->id.'=>'.$dataInfo->title.' Brand Status Changed By '.Auth::guard('staff-api')->user()->name;

                    GeneralController::storeSystemLog($tableName,$dataId,$comment,$userId,$userType,$dataType);

                    DB::commit();

                    $responseData=[
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>'Product Published Changed Successfully.',
                            'errMsg'=>null,
                        ];
                }
                else
                {
                   DB::rollBack();

                   $responseData=[
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>null,
                            'errMsg'=>'Failed To Change Product Published.'
                        ];
                }

            return response()->json($responseData,200);
        }
        catch(Exception $err)
        {
            DB::rollBack();
            
            GeneralController::storeSystemErrorLog("Seller\ProductController@changeProductPublished",$err);
            
            DB::commit();

            $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Something Went Wrong.Please Try Again.'
            ];

            return response()->json($responseData,200);
        }
    }

    public function changeProductStatus(Request $request)
    {
    	DB::beginTransaction();

        try{
                $dataInfo=Product::find($request->dataId);

                $dataInfo->status=$request->status;

                $dataInfo->updated_at=Carbon::now();

                if($dataInfo->save())
                {
                    $dataId=$dataInfo->id;

                    $tableName='brands';

                    $userId=1;

                    $userType=1;

                    $dataType=2;

                    $comment=$dataInfo->id.'=>'.$dataInfo->title.' Product Status Changed By ';
                    // $comment=$dataInfo->id.'=>'.$dataInfo->title.' Brand Status Changed By '.Auth::guard('staff-api')->user()->name;

                    GeneralController::storeSystemLog($tableName,$dataId,$comment,$userId,$userType,$dataType);

                    DB::commit();

                    $responseData=[
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>'Product Status Changed Successfully.',
                            'errMsg'=>null,
                        ];
                }
                else
                {
                   DB::rollBack();

                   $responseData=[
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>null,
                            'errMsg'=>'Failed To Change Product Status.'
                        ];
                }

            return response()->json($responseData,200);
        }
        catch(Exception $err)
        {
            DB::rollBack();
            
          
            DB::commit();

            $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Something Went Wrong.Please Try Again.'
            ];

            return response()->json($responseData,200);
        }
    }

    public function updateProductOffer(Request $request)
    {
        
    

            DB::beginTransaction();
            try{
    
                $dataInfo=Product::find($request->dataId);
    
                if(!empty($dataInfo)){
    
                    $dataInfo->discount=$request->discount;
                   $dataInfo->deal_type=$request->dealType;
                   $dataInfo->discount_type=$request->discount_type;
    
                    $dataInfo->updated_at=Carbon::now();

                    if($dataInfo->save())
                    {
                        $dataId=$dataInfo->id;
    
                        $tableName='products';
    
                        $userId=1;
    
                        $userType=1;
    
                        $dataType=2;
    
                        $comment='Product Updated By ';
                        // $comment=$dataInfo->id.'=>'.$dataInfo->title.' Banner Status Changed By '.Auth::guard('staff-api')->user()->name;
    
                        GeneralController::storeSystemLog($tableName,$dataId,$comment,$userId,$userType,$dataType);
                    
                        // GeneralController::storeEntryHistory($table,$dataId,$dataType,$userType,$userId,$optional);
    
                        DB::commit();
    
                        $responseData=[
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>'Successfully Update Discount.',
                                    'errMsg'=>null,
                            ];
                    }
                    else
                    {
                        DB::rollBack();
                        $responseData=[
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>null,
                                    'errMsg'=>'Failed To update Discount.Please Try Again.',
                            ];
                    }
                }
                else
                {
                    DB::rollBack();
    
                    $responseData=[
                                 'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>null,
                                'errMsg'=>'Requested Data Not Found.',
                            ];
    
                }
    
                return response()->json($responseData,200);
             }
             catch(Exception $err)
            {
                DB::rollBack();
                
                GeneralController::storeSystemErrorLog($err,"Backend\RightBannerController@updateRightSlider");
                
                DB::commit();
                
                $responseData=[
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>null,
                            'errMsg'=>'Something Went Wrong.Please Try Again.',
                    ];
    
                 return response()->json($responseData,200);
            }
        }
      
          

        public function addSize(Request $request)
        {
            DB::beginTransaction();
            try{
    
                $dataInfo=new Size();
    
                $dataInfo->size=$request->sizeLabel;
    
                $dataInfo->label=$request->sizeLabel;
                $dataInfo->seller_id=Auth::guard('seller-api')->user()->id;
    
                $dataInfo->status=1;
    
                $dataInfo->created_at=Carbon::now();
    
                if($dataInfo->save()){
    
                    DB::commit();
    
                    $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>'Size Added Successfully.',
                        'errMsg'=>null,
                     ];
    
                    return response()->json($responseData,200);
                }
                else{
    
                     DB::rollBack();
    
                    $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Failed To Add Size.Please Try Again.',
                     ];
    
                    return response()->json($responseData,200);
                }
    
            }
            catch(Exception $err){
    
                DB::rollBack();
    
                GeneralController::storeSystemErrorLog($err,"Frontend\SellerController@addSize");
    
                DB::commit();
    
                $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Something Went Wrong.Please Try Again.',
                     ];
    
                return response()->json($responseData,200);
            }
        }
        public function addColor(Request $request)
        {
            DB::beginTransaction();
            try{

                $title =$request->colorName;
         
                $exists = Color::where('color', $title)->count('id');
                if ($exists > 0) {
                    $responseData = [
                        'errMsgFlag' => true,
                        'msgFlag' => false,
                        'msg' => null,
                        'errMsg' => ' Color already Exists.Please Write Unique Name',
                    ];
                    return response()->json($responseData, 200);
        
                } else {
        
                    $dataInfo=new Color();
    
                    $dataInfo->color=$request->colorName;
        
                    $dataInfo->color_code=$request->colorCode;
                    $dataInfo->seller_id=Auth::guard('seller-api')->user()->id;
        
                    $dataInfo->status=1;
        
                    $dataInfo->created_at=Carbon::now();
        
                    if($dataInfo->save()){
        
                        DB::commit();
        
                        $responseData=[
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>'Color Added Successfully.',
                            'errMsg'=>null,
                         ];
        
                        return response()->json($responseData,200);
                    }
                    else{
        
                         DB::rollBack();
        
                        $responseData=[
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>null,
                            'errMsg'=>'Failed To Add Color.Please Try Again.',
                         ];
        
                        return response()->json($responseData,200);
                    }
        
                }
    
      
    
            }
            catch(Exception $err){
    
                DB::rollBack();
    
                GeneralController::storeSystemErrorLog($err,"Frontend\SellerController@addColor");
    
                DB::commit();
    
                $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Something Went Wrong.Please Try Again.',
                     ];
    
                return response()->json($responseData,200);
            }
        }
        public function checkUniqueName(Request $request)
        {
            $title = $request->input('colorName');
         
            $exists = Color::where('color', $title)->count('id');
            if ($exists > 0) {
                $responseData = [
                    'errMsgFlag' => true,
                    'msgFlag' => false,
                    'msg' => null,
                    'errMsg' => ' Color already Exists.Please Write Unique Name',
                ];
                return response()->json($responseData, 200);
    
            } else {
    
               
    
            }
            
        }
        public function getColorList(Request $request)
        {
           $dataList=Color::where('status',1)->where('seller_id',Auth::guard('seller-api')->user()->id)->orWhere('seller_id', NULL)->whereNull('deleted_at')->get();
            
            return response()->json($dataList,200); 
        }
        public function getSizeList(Request $request)
        {
            $dataList=Size::where('status',1)->where('seller_id',Auth::guard('seller-api')->user()->id)->orWhere('seller_id', NULL)->whereNull('deleted_at')->where('status',1)->get();
    
            return response()->json($dataList,200);
        }
      
        public function addBrand(Request $request)
        {
            
                 $dataInfo=new Brand();
    
                 if(isset($request->brandLogo) && !is_null($request->file('brandLogo')))
                 {      
                    
                     $image=$request->file('brandLogo');
                       
                         $imageName = $image->getClientOriginalName();
                            if (!Storage::disk('public')->exists('brands')) {
                                Storage::disk('public')->makeDirectory('brands');
                            }
                           
                           
                        Storage::disk('public')->put('brands/', $image);
                        
                        if(!is_null($imageName)){
                           
                            $path ='/storage/app/public/brands/'.$image->hashName();
    
                            $dataInfo->logo=$path;
                        }
                    }
    
                    $dataInfo->name=$request->name;
                    $dataInfo->seller_id=Auth::guard('seller-api')->user()->id;
                    // $dataInfo->name_bd=$request->name_bd;
    
                    $dataInfo->slug=$request->slug;
    
                    $dataInfo->status=1;
                  
                    // $banner->status=$request->status;
    
                    $dataInfo->created_at=Carbon::now();
                    
                    if($dataInfo->save())
                    {
                        
    
                        DB::commit();
    
                        $responseData=[
                                    'errMsgFlag'=>false,
                                    'msgFlag'=>true,
                                    'msg'=>'Successfully Added Brand.',
                                    'errMsg'=>null,
                            ];
                    }
                    else
                    {
                        DB::rollBack();
                        $responseData=[
                                    'errMsgFlag'=>true,
                                    'msgFlag'=>false,
                                    'msg'=>null,
                                    'errMsg'=>'Failed To Save Brand.Please Try Again.',
                            ];
                    }
                
    
                return response()->json($responseData,200);
             
        }
      
     
        public function getCategoryList(Request $request)
        {
            $dataList=Category::select('id','title','parent_id','status','look_type')
                                ->with(['subCategory'=>function($q) use($request){
                                            $q->select('id','title','parent_id','status','look_type')->where('status',1);
                                        },
                                        'subCategory.subCategory'=>function($q) use($request){
                                            $q->select('id','title','parent_id','status','look_type')->where('status',1);
                                        }
                                    ])->where('look_type',1)
                                            ->where('status',1)
                                                ->orderBy('title','asc')
                                                    ->get();
    
            return response()->json($dataList,200);
        }

        public function getRegisterBrandList(Request $request)
        {
            $dataInfo = SellerBrand::where('seller_id',Auth::guard('seller-api')->user()->id)->where('brand_id',$request->dataId)->where('approved',1)->first();

            if(!empty($dataInfo)) {
               $responseData=[
                     'errMsgFlag'=>false,
                     'msgFlag'=>true,
                     'errMsg'=>null,
                     'msg'=>null,
                     'dataInfo'=>$dataInfo
               ];  
            }
            else{
                 $responseData=[
                     'errMsgFlag'=>true,
                     'msgFlag'=>false,
                     'errMsg'=>'Requested Data Not Found.',
                     'msg'=>null,
                     'dataInfo'=>$dataInfo
               ];
            }
     
            return response()->json($responseData,200);
    
          
        }

        public function getShockingDealListPC(Request $request)
        {
            $dataList=ShockingDeal::inRandomOrder()
                            ->where('status',1)
                                ->whereNull('deleted_at')
                                    ->get();
    
            return response()->json($dataList,200);
        }
    
        public function getRightSliderListPC() {
            $dataList = RightBanner::where('status', 1)->get();
            return response()->json($dataList, 200);
        }
        public function getActiveSizeAttributeList(Request $request)
        {
            $query=SizeAttribute::whereNull('deleted_at');
    
            if(isset($request->variantSize) && $request->variantSize!='')
                $query->where('size_id',$request->variantSize);
    
            $dataList=$query->orderBy('id','desc')->get();
            
            return response()->json($dataList,200);
        }

}