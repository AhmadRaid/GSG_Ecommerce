<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $products = Product::join('categories', 'categories.id', '=', 'products.category_id')
            ->select([
                'products.*',
                'categories.name as category_name',
            ])
            ->paginate(15);


        return view('admin.products.index', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $categories = Category::pluck('name', 'id');

        return view('admin.products.create', [
            'categories' => $categories,
            'product' => new Product(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $image_path = $file->store('product_image', 'public');
            $request->merge([
                'image_path' => $image_path,
            ]);
        }
        $request->validate(Product::validateRules());

        /*$request->merge([
            'slug' => Str::slug($request->post('name')),
        ]);*/
        $product = Product::create($request->all());

        return redirect()->route('products.index')
            ->with('success', "Product ($product->name) created.");

    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $product = Product::withoutGlobalScope('active')->findOrFail($id);
        return view('admin.products.show', [
            'product' => $product,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $product = Product::withoutGlobalScope('active')->findOrFail($id);
        return view('admin.products.edit', [
            'product' => $product,
            'categories' => Category::withTrashed()->pluck('name', 'id'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $product = Product::withoutGlobalScope('active')->findOrFail($id);

        $request->validate(Product::validateRules());

        if ($request->hasFile('image')) {
            $file = $request->file('image'); // UplodedFile Object
            // $file->getClientOriginalName(); // Return file name
            // $file->getClientOriginalExtension();
            // $file->getClientMimeType(); // audio/mp3
            // $file->getType();
            // $file->getSize();

            // Filesystem - Disks
            // local: storage/app
            // public: storage/app/public
            // s3: Amazon Drive
            // custom: defined by us!
            $image_path = $file->store('/', [
                'disk' => 'uploads',
            ]);
            $request->merge([
                'image_path' => $image_path,
            ]);
        }

        $product->update($request->all());

        return redirect()->route('products.index')
            ->with('success', "Product ($product->name) updated.");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $product = Product::withoutGlobalScope('active')->findOrFail($id);
        $product->delete();

        Storage::disk('uploads')->delete($product->image_path);
        //unlink(public_path('uploads/' . $product->image_path));

        return redirect()->route('products.index')
            ->with('success', "Product ($product->name) deleted.");
    }

    public function trash()
    {
        $products = Product::withoutGlobalScope('active')->onlyTrashed()->paginate();
        return view('admin.products.trash', [
            'products' => $products,
        ]);
    }

    public function restore(Request $request, $id = null)
    {
        if ($id) {
            $product = Product::withoutGlobalScope('active')->onlyTrashed()->findOrFail($id);
            $product->restore();

            return redirect()->route('products.index')
                ->with('success', "Product ($product->name) restored.");
        }

        Product::withoutGlobalScope('active')->onlyTrashed()->restore();
        return redirect()->route('products.index')
            ->with('success', "All trashed products restored.");
    }

    public function forceDelete($id = null)
    {
        if ($id) {
            $product = Product::withoutGlobalScope('active')->onlyTrashed()->findOrFail($id);
            $product->forceDelete();

            return redirect()->route('products.index')
                ->with('success', "Product ($product->name) deleted forever.");
        }

        Product::withoutGlobalScope('active')->onlyTrashed()->forceDelete();
        return redirect()->route('products.index')
            ->with('success', "All trashed products deleted forever.");
    }
}
