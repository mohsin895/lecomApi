<?php

namespace App\Http\Controllers\Frontend;

use App\Events\OrderNotification;
use App\Events\SellerOrderNotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GeneralController;
use App\Http\Controllers\Frontend\CartRulesController;
use App\Library\SslCommerz\SslCommerzNotification;
use App\Mail\OrderShipped;
use App\Models\CartProductStockInfo;
use App\Models\CartShop;
use App\Models\OrderShop;
use App\Models\CartProduct;
use App\Models\OrderProduct;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use App\Models\ShippingCharge;
use App\Models\StockInfo;
use App\Models\SmsSetting;
use App\Models\Product;
use App\Models\Review;
use App\Models\CustomerAddress;
use App\Models\DeliveryCharge;
use App\Models\DeliveryRule;
use App\Models\Customer;
use App\Models\Thana;
use App\Models\VoucherDiscount;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderPayment;
use App\Models\ReviewImage;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\Account;
use Carbon\Carbon;
use App\Models\GeneralSetting;
use App\Models\Notification;
use Exception;
use Cart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public $image_files = array();


    public function orderPlaced(Request $request)
    {
       
       
            // $this->addToCartForCheck($request);

            $flag=false;

            $success=0;
            
            $fail=0;
            
            // $this->recheckCartBeforeOrder($request);
            $customerInfo=Auth::guard('customer-api')->user();

            $addressInfo=CustomerAddress::find($request->addressId);

            // $this->verifyPromoCode($request,$customerInfo);
            
            // $cartInfos=$this->cartInfos($request);

            // $totalItem=count($cartInfos);
            $customer= Customer::where('id',Auth::guard('customer-api')->user()->id)->first();
            $totalCartCheck=CartProductStockInfo::where('check_uncheck',1)->where('customer_id', Auth::guard('customer-api')->user()->id)->get();
            $totalItem=CartProductStockInfo::where('check_uncheck',1)->where('customer_id', Auth::guard('customer-api')->user()->id)->count();
            $tatalAmount =0;
            foreach($totalCartCheck as $total){
               
                $stock=StockInfo::where('id',$total->stock_info_id)->first();
           
                $totalStockAmount =($stock->startDate<=Carbon::now() && $stock->endDate >=Carbon::now()) ? ($stock->special_price*$total->quantity ):($stock->sell_price*$total->quantity);
                $tatalAmount += $totalStockAmount;
         
            }
            if($totalItem>0){

                $totalPrice=$tatalAmount;

                $productDiscount=$tatalAmount;

            }
            else{

                $totalPrice=0;

                 $productDiscount=0;
            }

           
            $orderInfo=new Order();

            $orderInfo->customer_id=Auth::guard('customer-api')->user()->id;

            $orderInfo->randomOrderCode='LTS'. "" .rand(10000000,19999999);
            $orderInfo->address_id=$request->addressId;

            $orderInfo->thana_id=$addressInfo->thana_id;

            $orderInfo->price=$totalPrice;
            $orderInfo->discount=$customer->discount_amount;

            $orderInfo->promo_discount=$productDiscount;

            $orderInfo->delivery_charge=$request->deliveryCharge;
            $orderInfo->delivery_type=$request->shippingCharge;

            $orderInfo->promo_id=$customer->promo_id;

            $orderInfo->is_bkash_paid=0;//($request->payMethod==3) ? 2:0;

            $orderInfo->is_online_paid=($request->payMethod==1) ? 2:0;

            $orderInfo->is_cash_on=0;

            $orderInfo->is_printed=0;

            $orderInfo->is_address_printed=0;

            $orderInfo->is_delivered=0;

            $orderInfo->is_cancelled=0;

            $orderInfo->is_proccessing=0;

            $orderInfo->is_packing=0;
            
            $orderInfo->is_shipping=0;
            
            $orderInfo->placed_by=1;

            $orderInfo->status=1;

            $orderInfo->created_at=Carbon::now();

            // $orderInfo->updated_at=Carbon::now();

            if($orderInfo->save()){
                $shop=CartShop::where('checkout_check_uncheck','>',0)->where('customer_id', Auth::guard('customer-api')->user()->id)->get();
                foreach($shop as $shopData){
                    $orderShop = new OrderShop();
                    $orderShop->seller_id= $shopData->seller_id;
                    $orderShop->customer_id= $shopData->customer_id;
                    $orderShop->order_id= $orderInfo->id;
                    $orderShop->promo_id=$shopData->promo_id;
                    $orderShop->discount_amount=$shopData->discount_amount;
                    $orderShop->save();
                    if($orderShop->save()){
                        $cartProduct=CartProduct::where('cart_shop_id',$shopData->id)->get();
                        foreach($cartProduct as $pro){
                            $orderProduct =new OrderProduct();
                            $orderProduct->product_id=$pro->product_id;
                            $orderProduct->order_shop_id=$orderShop->id;
                            $orderProduct->order_id=$orderInfo->id;
                            $orderProduct->customer_id=$pro->customer_id;
                            $orderProduct->seller_id=$pro->seller_id;
                            $orderProduct->save();
                        }
                       
                    }


                  
                   
                }

                $this->storeNotification($orderInfo);
               
                foreach($totalCartCheck as  $cartInfo) {
                   
                    $flag=$this->storeOrderDetails($cartInfo,$orderInfo) ;
                   
                    if($flag)
                        $success++;
                    else
                        $fail++;
                }


                $paymentFlag=$this->storePaymentInfo($orderInfo);

                if($paymentFlag){

                    // session()->put('invoiceDiscount',0);

                    // session()->put('promoDiscount',0);

                    // session()->put('promoCode',null);

                    DB::commit();

                  

                    $userId='Customer-'.$request->isPreOrder;
                    
                    // Cart::session($userId)->clear();

                    $responseData=[
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'errMsg'=>null,
                              
                                'isOrderPlaced'=>true,
                                'isOnlinePayment'=>($request->payMethod==1) ? true:false,
                                'isBkashPayment'=>($request->payMethod==3) ? true:false,
                                'isCashPayment'=>($request->payMethod==2) ? true:false,
                                'orderId'=>$orderInfo->id,
                            ];

                    return response()->json($responseData,200);
                }
                else{

                     DB::rollBack();

                   $responseData=[
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>null,
                                'errMsg'=>'Failed To Order Placed.Please Try Again.'
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
                            'errMsg'=>'Failed To Order Placed.Please Try Again.'
                            ];

                return response()->json($responseData,200); 
            }
            
       
           
        
    }
  
    public function storeOrderDetails($cartInfo,$orderInfo)
    {
        // return response()->json($cartInfo);
        $stock=StockInfo::where('id',$cartInfo->stock_info_id)->first();
        $orderShop=OrderShop::where('seller_id',$cartInfo->seller_id)->where('order_id',$orderInfo->id)->first();
        $orderproduct=OrderProduct::where('product_id',$stock->product_id)->where('order_id',$orderInfo->id)->first();
        $totalStockAmount =($stock->startDate<=Carbon::now() && $stock->endDate >=Carbon::now()) ? $stock->special_price : $stock->sell_price;

        $product=Product::where('id',$stock->product_id)->first();
        $categoryId=Category::where('id',$product->category_id)->first();
        $subcategoryId=Category::where('id',$product->subcategory_id )->first();
        $subsubcategoryId=Category::where('id',$product->sub_subcategory_id)->first();

      
        if(!empty($subsubcategoryId)){
            $commission=($totalStockAmount*$cartInfo->quantity*$subsubcategoryId->commission)/100;

        }elseif($subcategoryId){
            $commission=($totalStockAmount*$cartInfo->quantity*$subcategoryId->commission)/100;
        }elseif($categoryId){
            $commission=($totalStockAmount*$cartInfo->quantity*$categoryId->commission)/100;

        }else{
            $commission=0;
        }


        $addressInfo=CustomerAddress::find($orderInfo->address_id);
        $extraShippingCharge=ShippingCharge::first();
 
        if (!empty($addressInfo)) {
           
            if(!empty($product))
              
            
                    $deliveryChargeInfo=DeliveryCharge::where('product_id',$product['id'])->first();
                    if(!empty($deliveryChargeInfo)){
                        if($deliveryChargeInfo->max_quantity<$cartInfo['quantity']){
                            if($orderInfo->delivery_type== 1){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge);
                                
                            }elseif($orderInfo->delivery_type== 2){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge);

                            }elseif($orderInfo->delivery_type== 3){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->argentOutsideDhaka);

                            }elseif($orderInfo->delivery_type== 4){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->argentInsideDhaka);

                            }elseif($orderInfo->delivery_type== 5){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->veryArgentOutsideDhaka);

                            }elseif($orderInfo->delivery_type== 6){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->veryArgentInsideDhaka) ;

                            }else{
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                                $deliveryChargeSingleItem=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge);

                            }
                            
                        }
                        else{
                            if($orderInfo->delivery_type== 1){
                                $deliveryChargeSingleItem=$deliveryChargeInfo->outCityDeliveryCharge ;
                                
                            }elseif($orderInfo->delivery_type== 2){
                                $deliveryChargeSingleItem=$deliveryChargeInfo->interCityDeliveryCharge;

                            }elseif($orderInfo->delivery_type== 3){
                                $deliveryChargeSingleItem=$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->argentOutsideDhaka;

                            }elseif($orderInfo->delivery_type== 4){
                                $deliveryChargeSingleItem=$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->argentInsideDhaka;

                            }elseif($orderInfo->delivery_type== 5){
                                $deliveryChargeSingleItem=$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->veryArgentOutsideDhaka;

                            }elseif($orderInfo->delivery_type== 6){
                                $deliveryChargeSingleItem=$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->veryArgentInsideDhaka;

                            }
                            
                            else{
                                $deliveryChargeSingleItem=$deliveryChargeInfo->outCityDeliveryCharge;

                            }
                        }
                    }
                
         
        }
        else{
           
            if(!empty($product))
              
             
                    $deliveryChargeInfo=DeliveryCharge::where('product_id',$product['id'])->first();
                    if(!empty($deliveryChargeInfo)){
                        if($deliveryChargeInfo->max_quantity<$cartInfo['quantity']){
                            if($orderInfo->delivery_type== 1){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem =($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge);
                                
                            }elseif($orderInfo->delivery_type== 2){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem =($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge );

                            }elseif($orderInfo->delivery_type== 3){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem =($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->argentOutsideDhaka);

                            }elseif($orderInfo->delivery_type== 4){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem =($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->argentInsideDhaka);

                            }elseif($orderInfo->delivery_type== 5){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem =($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->veryArgentOutsideDhaka);

                            }elseif($orderInfo->delivery_type== 6){
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryChargeSingleItem =($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->veryArgentInsideDhaka);

                            }else{
                                $extraCharge=(int)($cartInfo['quantity']-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                                $deliveryChargeSingleItem =($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge);

                            }
                        }
                        else{

                            if($orderInfo->delivery_type== 1){
                                $deliveryChargeSingleItem =$deliveryChargeInfo->outCityDeliveryCharge;
                                
                            }elseif($orderInfo->delivery_type== 2){
                                $deliveryChargeSingleItem =$deliveryChargeInfo->interCityDeliveryCharge;

                            }elseif($orderInfo->delivery_type== 3){
                                $deliveryChargeSingleItem =$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->argentOutsideDhaka;

                            }elseif($orderInfo->delivery_type== 4){
                                $deliveryChargeSingleItem =$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->argentInsideDhaka;

                            }elseif($orderInfo->delivery_type== 5){
                                $deliveryChargeSingleItem =$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->veryArgentOutsideDhaka;

                            }elseif($orderInfo->delivery_type== 6){
                                $deliveryChargeSingleItem =$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->veryArgentInsideDhaka;

                            }else{
                                $deliveryChargeSingleItem =$deliveryChargeInfo->outCityDeliveryCharge;

                            }
                            
                        }
                    }
                
          
        }


        $orderDetails=new OrderItem();

        $orderDetails->order_id=$orderInfo->id;
        $orderDetails->randomItemId= rand(10000000,19999999);
        $orderDetails->seller_id=$product['seller_id'];
        $orderDetails->shop_id=$product['shop_id'];

        $orderDetails->stock_id=$stock->id;
        $orderDetails->order_shop_id=$orderShop->id;
        $orderDetails->order_product_id =$orderproduct->id;  
        $orderDetails->product_id=$stock->product_id;

        $orderDetails->quantity=$cartInfo['quantity'];

       $orderDetails->unitPrice=$stock->sell_price;

        $orderDetails->sell_rate=$totalStockAmount;

        $orderDetails->sell_price=$totalStockAmount*$cartInfo['quantity'];

        $orderDetails->discount=0;

        $orderDetails->commission=$commission;
        $orderDetails->delivery_charge=$deliveryChargeSingleItem;

        $orderDetails->is_free=0;

        $orderDetails->status=1;

         $orderDetails->created_at=Carbon::now();

        // $orderDetails->updated_at=Carbon::now();
        
        if($orderDetails->save()){
            $this->sellerNotification($orderDetails);
            // $this->deleteCartStockInfo($orderDetails);
            $stockInfo= StockInfo::where('id',$orderDetails->stock_id)->first();
            $stockInfo->orderItem=$orderDetails->quantity + $stockInfo->orderItem;
            $stockInfo->save();

           

            $account=new Account();
            $account->total_order_qty=$cartInfo['quantity'];
            $account->order_id=$orderInfo->id;
            $account->credit=(($totalStockAmount*$cartInfo['quantity'])+$orderInfo->delivery_charge) ;
            $account->save();
             
            if($this->reductProductQuantity($cartInfo) )
                return true;
            else
                return false;

            //     if( )
            //     return true;
            // else
            //     return false;
            
        }   
        else{
            return false;
        }
    }
   
   public function storeNotification($orderInfo){
    $notify = new Notification();
    $notify->order_id = $orderInfo->id;
    $notify->staff_views=1;  
    $notify->staff_views_all=1;  
    $notify->seller_views=1;  
    $notify->seller_views_all=1;  
    $notify->save();
    if($notify->save()){
        $notification=Notification::where('seller_views_all',1)->count();
      
        broadcast(new OrderNotification($notification));
      
    }

   }
   public function sellerNotification($orderDetails){
    $notify = new Notification();
    $notify->seller_id = $orderDetails->seller_id;
    $notify->order_id = $orderDetails->id;
    $notify->seller_views=1;  
    $notify->seller_views_all=1;  
    $notify->save();
    if($notify->save()){
      
        $sellerNotification=Notification::where('seller_views_all',1)->where('seller_id',$orderDetails->seller_id)->count();
     
        event((new SellerOrderNotificationEvent($sellerNotification)));
    }

   }
  
    public function orderConfirmationMessage($orderId)
    {
        $orderInfo=Order::with('customerAddress')
                            ->where('id',$orderId)
                                ->first();

           // dd($orderInfo);
            if(!empty($orderInfo)){

                $gs=GeneralSetting::first();
                $email = 'mohsinsikder999@gmail.com';
   
                $order_id=1;  
                     
                $subject='Loyel.com.bd';
                $messageData=[
                  'email' =>$email,
                  
                  'order_id'=>$order_id,
                  'gs'=>$gs,
                 
                ];
                Mail::send('email.order',$messageData,function($message) use($email,$subject){
                  $message->to($email)->subject('order Placed from' .' '.$subject);
                });
              
               $totalBill=(($orderInfo->price + $orderInfo->delivery_charge)-($orderInfo->invoice_discount+$orderInfo->promo_discount+$orderInfo->discount));
               
               $phone=(!is_null($orderInfo->customerAddress)) ? $orderInfo->customerAddress->phone:'01612423280';

               $phone=GeneralController::phoneNumberPrefix($phone);
               
               $message="আপনার অর্ডার ID:".$orderInfo->id." এবং বিলঃ BDT ".(($orderInfo->price+$orderInfo->deliveryCharge)-($orderInfo->promoDiscount+$orderInfo->discount+$orderInfo->invoiceDiscount));

                    GeneralController::sendSMS($phone,$message,1);

               return true;
            }
            else
                return false;

    }
    public function sentOrderSms(Request $request)
    {
        try{
            
            $flag=$this->orderConfirmationMessage($request->dataId);

            if($flag){

                $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>"A Confirmation Message Has Been Sent To Your Phone Number.",
                        'errMsg'=>null,
                    ];

                return response()->json($responseData,200);   
            }
            else{
                
                $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Order Information Not Found.',
                    ];

                return response()->json($responseData,200);   
            }
        }
        catch(Exception $err){
           
            $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Failed To Order Placed.Please Try Again.'
                    ];

           return response()->json($responseData,200); 
        }
    }
  
   
    public function getPromoDiscount(Request $request)
    {
        

       $promoDiscountInfo=VoucherDiscount::where('promo_code',trim($request->promoCode))
                        ->where('status',1)
                            ->where('available','>',0)
                                ->whereDate('startAt','<=',Carbon::today())
                                    ->whereDate('endAt','>=',Carbon::today())
                                        
                                            ->first();

          if(!empty($promoDiscountInfo)){
            if($promoDiscountInfo->staff_id == null){
                $shop=CartShop::where('seller_id',$promoDiscountInfo->seller_id)->where('customer_id',Auth::guard('customer-api')->user()->id)->first();

                $cartProductInfo =CartProductStockInfo::where('cart_shop_id',$shop->id)->where('check_uncheck',1)->get();
                $totalPrice =0;
                foreach($cartProductInfo as $cart){
                    $stock = StockInfo::where('id',$cart->stock_info_id)->first();
                    $totalStockAmount =($stock->startDate<=Carbon::now() && $stock->endDate >=Carbon::now()) ? ($stock->special_price*$cart->quantity ):($stock->sell_price*$cart->quantity);
                    $totalPrice +=$totalStockAmount;

                }
                if($totalPrice >=$promoDiscountInfo->min_order_value ){
                    if($promoDiscountInfo->isdiscount_in_percent ==1){
                        $shop->discount_amount = (($promoDiscountInfo->discount_amount *$totalPrice)/100);
                    }else{
                       $shop->discount_amount=$promoDiscountInfo->discount_amount;
                    }
                    $shop->promo_id =$promoDiscountInfo->id;
                    $shop->save();
                    $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>'Coupon Code Applyed Successfully.',
                        'errMsg'=>null,
                    ];

              return response()->json($responseData,200);
                }else{


                    
                    $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'This vouscher code is not required.',

                       
                 ];

             return response()->json($responseData,200);
                }
               
            }else{

                $Customer=Customer::where('id',Auth::guard('customer-api')->user()->id)->first();

                $cartProductInfo =CartProductStockInfo::where('customer_id',$Customer->id)->where('check_uncheck',1)->get();
                $totalPrice =0;
                foreach($cartProductInfo as $cart){
                    $stock = StockInfo::where('id',$cart->stock_info_id)->first();
                    $totalStockAmount =($stock->startDate<=Carbon::now() && $stock->endDate >=Carbon::now()) ? ($stock->special_price*$cart->quantity ):($stock->sell_price*$cart->quantity);
                    $totalPrice +=$totalStockAmount;

                }
                if($totalPrice >=$promoDiscountInfo->min_order_value ){
                    if($promoDiscountInfo->isdiscount_in_percent ==1){
                        $Customer->discount_amount = (($promoDiscountInfo->discount_amount *$totalPrice)/100);
                    }else{
                       $Customer->discount_amount=$promoDiscountInfo->discount_amount;
                    }
                    $Customer->promo_id =$promoDiscountInfo->id;
                    $Customer->save();
                    $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>'Coupon Code Applyed Successfully.',
                        'errMsg'=>null,
                    ];

              return response()->json($responseData,200);
                }else{


                    
                    $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'This vouscher code is not required.',

                       
                 ];

             return response()->json($responseData,200);
                }


            }

          }

    

       $responseData=[
                    'errMsgFlag'=>true,
                    'msgFlag'=>false,
                    'msg'=>null,
                    'errMsg'=>'This vouscher code is not required.',

                       
                 ];

        return response()->json($responseData,200);
    }
 
    public function reductProductQuantity($cartInfo)
    {
        $productQuantityInfo=StockInfo::find($cartInfo->stock_info_id);

        $productQuantityInfo->quantity=$productQuantityInfo->quantity-$cartInfo['quantity'];

        $productQuantityInfo->updated_at=Carbon::now();

        if($productQuantityInfo->save())
            return true;
        else
            return false;
    }

    public function deleteCartStockInfo($cartInfo)
    {
        $productQuantityInfo=CartProductStockInfo::where('check_uncheck',1)->where('customer_id', Auth::guard('customer-api')->user()->id)->get();


        $productQuantityInfo->delete();

        if($productQuantityInfo->delete())
            return true;
        else
            return false;
    }
 
    public function getDeliveryCharge(Request $request)
    {
        $addressInfo=CustomerAddress::find($request->addressId);
        $extraShippingCharge=ShippingCharge::first();
        if (!empty($addressInfo)) {
            $deliveryCharge=0;
            if(isset($request->productId))
                $array=$request->productId;
              else
                $array=[];
              foreach ($array as $key => $value) {
                    $productId=$request->productId[$key];
                    $deliveryChargeInfo=DeliveryCharge::where('product_id',$productId)->first();
                    if(!empty($deliveryChargeInfo)){
                        if($deliveryChargeInfo->max_quantity<$request->quantity[$key]){
                            if($request->shippingCharge == 1){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge);
                                
                            }elseif($request->shippingCharge == 2){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge);

                            }elseif($request->shippingCharge == 3){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->argentOutsideDhaka);

                            }elseif($request->shippingCharge == 4){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->argentInsideDhaka);

                            }elseif($request->shippingCharge == 5){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->veryArgentOutsideDhaka);

                            }elseif($request->shippingCharge == 6){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->veryArgentInsideDhaka) ;

                            }else{
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                                $deliveryCharge+=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge);

                            }
                            
                        }
                        else{
                            if($request->shippingCharge == 1){
                                $deliveryCharge+=$deliveryChargeInfo->outCityDeliveryCharge ;
                                
                            }elseif($request->shippingCharge == 2){
                                $deliveryCharge+=$deliveryChargeInfo->interCityDeliveryCharge;

                            }elseif($request->shippingCharge == 3){
                                $deliveryCharge+=$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->argentOutsideDhaka;

                            }elseif($request->shippingCharge == 4){
                                $deliveryCharge+=$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->argentInsideDhaka;

                            }elseif($request->shippingCharge == 5){
                                $deliveryCharge+=$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->veryArgentOutsideDhaka;

                            }elseif($request->shippingCharge == 6){
                                $deliveryCharge+=$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->veryArgentInsideDhaka;

                            }
                            
                            else{
                                $deliveryCharge+=$deliveryChargeInfo->outCityDeliveryCharge;

                            }
                        }
                    }
                }
            $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>null,
                        'errMsg'=>null,
                        'deliveryCharge'=>$deliveryCharge,
                    ];
        }
        else{
            $deliveryCharge=0;
            if(isset($request->productId))
                $array=$request->productId;
              else
                $array=[];
              foreach ($array as $key => $value) {
                    $productId=$request->productId[$key];
                    $deliveryChargeInfo=DeliveryCharge::where('product_id',$productId)->first();
                    if(!empty($deliveryChargeInfo)){
                        if($deliveryChargeInfo->max_quantity<$request->quantity[$key]){
                            if($request->shippingCharge == 1){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge);
                                
                            }elseif($request->shippingCharge == 2){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge );

                            }elseif($request->shippingCharge == 3){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->argentOutsideDhaka);

                            }elseif($request->shippingCharge == 4){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->argentInsideDhaka);

                            }elseif($request->shippingCharge == 5){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->veryArgentOutsideDhaka);

                            }elseif($request->shippingCharge == 6){
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->interCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                            $deliveryCharge+=($extraCharge+$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->veryArgentInsideDhaka);

                            }else{
                                $extraCharge=(int)($request->quantity[$key]-$deliveryChargeInfo->max_quantity)*($deliveryChargeInfo->outCityDeliveryCharge/$deliveryChargeInfo->max_quantity);
                                $deliveryCharge+=($extraCharge+$deliveryChargeInfo->outCityDeliveryCharge);

                            }
                        }
                        else{

                            if($request->shippingCharge == 1){
                                $deliveryCharge+=$deliveryChargeInfo->outCityDeliveryCharge;
                                
                            }elseif($request->shippingCharge == 2){
                                $deliveryCharge+=$deliveryChargeInfo->interCityDeliveryCharge;

                            }elseif($request->shippingCharge == 3){
                                $deliveryCharge+=$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->argentOutsideDhaka;

                            }elseif($request->shippingCharge == 4){
                                $deliveryCharge+=$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->argentInsideDhaka;

                            }elseif($request->shippingCharge == 5){
                                $deliveryCharge+=$deliveryChargeInfo->outCityDeliveryCharge + $extraShippingCharge->veryArgentOutsideDhaka;

                            }elseif($request->shippingCharge == 6){
                                $deliveryCharge+=$deliveryChargeInfo->interCityDeliveryCharge + $extraShippingCharge->veryArgentInsideDhaka;

                            }else{
                                $deliveryCharge+=$deliveryChargeInfo->outCityDeliveryCharge;

                            }
                            
                        }
                    }
                }
            $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>null,
                        'errMsg'=>null,
                        'deliveryCharge'=>$deliveryCharge,
                    ];
        }

        return response()->json($responseData,200);
    }



    public function getOrderTrackInfo(Request $request)
    {

       $dataInfo=OrderStatus::where('item_id',$request->dataId)->orderBy('id','desc')->first();



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
    public function getOrderTrackStatus(Request $request)
    {

       $dataInfo=OrderItem::with('orderItemStatus')->where('id',$request->dataId)->first();



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

    public function productReview(Request $request)
	{

		

			$dataInfo=OrderItem::where('id',$request->dataId)->first();

            if(empty(!$dataInfo)){

             $reviewId = Review::where('order_item_id',$request->dataId)->first();
             if(empty($reviewId)){
                $customerId=Customer::find(Auth::guard('customer-api')->user()->id);
                $itemId=OrderItem::where('id',$request->dataId)->first();
                // $productId=Product::where('id',$itemId->product_id)->first();
    
                $dataInfoReview=new Review();
                $dataInfoReview->product_id = $itemId->product_id;
                $dataInfoReview->seller_id = $itemId->seller_id;
                $dataInfoReview->shop_id = $itemId->shop_id;
                $dataInfoReview->order_item_id = $request->dataId;
                $dataInfoReview->stock_info_id = $itemId->stock_id;
                $dataInfoReview->customer_id = isset($customerId) ? $customerId->id : null;
                $dataInfoReview->rating = $request->rating;
                $dataInfoReview->description = $request->description;
                $dataInfoReview->seller_rating = $request->sellerRating;
                $dataInfoReview->seller_description = $request->sellerDescription;
                $dataInfoReview->rider_rating = $request->riderRating;
                $dataInfoReview->rider_description = $request->riderDescription;
				
				$dataInfoReview->save();

                if($dataInfoReview->save())
                {

                    
                    if($this->saveProductImage($request,$dataInfoReview) ){
                        DB::commit();
                        $responseData=[
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>"Successfully Add Review.",
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
                            'errMsg'=>'Failed To Add Review.',
                         ];
    
                        return response()->json($responseData,200);
                    }
                    // $images = $request->file('pro_imgs');
                    // foreach($images as $image) {
                    //     $productimage = new ReviewImage;
                    //     $image_tmp = Image::make($image);
           
                    //     $extension = $image->getClientOriginalExtension();
                    //     $imageName =  Str::slug($request['rating']).'-'.rand(111, 99999).time().".".$extension;
                    //     if (!Storage::disk('public')->exists('review')) {
                    //         Storage::disk('public')->makeDirectory('review');
                    //     }
                    //     $note_img = Image::make($image)->stream();
                    //             Storage::disk('public')->put('review/' . $imageName, $note_img);
                    //             $path = "/storage/app/public/review/".$imageName;
                         
                    //          $productimage->image = $path;
                    //           $productimage->review_id = $dataInfoReview->id;
                    //           $productimage->save();
                     
                    // }
                  
                        
                }
                else
                {
                    DB::rollBack();
                    $responseData=[
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>null,
                                'errMsg'=>'Failed To Save Product.Please Try Again.',
                        ];
                }

             }else{
                $customerId=Customer::find(Auth::guard('customer-api')->user()->id);
                $itemId=OrderItem::where('id',$request->dataId)->first();
                $productId=Product::where('id',$itemId->product_id)->first();
                $dataInfoReview=Review::where('order_item_id',$request->dataId)->first();
                $dataInfoReview->order_item_id = $request->dataId;
                $dataInfoReview->seller_id = $itemId->seller_id;
                $dataInfoReview->shop_id = $itemId->shop_id;
                $dataInfoReview->product_id = $productId->id;
                $dataInfoReview->customer_id = isset($customerId) ? $customerId->id : null;
                $dataInfoReview->rating = $request->rating;
                $dataInfoReview->description = $request->description;
                $dataInfoReview->seller_rating = $request->sellerRating;
                $dataInfoReview->seller_description = $request->sellerDescription;
                $dataInfoReview->rider_rating = $request->riderRating;
                $dataInfoReview->rider_description = $request->riderDescription;
              
				
				$dataInfoReview->save();

                if($dataInfoReview->save())
                {
                    if($this->saveProductImage($request,$dataInfoReview) ){
                        DB::commit();
                        $responseData=[
                            'errMsgFlag'=>false,
                            'msgFlag'=>true,
                            'msg'=>"Successfully Add Review.",
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
                            'errMsg'=>'Failed To Add Review.',
                         ];
    
                        return response()->json($responseData,200);
                    }
                    // $images = $request->file('pro_imgs');
                    // foreach($images as $image) {
                    //     $productimage = new ReviewImage;
                    //     $image_tmp = Image::make($image);
           
                    //     $extension = $image->getClientOriginalExtension();
                    //     $imageName =  Str::slug($request['rating']).'-'.rand(111, 99999).time().".".$extension;
                    //     if (!Storage::disk('public')->exists('review')) {
                    //         Storage::disk('public')->makeDirectory('review');
                    //     }
                    //     $note_img = Image::make($image)->stream();
                    //             Storage::disk('public')->put('review/' . $imageName, $note_img);
                    //             $path = "/storage/app/public/review/".$imageName;
                          
                    //          $productimage->image = $path;
                    //           $productimage->review_id = $dataInfoReview->id;
                    //           $productimage->save();
                    
                    // }
                  
              
                 
                    // DB::commit();

                    // $responseData=[
                    //             'errMsgFlag'=>false,
                    //             'msgFlag'=>true,
                    //             'msg'=>'Successfully Update Review.',
                    //             'errMsg'=>null,
                    //     ];
                }
                else
                {
                    DB::rollBack();
                    $responseData=[
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>null,
                                'errMsg'=>'Failed To Save Product.Please Try Again.',
                        ];
                }

             }
                
            }
            else
            {
                DB::rollBack();

                $responseData=[
                             'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>null,
                            'errMsg'=>'Review already exists in this Order.',
                        ];

            }

            return response()->json($responseData,200);
        
	
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
            if (!Storage::disk('public')->exists('review')) {
                Storage::disk('public')->makeDirectory('review');
            }
            
            $note_img = Image::make($image)->stream();
            Storage::disk('public')->put('review/' . $imageName, $note_img);
            $path = "/storage/app/public/review/".$imageName;
            array_push($this->image_files, $path);
        }
    }
    public function saveProductImage($request,$dataInfoReview)
    {

        foreach ($request->images as $i => $image) {

            $this->productImages($image, $i);
       
             $productimage = new ReviewImage;
            
       
              $productimage->image =$this->image_files[$i];
              $productimage->review_id = $dataInfoReview->id;
            
              $productimage->save();

      }

       return  true;
   
    }
   
    public function getReviewInfo(Request $request)
    {
        $dataInfo=Review::with('images')->where('order_item_id',$request->dataId)->first();
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

    public function getCustomerReviewInfo(Request $request)
    {
        $dataList=Review::with('images','productInfo','itemInfo','stockInfo','stockInfo.colorInfo','stockInfo.sizeInfo','stockInfo.sizeVariantInfo')->where('customer_id',Auth::guard('customer-api')->user()->id)->get();
        if(!empty($dataList)) {
            $responseData=[
                  'errMsgFlag'=>false,
                  'msgFlag'=>true,
                  'errMsg'=>null,
                  'msg'=>null,
                  'dataList'=>$dataList
            ];  
         }
         else{
              $responseData=[
                  'errMsgFlag'=>true,
                  'msgFlag'=>false,
                  'errMsg'=>'Requested Data Not Found.',
                  'msg'=>null,
                  'dataList'=>$dataList
            ];
         }
  
         return response()->json($responseData,200);
    }



    public function orderPlacedCod(Request $request)
    {
       DB::beginTransaction();
       try{

            $orderInfo= Order::where('id',$request->dataId)->first();

            $orderInfo->customer_id=Auth::guard('customer-api')->user()->id;

            $orderInfo->is_cash_on=1;

         
            $orderInfo->created_at=Carbon::now();

     

            if($orderInfo->save()){

                    DB::commit();


                        $gs=GeneralSetting::first();
                        $email = Auth::guard('customer-api')->user()->email;
                        if(!empty($email)){
                            $orderEmail=Order::with('orderItems','orderItems.productInfo','customerInfo','addressInfo','addressInfo.unionInfo','addressInfo.thanaInfo','addressInfo.districtInfo')->where('id',$orderInfo->id)->first();
            
                            $order_id=$orderEmail->randomOrderCode;  
                                 
                            // $subject=$gs->shop_name;
                            // $messageData=[
                            //   'email' =>$email,
                              
                            //   'order_id'=>$order_id,
                            //   'gs'=>$gs,
                            //   'orderEmail'=>$orderEmail,
                             
                            // ];
                            // Mail::send('email.order',$messageData,function($message) use($email,$subject){
                            //   $message->to($email)->subject('order Placed from' .' '.$subject);
                            // });
                            Mail::to($email)->send(new OrderShipped( $gs,$order_id,$orderEmail));

                        }
                      
            
                       
                      
                        $gs=SmsSetting::find(1);
                        
                  

                        $phone=$orderInfo->customerInfo->phone;

                        $phone=GeneralController::phoneNumberPrefix($phone);
                        $message=$gs->orderSmsDescription;
                        $message=str_replace('#orderId',$orderInfo->randomOrderCode,$message);
                        $message=str_replace('#data', date_format(date_create($orderInfo->created_at),'Y/m/d'),$message);
                        $message=str_replace('#time', date_format(date_create($orderInfo->created_at),' h:i A'),$message);
                        $message=str_replace('#amount', (($orderInfo->price+$orderInfo->delivery_charge)-($orderInfo->promo_discount+$orderInfo->discount+$orderInfo->invoiceDiscount)),$message);

						try {
                            //GeneralController::sendSMS($phone,$message);
							$response = 'success';
						} catch (Exception $exception) {
							$response = 'error';
						}
                        
                    


                    $responseData=[
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'errMsg'=>null,
                                'msg'=>'Your Order Has Been Placed Successfully.',
                                'isOrderPlaced'=>true,
                               
                                'orderId'=>$orderInfo->id,
                            ];

                    return response()->json($responseData,200);
              

                
            }
            else{

                DB::rollBack();

               $responseData=[
                            'errMsgFlag'=>true,
                            'msgFlag'=>false,
                            'msg'=>null,
                            'errMsg'=>'Failed To Order Placed.Please Try Again.'
                            ];

                return response()->json($responseData,200); 
            }
            
       }
       catch(Exception $err){

            DB::rollBack();
            
            GeneralController::storeSystemErrorLog($err,"OrderController@orderPlaced");
            
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

    
    public function orderPlacedOnlinePayment(Request $request)
    {
       DB::beginTransaction();
       try{

            $orderInfo=Order::where('id',$request->dataId)->first();


            $orderInfo->is_online_paid=1;



            $orderInfo->created_at=Carbon::now();

       

            if($orderInfo->save()){

                $paymentFlag=$this->storePaymentInfo($orderInfo);

                if($paymentFlag){

                    DB::commit();


                        $gs=GeneralSetting::first();
                        $email = Auth::guard('customer-api')->user()->email;
                        if(!empty($email)){
                            $orderEmail=Order::with('orderItems','orderItems.productInfo','customerInfo','addressInfo','addressInfo.unionInfo','addressInfo.thanaInfo','addressInfo.districtInfo')->where('id',$orderInfo->id)->first();
            
                            $order_id=$orderEmail->randomOrderCode;  
                                 
                            $subject=$gs->shop_name;
                            $messageData=[
                              'email' =>$email,
                              
                              'order_id'=>$order_id,
                              'gs'=>$gs,
                              'orderEmail'=>$orderEmail,
                             
                            ];
                            Mail::send('email.order',$messageData,function($message) use($email,$subject){
                              $message->to($email)->subject('order Placed from' .' '.$subject);
                            });

                        }
                    
            
                        $gs=SmsSetting::find(1);
                        
                        $phone=$orderInfo->customerInfo->phone;

                        $phone=GeneralController::phoneNumberPrefix($phone);
                        $message=$gs->orderSmsDescription;
                        $message=str_replace('#orderId',$orderInfo->randomOrderCode,$message);
                        $message=str_replace('#data', date_format(date_create($orderInfo->created_at),'Y/m/d'),$message);
                        $message=str_replace('#time', date_format(date_create($orderInfo->created_at),' h:i A'),$message);
                        $message=str_replace('#amount', (($orderInfo->price+$orderInfo->delivery_charge)-($orderInfo->promo_discount+$orderInfo->discount+$orderInfo->invoiceDiscount)),$message);

						
						try {
              
							//GeneralController::sendSMS($phone,$message);
							$response = 'success';
						} catch (Exception $exception) {
							$response = 'error';
						}
                        

                    $responseData=[
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'errMsg'=>null,
                                'msg'=>'Your Order Has Been Placed Successfully.',
                                'isOrderPlaced'=>true,
                                'isOnlinePayment'=>($request->payMethod==1) ? true:false,
                                'isBkashPayment'=>($request->payMethod==3) ? true:false,
                                'isCashPayment'=>($request->payMethod==2) ? true:false,
                                'orderId'=>$orderInfo->id,
                            ];

                    return response()->json($responseData,200);
                }
                else{

                     DB::rollBack();

                   $responseData=[
                                'errMsgFlag'=>true,
                                'msgFlag'=>false,
                                'msg'=>null,
                                'errMsg'=>'Failed To Order Placed.Please Try Again.'
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
                            'errMsg'=>'Failed To Order Placed.Please Try Again.'
                            ];

                return response()->json($responseData,200); 
            }
            
       }
       catch(Exception $err){

            DB::rollBack();
            
            GeneralController::storeSystemErrorLog($err,"OrderController@orderPlaced");
            
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
    public function storePaymentInfo($orderInfo)
    {
        $paymentInfo=new OrderPayment();

        $paymentInfo->order_id=$orderInfo->id;

        $paymentInfo->transaction_id="LTS-".$orderInfo->id."-".uniqid();

        $paymentInfo->payment_id="LTS-".$orderInfo->id."-".uniqid();

        $paymentInfo->status=($orderInfo->isCashPayment==1) ? 2:0;

        $paymentInfo->created_at=Carbon::now();

        // $paymentInfo->updated_at=Carbon::now();

        if($paymentInfo->save())
            return true;
        else
            return false;
    }
   
   
    public function onlinePayment(Request $request)
    {
        // dd($request);
        $orderInfo=Order::with('customerAddress','orderItems','addressInfo','customerInfo','paymentInfo')
                            ->where('id',$request->orderId)
                                ->first();
   

       
        if(!empty($orderInfo)){
      

          $customerName=(!is_null($orderInfo->customerAddress)) ? $orderInfo->customerAddress->name:'Customer Name';

          $customerPhone=(!is_null($orderInfo->customerAddress)) ? $orderInfo->customerAddress->phone:'01612423280';

          $customerEmail=(!empty($orderInfo->customerInfo)) ? $orderInfo->customerInfo->email:'demo@gmail.com';

          $customerAddress=(!is_null($orderInfo->customerAddress)) ? $orderInfo->customerAddress->address:'Customer Address.';

          $customerCity=(!is_null($orderInfo->areaInfo)) ? $orderInfo->areaInfo->name:'Customer City.';

          $postalCode=(!is_null($orderInfo->areaInfo)) ? $orderInfo->areaInfo->postalCode:'1216';

          $numberOfProduct=(!is_null($orderInfo->orderDetails)) ? $orderInfo->orderDetails->count():0;

          $payableAmount=(($orderInfo->price+$orderInfo->deliveryCharge)-($orderInfo->discount+$orderInfo->invoiceDiscount+$orderInfo->promoDiscount));

          $transactionId=(is_null($orderInfo->paymentInfo)) ? $orderInfo->transactionId:'MWBD-'.$orderInfo->id.'-'.uniqid();

          $orderPlaced=true;

          
          
               $post_data = array();

              $post_data['total_amount'] = $payableAmount;
              $post_data['currency'] = "BDT";
              $post_data['tran_id'] = $transactionId;
            //   $orderInfo->discount=100;
            //   $orderInfo->save();

              # CUSTOMER INFORMATION
              $post_data['cus_name'] = $customerName;
              $post_data['cus_email'] = $customerEmail;
              $post_data['cus_add1'] = $customerAddress;
              $post_data['cus_add2'] = "";
              $post_data['cus_city'] = $customerCity;
              $post_data['cus_state'] = $customerCity;
              $post_data['cus_postcode'] = $postalCode;
              $post_data['cus_country'] = "Bangladesh";
              $post_data['cus_phone'] = $customerPhone;
           //   $post_data['cus_fax'] = "";

              # SHIPMENT INFORMATION
            $post_data['ship_name'] = "";
              $post_data['ship_add1'] = "";
              $post_data['ship_add2'] = "";
              $post_data['ship_city'] = "";
              $post_data['ship_state'] = "";
              $post_data['ship_postcode'] = "";
              $post_data['ship_phone'] = "";
              $post_data['ship_country'] = "";

              $post_data['shipping_method'] = "NO";
              $post_data['product_name'] = "Makeup Materials";
              $post_data['product_category'] = "Goods";
              $post_data['product_profile'] = "physical-goods";
              $post_data['num_of_item'] = $numberOfProduct;

              # OPTIONAL PARAMETERS
              $post_data['value_a'] = "";
              $post_data['value_b'] = "";
              $post_data['value_c'] = "";
              $post_data['value_d'] = "";


              // $sslcommerz=new SslCommerzPaymentController();
              $sslc = new SslCommerzNotification();
              # initiate(Transaction Data , false: Redirect to SSLCOMMERZ gateway/ true: Show all the Payement gateway here )
              $payment_options = $sslc->makePayment($post_data, 'hosted');
      
              if (!is_array($payment_options)) {
                  print_r($payment_options);
                  $payment_options = array();
              }
        }
        else{
             return redirect()->away(env('PaymentMessageUrl').'/error/');
        }
    }

   
    public function onlinePaymentCancel(Request $request)
    {
       $tranId=explode("-", $request->tran_id);

        if(!empty($tranId)){

            $orderId=$tranId[1];

            $orderInfo=Order::find($orderId);

            $orderInfo->is_online_paid=3;

            $orderInfo->updated_at=Carbon::now();

            if($orderInfo->save()){

                $paymentInfo=OrderPayment::where('orderId',$orderId) 
                                            ->orderBy('id','DESC')
                                                ->first();

                $paymentInfo->payment_id=$request->bank_tran_id;

                $paymentInfo->status=3;

                $paymentInfo->updated_at=Carbon::now();
                $customer=Customer::where('id',$orderInfo->customer_id)->first();

                if($paymentInfo->save()){

                    return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/success');

                    // return redirect()->route('order.cancel.message',['orderId'=>$orderId]);

                }
                else{

                       return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/success');
                    // return redirect()->route('order.cancel.message',['orderId'=>$orderId]);

                }
            }
            else{
                $customer=Customer::where('id',$orderInfo->customer_id)->first();
                 return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/success');

              // return redirect()->route('order.cancel.message',['orderId'=>$orderId]);  

            }
        }
        else{

            return redirect()->route('order.cancel.message',['orderId'=>0]);
        }
    }
    public function onlinePaymentFail(Request $request)
    {
        $tranId=explode("-", $request->tran_id);

        if(!empty($tranId)){

            $orderId=$tranId[1];

            $orderInfo=Order::find($orderId);

            $orderInfo->is_online_paid=4;

            $orderInfo->updated_at=Carbon::now();

            if($orderInfo->save()){

                $paymentInfo=OrderPayment::where('order_id',$orderId) 
                                            ->orderBy('id','DESC')
                                                ->first();

                $paymentInfo->payment_id=$request->bank_tran_id;

                $paymentInfo->status=4;

                $paymentInfo->updated_at=Carbon::now();
                $customer=Customer::where('id',$orderInfo->customer_id)->first();

                if($paymentInfo->save()){

                    return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/profile');

                    // return redirect()->route('order.fail.message',['orderId'=>$orderId]);
                }
                else{

                    return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/profile');
                    
                    // return redirect()->route('order.fail.message',['orderId'=>$orderId]);
                }
            }
            else{
                $customer=Customer::where('id',$orderInfo->customer_id)->first();
                return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/profile');

              // return redirect()->route('order.fail.message',['orderId'=>$orderId]);  

            }
        }
        else{

            return redirect()->route('order.fail.message',['orderId'=>0]);
        }
    } 
    public function onlinePaymentSuccess(Request $request)
    {
        // dd($request);
        $tranId=explode("-", $request->tran_id);
       

        if(!empty($tranId)){

            $orderId=$tranId[1];

            $orderInfo=Order::find($orderId);

            $orderInfo->is_online_paid=1;

            $orderInfo->is_bkash_paid=0;

            $orderInfo->is_cash_on=0;

            $orderInfo->updated_at=Carbon::now();
            $customer=Customer::where('id',$orderInfo->customer_id)->first();

            if($orderInfo->save()){

                $paymentInfo=OrderPayment::where('order_id',$orderId) 
                                            ->orderBy('id','DESC')
                                                ->first();

                $paymentInfo->payment_id=$request->bank_tran_id;

                $paymentInfo->status=1;

                $paymentInfo->updated_at=Carbon::now();

                if($paymentInfo->save()){

                    return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/profile');
                    
                    // return redirect()->route('order.success.message',['orderId'=>$orderId]);
                }
                else{

                    return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/profile');

                    // return redirect()->route('order.fail.message',['orderId'=>$orderId]);
                }
            }
            else{
               
                return redirect()->away(env('OrderMessageUrl').Str::slug($customer->name).'/profile');

              // return redirect()->route('order.fail.message',['orderId'=>$orderId]);  
            }
        }
        else{

            return redirect()->away(env('ONLINE_FAIL_PAYMENT').'/fail');

            // return redirect()->route('order.fail.message',['orderId'=>0]);
        }

        // dump($request->bank_tran_id);
        // dd($request->all());

    }
  
//its garbase


    public function verifyPromoCode($request,$customerInfo)
    {
        
        $promoDiscount=0;

       $promoDiscountInfo=VoucherDiscount::where('promo_code',trim($request->promoCode))
                                                ->where('status',1)
                                                    ->where('available','>',0)
                                                        ->whereDate('startAt','<=',Carbon::today())
                                                            ->whereDate('endAt','>=',Carbon::today())
                                                             
                                                                    ->first();
        

      
         $cartInfos=$this->cartInfos($request);

        $totalItem=count($cartInfos);

        if($totalItem>0){

            $totalPrice=array_sum(array_column($cartInfos, "totalPrice"));

            $productDiscount=array_sum(array_column($cartInfos, "discount"));

        }
        else{

            $totalPrice=0;

            $productDiscount=0;
        }



        if(!empty($promoDiscountInfo)){
            
            $numOfPromoUses=Order::where('is_cancelled',0)
                                ->where('status',1)
                                    ->where('promo_id',$promoDiscountInfo->id)
                                        ->where('customer_id',$customerInfo->id)
                                            ->count();

            if($promoDiscountInfo->canBeUsed>=$numOfPromoUses ){

                if($promoDiscountInfo->isPriceRequired==1){
                    if($promoDiscountInfo->priceRequired<=($totalPrice-$productDiscount)){

                        if($promoDiscountInfo->isDiscountInPercent==1)
                            $promoDiscount=((($totalPrice-$productDiscount)*$promoDiscountInfo->discountAmount)/100);
                        else
                            $promoDiscount=$promoDiscountInfo->discountAmount;

                        session()->put("promoDiscount",$promoDiscount);
                    }
                    else
                        session()->put('promoDiscount',$promoDiscount);
                }   
                else{

                     if($promoDiscountInfo->isDiscountInPercent==1)
                            $promoDiscount=((($totalPrice-$productDiscount)*$promoDiscountInfo->discountAmount)/100);
                    else
                        $promoDiscount=$promoDiscountInfo->discountAmount;

                    session()->put("promoDiscount",$promoDiscount);
                }
            }
            else
                session()->put('promoDiscount',$promoDiscount);
        }
        else
            session()->put('promoDiscount',$promoDiscount);
        
        $cartInfos=$this->cartInfos($request);

        $totalItem=count($cartInfos);

        if($totalItem>0){

            $totalPrice=array_sum(array_column($cartInfos, "totalPrice"));

            $productDiscount=array_sum(array_column($cartInfos, "discount"));

        }
        else{

            $totalPrice=0;

            $productDiscount=0;
        }

        session()->put("promoCode",$request->promoCode);

        $promoCode=session()->get("promoCode");

 
    }


    public function addToCart(Request $request)
	{
		try{
         
          if(isset($request->productId))
            $array=$request->productId;
       
          else
            $array=[];
          foreach ($array as $key => $value) {
            
                $productId=$request->productId[$key];

                $quantity=$request->quantity[$key];

                $colorCode=$request->colorCode[$key];


                $sizeAttributeCode=$request->sizeAttributeCode[$key];


                $productQuantityInfo=StockInfo::whereHas('productInfo',function($q) use($request){
                        $q->where('status',1)->where('published',1);
                })
                ->with(['productInfo','colorInfo','sizeInfo','sizeVariantInfo','productInfo.unitInfo'])
                                                        ->where('product_id',$productId)
                                                         ->where('color_id',$colorCode)
                                                           ->where('size_attribute_id',$sizeAttributeCode)
                                                                ->first();
                                                                 

                if(!empty($productQuantityInfo)){

                     $userId='Customer-0';
                     

                    if($productQuantityInfo->quantity>0){
                     
                       
                        if($productQuantityInfo->quantity<$quantity)
                          $quantity=$productQuantityInfo->quantity;
                      
                        if(!is_null($productQuantityInfo->productInfo))
                            $productImage=$productQuantityInfo->productInfo->thumbnail_img;
                        else
                            $productImage=null;

                        $discount=($productQuantityInfo->startDate<=Carbon::now() && $productQuantityInfo->endDate >=Carbon::now()) ? (($productQuantityInfo->sell_price -$productQuantityInfo->special_price )):0;
                        $totalDiscount=($productQuantityInfo->startDate<=Carbon::now() && $productQuantityInfo->endDate >=Carbon::now()) ? (($productQuantityInfo->sell_price -$productQuantityInfo->special_price )*$quantity):0;
                        if($discount > 0){
                            $data=[
                                'id' => $productQuantityInfo->id.'=>notFree', // inique row ID
                                'name' => $productQuantityInfo->productInfo->name,
                                'price' => $productQuantityInfo->special_price*$quantity,
                                'quantity' => $quantity,
                                'attributes' => [
                                                'isFreeProduct'=>false,
                                                'rate'=>(int)$productQuantityInfo->special_price,
                                                'unitPrice'=>(int)$productQuantityInfo->sell_price,
                                                'totalPrice'=>$productQuantityInfo->sell_price*$quantity,
                                                'discount'=>$discount,
                                                'totalDiscount'=>$totalDiscount,
                                                'discountFlag'=>($discount>0) ? true:false,
                                                'size_id'=>$productQuantityInfo->size_id,
                                                'color_id'=>$productQuantityInfo->color_id,
                                                'size_attribute_id'=>$productQuantityInfo->size_attribute_id,
                                                'sizeAttribute'=>$productQuantityInfo->sizeVariantInfo->attribute,
                                                'product_id'=>$productQuantityInfo->product_id,
                                                'color'=>$productQuantityInfo->colorInfo->color,
                                                'size'=>$productQuantityInfo->sizeInfo->size,
                                                // 'quantityType'=>$productQuantityInfo->productInfo->unitInfo->label,
                                                'productImage'=>$productImage,
                                               
                                                'hasSizeVarity'=>$productQuantityInfo->productInfo->has_size,
                                                'hasColorVarity'=>$productQuantityInfo->productInfo->has_color,
                                            ],
                                'conditions'=>[],
                            ];

                        }else{
                            $data=[
                                'id' => $productQuantityInfo->id.'=>notFree', // inique row ID
                                'name' => $productQuantityInfo->productInfo->name,
                                'price' => $productQuantityInfo->sell_price*$quantity,
                                'quantity' => $quantity,
                                'attributes' => [
                                                'isFreeProduct'=>false,
                                                'rate'=>(int)$productQuantityInfo->sell_price,
                                                'totalPrice'=>$productQuantityInfo->sell_price*$quantity,
                                                'discount'=>$discount,
                                                'totalDiscount'=>$totalDiscount,
                                                'discountFlag'=>($discount>0) ? true:false,
                                                'size_id'=>$productQuantityInfo->size_id,
                                                'color_id'=>$productQuantityInfo->color_id,
                                                'size_attribute_id'=>$productQuantityInfo->size_attribute_id,
                                                'sizeAttribute'=>$productQuantityInfo->sizeVariantInfo->attribute,
                                                'product_id'=>$productQuantityInfo->product_id,
                                                'color'=>$productQuantityInfo->colorInfo->color,
                                                'size'=>$productQuantityInfo->sizeInfo->size,
                                                // 'quantityType'=>$productQuantityInfo->productInfo->unitInfo->label,
                                                'productImage'=>$productImage,
                                                'unitPrice'=>(int)$productQuantityInfo->sell_price,
                                                'hasSizeVarity'=>$productQuantityInfo->productInfo->has_size,
                                                'hasColorVarity'=>$productQuantityInfo->productInfo->has_color,
                                            ],
                                'conditions'=>[],
                            ];

                        }
                         
                       
                            
                         $userId='Customer-0';

                        Cart::session($userId)->add($data);
                        $cartInfos=$this->cartInfos($request);

                      
                    }
                    else{

                        //new Code 5/8/2023

                        $cartInfos=$this->cartInfos($request);

                        $totalItem=count($cartInfos);

                        if($totalItem>0){

                            $totalPrice=array_sum(array_column($cartInfos, "totalPriceWithQty"));

                            $productDiscount=array_sum(array_column($cartInfos, "totalPriceWithQtyDiscount"));
                          
                        }
                        else{

                            $totalPrice=0;

                            $productDiscount=0;
                          
                        }

                        $responseData=[
                                'msgFlag'=>false,
                                'errMsgFlag'=>true,
                                'msg'=>null,
                                'errMsg'=>'Requested Quantity More Than Stock Quantity.',
                                'cartInfos'=>$cartInfos,
                                'promoDiscount'=>$this->promoDiscount($request),
                           
                                'totalItem'=>$totalItem,
                                'totalPrice'=>$totalPrice,
                                'productDiscount'=>$productDiscount,

                                'promoCode'=>$this->promoCode(null),
                            ];
                    
                         return response()->json($responseData,200);
                    }
                }
                else{

                     //new Code 5/8/2023

                    $cartInfos=$this->cartInfos($request);

                        $totalItem=count($cartInfos);

                        if($totalItem>0){

                            $totalPrice=array_sum(array_column($cartInfos, "totalPriceWithQty"));

                            $productDiscount=array_sum(array_column($cartInfos, "totalPriceWithQtyDiscount"));
                          
                        }
                        else{

                            $totalPrice=0;

                            $productDiscount=0;
                          
                        }

                        $responseData=[
                                    'msgFlag'=>false,
                                    'errMsgFlag'=>true,
                                    'msg'=>null,
                                    'errMsg'=>'Requested Product Not Found.',
                                    'cartInfos'=>$cartInfos,
                                    'promoDiscount'=>$this->promoDiscount($request),
                                    'totalItem'=>$totalItem,
                                    'totalPrice'=>$totalPrice,
                                    'productDiscount'=>$productDiscount,
                        
                                    'promoCode'=>$this->promoCode(null),
                                ];
                    
                    return response()->json($responseData,200);
                }
          }

            CartRulesController::getCartInfos(0);

            $cartInfos=$this->cartInfos($request);

            $totalItem=count($cartInfos);

            if($totalItem>0){

                $totalPrice=array_sum(array_column($cartInfos, "totalPriceWithQty"));

                $productDiscount=array_sum(array_column($cartInfos, "totalPriceWithQtyDiscount"));
             
            }
            else{

                $totalPrice=0;

                $productDiscount=0;
           
            }

            $responseData=[
                      'errMsgFlag'=>false,
                      'msgFlag'=>true,
                      'msg'=>'Product Added To Cart Successfully',
                      'errMsg'=>null,
                      'cartInfos'=>$cartInfos,
                      'promoDiscount'=>$this->promoDiscount($request),
                      'totalItem'=>$totalItem,
                      'totalPrice'=>$totalPrice,
                      'productDiscount'=>$productDiscount,
                
                      'promoCode'=>$this->promoCode(null),
                  ];
        
             return response()->json($responseData,200);

  

       }
        catch(Exception $err){
            
            GeneralController::storeSystemErrorLog($err,"Frontend\OrderController@addToCart");

            $cartInfos=$this->cartInfos($request);

            $totalItem=count($cartInfos);

            if($totalItem>0){

                $totalPrice=array_sum(array_column($cartInfos, "totalPrice"));

                $productDiscount=array_sum(array_column($cartInfos, "totalPriceWithQtyDiscount"));
               
            }
            else{

                $totalPrice=0;

                $productDiscount=0;
             
            }

            $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Something Went Wrong.Please Try Again.',
                        'cartInfos'=>$cartInfos,
                        'promoDiscount'=>$this->promoDiscount($request),
                        'totalItem'=>$totalItem,
                        'totalPrice'=>$totalPrice,
                        'productDiscount'=>$productDiscount,
                     
                        'promoCode'=>$this->promoCode(null),
            ];

            return response()->json($responseData,200);
        }
	}



    public function getPromoId($promoCode)
    {
        $promoCodeInfo=VoucherDiscount::where('promo_code',$promoCode)
                                            ->first();

        if(!empty($promoCodeInfo))
            return $promoCodeInfo->id;
        else
            return null;

    }

    public function cartInfos($request)
    {

        $cartInfo=null;

        $cartInfo=CartRulesController::getCartInfos(0);

        return $cartInfo;

    }

	public function promoCode($request)
    {
        if(!session()->has('promoCode'))
            $promoCode=null;
        else
            $promoCode=session()->get("promoCode");

        return $promoCode;
    }
    public function invoiceDiscount($request)
    {
        if(!session()->has('invoiceDiscount'))
            $invoiceDiscount=0;
        else
            $invoiceDiscount=session()->get("invoiceDiscount");

        return $invoiceDiscount;
    }
    public function promoDiscount($request)
    {
         if(!session()->has('promoDiscount'))
            $promoDiscount=0;
        else
            $promoDiscount=session()->get("promoDiscount");

        return $promoDiscount;
    }
    public function getCartInfos(Request $request)
    {
        $cartInfos=$this->cartInfos($request);

        $totalItem=count($cartInfos);

        if($totalItem>0){

            $totalPrice=array_sum(array_column($cartInfos, "totalPrice"));

            $productDiscount=array_sum(array_column($cartInfos, "discount"));

        }
        else{

            $totalPrice=0;

            $productDiscount=0;
        }

       $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>null,
                        'errMsg'=>null,
                        'cartInfos'=>$cartInfos,
                        'promoDiscount'=>$this->promoDiscount($request),
                        'invoiceDiscount'=>$this->invoiceDiscount($request),
                        'totalItem'=>$totalItem,
                        'totalPrice'=>$totalPrice,
                        'productDiscount'=>$productDiscount,
                        'promoCode'=>$this->promoCode(null),
                 ];

        return response()->json($responseData,200);

    }
   
    public function getDeliveryChargeDiscount(Request $request)
    {
        $this->addToCartForCheck($request);

        $deliveryChargeDiscountInfo=DeliveryRule::latest()
                                                ->where('status',1)
                                                    ->where('start_at','<=',Carbon::now())
                                                        ->where('end_at','>=',Carbon::now())
                                                            ->whereIn('rules_for',[1,2])
                                                                ->first();

        $areaInfo=Thana::find($request->areaId);

        if(!empty($deliveryChargeDiscountInfo)) {
          
           if($deliveryChargeDiscountInfo->is_price_wise==1){

                CartRulesController::getCartInfos(0);

                $cartInfos=$this->cartInfos($request);

                $totalItem=count($cartInfos);

               if($totalItem>0){

                    $totalPrice=($totalItem>0) ? array_sum(array_column($cartInfos, "totalPrice")):0;

                    $productDiscount=($totalItem>0) ? array_sum(array_column($cartInfos, "discount")):0;

                }
                else{

                    $totalPrice=0;

                    $productDiscount=0;

                }

                $totalPrice=($totalItem>0) ? ($totalPrice-$productDiscount):0;

                if($totalPrice>=$deliveryChargeDiscountInfo->price_required) {
                   
                   if($deliveryChargeDiscountInfo->is_city_wise==1){
                       
                        $deliveryChargeDiscount=0;

                        foreach (json_decode($deliveryChargeDiscountInfo->offered_areas) as $key => $discountAreaInfo) {

                            if($discountAreaInfo->thana_id==$request->areaId){
                               
                                $deliveryChargeDiscount=($deliveryChargeDiscountInfo->discount_in_per==1) ? (($request->deliveryCharge*$deliveryChargeDiscountInfo->discount)/100):$deliveryChargeDiscountInfo->discount;
                               
                                break;
                            }
                        }
                        $responseData=[
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>null,
                                'errMsg'=>null,
                                'deliveryChargeDiscount'=>$deliveryChargeDiscount,
                            ];
                   }
                   if($deliveryChargeDiscountInfo->is_city_wise==1){

                    $deliveryChargeDiscount=0;

                        foreach (json_decode($deliveryChargeDiscountInfo->offered_areas) as $key => $discountAreaInfo) {

                            if($discountAreaInfo->thana_id==$request->areaId){
                               
                                $deliveryChargeDiscount=($deliveryChargeDiscountInfo->discount_in_per==1) ? (($request->deliveryCharge*$deliveryChargeDiscountInfo->discount)/100):$deliveryChargeDiscountInfo->discount;
                               
                                break;
                            }
                        }
                        $responseData=[
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>null,
                                'errMsg'=>null,
                                'deliveryChargeDiscount'=>$deliveryChargeDiscount,
                            ];

                   }
                   else{
                        $deliveryChargeDiscount=($deliveryChargeDiscountInfo->discount_in_per==1) ? (($request->deliveryCharge*$deliveryChargeDiscountInfo->discount)/100):$deliveryChargeDiscountInfo->discount;

                        $responseData=[
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>null,
                                'errMsg'=>null,
                                'deliveryChargeDiscount'=>$deliveryChargeDiscount,
                            ];
                   }
                }
                else{

                    $responseData=[
                        'errMsgFlag'=>false,
                        'msgFlag'=>true,
                        'msg'=>null,
                        'errMsg'=>null,
                        'deliveryChargeDiscount'=>0,
                    ];
                }
                

           }
           else{

                if($deliveryChargeDiscountInfo->is_city_wise==1){
                       
                        $deliveryChargeDiscount=0;

                        foreach (json_decode($deliveryChargeDiscountInfo->offered_areas) as $key => $discountAreaInfo) {

                            if($discountAreaInfo->thana_id==$request->areaId){
                               
                                $deliveryChargeDiscount=($deliveryChargeDiscountInfo->discount_in_per==1) ? (($request->deliveryCharge*$deliveryChargeDiscountInfo->discount)/100):$deliveryChargeDiscountInfo->discount;
                               
                                break;
                            }
                        }
                        $responseData=[
                                'errMsgFlag'=>false,
                                'msgFlag'=>true,
                                'msg'=>null,
                                'errMsg'=>null,
                                'deliveryChargeDiscount'=>$deliveryChargeDiscount,
                            ];  
                   }
           }
        }
        else{

            $responseData=[
                    'errMsgFlag'=>false,
                    'msgFlag'=>true,
                    'msg'=>null,
                    'errMsg'=>null,
                    'deliveryChargeDiscount'=>0,
                ];
        }

        return response()->json($responseData,200);
    }
  public function addToCartForCheck($request)
  {
    try{
         
          if(isset($request->productId))
            $array=$request->productId;
          else
            $array=[];
          foreach ($array as $key => $value) {
            
                $productId=$request->productId[$key];

                $quantity=$request->quantity[$key];

                $colorCode=$request->colorCode[$key];

               
                $sizeAttributeCode = $request->sizeAttributeCode[$key];

                $appId=1;

                $productQuantityInfo=StockInfo::whereHas('productInfo',function($q) use($request){
                        $q->where('status',1)->where('published',1);
                })
                ->with(['productInfo','sizeInfo','colorInfo','sizeVariantInfo','productInfo.unitInfo'])
                                                        ->where('product_id',$productId)
                                                         ->where('color_id',$colorCode)
                                                         
                                                           ->where('size_attribute_id',$sizeAttributeCode)
                                                                   ->first();
                                                                 

                if(!empty($productQuantityInfo)){

                     $userId='Customer-0';

                    // $cartProductInfo=Cart::session($userId)->get($productQuantityInfo->id.'=>notFree');

                    // if(!is_null($cartProductInfo)){
                    //     $quantity=$cartProductInfo->quantity+$quantity;
                    //     $foundFlag=true;
                    // }
                    // else{
                    //     // $quantity=$request->quantity;
                    //     $foundFlag=false;
                    // }

                    if($productQuantityInfo->quantity>0){
                       
                        if($productQuantityInfo->quantity<$quantity)
                          $quantity=$productQuantityInfo->quantity;
                        // if($foundFlag)
                        //     Cart::session($userId)->remove($productQuantityInfo->id.'=>notFree');

                        if(!is_null($productQuantityInfo->productInfo))
                            $productImage=$productQuantityInfo->productInfo->thumbnail_img;
                        else
                            $productImage=null;

                            $discount=($productQuantityInfo->startDate<=Carbon::now() && $productQuantityInfo->endDate >=Carbon::now()) ? (($productQuantityInfo->sell_price -$productQuantityInfo->special_price )*$quantity):0;

                            if($discount > 0){
                                $data=[
                                    'id' => $productQuantityInfo->id.'=>notFree', // inique row ID
                                    'name' => $productQuantityInfo->productInfo->name,
                                    'price' => $productQuantityInfo->special_price*$quantity,
                                    'quantity' => $quantity,
                                    'attributes' => [
                                                    'isFreeProduct'=>false,
                                                    'rate'=>(int)$productQuantityInfo->special_price,
                                                    'unitPrice'=>(int)$productQuantityInfo->sell_price,
                                                    'totalPrice'=>$productQuantityInfo->sell_price*$quantity,
                                                    'discount'=>$discount,
                                                    'discountFlag'=>($discount>0) ? true:false,
                                                    'size_id'=>$productQuantityInfo->size_id,
                                                    'color_id'=>$productQuantityInfo->color_id,
                                                    'size_attribute_id'=>$productQuantityInfo->size_attribute_id,
                                                    'sizeAttribute'=>$productQuantityInfo->sizeVariantInfo->attribute,
                                                    'product_id'=>$productQuantityInfo->product_id,
                                                    'color'=>$productQuantityInfo->colorInfo->color,
                                                    'size'=>$productQuantityInfo->sizeInfo->size,
                                                    // 'quantityType'=>$productQuantityInfo->productInfo->unitInfo->label,
                                                    'productImage'=>$productImage,
                                                  
                                                    'hasSizeVarity'=>$productQuantityInfo->productInfo->has_size,
                                                    'hasColorVarity'=>$productQuantityInfo->productInfo->has_color,
                                                ],
                                    'conditions'=>[],
                                ];
    
                            }else{
                                $data=[
                                    'id' => $productQuantityInfo->id.'=>notFree', // inique row ID
                                    'name' => $productQuantityInfo->productInfo->name,
                                    'price' => $productQuantityInfo->sell_price*$quantity,
                                    'quantity' => $quantity,
                                    'attributes' => [
                                                    'isFreeProduct'=>false,
                                                    'rate'=>(int)$productQuantityInfo->sell_price,
                                                    'totalPrice'=>$productQuantityInfo->sell_price*$quantity,
                                                    'discount'=>$discount,
                                                    'discountFlag'=>($discount>0) ? true:false,
                                                    'size_id'=>$productQuantityInfo->size_id,
                                                    'color_id'=>$productQuantityInfo->color_id,
                                                    'size_attribute_id'=>$productQuantityInfo->size_attribute_id,
                                                    'sizeAttribute'=>$productQuantityInfo->sizeVariantInfo->attribute,
                                                    'product_id'=>$productQuantityInfo->product_id,
                                                    'color'=>$productQuantityInfo->colorInfo->color,
                                                    'size'=>$productQuantityInfo->sizeInfo->size,
                                                    // 'quantityType'=>$productQuantityInfo->productInfo->unitInfo->label,
                                                    'productImage'=>$productImage,
                                                    'unitPrice'=>$productQuantityInfo->sell_price,
                                                    'hasSizeVarity'=>$productQuantityInfo->productInfo->has_size,
                                                    'hasColorVarity'=>$productQuantityInfo->productInfo->has_color,
                                                ],
                                    'conditions'=>[],
                                ];
    
                            }
                            
                         $userId='Customer-0';

                        Cart::session($userId)->add($data);
                        $cartInfos=$this->cartInfos($request);

                      
                    }
                    else{

                        $cartInfos=$this->cartInfos($request);

                        $totalItem=count($cartInfos);

                        if($totalItem>0){

                            $totalPrice=array_sum(array_column($cartInfos, "totalPrice"));

                            $productDiscount=array_sum(array_column($cartInfos, "discount"));

                        }
                        else{

                            $totalPrice=0;

                            $productDiscount=0;
                        }

                        $responseData=[
                                'msgFlag'=>false,
                                'errMsgFlag'=>true,
                                'msg'=>null,
                                'errMsg'=>'Requested Quantity More Than Stock Quantity.',
                                'cartInfos'=>$cartInfos,
                                'promoDiscount'=>$this->promoDiscount($request),
                                'invoiceDiscount'=>$this->invoiceDiscount($request),
                                'totalItem'=>$totalItem,
                                'totalPrice'=>$totalPrice,
                                'productDiscount'=>$productDiscount,
                                'promoCode'=>$this->promoCode(null),
                            ];
                    
                         return response()->json($responseData,200);
                    }
                }
                else{

                    $cartInfos=$this->cartInfos($request);

                        $totalItem=count($cartInfos);

                        if($totalItem>0){

                            $totalPrice=array_sum(array_column($cartInfos, "totalPrice"));

                            $productDiscount=array_sum(array_column($cartInfos, "discount"));

                        }
                        else{

                            $totalPrice=0;

                            $productDiscount=0;
                        }

                        $responseData=[
                                    'msgFlag'=>false,
                                    'errMsgFlag'=>false,
                                    'msg'=>null,
                                    'errMsg'=>'Requested Product Not Found.',
                                    'cartInfos'=>$cartInfos,
                                    'promoDiscount'=>$this->promoDiscount($request),
                                    'invoiceDiscount'=>$this->invoiceDiscount($request),
                                    'totalItem'=>$totalItem,
                                    'totalPrice'=>$totalPrice,
                                    'productDiscount'=>$productDiscount,
                                    'promoCode'=>$this->promoCode(null),
                                ];
                    
                    return response()->json($responseData,200);
                }
          }

            CartRulesController::getCartInfos(0);

            $cartInfos=$this->cartInfos($request);

            $totalItem=count($cartInfos);

            if($totalItem>0){

                $totalPrice=array_sum(array_column($cartInfos, "totalPrice"));

                $productDiscount=array_sum(array_column($cartInfos, "discount"));

            }
            else{

                $totalPrice=0;

                $productDiscount=0;
            }

            $responseData=[
                      'errMsgFlag'=>false,
                      'msgFlag'=>true,
                      'msg'=>'Product Added To Cart Successfully',
                      'errMsg'=>null,
                      'cartInfos'=>$cartInfos,
                      'promoDiscount'=>$this->promoDiscount($request),
                      'invoiceDiscount'=>$this->invoiceDiscount($request),
                      'totalItem'=>$totalItem,
                      'totalPrice'=>$totalPrice,
                      'productDiscount'=>$productDiscount,
                      'promoCode'=>$this->promoCode(null),
                  ];
        
             return response()->json($responseData,200);

 

       }
        catch(Exception $err){
            
            GeneralController::storeSystemErrorLog($err,"OrderController@addToCart");

            $cartInfos=$this->cartInfos($request);

            $totalItem=count($cartInfos);

            if($totalItem>0){

                $totalPrice=array_sum(array_column($cartInfos, "totalPrice"));

                $productDiscount=array_sum(array_column($cartInfos, "discount"));

            }
            else{

                $totalPrice=0;

                $productDiscount=0;
            }

            $responseData=[
                        'errMsgFlag'=>true,
                        'msgFlag'=>false,
                        'msg'=>null,
                        'errMsg'=>'Something Went Wrong.Please Try Again.',
                        'cartInfos'=>$cartInfos,
                        'promoDiscount'=>$this->promoDiscount($request),
                        'invoiceDiscount'=>$this->invoiceDiscount($request),
                        'totalItem'=>$totalItem,
                        'totalPrice'=>$totalPrice,
                        'productDiscount'=>$productDiscount,
                        'promoCode'=>$this->promoCode(null),
            ];

            return response()->json($responseData,200);
        }
  }
}