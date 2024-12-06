<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Section;
use App\Models\Category;
use App\Models\Brand;
use Auth;
use Image;
// use Intervention\Image\Facades\Image;

class ProductsController extends Controller
{
    public function products(){
       // Session::put('page','sections');
        $products = Product::with([
        'section'=>function($query){$query->select('id','name');},
        'category'=>function($query){$query->select('id','category_name');}
        ])->get()->toArray();
        //  dd($products);
        return view('admin.products.products')->with(compact('products'));
    }
    public function updateProductStatus(Request $request){
        if($request->ajax()){
           $data = $request->all();
           // echo "<pre>"; print_r($data); die;
           if($data['status']=="Active"){
               $status = 0;
           }else{
               $status = 1;
           }
           Product::where('id',$data['product_id'])->update(['status'=>$status]);
        //    return response()->json(['status' => $status, 'product_id' => $data['product_id']]);
           $response = ['status' => $status, 'product_id' => $data['product_id']];
           return response()->json($response, 200);
        }
       }
       public function deleteProduct($id){
        //حذف منتج
        Product::where('id',$id)->delete();
        $message = "Product has been deleted successfully!"; 
        return redirect()->back()->with('success_message',$message);
  }

  public function addEditProduct(Request $request, $id=null){
    if($id==""){
      //اضافة منتج
      $title = "Add Product";
      $product = new Product;
      $message = "Product added successfully!";
         } else{
        //تعديل منتج
        $title = "Edit Product";
        }
        if($request->isMethod('post')){
            $data = $request->all();
            // echo "<pre>"; print_r($data); die; 

            $rules = [
                'category_id' => 'required',
                'product_name' =>'required|regex:/^[\pL\s\-]+$/u',
                'product_code' =>'required',
                'product_price' =>'required|numeric',
                'product_color' =>'required|regex:/^[\pL\s\-]+$/u',    
          ];
          $this->validate($request,$rules);

          // Upload Product Image after Resize small: 250x250 medium: 500x500 large: 1000x1000

        if($request->hasFile('product_image')){
            $image_tmp = $request->file('product_image');            
            if($image_tmp->isValid()){          
            // Get Image Extension          
            $extension = $image_tmp->getClientOriginalExtension();          
            // Generate New Image Name           
            $imageName = rand(111,99999).'.'.$extension;           
            $largeImagePath = 'front/images/product_images/large/'.$imageName;
            $mediumImagePath = 'front/images/product_images/medium/'.$imageName;
            $smallImagePath = 'front/images/product_images/small/'.$imageName;          
            // Upload the Large, Medium and Small Images after Resize           
            Image::make($image_tmp)->resize(1000,1000)->save($largeImagePath);           
            Image::make($image_tmp)->resize(500,500)->save($mediumImagePath);           
            Image::make($image_tmp)->resize(250,250)->save($smallImagePath);         
            // Insert Image Name in products table      
            $product->product_image = $imageName;
            
            }
            }
        
          // احفظ تفاصيل المنتج في جدول المنتجات
          $categoryDetails = Category::find($data['category_id']);
          $product->section_id = $categoryDetails['section_id'];
          $product->category_id = $data['category_id'];
          $product->brand_id = $data['brand_id'];

          $adminType = Auth::guard('admin')->user()->type;
          $vendor_id = Auth::guard('admin')->user()->vendor_id;
          $admin_id = Auth::guard('admin')->user()->id;

          $product->admin_type = $adminType;
          $product->admin_id = $admin_id;
          if($adminType=="vendor"){
            $product->vendor_id = $vendor_id;

          }else{
            $product->vendor_id = 0;
          }

          $product->product_name = $data['product_name'];
          $product->product_code = $data['product_code'];
          $product->product_color = $data['product_color'];
          $product->product_price = $data['product_price'];
          $product->product_discount = $data['product_discount'];
          $product->product_weight = $data['product_weight'];
          $product->description = $data['description'];
          $product->meta_title = $data['meta_title'];
          $product->meta_description = $data['meta_description'];
          $product->meta_keywords = $data['meta_keywords'];
          if(!empty($data['is_featured'])) {
            $product->is_featured = $data['is_featured'];
            }else{    
            $product->is_featured = "No";
            }
            $product->status = 1;
            $product->save();
            return redirect('admin/products')->with('success_message',$message);


        }
        // الحصول على الاقسام مع الفئات و الفئات الفرعية 
        $categories = Section::with('categories')->get()->toArray();
        // dd($categories);
        // الحصول على جميع العلامات التجارية
        $brands = Brand::where('status',1)->get()->toArray();

        return view('admin.products.add_edit_product')->with(compact('title','categories','brands'));
  }

  // public function addEditProduct(Request $request, $id=null){
  //   //  Session::put('page','categories');
  //     if($id==""){
  //       //اضافة منتج
  //       $title = "Add Product";
  //       $category = new Product;
  //       $getCategories = array();
  //       $message = "Category added successfully!";
  //     }else{
  //      //تعديل منتج
  //      $title = "Edit Category";
  //      $category = Category::find($id);
  //       //    echo "<pre>"; print_r($category['category_name']); die;
  //       $getCategories = Category::with('subcategories')->where(['parent_id'=>0,
  //       'section_id'=>$category['section_id']])->get();
  //       $message = "Category updated successfully!";
  //       }

  //       if($request->isMethod('post')){
  //           $data = $request->all();
  //       //    echo "<pre>"; print_r($data); die; 
  //       $rules = [
  //           'category_name' => 'required|regex:/^[\pL\s\-]+$/u',
  //           'section_id' => 'required',
  //           'url' => 'required',

  //     ];
  //     $this->validate($request,$rules);
  //       //اعطاء قيمة للعمود في حال ترك فارغ لو رقم 
  //       // او عمل "" اذا كان كلام او تغيره null  من فاعدة البيانات 
  //     if($data['category_discount']==""){
  //       $data['category_discount'] = 0;}
  
  //       //رفع صورة الفئة 
  //        if($request->hasFile('category_image')){
  //            $image_tmp = $request->file('category_image');
  //             if($image_tmp->isValid()){
  //               // الحصول على ملحق الصورة
  //                $extension = $image_tmp->getClientOriginalExtension();  
  //               //انشاء اسم صورة جديد 
  //                $imageName = rand(111,99999).'.'.$extension; 
  //                $imagePath = 'front/images/category_images/'.$imageName;
  //               //رفع الصورة 
  //               Image::make($image_tmp)->save($imagePath);
  //               $category->category_image = $imageName;
  //             }
  //           }else{
  //               $category->category_image = "";
  //        }

  //        $category->section_id = $data['section_id'];
  //        $category->parent_id = $data['parent_id'];
  //        $category->category_name = $data['category_name'];
  //        $category->category_discount = $data['category_discount'];
  //        $category->description = $data['description'];
  //        $category->url = $data['url'];       
  //        $category->meta_title = $data['meta_title'];
  //        $category->meta_description = $data['meta_description'];
  //        $category->meta_keywords = $data['meta_keywords'];
  //        $category->status = 1;
  //        $category->save();

  //        return redirect('admin/categories')->with('success_message',$message);

  //    }
          
  //     //الحصول على كل الاقسام 
  //     $getSections = Section::get()->toArray();

  //     return view('admin.products.add_edit_product')->with(compact('title',
  //     'category','getSections','getCategories'));
  // }


}
