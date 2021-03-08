<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Artisan; 
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Str;
use App\Models\User;
use App\Repositories\Artisan\ArtisanRepository;
use App\Repositories\Product\ProductRepository;

class ArtisanController extends Controller
{
    private ArtisanRepository $artisanRepo;
    private ProductRepository $productRepo;

    public function __construct()
    {
        $this->artisanRepo = new ArtisanRepository;
        $this->productRepo = new ProductRepository;
    }

    public function profile(Artisan $artisan) 
    {   
        $products = $this->productRepo->getArtisanProducts($artisan->id);
        $highlightProducts = $this->productRepo->getArtisanHighlightProducts($artisan->id);
        return view('artisan.profileArtisan', compact('products', 'artisan', 'highlightProducts'));   
    }

    public function seeProfile() 
    {     
        $user_id = auth()->id();  
        $artisan = $this->artisanRepo->getArtisan($user_id);
        if($artisan->aproved)
        {
            $products = $this->productRepo->getArtisanProducts($artisan->id);
            $highlightProducts = $this->productRepo->getArtisanHighlightProducts($artisan->id);
        }
        else
        {
            return view('admin.responsesAdmin', ["message" => "Tu perfil está siendo evaluado, ¡Recibirás notícias pronto por email!"]);
        }
        
        return view('artisan.profileArtisan', compact('products', 'artisan', 'highlightProducts'));   
    }

    public function store(Request $request){

        $id = auth()->id();
        $user = User::find($id);
        $user->isArtisan = 1;
        $user->save();

        $newArtisan = Artisan::create([
            'name' => $request->name,
            'location' =>$request->location,
            'description' =>$request->description,
            'image' => $request->image, 
            'user_id' =>auth()->id(),
            'slug' => Str::slug($request->name, '-')
            ]);
            
            $newArtisan->save(); 

            return redirect('/artisan/' . $newArtisan->slug);
            
    }

    public function getAll(){

        $artisans = $this->artisanRepo->getAll();
        return view('artisan.artisans', compact('artisans'));
    }

    public function orders()
    {
        $user_id = auth()->id();
        $user = User::find($user_id); 
        $id = DB::table('artisans')
            ->where('user_id','=',$user_id)
            ->value('id');
        $artisan = Artisan::find($id);
        $orders = $this->productRepo->getOrders($id);
        $archivedOrders = $this->productRepo->getArchivedOrders($id);
    
        return view('artisan.artisanOrders', compact('orders','archivedOrders'));
    }

    public function destroy()
    {
        $user_id = auth()->id();  
        $artisan = $this->artisanRepo->getArtisan($user_id);
        $artisan->delete();

        return redirect('/');
    }

    public function edit()
    {
        $user_id = auth()->id();  
        $artisan = $this->artisanRepo->getArtisan($user_id);       
        return view('artisan.editArtisan', compact('artisan'));
    }

    public function update(Request $request , Artisan $artisan)
    {

        $artisan->name = $request->name;
        $artisan->location = $request->location;
        $artisan->description = $request->description;
        $artisan->image  = $request->image;
        $artisan->user_id =auth()->id();
        $artisan->slug =Str::slug($request->name, '-');

        $artisan->save();
            
        return redirect('/artisan/' . $artisan->slug);
    }

    public function archiveOrder($id)
    {
        DB::table('product_user')
            ->where('id','=',$id)
            ->update(['archived'=> 1]);

        return back();
    }

}
