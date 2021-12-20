<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductAmenties;
use App\Models\area_locality;
use App\Models\Property_type;
use Auth;

class product extends Model
{
    use HasFactory; 
    protected $fillable = [
        'user_id',
        'address',
        'city',
        'rent_cond',
        'rent_availability',
        'sale_availability',
        'maintenance_charge',
        'possession_by',
        'locality',
        'pincode',
        'display_address',
        'ownership',
        'expected_pricing',
        'inclusive_pricing_details',
        'tax_govt_charge',
        'price_negotiable',
        'negotiable_status',
        'maintenance_charge_status',
        'maintenance_charge',
        'maintenance_charge_condition',
        'deposit',
        'available_for',
        'brokerage_charges',
        'type',
        'product_image1',
        'product_image2',
        'product_image3',
        'product_image4',
        'product_image5',
        'bedroom',
        'bathroom',
        'balconies',
        'additional_rooms',
        'additional_rooms_status',
        'furnishing_status',
        'furnishings',
        'total_floors',
        'property_on_floor',
        'rera_registration_status',
        'amenities',
        'facing_towards',
        'description',
        'additional_parking_status',
        'parking_covered_count',
        'parking_open_count',
        'availability_condition',
        'buildyear',
        'age_of_property',
        'expected_rent',
        'inc_electricity_and_water_bill',
        'security_deposit',
        'duration_of_rent_aggreement',
        'month_of_notice',
        'equipment',
        'features',
        'nearby_places',
        'area',
        'area_unit',
        'carpet_area',
        'property_detail',
        'build_name',
        'willing_to_rent_out_to',
        'agreement_type',
        'nearest_landmark',
        'map_latitude',
        'map_longitude',
        'video_link',
        'draft',
        'product_uid',
        'order_status',
        'address_details',
        'state_id',
        'district_id',
        'locality_id',
        'sub_locality_id',
        'enabled'
    ];

    public function productid()
    {

        return $this->hasMany('App\Models\product', 'id');
    }
    public function UserDetail()
    {
        return $this->hasOne('App\Models\User', 'id','user_id');
    }

    public function product_comparision()
    {

        $user_id = Auth::user()->id;
        return $this->hasOne('App\Models\Product_Comparision', 'product_id','id')->where('status', '1')->where('user_id', $user_id)->orderBy('id', 'asc');
    }

    public function Single_wishlist()
    {
        
        $user_id = Auth::user()->id;
        return $this->hasOne('App\Models\Wishlist', 'product_id','id')->where('user_id', $user_id)->where('status', '1');
    }
    
    public function product_wishlist_crm()
    {
        return $this->hasOne('App\Models\Wishlist', 'product_id','user_id')->where('status', '1');
    }
    public function product_comp()
    {
        return $this->hasOne('App\Models\Product_Comparision', 'product_id','user_id')->where('status', '1')->orderBy('id', 'asc');
    }
    public function wishlist()
    {
        return $this->hasMany('App\Models\Wishlist', 'product_id','id');
    }
    public function locality()
    {
        return $this->hasOne('App\Models\area', 'id','locality');
    }
    public function amenities()
    {
        return $this->hasMany('App\Models\ProductAmenties', 'product_id','id')->with('amenties');
    }
    public function product_img()
    {
        return $this->hasMany('App\Models\Product_img', 'product_id','id')->where('status', '1');
    }
    public function Property_Type()
    {
        return $this->hasOne('App\Models\Property_type', 'id','type')->where('status', '1');
    }
    // public function Pro_order()
    // {
    //     return $this->hasOne('App\Models\product_order', 'product_id','product_uid')->where('transaction_status', 'TXN_SUCCESS');
    // }

    public function product_order()
    {
        return $this->hasOne('App\Models\product_order', 'id','product_id')->where(['status'=> '1']);
    }
 
    public function Pro_order()
     {
         return $this->hasOne('App\Models\invoices', 'product_id','product_uid')->where('transaction_status', 'TXN_SUCCESS');
     }
     public function product_state()
    {
        return $this->hasone('App\Models\area_state', 'state_id','state_id');
    }
     public function product_district()
    {
        return $this->hasone('App\Models\area_district', 'district_id','district_id');
    }
    public function product_locality()
    {
        return $this->hasone('App\Models\area_locality', 'locality_id','locality_id');
    }
    public function product_sub_locality()
    {
        return $this->hasone('App\Models\area_sub_locality', 'sub_locality_id','sub_locality_id');
    }

   
    public function roles()
    {

    }

    public function scopeSearch($query, $searchTerm) {
        if ($searchTerm->data['build_name']) {
            $query = $query->where('build_name', 'like', "%" . $searchTerm->data['build_name'] . "%");
        }
        if ($searchTerm->data['location']) {
           $query = $query->where('address', 'like', "%" . $searchTerm->data['location'] . "%");
        }
        if ($searchTerm->data['city']) {
           $query = $query->where('state_id','1');
        }
        if ($searchTerm->data['area_unit']) {
            $query = $query->where('area_unit', $searchTerm->data['area_unit']);
        }
        if ($searchTerm->data['type']) {
            // $type_id = Property_type::where('name','like', "%" .$searchTerm->data['type'] . "%")->first();

            // $type_value=$searchTerm->data['type'];
            $query = $query->where('type','like', "%" .$searchTerm->data['type'] . "%");
        }
        if ($searchTerm->data['bathrooms']) {
            $query = $query->where('bathroom', $searchTerm->data['bathrooms']);
        }
        if ($searchTerm->data['bedrooms']) {

            $query = $query->where('bedroom', $searchTerm->data['bedrooms']);
        }
        if ($searchTerm->data['sliderControl'][0]) {
            $min = $searchTerm->data['sliderControl'][0];
            $query->where(function($query) use ($min){
                $query->where('expected_pricing', '>=', $min)
                      ->orWhere('expected_rent', '>=', $min);
            });
        }
        if ($searchTerm->data['sliderControl'][1]) {
            $max = $searchTerm->data['sliderControl'][1];
            $query->where(function($query) use ($max){
                $query->where('expected_pricing', '<=', $max)
                      ->orWhere('expected_rent', '<=', $max);
            });
        }

        if ($searchTerm->data['years']) {
            $query = $query->where('buildyear', $searchTerm->data['years']);
        }
        if ($searchTerm->data['property_status']== "all") {
            $query = $query->orderBy('id', 'desc');
        }
        if ($searchTerm->data['property_status']== "recently") {
            $query = $query->orderBy('id', 'desc')->take(6);
        }
        if ($searchTerm->data['property_status']== "viewed") {
            $query = $query->where('view_counter', '>=',10)->orderBy('view_counter', 'desc');
        } 
        if ($searchTerm->data['locality']) {

            $locality = area_locality::select('locality_id')->where('locality', $searchTerm->data['locality'])->get();
            if(count($locality)>0){
              $query = $query->whereIn('locality_id',$locality)->orderBy('id', 'desc');  
            }else{
                $sub_locality = area_sub_locality::select('locality_id')->where('sub_locality', $searchTerm->data['locality'])->get();
                // $locality_update = area_locality::select('locality_id')->where('locality_id', $sub_locality)->get();
                 $query = $query->whereIn('locality_id',$sub_locality)->orderBy('id', 'desc');
            }
        }
        if ($searchTerm->amenities) {
            $amenities_data = ProductAmenties::select('product_id')->whereIn('amenties',$searchTerm->amenities)->get();
             $amenitiesID=json_decode(json_encode($amenities_data), true);
             $length=count($amenitiesID);
              $array=[]; 
            for($i=0; $i<$length; $i++){
                array_push($array,$amenitiesID[$i]['product_id']);
            }
            $query = $query->whereIn('id',$array)->orderBy('id', 'desc');            
        }
        return $query;  
    }

}
