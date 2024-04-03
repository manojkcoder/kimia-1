<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Newsletter;
use App\Jobs\ContactsEmailJob;
use App\Jobs\CustomOrderEmailJob;

class FrontendController extends Controller
{
    public function index(Request $request): Response{
        $amineProducts = Product::where(["category" => "amine"])->orderBy("id","DESC")->limit(6)->get();
        $acidProducts = Product::where(["category" => "acid"])->orderBy("id","DESC")->limit(6)->get();
        $aldehydeProducts = Product::where(["category" => "aldehyde"])->orderBy("id","DESC")->limit(6)->get();
        $halideProducts = Product::where(["category" => "halide"])->orderBy("id","DESC")->limit(6)->get();
        return Inertia::render("Frontend/Home",[
            "amineProducts" => $amineProducts,
            "acidProducts" => $acidProducts,
            "aldehydeProducts" => $aldehydeProducts,
            "halideProducts" => $halideProducts
        ]);
    }
    public function about(Request $request): Response{
        return Inertia::render("Frontend/About");
    }
    public function services(Request $request): Response{
        return Inertia::render("Frontend/Services");
    }
    public function contacts(Request $request): Response{
        return Inertia::render("Frontend/Contacts");
    }
    public function products(Request $request){
        $category = $request->category ? $request->category : null;
        $search = $request->search ? $request->search : null;
        $products = Product::select("id","name","slug","category","catalog_number","price","image");
        if($category){
            $products = $products->where("category",$category);
        }
        if($search){
            $products = $products->where(function($query) use ($search){
                return $query->where('name','like',"%{$search}%")->orWhere('catalog_number','like',"%{$search}%")->orWhere('cas_number','like',"%{$search}%");
            });
        }
        $products = $products->paginate(12);
        return Inertia::render("Frontend/Products",compact("products","category","search"));
    }
    public function productByCategory(Request $request,$slug): Response{
        $products = Product::where("category",$slug)->paginate(12);
        return Inertia::render("Frontend/ProductByCategory",["category" => $slug,"products" => $products]);
    }
    public function productDetail(Request $request,$slug){
        $product = Product::where("slug",$slug)->first();
        if(!$product){
            return Redirect::route("home");
        }
        if($product->product_type == "variant"){
            $product->productVariants = ProductVariant::with("variantDetail")->where("product_id",$product->id)->get(["id","variant_detail_id","price"]);
        }
        $relatedProducts = Product::where("category",$product->category)->where("slug","!=",$slug)->orderBy("id","DESC")->limit(4)->get();
        return Inertia::render("Frontend/ProductDetail",compact("product","relatedProducts"));
    }
    public function sendContacts(Request $request){
        $data = $request->all();
        $data["to_address"] = env("ADMIN_EMAIL_ADDRESS");
        ContactsEmailJob::dispatch($data);
        return json_encode(["message" => "Your enquiry has been submitted."]);
    }
    public function Newsletter(Request $request){
        $newsletter = Newsletter::where("email",$request->email)->first();
        if($newsletter){
            return json_encode(["status" => 419,"message" => "You have already subscribed."]);
        }
        $newsletter = new Newsletter();
        $newsletter->email = $request->email;
        $newsletter->save();
        return json_encode(["status" => 200,"message" => "Thanks for subscribe."]);
    }
    public function customOrder(Request $request){
        $data = $request->all();
        $data["to_address"] = env("ADMIN_EMAIL_ADDRESS");
        CustomOrderEmailJob::dispatch($data);
        return json_encode(["message" => "Your enquiry has been submitted."]);
    }
}