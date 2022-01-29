<?php

namespace App\Http\Controllers\Api;

use App\Models\product;
use App\Models\eventtracker;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Image;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Wishlist;
use App\Models\Amenitie;
use App\Models\ProductAmenties;
use App\Models\UserProductCount;
use App\Models\Product_img;
use App\Models\area_locality;
use App\Models\Product_Comparision;
use App\Models\property_room_pivot;
use App\Models\invoices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Input;
use App\Http\Resources\API\Product\ProductListResource;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = product::where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->with('UserDetail','product_img','Property_Type')->orderBy('id', 'desc')->get();
        return response()->json([
            'data' =>$data,
        ], 201);
    }
    public function product_city_details()
    {
     $chattarpur_id=area_locality::select('locality_id')->where(['locality' =>'CHATTARPUR','status' => '1'])->first();
     $locality_data=product::where(['locality_id' =>$chattarpur_id['locality_id'], 'delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'asc')->get();
      $Chattarpur_data = $locality_data->groupBy('locality_id')->map(function ($row) {return $row->count();});
      $chattarpur=[];
      if(count($Chattarpur_data)>0){
        $chattarpur_array=['city'=>'CHATTARPUR','chattarpur_count'=>$Chattarpur_data['1281']];
         array_push($chattarpur,$chattarpur_array);
    }
    // all data for delhi location 
        $product_data=product::where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'asc')->get(); 
        $grouped = $product_data->groupBy('state_id')->map(function ($row) {return $row->count();});
       $data=product::select('id','state_id')->where(['state_id'=> 1,'delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->groupBy('state_id')->havingRaw('COUNT(*) > 0')->orderBy('id', 'asc')->get(); 

        $city_data=[];
        foreach ($data as $key => $value) {
            $city_count= $grouped[$value['state_id']];
                $count=['id'=>$value['id'],'city'=>'DELHI','city_count'=>$city_count];
                array_push($city_data,$count);
        }
        return response()->json([
            'data'=>$city_data,
            'Chattarpur_data'=> $chattarpur 
        ], 200);
    }

    public function product_category_details()
    {
        $product_data=product::where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'asc')->get(); 
        $grouped = $product_data->groupBy('type')->map(function ($row) {return $row->count();});

      $data=product::select('id','type')->with('Property_Type')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->groupBy('type')->havingRaw('COUNT(*) > 0')->orderBy('id', 'asc')->get(); 
// return $data;
        $category_data=[];
        foreach ($data as $key => $value) {
            $category_count= $grouped[$value['type']];
                $count=['id'=>$value['id'],'category'=>$value,'category_count'=>$category_count];
                array_push($category_data,$count);
        }
        return response()->json([
            'data'=>$category_data 
        ], 200);
    }

    public function index_featured()
    {
        $data = product::with('UserDetail','product_img','Property_Type','product_state','product_locality','Property_area_unit')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'desc')->take(9)->get();
        return response()->json([
            'data' =>$data,
        ], 201);
    }
    public function index_featured_wishlist()
    {
       $user_id = Auth::user()->id;
        $product = product::with('UserDetail','Single_wishlist','product_img','product_comparision','Property_Type','product_state','product_locality','Property_area_unit')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'desc')->take(9)->get();
            return response()->json([
            'data' =>$product,
          ], 201);
    }

    public function product_list_featured()
    {
        $data = product::with('UserDetail','product_img','Property_Type')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'desc')->get();
        return response()->json([
            'data' =>$data,
        ], 201);
    }

 public function product_listing_wishlist()
    {
        $user_id = Auth::user()->id;
        $product = product::with('UserDetail','product_img','product_comparision','Property_Type')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'desc')->get();

        // return  $product;
        $Wishlist=Wishlist::where('user_id', $user_id)->orderBy('id', 'asc')->get();
        $productcount = count($product);
        $wishlistcount = count($Wishlist);

       $productArray = json_decode(json_encode($product), true);
       $WishlistArray = json_decode(json_encode($Wishlist), true);

        // return  $productArray;
       
       // wishlist check with product id nd wishlist id
       for ($i=0; $i < $productcount; $i++) {    
            for ($j=0; $j < $wishlistcount; $j++) { 
                if($productArray[$i]['id']==$WishlistArray[$j]['product_id']){
                    $addWishlist="true";
                    array_push($productArray[$i],$addWishlist);
                }
            }
        }
        if($productArray){
            return response()->json([
            'data' =>$productArray,
          ], 201);
        }else{
            return response()->json([
            'data' =>$product,
          ], 201);
        }
    }
    public function search_pro_type(Request $request)
    {
        // return $request->input('id');
        $product = product::with('UserDetail','Property_Type','product_img')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes', 'type'=> $request->id])->get();
        return response()->json([
            'data' =>$product,
          ], 201);
        
    }
    
    public function search_pro_type_login(Request $request)
    {
      try{
          $product = product::with('UserDetail','Property_Type','product_img','Property_Type','product_comparision')->where(['delete_flag'=> '0','draft'=> '0','type'=> $request->id,'order_status'=> '0', 'enabled' => 'yes'])->get();
          return response()->json([
              'data' =>$product,
            ], 201);
        }catch(\Exception $e) {
              return $this->getExceptionResponse($e);
        } 
        
    }
    public function User_propertysearchlist(Request $request)
    {
      try{
        $current_date= Carbon::now()->format('Y-m-d H:i:s');
        $product = product::with('product_img_listing','listing_wishlist','listing_pro_comp','Property_Type','pro_user_details','Property_area_unit','product_state','product_locality')->select('products.id as product_id','products.build_name','products.user_id','products.rent_availability','products.sale_availability','products.state_id','products.locality_id','products.sale_availability','products.type','products.expected_pricing','products.expected_rent','products.bedroom','products.area_unit','products.bathroom','invoices.plan_type','invoices.plan_name','order_plan_features.plan_created_at','order_plan_features.client_visit_priority as priority',DB::raw(' order_plan_features.product_plans_days -DATEDIFF("'.$current_date.'",order_plan_features.plan_created_at)  as "plans_day_left"'))
            ->leftjoin('invoices','invoices.property_uid','=','products.product_uid')
            ->leftjoin('order_plan_features','order_plan_features.order_id','=','invoices.order_id')
            ->where(['rent_availability'=>'1','delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])
           ->search($request)
           ->orderBy('plans_day_left','asc')
            ->paginate(8);
            return response()->json([
              'data'=> $product,
              'status'=>200
            ], 201);
        }catch(\Exception $e) {
              return $this->getExceptionResponse($e);
        }      
    }
    public function propertysearch_list(Request $request)
    {
      
      try{
        $current_date= Carbon::now()->format('Y-m-d H:i:s');
        $product = product::with('product_img_listing','Property_Type','pro_user_details','Property_area_unit','product_state','product_locality')->select('products.id as product_id','products.build_name','products.user_id','products.rent_availability','products.sale_availability','products.state_id','products.locality_id','products.sale_availability','products.type','products.expected_pricing','products.expected_rent','products.bedroom','products.area_unit','products.bathroom','invoices.plan_type','invoices.plan_name','order_plan_features.plan_created_at','order_plan_features.client_visit_priority as priority',DB::raw(' order_plan_features.product_plans_days -DATEDIFF("'.$current_date.'",order_plan_features.plan_created_at)  as "plans_day_left"'))
            ->leftjoin('invoices','invoices.property_uid','=','products.product_uid')
            ->leftjoin('order_plan_features','order_plan_features.order_id','=','invoices.order_id')
            ->where(['rent_availability'=>'1','delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])
           ->search($request)
           ->orderBy('plans_day_left','asc')
            ->paginate(8);
            return response()->json([
              'data'=> $product,
              'status'=>200
            ]);
        }catch(\Exception $e) {
              return $this->getExceptionResponse($e);
        } 
        
    }
    public function feature_property()
    {
        $product = product::with('UserDetail','product_img','Property_area_unit')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->where('view_counter', '>=',1)->orderBy('view_counter', 'desc')->take(4)->get();

        return response()->json([
            'data' =>$product,
          ]);
        
    }
    public function Recently_view()
    {
        $product = product::with('UserDetail','Property_Type','Property_area_unit')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->where('view_counter', '>=',1)->orderBy('view_counter', 'desc')->take(4)->get();

        return response()->json([
            'data' =>$product,
          ], 201);
        
    }

      public function search_prod_by_city(Request $request){
        try{
             $locality_id = $request->locality_id;
            $productSimilar=product::with('UserDetail','Property_area_unit','Property_Type','product_img','product_state','product_locality')->where(['locality_id'=> $locality_id,'delete_flag'=>0,'draft'=>'0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'desc')->take(4)->get();
                return response()-> json([
                    'data' => $productSimilar,
                ]);
          }catch(\Exception $e) {
              return $this->getExceptionResponse($e);
        } 
      }
     
     public function loginSimilarproperty(Request $request)
     {
        try{
             $locality_id = $request->locality_id;  
              $productArray = product::with('UserDetail','Property_area_unit','product_img','product_comparision','Property_Type','product_state','product_locality','Single_wishlist','Property_area_unit')->where(['locality_id'=> $locality_id,'delete_flag'=> 0,'draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->orderBy('id', 'desc')->take(4)->get();
              return response()->json([
              'data' =>$productArray,
            ], 201);
         }catch(\Exception $e) {
              return $this->getExceptionResponse($e);
        }     

    }

     public function product_login_see(Request $request){
        try{
            $request->validate([
                'id' => 'required',
            ]);
            $prod_id = $request->id;
            $user_id = Auth::user()->id;
            $product = product::where(['delete_flag'=> '0','enabled' => 'yes', 'draft'=> '0','id'=>$prod_id])->with('amenities','Property_Type','product_img','product_comparision','Single_wishlist','UserDetail','product_state','product_district','product_locality','product_sub_locality','Property_area_unit','willing_rent_out','maintenance_condition','aggeement_type','ageement_duration')->orderBy('id', 'desc')->first();

            product::where('id', $prod_id)->update(['view_counter' => DB::raw('view_counter + 1')]);
                return response()-> json([
                    'data' => $product,
                ]);
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }    

     }

     public function search_prod_by_id(Request $request){
        try{
            $request->validate([
                'id' => 'required',
            ]);
            $prod_id = $request->id;
            $product_data = product::where((['delete_flag'=> '0','enabled' => 'yes','draft'=> '0','id'=>$prod_id,'order_status'=> '0']))->with('amenities','Property_Type','product_img','UserDetail','product_state','product_locality','property_room','Property_area_unit','willing_rent_out','maintenance_condition','aggeement_type','ageement_duration')->first();

        // increase product count 
            product::where('id', $prod_id)->update(['view_counter' => DB::raw('view_counter + 1')]);

            return response()-> json([
                'data' => $product_data,
            ]);
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
       }    

     }


     public function Login_search_home(Request $request)
    {
        $user_id = Auth::user()->id;
        $product = product::with('UserDetail','product_img','product_comparision','Property_Type')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->search($request)->orderBy('id', 'desc')->get();
        $Wishlist=Wishlist::where('user_id', $user_id)->orderBy('id', 'asc')->get();
        $productcount = count($product);
        $wishlistcount = count($Wishlist);

       $productArray = json_decode(json_encode($product), true);
       $WishlistArray = json_decode(json_encode($Wishlist), true);
       
       // wishlist check with product id nd wishlist id
       for ($i=0; $i < $productcount; $i++) {    
            for ($j=0; $j < $wishlistcount; $j++) { 
                if($productArray[$i]['id']==$WishlistArray[$j]['product_id']){
                    $addWishlist="true";
                    array_push($productArray[$i],$addWishlist);
                }
            }
        }
        if($productArray){
            return response()->json([
            'data' =>$productArray,
          ], 201);
        }else{
            return response()->json([
            'data' =>$product,
          ], 201);
        }
    }

     public function search_func(Request $request){
        $product = product::with('UserDetail','product_img','Property_Type')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0', 'enabled' => 'yes'])->search($request)->get();
        return response()->json([
            'data' =>$product,
          ], 201);
     }
    public function city_search_func(Request $request){
        $request->validate([
            'city' => 'required'
        ]);
        $data = product::Where(['city' =>  $request->city,'delete_flag' => 0,'draft'=> '0','order_status'=> '0'])->with('UserDetail','product_img','Property_Type')->get();

        return response()-> json([
            'data' => $data,
        ]);
     }
    public function city_search_login_uesr(Request $request){
        $request->validate([
            'city' => 'required'
        ]);
        $user_id = Auth::user()->id;
        $product = product::Where(['city' =>  $request->city,'delete_flag' => 0,'draft'=> '0','order_status'=> '0'])->with('UserDetail','product_img','product_comparision','Property_Type')->get();

        $Wishlist=Wishlist::where('user_id', $user_id)->orderBy('id', 'asc')->get();
        $productcount = count($product);
        $wishlistcount = count($Wishlist);

       $productArray = json_decode(json_encode($product), true);
       $WishlistArray = json_decode(json_encode($Wishlist), true);
        // dd($productArray);
       
       // wishlist check with product id nd wishlist id
       for ($i=0; $i < $productcount; $i++) {    
            for ($j=0; $j < $wishlistcount; $j++) { 
                if($productArray[$i]['id']==$WishlistArray[$j]['product_id']){
                    $addWishlist="true";
                    array_push($productArray[$i],$addWishlist);
                }
            }
        }
        if($productArray){
            return response()->json([
            'data' =>$productArray,
          ], 201);
        }else{
            return response()->json([
            'data' =>$product,
          ], 201);

        }

        // return response()-> json([
        //     'data' => $data,
        // ]);
     }

    public function product_index(){

        $mer=[];
        $max_id = DB::table('products')->where('id', DB::raw("(select max(`id`) from products)"))->value("id");
        for ($id_var = 0; $id_var<=$max_id; $id_var++){
        for ($variable = $id_var; $variable == $id_var; $variable++)
            {
                $product_id_func = product::where('id','=', $variable)->get()->toArray();
                $userid = DB::table('products')->select('user_id')->where("id", $variable)->value("value");
                $tableq = DB::table('users')->select('name','email','profile_pic')->where('id', $userid)->get()->toArray();
                $merged1 = array_merge($product_id_func, $tableq);
            }
        $mer = array_merge($mer, $merged1);
        }
        return response()-> json([
            'data' => $mer,
        ]);

    }
    

    public function first(Request $request){
     
      $data1=$request->form_step1;
      $data2=$request->form_step2;
      $data3=$request->form_step3;
      $data4=$request->form_step4;

      if($data1['draft_form_id']>0){
        $product = product::where('id', $data1['draft_form_id'])->with('amenities')->first();
        $data = product::find($data1['draft_form_id']);

        $usertype = Auth::user()->usertype;
            
        $product_id=$data1['draft_form_id'];
       $user_id = Auth::user()->id;
        $video_link=str_replace("https://www.youtube.com/watch?v=","",$data4['video_link']);
         $addtional_room=implode(',',$request->rooms);
          $product_uid= rand (1000,9999).time();
        // inserted images functionalty
        $DB_img=Product_img::where('user_id', $user_id)->where('product_id',
            $product_id)->get();
        $db_img_length=count($DB_img);

       // product images functionalty
        if($db_img_length<5){
            $product_image= $request->input('images');
            $Product_img_length=count($product_image);   
            if($Product_img_length>0){
                foreach ($product_image as $value) {
                    
                    $base64_image = $value; // your base64 encoded
                    @list($type, $file_data) = explode(';', $base64_image);
                    @list(, $file_data) = explode(',', $file_data);
                    $imageName = 'product_image_file/IMAGE'.Str::random(30).'.'.'png';
                    Storage::disk('public')->put($imageName, base64_decode($file_data));
                        $Product_images_data = [
                            'user_id' => $user_id,
                            'product_id' => $product_id,
                            'image' =>$imageName 
                        ];
                    Product_img::create($Product_images_data);
                }
                
            }
        }

        // $data->product_uid= $product_uid;
        // $data->user_id= $user_id;
        $data->build_name = $data1['property_name'];
        $data->type = $data1['property_type'];
        $data->bedroom = $data1['bedrooms'];
        $data->bathroom = $data1['bathrooms'];
        $data->balconies =  $data1['balconies'];
        $data->area = $data1['property_area'];
        $data->area_unit =$data1['area_unit'];
        $data->property_detail = $data1['property_desc'];

        // step 2
        $data->address = $data2['address'];
        $data->city = $data2['city'];
        $data->locality =$data2['locality'];
        $data->nearest_landmark = $data2['nearest_place'];
        $data->map_latitude = $data2['map_latitude'];
        $data->map_longitude = $data2['map_longitude'];
        $data->pincode= $data2['pincode'];

        // step 3
        $data->additional_rooms=$addtional_room;
        $data->additional_rooms_status=$data3['additional_rooms'];
        $data->availability_condition = $data3['availability_condition'];
        $data->rera_registration_status = $data3['rera_registration_status'];
        $data->facing_towards = $data3['facing_towards'];
        $data->furnishing_status = $data3['furnishings'];
        $data->additional_parking_status = $data3['reserved_parking'];
        $data->parking_covered_count = $data3['parking_covered_count'];
        $data->parking_open_count = $data3['parking_open_count'];
        $data->total_floors = $data3['total_floors'];
        $data->property_on_floor = $data3['property_floor'];
        $data->buildyear = $data3['year_built'];
        $data->possession_by = $data3['possession_by'];

        // step 4
        $data->inc_electricity_and_water_bill = $data4['electricity_water'];
        $data->expected_pricing = $data4['expected_pricing'];
        $data->sale_availability =1;
        $data->maintenance_charge_status = $data4['maintenance_charge_status'];
        $data->maintenance_charge = $data4['maintenance_charge'];
        $data->maintenance_charge_condition =$data4['maintenance_charge_condition'];
        $data->price_negotiable =$data4['price_negotiable'];
        $data->negotiable_status=$data4['price_negotiable_status'];
        $data->ownership=$data4['ownership'];
        $data->tax_govt_charge = $data4['tax_govt_charge'];
        $data->draft=$data4['draft_form_id'];
        $data->video_link = $video_link;
        $data->enabled = 'yes';
        $data->save();
        $product_id=$data1['draft_form_id'];
       $user_id = Auth::user()->id;
       // check amenties 
       $amenities_check=$request->amenties;
       $length=count($amenities_check);
       if($length>0){
            foreach ($amenities_check as $Check_amenities) {
                $ProductAmenties = [
                    'amenties' =>$Check_amenities,
                    'user_id' => $user_id,
                    'product_id' => $product_id
                ];
                
                $ProductAmenties_Count = ProductAmenties::where('user_id',$user_id)->where('product_id',$product_id)->where('amenties',$ProductAmenties['amenties'])->get();
                $count = count($ProductAmenties_Count);
                
                if($count==0){
                ProductAmenties::create($ProductAmenties);
                }
            }
        }
        return response() -> json([
                'message' => 'Successfully Updated',
                'last_id' => $data1['draft_form_id'],
        ]);
      }else{
        $user_id = Auth::user()->id;
        $video_link=str_replace("https://www.youtube.com/watch?v=","",$data4['video_link']);

         $addtional_room=implode(',',$request->rooms);
           
         $product_uid= rand (1000,9999).time();

            $product_data = new Product([
            'user_id' => $user_id ,
            'product_uid' => $product_uid,
            'build_name' =>$data1['property_name'],
            'type' =>$data1['property_type'],
            'bedroom' => $data1['bedrooms'],
            'bathroom' => $data1['bathrooms'],
            'balconies' => $data1['balconies'],
            'area' => $data1['property_area'],
            'area_unit' => $data1['area_unit'],
            'property_detail' =>$data1['property_desc'],

            // step 2
            'address' => $data2['address'],
            'city' => $data2['city'],
            'locality' =>  $data2['locality'],
            'nearest_landmark' =>  $data2['nearest_place'],
            'map_latitude' => $data2['map_latitude'],
            'map_longitude' => $data2['map_longitude'],
            'pincode' =>  $data2['pincode'],

            // step 3
            'additional_rooms_status' => $data3['additional_rooms'],
            'additional_rooms' => $addtional_room,
            'availability_condition' => $data3['availability_condition'],
            'rera_registration_status' =>$data3['rera_registration_status'],
            'facing_towards'=>$data3['facing_towards'],
            'furnishing_status'=>$data3['furnishings'],
            'additional_parking_status' =>$data3['reserved_parking'],
            'parking_covered_count' => $data3['parking_covered_count'],
            'parking_open_count' =>$data3['parking_open_count'],
            'property_on_floor' =>$data3['property_floor'],
            'total_floors' =>$data3['total_floors'],
            'buildyear' =>$data3['year_built'],
            'possession_by' =>$data3['possession_by'],

            // step 4
            'inc_electricity_and_water_bill' => $data4['electricity_water'],
            'expected_pricing' =>$data4['expected_pricing'],
            'sale_availability' =>1,
            'maintenance_charge_status' =>$data4['maintenance_charge_status'],
            'maintenance_charge' =>$data4['maintenance_charge'],
            'maintenance_charge_condition' =>$data4['maintenance_charge_condition'],
            'price_negotiable' =>$data4['price_negotiable'],
            'negotiable_status' =>$data4['price_negotiable_status'],
            'ownership' =>$data4['ownership'],
            'tax_govt_charge' =>$data4['tax_govt_charge'],
            'video_link'=>$video_link,
            'draft' =>$data4['draft_form_id'],
            'enabled' => 'yes',
            // 'possession_by' => $request->possession_by ,
            // 'brokerage_charges' => $request->brokerage_charges ,
            ]);

            $user_type = Auth::user()->usertype;

            if ($user_type == 1)
                return response()->json([
                    'message' => 'Unauthorised User',
                ], 201);

            $product_data->save();
            $lastid=$product_data->id;
            eventtracker::create(['symbol_code' => '8', 'event' => Auth::user()->name.' created a new property listing for rent.']);

       
         $product_id=$product_data->id;
         // inserted images functionalty
        $DB_img=Product_img::where('user_id', $user_id)->where('product_id',
            $product_id)->get();
        $db_img_length=count($DB_img);
        // product images functionalty
        if($db_img_length<5){
           $product_image= $request->input('images');
           $Product_img_length=count($product_image);   
            if($Product_img_length>0){
                foreach ($product_image as $value) {   
                    $base64_image = $value; // your base64 encoded
                    @list($type, $file_data) = explode(';', $base64_image);
                    @list(, $file_data) = explode(',', $file_data);
                    $imageName = 'product_image_file/IMAGE'.Str::random(30).'.'.'png';
                    Storage::disk('public')->put($imageName, base64_decode($file_data));
                        $Product_images_data = [
                            'user_id' => $user_id,
                            'product_id' => $product_id,
                            'image' =>$imageName 
                        ];
                    Product_img::create($Product_images_data);
                }
            }
        }

           $amenities=$request->amenties;
           $length=count($amenities);
            foreach ($amenities as $amenties_value) {   
             $ProductAmenties = [
                    'amenties' =>$amenties_value,
                    'user_id' => $user_id,
                    'product_id' => $product_id
                ];
                ProductAmenties::create($ProductAmenties);

            }
            return response()->json([
                'message' => 'Successfully created',
                'last_id' => $lastid,
            ], 201);
      }

    }

    public function insert_product_rent(Request $request)
    {
      $data1=$request->form_step1;
      $data2=$request->form_step2;
      $data3=$request->form_step3;
      $data4=$request->form_step4;
    //   return $data1;

      if($data1['draft_form_id']>0){
        $product = product::where('id', $data1['draft_form_id'])->with('amenities')->first();
        $data = product::find($data1['draft_form_id']);

        $usertype = Auth::user()->usertype;
            
        $product_id=$data1['draft_form_id'];
       $user_id = Auth::user()->id;
        $video_link=str_replace("https://www.youtube.com/watch?v=","",$data4['video_link']);
    
          $product_uid= rand (1000,9999).time();
        // inserted images functionalty
        $DB_img=Product_img::where('user_id', $user_id)->where('product_id',
            $product_id)->get();
        $db_img_length=count($DB_img);

       // product images functionalty
        if($db_img_length<5){
            $product_image= $request->input('images');
            $Product_img_length=count($product_image);   
            if($Product_img_length>0){
                foreach ($product_image as $value) {
                    
                    $base64_image = $value; // your base64 encoded
                    @list($type, $file_data) = explode(';', $base64_image);
                    @list(, $file_data) = explode(',', $file_data);
                    $imageName = 'product_image_file/IMAGE'.Str::random(30).'.'.'png';
                    Storage::disk('public')->put($imageName, base64_decode($file_data));
                        $Product_images_data = [
                            'user_id' => $user_id,
                            'product_id' => $product_id,
                            'image' =>$imageName 
                        ];
                    Product_img::create($Product_images_data);
                }
                
            }
        }

        // $data->product_uid= $product_uid;
        // $data->user_id= $user_id;
        $data->build_name = $data1['property_name'];
        $data->type = $data1['property_type'];
        $data->bedroom = $data1['bedrooms'];
        $data->bathroom = $data1['bathrooms'];
        $data->balconies =  $data1['balconies'];
        $data->area = $data1['property_area'];
        $data->area_unit =$data1['area_unit'];
        $data->property_detail = $data1['property_desc'];

        // step 2
        $data->address = $data2['address'];
        $data->address_details = $data2['address_details'];
        $data->state_id = $data2['city'];
        $data->district_id = $data2['district_id'];
        $data->locality_id =$data2['locality'];
        $data->sub_locality_id =$data2['sub_locality'];
        $data->map_latitude = $data2['map_latitude'];
        $data->map_longitude = $data2['map_longitude'];

        // step 3
        // $data->additional_rooms=$addtional_room;
        $data->additional_rooms_status=$data3['additional_rooms'];
           // return $request->rooms;
          if($data3['additional_rooms']== 1 ){
                        // check amenties
                $addtional_rooms=$request->rooms;
                $length=count($addtional_rooms);
                 $room_delete= property_room_pivot::where('product_id',$product_id)->delete();
                if($length>0){
                    foreach ($addtional_rooms as $rooms) {
                        $room_data = [
                            'room_id' =>$rooms,
                            'product_id' => $product_id
                        ];
                        property_room_pivot::create($room_data);
                    }
                }
            }
        $data->agreement_type=$data3['agreement_type'];
        $data->duration_of_rent_aggreement=$data3['agreement_duration'];
        $data->available_for = $data3['available_date'];
        $data->facing_towards = $data3['facing_towards'];
        $data->furnishing_status = $data3['furnishings'];
        $data->month_of_notice = $data3['notice_month'];
        $data->additional_parking_status = $data3['reserved_parking'];
        $data->parking_covered_count = $data3['parking_covered_count'];
        $data->parking_open_count = $data3['parking_open_count'];
        $data->total_floors = $data3['total_floors'];
        $data->property_on_floor = $data3['property_floor'];
        $data->willing_to_rent_out_to = $data3['willing_to_rent'];
        $data->buildyear = $data3['year_built'];

        // step 4
        $data->inc_electricity_and_water_bill = $data4['electricity_water'];
        $data->expected_rent = $data4['expected_rent'];
        $data->rent_availability =1;
        $data->maintenance_charge_status = $data4['maintenance_charge_status'];
        $data->maintenance_charge = $data4['maintenance_charge'];
        $data->maintenance_charge_condition =$data4['maintenance_charge_condition'];
        $data->price_negotiable =$data4['price_negotiable'];
        $data->negotiable_status=$data4['price_negotiable_status'];
        $data->security_deposit=$data4['security_deposit'];
        $data->draft=$data4['draft_form_id'];
        $data->rent_cond =1;
        $data->video_link = $video_link;
        $data->save();
        $product_id=$data1['draft_form_id'];
       $user_id = Auth::user()->id;
       // check amenties 
       $amenities_check=$request->amenties;
       $length=count($amenities_check);
       if($length>0){
            foreach ($amenities_check as $Check_amenities) {
                $ProductAmenties = [
                    'amenties' =>$Check_amenities,
                    'user_id' => $user_id,
                    'product_id' => $product_id
                ];
                
                $ProductAmenties_Count = ProductAmenties::where('user_id',$user_id)->where('product_id',$product_id)->where('amenties',$ProductAmenties['amenties'])->get();
                $count = count($ProductAmenties_Count);
                
                if($count==0){
                ProductAmenties::create($ProductAmenties);
                }
            }
        }
        return response() -> json([
                'message' => 'Successfully Updated',
                'last_id' => $data1['draft_form_id'],
        ]);
      }else{
        $user_id = Auth::user()->id;
        $video_link=str_replace("https://www.youtube.com/watch?v=","",$data4['video_link']);

           
         $product_uid= rand (1000,9999).time();

            $product_data = [
            'user_id' => $user_id ,
            'product_uid' => $product_uid,
            'build_name' =>$data1['property_name'],
            'type' =>$data1['property_type'],
            'bedroom' => $data1['bedrooms'],
            'bathroom' => $data1['bathrooms'],
            'balconies' => $data1['balconies'],
            'area' => $data1['property_area'],
            'area_unit' => $data1['area_unit'],
            'property_detail' =>$data1['property_desc'],

            // step 2
            'address_details' =>  $data2['address_details'],
            'address' => $data2['address'],
            'state_id' => $data2['city'],
            'district_id' =>$data2['district_id'],
            'locality_id' =>  $data2['locality'],
            'sub_locality_id' => $data2['sub_locality'],
            'map_latitude' => $data2['map_latitude'],
            'map_longitude' => $data2['map_longitude'],

            // step 3
            'additional_rooms_status' => $data3['additional_rooms'],
            // 'additional_rooms' => $addtional_room,
            'agreement_type' =>$data3['agreement_type'],
            'duration_of_rent_aggreement' =>$data3['agreement_duration'] ,
            'available_for' =>$data3['available_date'],
            'facing_towards'=>$data3['facing_towards'],
            'furnishing_status'=>$data3['furnishings'],
            'month_of_notice'=>$data3['notice_month'],
            'additional_parking_status' =>$data3['reserved_parking'],
            'parking_covered_count' => $data3['parking_covered_count'],
            'parking_open_count' =>$data3['parking_open_count'],
            'property_on_floor' =>$data3['property_floor'],
            'total_floors' =>$data3['total_floors'],
            'willing_to_rent_out_to' =>$data3['willing_to_rent'],
            'buildyear' =>$data3['year_built'],

            // step 4
            'inc_electricity_and_water_bill' => $data4['electricity_water'],
            'expected_rent' =>$data4['expected_rent'],
            'rent_availability' =>1,
            'maintenance_charge_status' =>$data4['maintenance_charge_status'],
            'maintenance_charge' =>$data4['maintenance_charge'],
            'maintenance_charge_condition' =>$data4['maintenance_charge_condition'],
            'price_negotiable' =>$data4['price_negotiable'],
            'negotiable_status' =>$data4['price_negotiable_status'],
            'security_deposit' =>$data4['security_deposit'],
            // 'tax_govt_charge' =>$data4['tax_govt_charge'],
            'rent_cond' =>1,
            'video_link'=>$video_link,
            'draft' =>$data4['draft_form_id'],
            // 'possession_by' => $request->possession_by ,
            // 'brokerage_charges' => $request->brokerage_charges ,
            ];

            $user_type = Auth::user()->usertype;

            if ($user_type == 1)
                return response()->json([
                    'message' => 'Unauthorised User',
                ], 201);
                // return $product_data;
           $product_db_result=product::create($product_data);
            $lastid=$product_db_result->id;
            eventtracker::create(['symbol_code' => '8', 'event' => Auth::user()->name.' created a new property listing for rent.']);

       
         $product_id=$lastid;
          if($data3['additional_rooms']== 1 ){
                        // check amenties
              $addtional_rooms=$request->rooms;
              $length=count($addtional_rooms);
              if($length>0){
                $room_delete= property_room_pivot::where('product_id',$product_id)->delete();
                  foreach ($addtional_rooms as $rooms) {
                      $room_data = [
                          'room_id' =>$rooms,
                          'product_id' => $product_id
                      ];
                      property_room_pivot::create($room_data);
                  }
              }
            }
         // inserted images functionalty
        $DB_img=Product_img::where('user_id', $user_id)->where('product_id',
            $product_id)->get();
        $db_img_length=count($DB_img);
        // product images functionalty
        if($db_img_length<5){
           $product_image= $request->input('images');
           $Product_img_length=count($product_image);   
            if($Product_img_length>0){
                foreach ($product_image as $value) {   
                    $base64_image = $value; // your base64 encoded
                    @list($type, $file_data) = explode(';', $base64_image);
                    @list(, $file_data) = explode(',', $file_data);
                    $imageName = 'product_image_file/IMAGE'.Str::random(30).'.'.'png';
                    Storage::disk('public')->put($imageName, base64_decode($file_data));
                        $Product_images_data = [
                            'user_id' => $user_id,
                            'product_id' => $product_id,
                            'image' =>$imageName 
                        ];
                    Product_img::create($Product_images_data);
                }
            }
        }

           $amenities=$request->amenties;
           $length=count($amenities);
            foreach ($amenities as $amenties_value) {   
             $ProductAmenties = [
                    'amenties' =>$amenties_value,
                    'user_id' => $user_id,
                    'product_id' => $product_id
                ];
                ProductAmenties::create($ProductAmenties);

            }
            return response()->json([
                'message' => 'Successfully created',
                'last_id' => $lastid,
            ], 201);
      }
       
    }

    public function delete_product (Request $request){
        $request->validate([
            'product_id' => 'required',
        ]);
        $product_userid = product::where('id', $request->product_id)->value('user_id');
        $user_id = Auth::user()->id;

        if ($user_id != $product_userid)
            return response()->json([
                'message' => 'Unauthorised User',
            ], 401);

         // product img delete
       //  $product_img=Product_img::select('image')->where('product_id', $request->product_id)->get();
       //  $img_lenght=count($product_img);

       // if($img_lenght>0){
       //      for ($i=0; $i<$img_lenght ; $i++) { 
       //       $image_path='storage/'.$product_img[$i]['image'];
       //        unlink($image_path);
       //      }
       //  }
        // product::where('id', $request->product_id)->delete();

        product::where('id', $request->product_id)->update(['delete_flag' => 1 ]);

        // propduct count inactive
        UserProductCount::where('product_id', $request->product_id)->update(['status' => '0']);

        // wishlist inactive
        Wishlist::where('product_id', $request->product_id)->update(['status' => '0']);

        // property comaprision
        Product_Comparision::where('product_id', $request->product_id)->update(['status' => '0']);

        return response()->json([
            'message' => 'Successfully deleted Product',
        ], 201);

    }

    public function agent_properties()
    {
        try{

            $user_id = Auth::user()->id;

            $data = product::with('product_img')->where('user_id', $user_id)->where(['delete_flag'=> '0','draft'=> '0'])->orderBy('id', 'desc')->paginate(2);
            //$tableq = DB::table('users')->select('id','name','email','profile_pic')->get();
            return response()->json([
                //'users'=> $tableq,
                'data' =>$data,
            ], 200);
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }   

    }
    public function Draft_properties()
    {
        try{
            $user_id = Auth::user()->id;
            $data = product::with('product_img')->where(['delete_flag'=> '0','draft'=> '1','order_status'=> '0','user_id'=>  $user_id])->orderBy('id', 'desc')->paginate(2);
            return response()->json([
                'data' =>$data,
            ], 200);

        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        } 

    }

    public function dashboard_indexer(){

        $user_id = Auth::user()->id;
        $views = product::select('view_counter')->where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0','user_id'=>  $user_id])->get()->sum('view_counter');

        $property_count = product::where(['delete_flag'=> '0','draft'=> '0','order_status'=> '0','user_id'=>  $user_id])->get()->count();

       $wishlist_data= Wishlist::where(['status'=> '1','user_id' =>$user_id])->get()->count();


        return response()->json([
            'view_count' => $views,
            'property_count' => $property_count,
            'wishlist_count'  =>$wishlist_data,
        ], 200);
    }
    public function update_Sales_product(Request $request){
      $data1=$request->form_step1;
      $data2=$request->form_step2;
      $data3=$request->form_step3;
      $data4=$request->form_step4;
        $request -> validate([
            'id' => 'required'
        ]);
        $product = product::where('id', $request->id)->with('amenities')->first();

        $data = product::find($request->id);
        $usertype = Auth::user()->usertype;
            
        $product_id=$request->id;
       $user_id = Auth::user()->id;
        $video_link=str_replace("https://www.youtube.com/watch?v=","",$data4['video_link']);
       
         $addtional_room=implode(',',$request->rooms);
         // return $additional_rooms;
        // inserted images functionalty
        $DB_img=Product_img::where('user_id', $user_id)->where('product_id', $product_id)->get();
        $db_img_length=count($DB_img);

       // product images functionalty
        if($db_img_length<5){
            $product_image= $request->input('images');
            $Product_img_length=count($product_image);   
            if($Product_img_length>0){
                foreach ($product_image as $value) {
                    
                    $base64_image = $value; // your base64 encoded
                    @list($type, $file_data) = explode(';', $base64_image);
                    @list(, $file_data) = explode(',', $file_data);
                    $imageName = 'product_image_file/IMAGE'.Str::random(30).'.'.'png';
                    Storage::disk('public')->put($imageName, base64_decode($file_data));
                        $Product_images_data = [
                            'user_id' => $user_id,
                            'product_id' => $product_id,
                            'image' =>$imageName 
                        ];
                    Product_img::create($Product_images_data);
                }
                
            }
        }

        // $data->product_uid= $product_uid;
        // $data->user_id= $user_id;
        $data->build_name = $data1['property_name'];
        $data->type = $data1['property_type'];
        $data->bedroom = $data1['bedrooms'];
        $data->bathroom = $data1['bathrooms'];
        $data->balconies =  $data1['balconies'];
        $data->area = $data1['property_area'];
        $data->area_unit =$data1['area_unit'];
        $data->property_detail = $data1['property_desc'];

        // step 2
        $data->address = $data2['address'];
        $data->city = $data2['city'];
        $data->locality =$data2['locality'];
        $data->nearest_landmark = $data2['nearest_place'];
        $data->map_latitude = $data2['map_latitude'];
        $data->map_longitude = $data2['map_longitude'];
        $data->pincode= $data2['pincode'];

        // step 3
        $data->additional_rooms=$addtional_room;
        $data->additional_rooms_status=$data3['additional_rooms'];
        $data->availability_condition = $data3['availability_condition'];
        $data->rera_registration_status = $data3['rera_registration_status'];
        $data->facing_towards = $data3['facing_towards'];
        $data->furnishing_status = $data3['furnishings'];
        $data->additional_parking_status = $data3['reserved_parking'];
        if($data3['reserved_parking'] == 1){
            $data->parking_covered_count = $data3['parking_covered_count'];
            $data->parking_open_count = $data3['parking_open_count']; 
        }else{
            $data->parking_covered_count =null;
            $data->parking_open_count =null;
        }
        $data->total_floors = $data3['total_floors'];
        $data->property_on_floor = $data3['property_floor'];
        $data->buildyear = $data3['year_built'];
        $data->possession_by = $data3['possession_by'];

        // step 4
        $data->inc_electricity_and_water_bill = $data4['electricity_water'];
        $data->expected_pricing = $data4['expected_pricing'];
        $data->sale_availability =1;
        $data->maintenance_charge_status = $data4['maintenance_charge_status'];
        if($data4['maintenance_charge_status'] == 1){
            $data->maintenance_charge = $data4['maintenance_charge'];
            $data->maintenance_charge_condition =$data4['maintenance_charge_condition'];
        }else{
            $data->maintenance_charge_condition =null;
            $data->maintenance_charge =null;
        }
        $data->negotiable_status=$data4['price_negotiable_status'];
        if($data4['price_negotiable_status'] == 1){
        $data->price_negotiable =$data4['price_negotiable'];
        }else{
           $data->price_negotiable =null;
        }
        $data->ownership=$data4['ownership'];
        $data->tax_govt_charge = $data4['tax_govt_charge'];
        $data->draft=$data4['draft_form_id'];
        $data->video_link = $video_link;
        $data->save();
        $product_id=$request->id;
       $user_id = Auth::user()->id;

      if($data3['furnishings']== 1){
       // check amenties
       $amenities_check=$request->amenties;
       $length=count($amenities_check);
       if($length>0){
            foreach ($amenities_check as $Check_amenities) {
                $ProductAmenties = [
                    'amenties' =>$Check_amenities,
                    'user_id' => $user_id,
                    'product_id' => $product_id
                ];
                
                $ProductAmenties_Count = ProductAmenties::where('user_id',$user_id)->where('product_id',$product_id)->where('amenties',$ProductAmenties['amenties'])->get();
                $count = count($ProductAmenties_Count);
                
                if($count==0){
                ProductAmenties::create($ProductAmenties);
                }
            }
        }

    }else{
        if($request->amenties){
           // uncheck Amenties
           $amenity_Uncheck=$request->amenties;
           $Uncheck_length=count($amenity_Uncheck);
           if($Uncheck_length>0){
                foreach ($amenity_Uncheck as $uncheck_amen) {
                    $Uncheck_Amenties = [
                        'amenties' =>$uncheck_amen,
                        'user_id' => $user_id,
                        'product_id' => $product_id
                    ];

                    $Amenties_uncheck_Count = ProductAmenties::where('user_id',$user_id)->where('product_id',$product_id)->where('amenties',$Uncheck_Amenties['amenties'])->get();
                    $count = count($Amenties_uncheck_Count);
                    
                    if($count>0){
                    ProductAmenties::where($Uncheck_Amenties)->delete();
                    }
                }
            }   
        }

    }
        return response() -> json([
            'message' => 'Successfully Updated',
            'data' => $data
        ]);


    }

    public function get_proeprty_id(Request $request){
      $request->validate([
                'product_id' => 'required|integer',
            ]);

        $product_id = $request->input('product_id');
        try{
            $token  = $request->header('authorization');
            $object = new Authicationcheck();
            if($object->authication_check($token) == true){
               $data = product::select('id','build_name')->where(['id'=>$product_id,'delete_flag'=> '0'])->first();
              if($data){
                return response()->json([
                     'message' =>'SUCCESS',
                     'data' => $data,
                     'status'=>200
                 ], 200);
              }else{
                return response()->json([
                     'message' =>'FAIL',
                     'description' => 'Product Id is Invalid !!!...',
                     'status'=>200
                 ], 200);
              }

            }else{
                return response() -> json([
                    'message' => 'FAIL',
                    'description'=>'Unauthication',
                    'status'=> 401,
                ]);
            }   
        }catch(\Exception $e) {
            return $this->getExceptionResponse1($e);
        } 
    }
    public function get_crm_property(Request $request){
        try{
            $token  = $request->header('authorization');
            $object = new Authicationcheck();
            if($object->authication_check($token) == true){
                $data = product::where(['property_mode'=> 'crm','delete_flag'=> '0'])->with('amenities','UserDetail','product_img','product_state','product_locality','property_room','Property_area_unit','willing_rent_out','maintenance_condition','aggeement_type','ageement_duration')->orderBy('id', 'desc')->get();
                return response()->json([
                    'message' =>'SUCCESS',
                    'data' => $data,
                    'status'=>200
                ], 200);
            }else{
                return response() -> json([
                    'message' => 'FAIL',
                    'description'=>'Unauthication',
                    'status'=> 401,
                ]);
            }   
        }catch(\Exception $e) {
            return $this->getExceptionResponse1($e);
        } 
    }
    

     public function get_property_byid(Request $request){
       $request->validate([
                'property_id' => 'required|integer',
            ]);
        try{
            $token  = $request->header('authorization');
            $object = new Authicationcheck();
            if($object->authication_check($token) == true){
                $property_id =$request->property_id;
                // return $property_id;
                $data = product::where(['delete_flag'=> '0','id'=>$request->property_id])->with('amenities','Property_Type','product_img','UserDetail','product_state','product_district','product_locality','product_sub_locality','Property_area_unit','property_room','willing_rent_out','maintenance_condition','aggeement_type','ageement_duration')->get();
                 if(count($data)>0){
                  $static_data=$object->static_data();
                    return response()->json([
                         'message' =>'SUCCESS',
                         'data' => $data,
                          'static_data'=>$static_data,
                         'status'=>200
                     ], 200);
                  }else{
                    return response()->json([
                         'message' =>'FAIL',
                         'description' => 'Product Id is Invalid !!!...',
                         'status'=>200
                     ], 200);
                }
             }else{
                return response() -> json([
                    'message' => 'Failure',
                    'description'=>'Unauthication',
                    'status'=> 401,
                ]);
            }       
        }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
        }    

     }
    public function get_all_property_userDetails(Request $request){
        try{
            $token  = $request->header('authorization');
            $object = new Authicationcheck();
            if($object->authication_check($token) == true){
                $property = product::where(['delete_flag'=> '0'])->with('amenities','Property_Type','product_img','UserDetail','product_state','product_locality','property_room','Property_area_unit','willing_rent_out','maintenance_condition','aggeement_type','ageement_duration')->orderBy('id', 'desc')->get();
                $user = user::get();
                $static_data=$object->static_data();
                return response()->json([
                    'message' =>'SUCCESS',
                    'property' => $property,
                    'user'=>$user,
                    'static_data'=>$static_data,
                    'status'=>200
                ], 200);
            }else{
                return response() -> json([
                    'message' => 'FAIL',
                    'description'=>'Unauthication',
                    'status'=> 401,
                ]);
            } 

        }catch(\Exception $e) {
            return $this->getExceptionResponse1($e);
        } 
    }   
    public function crm_create_product_rent(Request $request){
        $request->validate([
            'crm_user_email' => 'required|email',
            'draft_id'=>'required|integer'
        ]);
       try{
            $token  = $request->header('authorization');
            $object = new Authicationcheck();
            if($object->authication_check($token) == true){
                $data = product::find($request->id);
                // if($data){
                //     if($request->additional_rooms_status==1){
                //         $addtional_room=implode(',',$request->additional);
                //     }else{
                //         $addtional_room=NULL;
                //     } 
                                
                //     $data->build_name =$request->build_name;
                //     $data->type = $request->type;
                //     $data->bedroom = $request->bedroom;
                //     $data->bathroom = $request->bathroom;
                //     $data->balconies =  $request->balconies;
                //     $data->area = $request->area;
                //     $data->area_unit = $request->area_unit;
                //     $data->property_detail = $request->property_detail;

                //     // step 2
                //     $data->address = $request->address;
                //     $data->address_details = $request->address_details;
                //     $data->state_id = $request->state_id;
                //     $data->district_id = $request->district_id;
                //     $data->locality_id = $request->locality_id;
                //     $data->sub_locality_id =$request->sub_locality_id;
                //     $data->map_latitude = $request->map_latitude;
                //     $data->map_longitude =  $request->map_longitude;

                //     // step 3
                //     if($request->additional_rooms_status == 1){
                //         if($request->additional !=null){ $addtional_room=implode(',',$request->additional);
                //             $data->additional_rooms=$addtional_room;
                //         }else{
                //            $data->additional_rooms=$request->additional;  
                //         }
                //     }else{
                //         $data->additional_rooms=NULL;  
                //     }
                //     $data->additional_rooms_status=$request->additional_rooms_status;
                //     $data->agreement_type=$request->agreement_type;
                //     $data->duration_of_rent_aggreement=$request->duration_of_rent_aggreement;
                //     $data->available_for = $request->available_for;
                //     $data->facing_towards = $request->facing_towards;
                //     $data->furnishing_status = $request->furnishing_status;
                //     $data->month_of_notice = $request->month_of_notice;
                //     $data->additional_parking_status =$request->additional_parking_status;
                //     if($request->additional_parking_status == 1){
                //         $data->parking_covered_count=$request->parking_covered_count;
                //         $data->parking_open_count=$request->parking_open_count;
                //     }else{  
                //         $data->parking_covered_count=NULL;
                //         $data->parking_open_count=NULL;
                //     }
                //     $data->total_floors = $request->total_floors;
                //     $data->property_on_floor = $request->property_on_floor;
                //     $data->willing_to_rent_out_to =$request->willing_to_rent_out_to;
                //     $data->buildyear = $request->buildyear;

                //     // step 4
                //     $data->inc_electricity_and_water_bill =$request->inc_electricity_and_water_bill;
                //     $data->expected_rent = $request->expected_rent;
                //     $data->rent_availability =1;
                //     $data->maintenance_charge_status = $request->maintenance_charge_status;
                //     if($request->maintenance_charge_status == 1){
                //         $data->maintenance_charge=$request->maintenance_charge;
                //         $data->maintenance_charge_condition=$request->maintenance_charge_condition;
                //     }else{
                //         $data->maintenance_charge=NULL;
                //         $data->maintenance_charge_condition=NULL;    
                //     }
                //     $data->negotiable_status=$request->negotiable_status;
                //     if($request->negotiable_status==1){
                //         $data->price_negotiable=$request->price_negotiable;         
                //     }else{
                //         $data->price_negotiable=NULL;
                //     }
                //     $data->security_deposit=$request->security_deposit;
                //     $data->draft=$request->draft_id;
                //     $data->property_mode=$request->property_mode;
                //     $data->crm_user_email=$request->crm_user_email;
                //     $data->rent_cond =1;
                //     if($request->video_link){
                //         $data->video_link=str_replace("https://www.youtube.com/watch?v=","",$request->video_link);
                //     }
                //     $data->updated_at= Carbon::now()->format('Y-m-d H:i:s');
                //     $data->save();
                //     $product_id= $request->id;
                //     if($request->furnishing_status == 1 ){
                //         // check amenties
                //             $amenities_check=$request->amenityDetail;
                //             $length=count($amenities_check);
                //             if($length>0){
                //                 $amenity_delete= ProductAmenties::where(['user_id'=>$request->userId,'product_id'=>$product_id])->delete();
                //                 foreach ($amenities_check as $Check_amenities) {
                //                     $ProductAmenties = [
                //                         'amenties' =>$Check_amenities,
                //                         'user_id' => $request->userId,
                //                         'product_id' => $product_id
                //                     ];
                //                     ProductAmenties::create($ProductAmenties);
                //                 }
                //             }
                //         }else{
                //             //  return $request->amenityDetail;
                //             if($request->amenityDetail){
                //                 $amenity_delete= ProductAmenties::where(['user_id'=>$request->userId,'product_id'=>$product_id])->delete();
                //             }
                //         }
                        
                //     return response() -> json([
                //         'message' => 'Successfully Updated Draft Property',
                //         'product_id' => $product_id,
                //         'status'=> 200
                //     ]);
                // }else{
                    // $video_link=str_replace("https://www.youtube.com/watch?v=","",$data4['video_link']);
                    
                            
                    $product_uid= rand (1000,9999).time();
                    //  return $product_uid;

                        $product_data = [
                        'user_id' => $request->userId,
                        'crm_user_email'=>$request->crm_user_email,
                        'product_uid' => $product_uid,
                        'build_name' =>$request->build_name,
                        'type' =>$request->type,
                        'bedroom' => $request->bedroom,
                        'bathroom' => $request->bathroom,
                        'balconies' => $request->balconies,
                        'area' =>$request->area,
                        'area_unit' => $request->area_unit,
                        'property_detail' =>$request->property_detail,

                        // step 2
                        // 'address_details' => $request->userId,
                        'address' =>$request->address,
                        'state_id' =>$request->state_id,
                        'district_id' =>$request->district_id,
                        'locality_id' => $request->locality_id,
                        'sub_locality_id' => $request->sub_locality_id,
                        'map_latitude' => $request->map_latitude,
                        'map_longitude' => $request->map_longitude,

                        // step 3
                        'additional_rooms_status' =>$request->additional_rooms_status,
                        'agreement_type' => $request->agreement_type,
                        'duration_of_rent_aggreement' => $request->duration_of_rent_aggreement,
                        'available_for' =>$request->available_for,
                        'facing_towards'=>$request->facing_towards,
                        'furnishing_status'=>$request->furnishing_status,
                        'month_of_notice'=>$request->month_of_notice,
                        'additional_parking_status' =>$request->additional_parking_status,
                        'parking_covered_count' =>$request->parking_covered_count,
                        'parking_open_count' =>$request->parking_open_count,
                        'property_on_floor' =>$request->property_on_floor,
                        'total_floors' =>$request->total_floors,
                        'willing_to_rent_out_to' =>$request->willing_to_rent_out_to,
                        'buildyear' =>$request->buildyear,

                        // step 4
                        'inc_electricity_and_water_bill' =>$request->inc_electricity_and_water_bill,
                        'expected_rent' =>$request->expected_rent,
                        'rent_availability' =>1,
                        'maintenance_charge_status' =>$request->maintenance_charge_status,
                        'maintenance_charge' =>$request->maintenance_charge,
                        'maintenance_charge_condition' =>$request->maintenance_charge_condition,
                        'negotiable_status' =>$request->negotiable_status,
                        'price_negotiable' =>$request->price_negotiable,
                        'security_deposit' =>$request->security_deposit,
                        'rent_cond' =>1,
                        // 'video_link'=>$video_link,
                        'draft' =>$request->draft_id,
                        'property_mode'=>$request->property_mode
                        ];
                    $product_db_result=product::create($product_data);
                   
                    $product_id=$product_db_result->id;
                    if($request->additional_rooms_status== 1 ){
                        // check amenties
                        $addtional_rooms=$request->additional;
                        $length=count($addtional_rooms);
                        if($length>0){
                            $room_delete= property_room_pivot::where('product_id',$product_id)->delete();
                            foreach ($addtional_rooms as $rooms) {
                                $room_data = [
                                    'room_id' =>$rooms,
                                    'product_id' => $product_id
                                ];
                                property_room_pivot::create($room_data);
                            }
                        }
                    }
                    if($request->furnishing_status == 1 ){
                        // check amenties
                            $amenities_check=$request->amenityDetail;
                            $length=count($amenities_check);
                            if($length>0){
                                foreach ($amenities_check as $Check_amenities) {
                                    $ProductAmenties = [
                                        'amenties' =>$Check_amenities,
                                        'user_id' => $request->userId,
                                        'product_id' => $product_id
                                    ];
                                    ProductAmenties::create($ProductAmenties);
                                }
                            }
                        }
                        return response()->json([
                            'message' =>'SUCCESS',
                            'description' => 'Successfully created Draft Property',
                            // 'product_id' => $product_id,
                            'status'=>200
                        ], 201);
                // }
            }else{
                return response() -> json([
                    'message' => 'FAIL',
                    'description'=>'Unauthication',
                    'status'=> 401,
                ]);
            }
        
        }catch(\Exception $e) {
              return $this->getExceptionResponse($e);
        }  
    
    }
    public function crm_update_product_rent(Request $request){
      // return $request->input();
        try{
            $token  = $request->header('authorization');
            $object = new Authicationcheck();
            if($object->authication_check($token) == true){
                $request -> validate([
                    'id' => 'required|integer'
                ]);
               $product_id=$request->id;   
               $data = product::where(['delete_flag'=> '0'])->find($product_id);
               if($data){
                $data->build_name = $request->build_name;
                $data->type = $request->type;
                $data->bedroom =$request->bedroom;
                $data->bathroom =$request->bathroom;
                $data->balconies = $request->balconies;
                $data->area =$request->area;
                $data->area_unit =$request->area_unit;
                $data->property_detail =$request->property_detail;
                // step 2
                $data->address =$request->address;
                $data->address_details = $request->address_details;
                $data->state_id =$request->state_id;
                $data->district_id = $request->district_id;
                $data->locality_id =$request->locality_id;
                $data->sub_locality_id =$request->sub_locality_id;
                $data->map_latitude = $request->map_latitude;
                $data->map_longitude = $request->map_longitude;
                // step 3                 
                $data->additional_rooms_status=$request->additional_rooms_status;
                if($request->additional_rooms_status== 1 ){
                    // check amenties
                    $addtional_rooms=$request->additional;
                    $length=count($addtional_rooms);
                    if($length>0){
                        $room_delete= property_room_pivot::where('product_id',$product_id)->delete();
                        foreach ($addtional_rooms as $rooms) {
                            $room_data = [
                                'room_id' =>$rooms,
                                'product_id' => $product_id
                            ];
                            property_room_pivot::create($room_data);
                        }
                    }
                }else{
                    $room_delete= property_room_pivot::where('product_id',$product_id)->delete();
                }

              
                $data->agreement_type=$request->agreement_type;
                $data->duration_of_rent_aggreement=$request->duration_of_rent_aggreement;
                $data->available_for = $request->available_for;
                $data->facing_towards =$request->facing_towards;
                $data->furnishing_status =$request->furnishing_status;
                $data->month_of_notice =$request->month_of_notice;
                $data->total_floors = $request->total_floors;
                $data->property_on_floor =$request->property_on_floor;
                $data->willing_to_rent_out_to =$request->willing_to_rent_out_to;
                $data->buildyear = $request->buildyear;
                $data->additional_parking_status=$request->additional_parking_status;
               if($request->additional_parking_status == 1){
                    $data->parking_covered_count=$request->parking_covered_count;
                    $data->parking_open_count=$request->parking_open_count;
                }else{  
                    $data->parking_covered_count=NULL;
                    $data->parking_open_count=NULL;
                }
             //    step 4
                $data->inc_electricity_and_water_bill =$request->inc_electricity_and_water_bill;
                $data->expected_rent = $request->expected_rent;
                $data->rent_availability =1;
                $data->maintenance_charge_status = $request->maintenance_charge_status;
                if($request->maintenance_charge_status == 1){
                    $data->maintenance_charge=$request->maintenance_charge;
                    $data->maintenance_charge_condition=$request->maintenance_charge_condition;
                }else{
                    $data->maintenance_charge=NULL;
                    $data->maintenance_charge_condition=NULL;    
                }
               $data->security_deposit=$request->security_deposit;
                $data->negotiable_status=$request->negotiable_status;
                if($request->negotiable_status==1){
                    $data->price_negotiable=$request->price_negotiable;         
                }else{
                    $data->price_negotiable=NULL;
                }
                // $data->draft=$request->draft_form_id;
                $data->rent_cond =1;
                if($request->video_link){
                    $data->video_link=str_replace("https://www.youtube.com/watch?v=","",$request->video_link);
                
                }
                $data->updated_at= Carbon::now()->format('Y-m-d H:i:s');
                
                if($data['furnishing_status']== 1 ){
                    // check amenties
                    $amenities_check=$request->amenityDetail;
                    $length=count($amenities_check);
                    if($length>0){
                        $amenity_delete= ProductAmenties::where('product_id',$product_id)->delete();
                        foreach ($amenities_check as $Check_amenities) {
                            $ProductAmenties = [
                                'amenties' =>$Check_amenities,
                                'user_id' => $request->userId,
                                'product_id' => $product_id
                            ];
                            ProductAmenties::create($ProductAmenties);
                        }
                    }
                }else{
                    // return "iqbal";
                    //  return $request->amenityDetail;
                    // if($request->amenityDetail){
                        $amenity_delete= ProductAmenties::where('product_id',$product_id)->delete();
                    // }
                }
                if($data->save()){
                 return response() -> json([
                    'message' => 'SUCCESS',
                    'description'=>'Successfully Updated',
                     'status'=> 200
                 ]);
                }else{
                 return response() -> json([
                     'message' => 'FAIL',
                     'description'=>'Somthing Error !!!...',
                     'status'=> 201,
                 ]);
                } 
               }else{
                return response() -> json([
                    'message' => 'FAIL',
                    'description'=>'This Product Deatils  Inavalid!!!..',
                    'status'=> 404,
                ]);
               }
              
            } else{                
                return response() -> json([
                    'message' => 'FAIL',
                    'description'=>'Unauthication',
                    'status'=> 401,
                ]);
            }
        }catch(\Exception $e) {
            return $this->getExceptionResponse1($e);
        }
        
    }
     public function product_rent_update(Request $request)
    {
      $data1=$request->form_step1;
      $data2=$request->form_step2;
      $data3=$request->form_step3;
      $data4=$request->form_step4;
        $request -> validate([
            'id' => 'required'
        ]);
        $product = product::where('id', $request->id)->with('amenities')->first();

        $data = product::find($request->id);
        $usertype = Auth::user()->usertype;
            
        $product_id=$request->id;
       $user_id = Auth::user()->id;
        $video_link=str_replace("https://www.youtube.com/watch?v=","",$data4['video_link']);
       
         // return $additional_rooms;
        // inserted images functionalty
        $DB_img=Product_img::where('user_id', $user_id)->where('product_id', $product_id)->get();
        $db_img_length=count($DB_img);

       // product images functionalty
        if($db_img_length<5){
            $product_image= $request->input('images');
            $Product_img_length=count($product_image);   
            if($Product_img_length>0){
                foreach ($product_image as $value) {
                    
                    $base64_image = $value; // your base64 encoded
                    @list($type, $file_data) = explode(';', $base64_image);
                    @list(, $file_data) = explode(',', $file_data);
                    $imageName = 'product_image_file/IMAGE'.Str::random(30).'.'.'png';
                    Storage::disk('public')->put($imageName, base64_decode($file_data));
                        $Product_images_data = [
                            'user_id' => $user_id,
                            'product_id' => $product_id,
                            'image' =>$imageName 
                        ];
                    Product_img::create($Product_images_data);
                }
                
            }
        }

        // $data->product_uid= $product_uid;
        // $data->user_id= $user_id;
        $data->build_name = $data1['property_name'];
        $data->type = $data1['property_type'];
        $data->bedroom = $data1['bedrooms'];
        $data->bathroom = $data1['bathrooms'];
        $data->balconies =  $data1['balconies'];
        $data->area = $data1['property_area'];
        $data->area_unit =$data1['area_unit'];
        $data->property_detail = $data1['property_desc'];

        // step 2
        $data->address = $data2['address'];
        $data->address_details = $data2['address_details'];
        $data->state_id = $data2['city'];
        $data->district_id = $data2['district_id'];
        $data->locality_id =$data2['locality'];
        $data->sub_locality_id =$data2['sub_locality'];
        $data->map_latitude = $data2['map_latitude'];
        $data->map_longitude = $data2['map_longitude'];

        // step 3
        // $data->additional_rooms=$addtional_room;
        $data->additional_rooms_status=$data3['additional_rooms'];
        if($data3['additional_rooms']== 1 ){
            // check amenties
            $addtional_rooms=$request->rooms;
            $length=count($addtional_rooms);
            $room_delete= property_room_pivot::where('product_id',$product_id)->delete();
            if($length>0){
                foreach ($addtional_rooms as $rooms) {
                    $room_data = [
                        'room_id' =>$rooms,
                        'product_id' => $product_id
                    ];
                    property_room_pivot::create($room_data);
                }
            }
        }else{
            $room_delete= property_room_pivot::where('product_id',$product_id)->delete();
        }
        $data->agreement_type=$data3['agreement_type'];
        $data->duration_of_rent_aggreement=$data3['agreement_duration'];
        // $data->availability_condition = $data3['availability_condition'];
        $data->available_for = $data3['available_date'];
        $data->facing_towards = $data3['facing_towards'];
        $data->furnishing_status = $data3['furnishings'];
        $data->month_of_notice = $data3['notice_month'];
        $data->additional_parking_status = $data3['reserved_parking'];
        if($data3['reserved_parking'] == 1){
            $data->parking_covered_count = $data3['parking_covered_count'];
            $data->parking_open_count = $data3['parking_open_count']; 
        }else{
            $data->parking_covered_count =null;
            $data->parking_open_count =null;
        }
        $data->total_floors = $data3['total_floors'];
        $data->property_on_floor = $data3['property_floor'];
        $data->willing_to_rent_out_to = $data3['willing_to_rent'];
        $data->buildyear = $data3['year_built'];

        // step 4
        $data->inc_electricity_and_water_bill = $data4['electricity_water'];
        $data->expected_rent = $data4['expected_rent'];
        $data->rent_availability =1;
        $data->maintenance_charge_status = $data4['maintenance_charge_status'];
        if($data4['maintenance_charge_status'] == 1){
            $data->maintenance_charge = $data4['maintenance_charge'];
            $data->maintenance_charge_condition =$data4['maintenance_charge_condition'];
        }else{
            $data->maintenance_charge_condition =null;
            $data->maintenance_charge =null;
        }
        $data->negotiable_status=$data4['price_negotiable_status'];
        if($data4['price_negotiable_status'] == 1){
        $data->price_negotiable =$data4['price_negotiable'];
        }else{
           $data->price_negotiable =null;
        }
        $data->security_deposit=$data4['security_deposit'];
        // $data->tax_govt_charge = $data4['tax_govt_charge'];
        $data->draft=$data4['draft_form_id'];
        $data->rent_cond =1;
        $data->video_link = $video_link;
        $data->save();
        $product_id=$request->id;
       $user_id = Auth::user()->id;
 
                if($data3['furnishings']== 1 ){
                    // check amenties
                    $amenities_check=$request->amenties;
                    $length=count($amenities_check);
                    if($length>0){
                        $amenity_delete= ProductAmenties::where(['product_id'=>$product_id,'user_id'=>$user_id])->delete();
                        foreach ($amenities_check as $Check_amenities) {
                            $ProductAmenties = [
                                'amenties' =>$Check_amenities,
                                'product_id' => $product_id,
                                'user_id' =>$user_id
                            ];
                            ProductAmenties::create($ProductAmenties);
                        }
                    }
                }else{
                    //  return $request->amenityDetail;
                    // if($request->amenties){
                        $amenity_delete= ProductAmenties::where(['product_id'=>$product_id,'user_id'=>$user_id])->delete();
                    // }
                }
        return response() -> json([
            'message' => 'Successfully Updated',
            'data' => $data
        ]);

    }



    public function update_product(Request $request)
    {
    
        $request -> validate([
            // 'id' => 'required',
            // // 'view_counter' => 'required',
            // 'address' => 'required',
            // 'city' => 'required',
            // 'rent_cond' => 'required',
            // 'rent_availability' => 'required',
            // 'sale_availability' => 'required',
            // 'possession_by' => 'required',
            // 'locality' => 'required',
            // 'display_address' => 'required',
            // 'ownership' => 'required',
            // 'expected_pricing' => 'required',
            // 'inclusive_pricing_details' => 'required',
            // 'tax_govt_charge' => 'required',
            // 'price_negotiable' => 'required',
            // 'maintenance_charge_status' => 'required',
            // 'maintenance_charge' => 'required',
            // 'maintenance_charge_condition' => 'required',
            // 'deposit' => 'required',
            // 'available_for' => 'required',
            // 'brokerage_charges' => 'required',
            // 'type' => 'required',
            // 'product_image1' => 'required',
            // 'product_image2' => 'required',
            // 'product_image3' => 'required',
            // 'product_image4' => 'required',
            // 'product_image5' => 'required',
            'bedroom' => 'required',
            'bathroom' => 'required',
            'balconies' => 'required',
            'additional_rooms' => 'required',
            'furnishing_status' => 'required',
            'furnishings' => 'required',
            'total_floors' => 'required',
            'property_on_floor' => 'required',
            'rera_registration_status' => 'required',
            'amenities' => 'required',
            'facing_towards' => 'required',
            'additional_parking_status' => 'required',
            // 'description' => 'required',
            'parking_covered_count' => 'required',
            'parking_open_count' => 'required',
            'availability_condition' => 'required',
            'buildyear' => 'required',
            'expected_rent' => 'required',
            'inc_electricity_and_water_bill' => 'required',
            'security_deposit' => 'required',
            'duration_of_rent_aggreement' => 'required',
            'month_of_notice' => 'required',
            'equipment' => 'required',
            'features' => 'required',
            'nearby_places' => 'required',
            'area' => 'required',
            'area_unit' => 'required',
            'carpet_area' => 'required',
            'property_detail' => 'required',
            'build_name' => 'required',
            'willing_to_rent_out_to' => 'required',
            'agreement_type' => 'required',
            'nearest_landmark' => 'required',
            'map_latitude' => 'required',
            'map_longitude' => 'required',
            'delete_flag' => 'required',
        ]);

        $data = product::find($request->id);

        $usertype = Auth::user()->id;

        if($usertype == $data->user_id){
            return response()->json([
                'unauthorised',
            ], 401);
        }

        $data->view_counter = $request->view_counter;
        $data->address = $request->address;
        $data->city = $request->city;
        $data->rent_cond = $request->rent_cond;
        $data->rent_availability = $request->rent_availability;
        $data->sale_availability = $request->sale_availability;
        $data->possession_by = $request->possession_by;
        $data->locality = $request->locality;
        $data->display_address = $request->display_address;
        $data->ownership = $request->ownership;
        $data->expected_pricing = $request->expected_pricing;
        $data->inclusive_pricing_details = $request->inclusive_pricing_details;
        $data->tax_govt_charge = $request->tax_govt_charge;
        $data->price_negotiable = $request->price_negotiable;
        $data->maintenance_charge_status = $request->maintenance_charge_status;
        $data->maintenance_charge = $request->maintenance_charge;
        $data->maintenance_charge_condition = $request->maintenance_charge_condition;
        $data->deposit = $request->deposit;
        $data->available_for = $request->available_for;
        $data->brokerage_charges = $request->brokerage_charges;
        $data->type = $request->type;
        $data->product_image1 = $request->product_image1;
        $data->product_image2 = $request->product_image2;
        $data->product_image3 = $request->product_image3;
        $data->product_image4 = $request->product_image4;
        $data->product_image5 = $request->product_image5;
        $data->bedroom = $request->bedroom;
        $data->bathroom = $request->bathroom;
        $data->balconies = $request->balconies;
        $data->additional_rooms = $request->additional_rooms;
        $data->furnishing_status = $request->furnishing_status;
        $data->furnishings = $request->furnishings;
        $data->total_floors = $request->total_floors;
        $data->property_on_floor = $request->property_on_floor;
        $data->rera_registration_status = $request->rera_registration_status;
        $data->amenities = $request->amenities;
        $data->facing_towards = $request->facing_towards;
        $data->additional_parking_status = $request->additional_parking_status;
        // $data->description = $request->description;
        $data->parking_covered_count = $request->parking_covered_count;
        $data->parking_open_count = $request->parking_open_count;
        $data->availability_condition = $request->availability_condition;
        $data->buildyear = $request->buildyear;
        $data->expected_rent = $request->expected_rent;
        $data->inc_electricity_and_water_bill = $request->inc_electricity_and_water_bill;
        $data->security_deposit = $request->security_deposit;
        $data->duration_of_rent_aggreement = $request->duration_of_rent_aggreement;
        $data->month_of_notice = $request->month_of_notice;
        $data->equipment = $request->equipment;
        $data->features = $request->features;
        $data->nearby_places = $request->nearby_places;
        $data->area = $request->area;
        $data->area_unit = $request->area_unit;
        $data->carpet_area = $request->carpet_area;
        $data->property_detail = $request->property_detail;
        $data->build_name = $request->build_name;
        $data->willing_to_rent_out_to = $request->willing_to_rent_out_to;
        $data->agreement_type = $request->agreement_type;
        $data->nearest_landmark = $request->nearest_landmark;
        $data->map_latitude = $request->map_latitude;
        $data->map_longitude = $request->map_longitude;
        $data->delete_flag = $request->delete_flag;

        $data->save();

        return response() -> json([
            'data' => $data
        ]);

    }
    public function property_get_id(Request $request)
    {
        try{
              $request -> validate([
                    'id' => 'required'
                ]);

               $user_id = Auth::user()->id;
                //$product = product::where('id', $request->id)->where('user_id',$user_id)->with('amenities','product_img','Property_Type','locality')->first();
                $product = product::where('id', $request->id)->where('user_id',$user_id)->with('amenities','product_img','product_state','product_district','product_locality','product_sub_locality','property_room')->first();
                    return response()->json([
                        'data' => $product
                    ]);
                    return response()->json([
                        'data' => $product
                    ]);
         }catch(\Exception $e) {
            return $this->getExceptionResponse($e);
         }       
    }

    public function get_product_details(Request $request) {
        $request -> validate([
            'id' => 'required'
        ]);

        return $product_details = product::where('id', $request->id)->with('Property_area_unit', 'maintenance_condition')->get();
    }

    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\product  $product
     * @return \Illuminate\Http\Response
     */

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(product $product)
    {
        //
    }
     public function delete_video(Request $request){

        $request->validate([
            'product_id' => 'required',
            'video'=>'required',
        ]);
        product::where([ 'video_link'=> $request->video,'id'=>$request->product_id ])->update(['video_link' => '']);

        return response()->json([
            'message' => 'Video Successfully deleted',
        ], 201);

    }
}
