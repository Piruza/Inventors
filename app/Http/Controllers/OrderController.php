<?php

namespace App\Http\Controllers;

use \App\Models\Order;
use \App\Models\Brand;
use \App\Models\BrandModel;
use \App\Models\CarInfo;
use \App\Models\BrandModelPart;
use \App\Models\CarPart;
use \App\Models\BrandModelCategory;
use \App\Models\UserCar;
use \App\Models\VinCars;
use \App\Models\PartStatus;
use \App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    private $header;
    private $key;
    private static $uri = 'https://api.parts-catalogs.com/v1/';

    public function __construct(){
        set_time_limit(0);
        $this->middleware('authCheck');
        
        $this->key = env('CATALOG_API_KEY');
        $this->header = [ 
            'Authorization: '.$this->key,
            'accept: application/json',
            'Accept-Language: en'
        ];
    }

    public function makeOrder(Request $request){
        $validator = Validator::make($request->all(),[
            'carId' => 'required|string',
            'partId' => 'required|string',
            'partStatusId' => 'required|integer'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $order = auth()->user()->orders()->create([
            'carId' => $request->carId,
            'partId' => preg_replace('/\s+/', '-', $request->partId),
            'partStatusId' => $request->partStatusId,
        ]);

        return $this->toJson($order, 200);
    }

    public function getOrders(Request $request){
        $validator = Validator::make($request->all(),[
            'onlyActives' => 'boolean|nullable',
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $orders = auth()->user()->orders()
                  ->orderBy('created_at')
                  ->get();

        if(isset($request->onlyActives)){
            $onlyActives = (int) $request->onlyActives;
            $orders = auth()->user()->orders()
                      ->where('orderStatusId', $onlyActives)
                      ->orderBy('created_at')
                      ->get();
        }

        if(count($orders)){
            foreach($orders as $order){
                $order->car = auth()->user()->cars()->where('carId', $order->carId)->first();
                $order->part = CarPart::where('uid', $order->partId)->first();
                $order->partStatus = PartStatus::where("id", $order->partStatusId)->first();
                $order->partGroup = null;
                if(isset($order->part->brand_model_part_id))
                    $order->partGroup = BrandModelPart::where("id", $order->part->brand_model_part_id)->first();
            }
        }
            
        return $this->toJson([
            'orders' => $orders
        ], 200);

    }

    public function getOrder(Request $request){
        $validator = Validator::make($request->all(),[
            'orderId' => 'required|integer',
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $order = Order::where('id', $request->orderId)->first();
        if($order){
            $car = UserCar::where('carId', $order->carId)->first();
            $part = CarPart::where('uid', $order->partId)->first();
            $partStatus = PartStatus::where("id", $order->partStatusId)->first();
            $partGroup = null;
            if(isset($order->part->brand_model_part_id))
                $partGroup = BrandModelPart::where("id", $order->part->brand_model_part_id)->first();

            return $this->toJson([
                'order' => $order,
                'car' => $car,
                'part' => $part,
                'partGroup' => $partGroup,
                'partStatus' => $partStatus
            ], 200);

        }

        return $this->toJson([
            'order' => null,
            'msg' => 'No order found'
        ], 200);

    }

    public function getSellerOrders(){
        //Seller Cars
        $cars = auth()->user()->cars()->get();

        $sellerOrders = [];

        $allOrders = Order::where('orderStatusId', 1)->get();
        
        foreach($allOrders as $order){
            $req = new Request([
                'orderId' => $order->id
            ]);

            $tempData = [json_decode($this->getOrder($req))->data];
            $tempOrder = $tempData[0]->order;
            $tempCar = $tempData[0]->car;
            $tempPart = $tempData[0]->part;
            
            if(!$tempCar) continue;
            foreach($cars as $i => $car){
                
                //Check if seller ownes similar car 
                $ownes = true;
                
                if($car->brand != $tempCar->brand || $car->modelId != $tempCar->modelId){
                    $ownes = false;
                }

                if($tempCar->carYear != null && !empty($tempCar->carYear )){
                    if(!in_array($tempCar->carYear, json_decode($car->carYear)))
                        $ownes = false;
                }

                if($tempCar->transmitionType != null && !empty($tempCar->transmitionType )){
                    if(!in_array($tempCar->transmitionType, json_decode($car->transmitionType)))
                        $ownes = false;
                }

                if($tempCar->region != null && !empty($tempCar->region )){
                    if(!in_array($tempCar->region, json_decode($car->region)))
                        $ownes = false;
                }
               
                if($ownes){
                    array_push($sellerOrders, $tempData);
                }
            }

        }

        return $this->toJson([
            'orders' => $sellerOrders
        ], 200);
    }

    public function makeOffer(Request $request){
        $validator = Validator::make($request->all(),[
            'orderId' => 'integer|required',
            'price' => 'string|required',
            'condition' => 'string|required',
            'guaranteeDays' => 'string|required',
            'deliveryDays' => 'string|required',
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $offer = auth()->user()->offers()->create([
            'orderId' => $request->orderId, 
            'price' => $request->price, 
            'condition' => $request->condition, 
            'guaranteeDays' => $request->guaranteeDays, 
            'deliveryDays' => $request->deliveryDays
        ]);

        return $this->toJson([
            'offer' => $offer
        ], 200);

    }

    public function getOffers(Request $request){
        $validator = Validator::make($request->all(),[
            'orderId' => 'integer|nullable',
        ]);
        // return auth()->user()->typeId;
        if(auth()->user()->typeId == 2){
            $offers = auth()->user()->offers()->get();
            
            if(isset($request->orderId)){
               if(!$offers = auth()->user()->offers()->where('orderId', $request->orderId)->first()){
                    return $this->toJson([
                        'offers' => []
                    ], 200);
               }
               $order = Order::where('id', $offers->orderId)->first();
               $offers->myOfferIsAccepted = $order->hasOfferAccepted && $order->acceptedOfferId == $offer->id ? 1 : 0;
               $offers->order = $order;
            }else{
                foreach($offers as $offer){
                   $order = Order::where('id', $offer->orderId)->first();
                   $offer->myOfferIsAccepted = $order->hasOfferAccepted && $order->acceptedOfferId == $offer->id ? 1 : 0;
                   $offer->order = $order;
                }
            }
            
            return $this->toJson([
                'offers' => $offers
            ], 200);
        }

        if(isset($request->orderId)){
            if(!$orders = Order::where('id', $request->orderId)->first()){
                return $this->toJson([
                        'offers' => []
                ], 200);
            }
            $orders->offers = [];

            if($orders){
                $orders->offers = Offer::where('orderId', $orders->id)->orderBy('created_at', 'desc')->get();
                $orders->car = UserCar::where('carId', $orders->carId)->first();
                $orders->part = CarPart::where('uid', $orders->partId)->first();
            }

        }else{
            $orders = auth()->user()->orders()->where('orderStatusId', 1)->orderBy('created_at', 'desc')->get();
            
            foreach($orders as $order){
                $order->offers = Offer::where('orderId', $order->id)->orderBy('created_at', 'desc')->get();
                $order->car = UserCar::where('carId', $order->carId)->first();
                $order->part = CarPart::where('uid', $order->partId)->first();
            }
        }

        return $this->toJson([
            'offers' => $orders
        ], 200);
    }
}
