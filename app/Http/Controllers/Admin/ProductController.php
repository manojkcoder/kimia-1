<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\VariantDetail;
use App\Models\ProductVariant;
use Carbon\Carbon;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;

class ProductController extends Controller
{
    public function index(){
        return Inertia::render("Admin/Products/Index");
    }
    public function getAllProducts(Request $request){
        $length = $request->length ? $request->length : 10;
        $products = Product::orderBy("id","desc");
        if($length > 0){
            $output = $products->paginate($length);
        }else{
            $products = $products->get();
            $output = [
                "current_page" => 1,
                "data" => $products,
                "from" => 1,
                "to" => count($products),
                "total" => count($products),
                "last_page" => 1
            ];
        }
        return json_encode($output);
    }
    public function create(){
        $variantProductDetails = VariantDetail::where("variant_id",1)->orderBy("order_no","ASC")->get(["id","variant_id","name"]);
        return Inertia::render("Admin/Products/Edit",["product" => new Product,"variantProductDetails" => $variantProductDetails]);
    }
    public function store(ProductStoreRequest $request){
        $slug = "PRD" . Carbon::now()->format("mdY") . rand(000000,999999);
        $request->price = $request->price ? $request->price : null;
        $product = new Product($request->except("image","productVariants","specifications"));
        $product->slug = $slug;
        $product->specifications = json_encode($request->specifications);
        $file = $request->file("image");
        $fileName = str_replace([" ","'",'"'],"-",$file->getClientOriginalName());
        $file->move("assets/images/products",$fileName);
        $product->image = "/assets/images/products/" . $fileName;
        $product->save();
        if($request->product_type == "variant"){
            $productVariants = $request->productVariants ? $request->productVariants : [];
            if(count($productVariants)){
                foreach($productVariants as $pVariant){
                    $productVariant = new ProductVariant();
                    $productVariant->product_id = $product->id;
                    $productVariant->variant_id = $pVariant["variant_id"];
                    $productVariant->variant_detail_id = $pVariant["id"];
                    $productVariant->price = $pVariant["price"];
                    $productVariant->save();
                }
            }
        }
        return Redirect::route("admin.products.index")->with("success", "Product created successfully");
    }
    public function show(string $id){
        //
    }
    public function edit(Product $product){
        $variantProductDetails = VariantDetail::where("variant_id",1)->orderBy("order_no","ASC")->get(["id","variant_id","name"]);
        foreach($variantProductDetails as $vpDetail){
            $productVariant = ProductVariant::where(["product_id" => $product->id,"variant_detail_id" => $vpDetail->id])->first();
            if($productVariant){
                $vpDetail["status"] = true;
                $vpDetail["price"] = $productVariant->price;
            }else{
                $vpDetail["status"] = false;
                $vpDetail["price"] = "";
            }
        }
        return Inertia::render("Admin/Products/Edit",compact("product","variantProductDetails"));
    }
    public function update(ProductUpdateRequest $request,$id){
        $product = Product::findOrfail($id);
        $request->price = $request->price ? $request->price : null;
        $product->update($request->except("_method","slug","image","productVariants"));
        $file = $request->file("image");
        if($file){
            $oldImage = $product->image;
            if($oldImage){
                if(file_exists(public_path($oldImage))){
                    unlink(public_path($oldImage));
                }
            }
            $fileName = str_replace([" ","'",'"'],"-",$file->getClientOriginalName());
            $file->move("assets/images/products",$fileName);
            $product->image = "/assets/images/products/" . $fileName;
            $product->save();
        }
        ProductVariant::where("product_id",$product->id)->delete();
        if($request->product_type == "variant"){
            $productVariants = $request->productVariants ? json_decode($request->productVariants) : [];
            if(count($productVariants)){
                foreach($productVariants as $pVariant){
                    $productVariant = new ProductVariant();
                    $productVariant->product_id = $product->id;
                    $productVariant->variant_id = $pVariant->variant_id;
                    $productVariant->variant_detail_id = $pVariant->id;
                    $productVariant->price = $pVariant->price;
                    $productVariant->save();
                }
            }
        }
        return Redirect::route("admin.products.index")->with("success","Product updated successfully");
    }
    public function destroy(Product $product){
        $product->delete();
        return Inertia::location(route("admin.products.index"));
    }
}