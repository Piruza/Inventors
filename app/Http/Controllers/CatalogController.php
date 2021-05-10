<?php

namespace App\Http\Controllers;

use \App\Models\Brand;
use \App\Models\BrandModel;
use \App\Models\CarInfo;
use \App\Models\BrandModelPart;
use \App\Models\CarPart;
use \App\Models\BrandModelCategory;
use \App\Models\UserCar;
use \App\Models\VinCars;
use \App\Models\PartStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CatalogController extends Controller
{

    private $header;
    private $key;
    private static $uri = 'https://api.parts-catalogs.com/v1/';
    
    public function __construct(){
        set_time_limit(0);

        $this->middleware('authCheck', ['except' => 
            [
                'info'
            ]
        ]);
        
        $this->key = env('CATALOG_API_KEY');
        $this->header = [ 
            'Authorization: '.$this->key,
            'accept: application/json',
            'Accept-Language: en'
        ];
    }

    public function info(){
        echo phpinfo();
    }

    public function makeRequest($url){
        $full_path = self::$uri.$url;
        $ch = curl_init($full_path);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header);

        return json_decode(curl_exec($ch));
    }
    
    public function getBrands(){
        $brands = Brand::orderByDesc('favorite')->orderBy('id')->get();
        if(count($brands)) {
            return $this->toJson([
              'brands' =>  $brands
            ], 200);
        }

        $data = $this->makeRequest('catalogs');

        foreach($data as $item){
            Brand::create([
                'id' => $item->id,
                'name' => $item->name,
                'modelsCount' => $item->modelsCount
            ]);
            $item->favorite = 0;
        }

        return $this->toJson([
            'brands' =>  $data
        ], 200);
    }

    public function getModels(Request $request){
        $validator = Validator::make($request->all(),[
            'catalogId' => 'required|string',
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $models = BrandModel::where('brand', $request->catalogId)->get();
        if(count($models)) return $this->toJson($models, 200);

        $url = 'catalogs/'.$request->catalogId.'/models';

        $data = $this->makeRequest($url);

        foreach($data as $item){
            BrandModel::create([
                'id' => $item->id,
                'brand' => $request->catalogId,
                'name' => $item->name,
                'img' => $item->img
            ]);
        }

        return $this->toJson($data, 200);
    }

    public function getModelParams(Request $request){
        $validator = Validator::make($request->all(),[
            'catalogId' => 'required|string',
            'modelId' => 'required|string',
            'parameter' => 'nullable'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $parameter = '';
        if(isset($request->parameter))
            $parameter = implode(',', $request->parameter);

        $query = "modelId={$request->modelId}&parameter={$parameter}";
        $url = 'catalogs/'.$request->catalogId.'/cars-parameters/?'.$query;

        $data = $this->makeRequest($url);

        return $this->toJson($data, 200);
    }

    public function getCarList(Request $request){
        $validator = Validator::make($request->all(),[
            'catalogId' => 'required|string',
            'modelId' => 'required|string',
            'parameter' => 'nullable'
        ]);

        
        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $parameter = '';
        if(isset($request->parameter))
            $parameter = implode(',', $request->parameter);

        
        $query = "modelId={$request->modelId}&parameter={$parameter}";
        $url = 'catalogs/'.$request->catalogId.'/cars2/?'.$query;
        $data = $this->makeRequest($url);

        return $this->toJson($data, 200);
    }

    public function getModelCategories(Request $request){
        $validator = Validator::make($request->all(),[
            'catalogId' => 'required|string',
            'carId' => 'required|string',
            'groupId' => 'nullable|string'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $groupId = '';

        // //Check if in DB
        if(!isset($request->groupId)){
            $DBCategories = BrandModelCategory::where('carId', $request->carId)->where('categoryLevel', 1)->get();
            
            if(count($DBCategories)){
                foreach($DBCategories as $category){
                    $category->hasSubgroups = (bool) $category->hasSubgroups;
                    $category->hasParts = (bool) $category->hasParts;
                }

                return $this->toJson($DBCategories, 200);
            }
        }else{
             $groupId = $request->groupId;

            $DBCategories = BrandModelCategory::where('carId', $request->carId)->where('parentId', $groupId)->get();
            
            foreach($DBCategories as $category){
                $category->hasSubgroups = (bool) $category->hasSubgroups;
                $category->hasParts = (bool) $category->hasParts;
            }

            if(count($DBCategories)){
                return $this->toJson($DBCategories, 200);
            }
        }
        

        $query = "carId={$request->carId}&groupId={$groupId}";
        $url = 'catalogs/'.$request->catalogId.'/groups2/?'.$query;

        $data = $this->makeRequest($url);

        if(!is_array($data) && property_exists($data, 'code')) return $this->toJson(null, 400, ['msg' => 'No result found']);

        if(count($data)){
            $result = auth()->user()->cars()->where('carId', $request->carId)->first();
            $fromDB = true;

            if(!$result){
                $url = "catalogs/{$request->catalogId}/cars2/{$request->carId}";
                $result = $this->makeRequest($url);
                $fromDB = false;
            }

            foreach($data as $item){
                $item->uid = $item->id;
                unset($item->id);

            //     //Check Category Level
                $categoryLevel = 1;
                $bc = BrandModelCategory::where('uid', $item->parentId)->first();
                if($bc) $categoryLevel = $bc->categoryLevel + 1;

                BrandModelCategory::create(
                    [
                        'carId' => $fromDB ? $result->carId : $result->id,
                        'modelId' => $result->modelId,
                        'catalogId' => $fromDB ? $result->brand : $result->catalogId,
                        'uid' => $item->uid,
                        'hasSubgroups' => $item->hasSubgroups,
                        'hasParts' => $item->hasParts,
                        'name' => $item->name,
                        'img' => $item->img,
                        'description' => $item->description,
                        'parentId' => $item->parentId,
                        'categoryLevel' => $categoryLevel
                    ]
                );

                $item->categoryLevel = $categoryLevel;
            }
        }

        return $this->toJson($data, 200);
    }

    public function getModelParts(Request $request){
        $validator = Validator::make($request->all(),[
            'catalogId' => 'required|string',
            'carId' => 'required|string',
            'groupId' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $result = auth()->user()->cars()->where('carId', $request->carId)->first();
        $fromDB = true;

        if(!$result){
            $url = "catalogs/{$request->catalogId}/cars2/{$request->carId}";
            $result = $this->makeRequest($url);
            $fromDB = false;
        }

        //Check if in DB
        $DBBrandModelPart = BrandModelPart::where([
            ['carId', '=', $fromDB ? $result->carId : $result->id],
            ['groupId', '=', $request->groupId]
        ])->first();

        if($fromDB && $DBBrandModelPart) {
            $DBBrandModelPart->parts = CarPart::where('brand_model_part_id', $DBBrandModelPart->id)->get();
            $DBBrandModelPart->positions = json_decode($DBBrandModelPart->positions);
            unset($DBBrandModelPart->partGroups);


            return $this->toJson($DBBrandModelPart, 200);
        }

        return $this->toJson([], 200);

        $query = "carId={$request->carId}&groupId={$request->groupId}";
        $url = 'catalogs/'.$request->catalogId.'/parts2/?'.$query;

        $data = $this->makeRequest($url);

        if($data && isset($data->partGroups)){
            
            $brandModelPart = BrandModelPart::create([
                'carId' => $fromDB ? $result->carId : $result->id,
                'modelId' => $result->modelId,
                'catalogId' => $result->brand,
                'groupId' => $request->groupId,
                'img' => isset($data->img) ? $data->img : null,
                'imgDescription' => isset($data->imgDescription) ? $data->imgDescription : null,
                'partGroups' => json_encode($data->partGroups),
                'positions' => json_encode($data->positions)
            ]);

            if(isset($data->partGroups)){
                $parts = $data->partGroups[0]->parts;
                $data->parts = $parts;
                unset($data->partGroups);
            }else{
                $data->parts = $parts = [];
            }

            foreach($parts as $part){
                $part->id =  preg_replace('/\s+/', '-', $part->id);
                $part->number =  preg_replace('/\s+/', '-', $part->number);
                CarPart::updateOrCreate(
                    ['uid' => $part->id],
                    [
                        'brand_model_part_id' => $brandModelPart->id,
                        'uid' => $part->id,
                        'number' => $part->number,
                        'name' => $part->name,
                        'notice' => $part->notice,
                        'description' => $part->description,
                        'positionNumber' => $part->positionNumber,
                        'url' => $part->url
                    ]
                );
            }
        }

        return $this->toJson($data, 200);
    }

    public function getUserCars(){

        $cars = auth()->user()->cars()->get();

        foreach($cars as $car){
            $car->jsonResponse = json_decode($car->jsonResponse);
        }

        return $this->toJson(
            $cars,
            200
        );
    }

    public function addCar(Request $request){
        $validator = Validator::make($request->all(),[
            'catalogId' => 'required|string',
            'carId' => 'required|string',
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $url = 'catalogs/'.$request->catalogId.'/cars2/'.$request->carId;
        $data = $this->makeRequest($url);

        if(property_exists($data, 'code')) return $this->toJson(null, 400, ['msg' => 'No result found']);


        $parameters = $data->parameters;
        $carYear = null;
        $wheelType = null;
        $engine = null;
        $transmitionType = null;
        $region = null;
        $bodyType = null;
        $specSeries = null;

        foreach($parameters as $parameter){
            if($parameter->key == 'year'){
                $carYear = $parameter->value;
            }
            if($parameter->key == 'sales_region'){
                $region = $parameter->value;
            }
            if($parameter->key == 'steering'){
                $wheelType = $parameter->value;
            }
            if($parameter->key == 'trans_type'){
                $transmitionType = $parameter->value;
            }
            if($parameter->key == 'engine'){
                $engine = $parameter->value;
            }
            if($parameter->key == 'spec_series'){
                $specSeries = $parameter->value;
            }
            if($parameter->key == 'body_type'){
                $bodyType = $parameter->value;
            }
        }

        $userCar = UserCar::where('user_id', auth()->id())->where('carId', $data->id)->first();

        if($userCar) return $this->toJson($userCar, 200);

        $car = auth()->user()->cars()->create([
            'brand' => $data->catalogId,
            'userType' => auth()->user()->typeId,
            'modelId' => $data->modelId,
            'carId' => $data->id,
            'carName' => $data->name,
            'carYear' => $carYear,
            'wheelType' => $wheelType,
            'carModelName' => $data->modelName,
            'engine' => $engine,
            'region' => $region,
            'transmitionType' => $transmitionType,
            'specSeries' => $specSeries,
            'bodyType' => $bodyType,
            'jsonResponse' => json_encode($data)
        ]);

        Brand::where('id', $car->brand)->update([
            'favorite' => 1 
        ]);

        return $this->toJson(
            $car,
            200
        );
    }

    public function deleteCar(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }
        
        $car = auth()->user()->cars()->where('carId',$request->id)->first();

        if(!$car) return $this->toJson(null, 400, [
            'msg' => 'Car not found'
        ]);

        if(!auth()->user()->cars()->where('brand', $car->brand)->first()){
            Brand::where('id', $car->brand)->update([
                'favorite' => 0
            ]);
        }

        $car->delete();


        return $this->toJson(
            ['msg' => 'Car deleted'],
            200
        );
    }

    public function findByVIN(Request $request){
        $validator = Validator::make($request->all(),[
            'code' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $url = 'car/info?q='.$request->code;
        $data = $this->makeRequest($url);

        VinCars::create([
            'vinCode' => $request->code,
            'user_id' => auth()->id(),
            'user_type' => auth()->user()->typeId,
            'result' => json_encode($data)
        ]);

        if(!empty($data))
            return $this->toJson($data, 200);

        return $this->toJson(null, 400, ['msg' => 'No result found']);

    }

    public function searchByPartNumber(Request $request){
        

        //Get From DB
        $validator = Validator::make($request->all(),[
            'partNumber' => 'required|string',
            'carId' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $partNumber = str_replace(' ', "-", $request->partNumber);

        $car = auth()->user()->cars()->where('carId',$request->carId)->first();

        if(!$car)
            return $this->toJson(null, 400, ['msg' => 'Car not found in user\'s list']);


        $categories = [];
        $category = null;
        $subCategory = null;
        $otherCategory = null;
        $parts = null;

        $req = new Request([
            'catalogId' => $car->brand,
            'carId' => $car->carId
        ]);

        $part = CarPart::where('uid', $partNumber)->first();


        if($part){
            $data = BrandModelPart::where('id', $part->brand_model_part_id)->first();
            $cat = BrandModelCategory::where('uid', $data->groupId)->first();
            if($cat){
                while($cat->parentId != null){
                    $temp = BrandModelCategory::where('uid', $cat->parentId)->first();
                    
                    $cat->parts = $part;
                    if($cat->categoryLevel == 3){
                        $subCategory = $temp;
                        $subCategory->children = $cat;
                    }else if($cat->categoryLevel == 2){
                        $category = $temp;
                        $category->subcategory = $cat;
                        array_push($categories, $category);
                    }

                    $cat = $temp;
                }
            }

            return $this->toJson($categories, 200);
        }
        // return $this->toJson([], 200);

        // try{
        //     $list = $this->getModelCategories($req);
        //     $categories = json_decode($list)->data;
        // }catch(Exception $e){
        //     $categories = [$list];
        // }

        if(!is_array($categories) && property_exists($categories, 'code')) return $this->toJson(null, 400, ['msg' => 'Categories not found']);

        $result = [];
        
        // return $this->toJson($result, 200);


        foreach($categories as $index => $category){
            if(isset($category->categoryLevel) && $category->categoryLevel != 1) continue;
            $category->subcategories = [];
            $category->foundPart = false;
            if($category->hasSubgroups){

                try{
                    $req = new Request([
                        'catalogId' => $car->brand,
                        'carId' => $car->carId,
                        'groupId' => $category->uid
                    ]);
                    $list = $this->getModelCategories($req);
                    $subCategories = json_decode($list)->data;
                }catch(Exception $e){
                    $subCategories = [$list];
                }
                Log::info('Category: '. $category->name);

                foreach($subCategories as $key => $subCategory){
                    $subCategory->children = [];
                    if($subCategory->hasSubgroups){
                        
                        try{
                            $req = new Request([
                                'catalogId' => $car->brand,
                                'carId' => $car->carId,
                                'groupId' => $subCategory->uid
                            ]);
                            $list = $this->getModelCategories($req);
                            $otherCategories = json_decode($list)->data;
                        }catch(Exception $e){
                            $otherCategories = [$list];
                        }

                        Log::info(' - Subcategory: '. $subCategory->name);
                        
                        foreach($otherCategories as $k => $oc){
                            if(!$oc->hasParts) continue;
                            $req = new Request([
                                'catalogId' => $car->brand,
                                'carId' => $car->carId,
                                'groupId' => $oc->uid
                            ]);

                            try{
                                $list = $this->getModelParts($req);
                                $parts = json_decode($list)->data;
                            }catch(Exception $e){
                                $parts = [$list];
                            }
                            
                            Log::info(' ---- Other category: '. $oc->name);

                            $found = false;
                            if(isset($parts->parts)){
                                foreach($parts->parts as $part){
                                    if($found){
                                        return $this->toJson([$category], 200);                                
                                    }
    
                                    if (strtolower($part->id) == strtolower($partNumber)) {
                                        array_push($subCategory->children, $part);
                                        array_push($category->subcategories, $subCategory);
                                        $found = true;
                                        $category->foundPart = true;
                                        Log::info('---- FOUND ----');
                                    }
                                }
                            }
                        }

                    }else{
                        $req = new Request([
                            'catalogId' => $car->brand,
                            'carId' => $car->carId,
                            'groupId' => $subCategory->uid
                        ]);

                        try{
                            $list = $this->getModelParts($req);
                            $parts = json_decode($list)->data;
                        }catch(Exception $e){
                            $parts = [$list];
                        }

                        Log::info(' - Subcategory: '. $subCategory->name);

                        $found = false;
                        if(isset($parts->parts)){
                            foreach($parts->parts as $part){
                                if($found){
                                   return $this->toJson([$category], 200);                                 
                                }
                                
                                if (strtolower($part->id) == strtolower($partNumber)) {
                                    array_push($subCategory->children, $part);
                                    array_push($category->subcategories, $subCategory);
                                    $found = true;
                                    $category->foundPart = true;
                                    Log::info('---- FOUND ----');
                                }
                            }
                        }
                    }    
                }
            }else if($category->hasParts){
                $req = new Request([
                    'catalogId' => $car->brand,
                    'carId' => $car->carId,
                    'groupId' => $category->uid
                    ]);
                                       
                try{
                    $list = $this->getModelParts($req);
                    $parts = json_decode($list)->data;
                }catch(Exception $e){
                    $parts = [$list];
                }

                Log::info('Category: '. $category->name);

                $found = false;
                if(isset($parts->parts)){
                    foreach($parts->parts as $part){
                        if($found){
                            return $this->toJson([$category], 200);                                 
                        }
                                
                        if (strtolower($part->id) == strtolower($partNumber)) {
                            array_push($subCategory->children, $part);
                            array_push($category->subcategories, $subCategory);
                            $found = true;
                            $category->foundPart = true;
                            Log::info('---- FOUND ----');
                        }
                    }
                }
            }
            array_push($result, $category);         
        }

        return $this->toJson(
            $result,
            200
        );
    }

    public function searchByPartName(Request $request){
        

        //Get From DB
        $validator = Validator::make($request->all(),[
            'keyword' => 'required|string',
            'carId' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $keyword = strtolower($request->keyword);

        $car = auth()->user()->cars()->where('carId',$request->carId)->first();

        if(!$car)
            return $this->toJson(null, 400, ['msg' => 'Car not found in user\'s list']);


        $categories = [];
        $category = null;
        $subcategory = null;
        $otherCategory = null;
        $parts = null;

        $req = new Request([
            'catalogId' => $car->brand,
            'carId' => $car->carId
        ]);
        
  
        $parts = BrandModelPart::where('carId', $car->carId)->with(["carParts" => function($q)  use ($keyword){
              $q->where('name', 'like', "%{$keyword}%");
        }])->groupBy('id')->get(['id', 'groupId']);
        
        foreach($parts as $part){
            if(!count($part->carParts)) continue;
            if($cat = BrandModelCategory::where('uid', $part->groupId)->first()){
                if($cat){
                    $cat->parts = $part->carParts;
                    while($cat->parentId != null){
                        $temp = BrandModelCategory::where('uid', $cat->parentId)->first();
                        if($cat->categoryLevel == 3){
                            $subCategory = $temp;
                            $subCategory->children = $cat;
                        }else if($cat->categoryLevel == 2){
                            $category = $temp;
                            if(!$categories){
                                $category->subcategory = [$cat];
                                array_push($categories, $category);
                                continue;
                            }
                            $added = false;
                            foreach($categories as $cc){
                                if($added) continue;
                                if($cc->id == $category->id){
                                    // return  gettype($cc->subcategory);
                                    $tempCat = array_merge($cc->subcategory, [$cat]);
                                    $cc->subcategory = $tempCat;
                                    $added = true;
                                }else{
                                    $category->subcategory = [$cat];
                                    array_push($categories, $category);
                                    $added = true;
                                }
                            }
                        }

                        $cat = $temp;
                    }
                }
            }
            
        }

        return $categories;
        

        // ************************************************

            $catalogs = BrandModelCategory::where('hasParts', 1)->get();
            $arr = [];

            foreach($catalogs as $cc){
                $req = new Request([
                    'catalogId' => $car->brand,
                    'carId' => $car->carId,
                    'groupId' => $cc->uid
                ]);

                try{
                    $list = $this->getModelParts($req);
                    $parts = json_decode($list)->data;
                }catch(Exception $e){
                    $parts = [$list];
                }
                
                Log::info('-- ITEM: '. $cc->name);

                $found = false;
                foreach($parts->parts as $part){
                    if($found) continue;
                    if (strpos($part->name, $keyword) !== false) {
                        array_push($arr, $part);
                        Log::info('---- FOUND ----');
                    }
                }
            }

            return $this->toJson($arr, 200);

        // // ************************************************
        

        try{
            $list = $this->getModelCategories($req);
            $categories = json_decode($list)->data;
        }catch(Exception $e){
            $categories = [$list];
        }

        if(!is_array($categories) && property_exists($categories, 'code')) return $this->toJson(null, 400, ['msg' => 'Categories not found']);

        // $data['categories'] = [...$categories];

        $result = [];

        foreach($categories as $index => $category){
            if(isset($category->categoryLevel) && $category->categoryLevel != 1) continue;
            $category->subcategories = [];
            $category->foundPart = false;
            if($category->hasSubgroups){
                try{
                    $req = new Request([
                        'catalogId' => $car->brand,
                        'carId' => $car->carId,
                        'groupId' => $category->uid
                    ]);
                    $list = $this->getModelCategories($req);
                    $subCategories = json_decode($list)->data;
                }catch(Exception $e){
                    $subCategories = [$list];
                }

                Log::info('Category: '. $category->name);

                foreach($subCategories as $key => $subCategory){
                    $subCategory->children = [];
                    if($subCategory->hasSubgroups){
                        
                        try{
                            $req = new Request([
                                'catalogId' => $car->brand,
                                'carId' => $car->carId,
                                'groupId' => $subCategory->uid
                            ]);
                            $list = $this->getModelCategories($req);
                            $otherCategories = json_decode($list)->data;
                        }catch(Exception $e){
                            $otherCategories = [$list];
                        }

                        Log::info(' - Subcategory: '. $subCategory->name);
                        
                        foreach($otherCategories as $k => $oc){
                            if($oc->hasSubgroups){
                                try{
                                    $req = new Request([
                                        'catalogId' => $car->brand,
                                        'carId' => $car->carId,
                                        'groupId' => $subCategory->uid
                                    ]);
                                    $list = $this->getModelCategories($req);
                                    $others = json_decode($list)->data;
                                }catch(Exception $e){
                                    $others = [$list];
                                }
                                
                                foreach($others as $other){
                                    if(!$other->hasParts) continue;
                                    $req = new Request([
                                        'catalogId' => $car->brand,
                                        'carId' => $car->carId,
                                        'groupId' => $other->uid
                                    ]);
        
                                    try{
                                        $list = $this->getModelParts($req);
                                        $parts = json_decode($list)->data;
        
                                    }catch(Exception $e){
                                        $parts = [$list];
                                    }
                                    
                                    Log::info(' ---- Other category: '. $oc->name);
                                    $found = false;
                                    if(isset($parts->parts)){
                                        foreach($parts->parts as $part){
                                            if($found) continue;
                                            if (strpos(strtolower($part->name), $keyword) !== false) {
                                                array_push($subCategory->children, $part);
                                                array_push($category->subcategories, $subCategory);
                                                $found = true;
                                                $category->foundPart = true;
                                                Log::info('---- FOUND ----');
                                            }
                                        }
                                    }
                                }
                                
                            }else if($oc->hasParts){
                                $req = new Request([
                                    'catalogId' => $car->brand,
                                    'carId' => $car->carId,
                                    'groupId' => $oc->uid
                                ]);
    
                                try{
                                    $list = $this->getModelParts($req);
                                    $parts = json_decode($list)->data;
    
                                }catch(Exception $e){
                                    $parts = [$list];
                                }
                                
                                Log::info(' ---- Other category: '. $oc->name);
                                $found = false;
                                if(isset($parts->parts)){
                                    foreach($parts->parts as $part){
                                        if($found) continue;
                                        if (strpos(strtolower($part->name), $keyword) !== false) {
                                            array_push($subCategory->children, $part);
                                            array_push($category->subcategories, $subCategory);
                                            $found = true;
                                            $category->foundPart = true;
                                            Log::info('---- FOUND ----');
                                        }
                                    }
                                }
                            }
                        }

                    }else{
                        $req = new Request([
                            'catalogId' => $car->brand,
                            'carId' => $car->carId,
                            'groupId' => $subCategory->uid
                        ]);

                        try{
                            $list = $this->getModelParts($req);
                            $parts = json_decode($list)->data;
                        }catch(Exception $e){
                            $parts = [$list];
                        }

                        $found = false;
                        if(isset($parts->parts)){
                            foreach($parts->parts as $part){
                                if($found) continue;
                                if (strpos(strtolower($part->name), $keyword) !== false) {
                                    array_push($subCategory->children, $part);
                                    array_push($category->subcategories, $subCategory);
                                    $found = true;
                                    $category->foundPart = true;
                                    Log::info('---- FOUND ----');
                                }
                            }
                        }
                    }    
                }
            }else if($category->hasParts){
                $req = new Request([
                    'catalogId' => $car->brand,
                    'carId' => $car->carId,
                    'groupId' => $category->uid
                    ]);
                                       
                try{
                    $list = $this->getModelParts($req);
                    $parts = json_decode($list)->data;
                }catch(Exception $e){
                    $parts = [$list];
                }

                Log::info('Category: '. $category->name);
                $found = false;
                if(isset($parts->parts)){
                    foreach($parts->parts as $part){
                        $partsTemp = [];
                        if($found) continue;
                        if (strpos(strtolower($part->name), $keyword) !== false) {
                            array_push($subCategory->children, $part);
                            array_push($category->subcategories, $subCategory);
                            $found = true;
                            $category->foundPart = true;
                            Log::info('---- FOUND ----');
                        }
                    }
                }
            }
            if($category->foundPart)
                array_push($result, $category);         
        }
        
        Log::info('---------- DONE ----------');

        Log::info($result);
        return $this->toJson(
            $result,
            200
        );
        
        Log::info('---------- RETURN DONE ----------');
    }

    public function getPartStatuses(){
        return $this->toJson(PartStatus::all(), 200);
    }   

    public function addSellerCar(Request $request){
        $validator = Validator::make($request->all(),[
            'brand' => 'required|string',
            'modelId' => 'required|string',
            'carYear' => 'required|array',
            'region' => 'nullable|array',
            'transmitionType' => 'nullable|array',
            'specSeries' => 'nullable|array',
            'engine' => 'nullable|array',
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $req = new Request([
            'catalogId' => $request->brand
        ]);

        $data  = $this->getModels($req);

        if(!$data) return $this->toJson(null, 400, ['msg' => 'No models found']);

        $carName = null;

        foreach(json_decode($data)->data as $item){
            if($item->id == $request->modelId){
                $carName = $request->brand. " " . $item->name;
                continue;
            }
        }

        $car = auth()->user()->cars()->create([
            'brand' => $request->brand,
            'userType' => auth()->user()->typeId,
            'modelId' => $request->modelId,
            'carName' => $carName,
            'carYear' => json_encode($request->carYear),
            'carModelName' => $carName,
            'engine' => json_encode($request->engine),
            'region' => json_encode($request->region),
            'transmitionType' => json_encode($request->transmitionType),
            'specSeries' => json_encode($request->specSeries)
        ]);

        Brand::where('id', $car->brand)->update([
            'favorite' => 1 
        ]);


        return $this->toJson(
            $car,
            200
        );
    }
    
    public function autoFetch(){
        $brands = ['bmw'];
        
        foreach($brands as $brand){
            $req = new Request([
                'catalogId' => $brand,
            ]);
            
            $models = ['f2a8c99813e31b666a6a732aa6732c42', 'fd00d0e6f67eb24e666ac77d42072618', '0514a3f8cae761e9779bb3676b35ad8a', 'b0906a96a3888efa5d81be4eb4bbacf1', 'bb20547730ecf3c110cebe421286e1b2', '7e5a531d03af4fb4a60ca1051f422f8e'];
            
            foreach($models as $model){
                $years =  ['6ce6d4141649e649b80d244ca6b525b9', 'c182c5a082ccce71af450c97c694c0b4', '6448e17c83f950cb8eeef93fa5caa63a', 'a9643aaf208b0bb58451e67c5341c246'];
                foreach($years as $year){
                    $req = new Request([
                        'catalogId' => $brand,
                        'modelId' => $model,
                        'parameter' => [$year]
                    ]);
                    
                    $cars = json_decode($this->getCarList($req))->data;
                    
                    foreach($cars as $car){
                        if(!$car) continue;
                        
                        $req = new Request([
                            'catalogId' => $brand,
                            'carId' => $car->id
                        ]);
                        
                        $this->addCar($req);
                        
                        $categories = json_decode($this->getModelCategories($req))->data;
                        
                        foreach($categories as $category){
                            if(!$category->hasParts){
                                $req = new Request([
                                    'catalogId' => $brand,
                                    'carId' => $car->id,
                                    'groupId' => $category->uid
                                ]);
                                
                                $subcategories = json_decode($this->getModelCategories($req))->data;
                                foreach($subcategories as $subcategory){
                                    if(!$subcategory->hasParts){
                                        $req = new Request([
                                            'catalogId' => $brand,
                                            'carId' => $car->id,
                                            'groupId' => $subcategory->uid
                                        ]);
                                        
                                        $others = json_decode($this->getModelCategories($req))->data;
                                        foreach($others as $other){
                                            if(!$other->hasParts) continue;
                                            $req = new Request([
                                                'catalogId' => $brand,
                                                'carId' => $car->id,
                                                'groupId' => $other->uid
                                            ]);

                                            Log::info('-- Other Category --');
                                            Log::info($other->name);
                    
                                            $list = $this->getModelParts($req);

                                        }
                                    }else if($subcategory->hasParts){
                                        $req = new Request([
                                            'catalogId' => $brand,
                                            'carId' => $car->id,
                                            'groupId' => $subcategory->uid
                                        ]);
    
                                        Log::info('-- Subcategory --');
                                        Log::info($subcategory->name);
                
                                        $list = $this->getModelParts($req);
                                    }
                                }
                            }else{
                                $req = new Request([
                                    'catalogId' => $brand,
                                    'carId' => $car->id,
                                    'groupId' => $subcategory->uid
                                ]);

                                Log::info('-- Category --');
                                Log::info($category->name);
        
                                $list = $this->getModelParts($req);
                            }
                        }
                
                    }
                }
                
            }
            
        }
        
        Log::info('FINISHED');
        return 'Finished';
    }
    
    public function deleteSellerCar(Request $request){
        $validator = Validator::make($request->all(),[
            'id' => 'required|integer'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }
        
        $car = auth()->user()->cars()->where('id',$request->id)->first();

        if(!$car) return $this->toJson(null, 400, [
            'msg' => 'Car not found'
        ]);

        if(!auth()->user()->cars()->where('brand', $car->brand)->first()){
            Brand::where('id', $car->brand)->update([
                'favorite' => 0
            ]);
        }

        $car->delete();


        return $this->toJson(
            ['msg' => 'Car deleted'],
            200
        );
    }
    
}
