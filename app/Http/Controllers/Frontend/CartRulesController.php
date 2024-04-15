<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Cart;

class CartRulesController extends Controller
{
 
    public static function getCartInfos($num)
    {
        $customCart=[];

        $product_ids=[];

         $userId='Customer-'.$num;

        $cartInfos=Cart::session($userId)->getContent();

        $totalCartValue=Cart::session($userId)->getTotal();

        session()->put('takenRules',[]);

        session()->put('restrictedRules',[]);

        foreach ($cartInfos as $key => $cartInfo) {

           // dump($cartInfo->attributes) ;
            if($cartInfo->attributes->isFreeProduct==false){

                $cart=[
                        'productQuantityId'=>explode("=>",$cartInfo->id)[0],
                        'product_id'=>$cartInfo->attributes->product_id,
                        'quantity'=>$cartInfo->quantity,
                        'rate'=>$cartInfo->attributes->rate ,
                        'unitPrice'=>$cartInfo->attributes->unitPrice,
                        'price'=>$cartInfo->price,
                        'totalPriceWithQty'=>$cartInfo->attributes->unitPrice * $cartInfo->quantity,
                        'totalPriceWithQtyDiscount'=>$cartInfo->attributes->discount * $cartInfo->quantity,
                        'totalPrice'=>$cartInfo->attributes->totalPrice,
                        'discount'=>$cartInfo->attributes->discount,
                        'totalDiscount'=>$cartInfo->attributes->totalDiscount,
                        'discountFlag'=>$cartInfo->attributes->discountFlag,
                        'isFreeProduct'=>$cartInfo->attributes->isFreeProduct,
                        'color_id'=>$cartInfo->attributes->color_id,
                        'size_id'=>$cartInfo->attributes->size_id,
                        'size_attribute_id'=>$cartInfo->attributes->size_attribute_id,
                        'quantityType'=>$cartInfo->attributes->quantityType,
                        'name'=>$cartInfo->name,
                        'size'=>$cartInfo->attributes->size,
                        'color'=>$cartInfo->attributes->color,
                        'sizeAttribute'=>$cartInfo->attributes->sizeAttribute,
                        'productImage'=>$cartInfo->attributes->productImage,
                        'buyRate'=>$cartInfo->attributes->buyRate,
                        'hasSizeVarity'=>$cartInfo->attributes->hasSizeVarity,
                        'hasColorVarity'=>$cartInfo->attributes->hasColorVarity,
                    ];

             array_push( $customCart,$cart);

             array_push( $product_ids,$cartInfo->attributes->product_id);

            }
        }




        return $customCart;
       
    }
}
