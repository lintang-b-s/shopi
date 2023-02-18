<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\PostComment;
use Illuminate\Http\Request;
use App\Http\Resources\Post as PostResource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use Illuminate\Database\Eloquent\Builder;


use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use File;

class BlogPostController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth')
            ->only(['create', 'store', 'edit', 'update', 'destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //

        // return PostResource::collection(BlogPost::mostComment()->paginate(20));
        $query = BlogPost::query()->with('author')->with('tag')->with('category');

        // $filter = request('filter');

        // [$criteria, $value] = explode(':', $filter);
        // dd($query);

        $query->when(request()->filled('filter'), function ($query) {
            $filters = explode(',', request('filter'));

            foreach ($filters as $filter) {
                [$criteria, $value] = explode(':', $filter);
                $query->whereHas($criteria , function (Builder $query) use ($value, $criteria){
                    $query->where('title' , $value);
                });
            }

            return $query;
         });

         return PostResource::collection($query->paginate(20));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePostRequest $request)
    {
        //
       
        $data = $request->all();
        $storePostRequest = new StorePostRequest;
        $validator = app('validator')->make($data, $storePostRequest->rules());
        if ($validator->fails()) {
            throw new \Dingo\Api\Exception\StoreResourceFailedException('Could not create new user.', $validator->errors());
        }
        $user = app('Dingo\Api\Auth\Auth')->user();
      
     
        $data['author_id'] = $user->user_id;
        

        $path = public_path('app/public/assets/file-post');
        if(!File::isDirectory($path)){
            $response = Storage::makeDirectory('public/assets/file-post');
        }

        // change file locations
        if(isset($data['thumbnail'])){
            $data['thumbnail'] = $request->file('thumbnail')->store(
                'assets/file-post', 'public'
            );
        }else{
            $data['thumbnail'] = "";

        }

        $blogPost = BlogPost::create($data);
    

        if ($request->filled('category_id')) {
           
            $category_id = json_decode($request->input('category_id', []), true);
            
            $blogPost->category()->attach($category_id);
        }
        if ($request->filled('tag_id'))
        {
            $tag_id = json_decode($request->input('tag_id', []), true);
            $blogPost->tag()->attach($tag_id);
        }


    
        return response()->json(['data' => new PostResource($blogPost)]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BlogPost  $blogPost
     * @return \Illuminate\Http\Response
     */
    public function show(BlogPost $blogPost)
    {
       
        return new PostResource($blogPost->load('post_comment'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BlogPost  $blogPost
     * @return \Illuminate\Http\Response
     */
    public function edit(BlogPost $blogPost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BlogPost  $blogPost
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePostRequest $request, BlogPost $blogPost)
    {

        $this->authorize('',$blogPost);
        
        $data = $request->all();

        if(isset($data['thumbnail'])){

            // first checking old thumbnail to delete from storage
           $get_item = $blogPost['thumbnail'];

           // change file locations
           $data['thumbnail'] = $request->file('thumbnail')->store(
               'assets/file-post', 'public'
           );

           // delete old thumbnail from storage
           $data_old = 'storage/'.$get_item;
           if (File::exists($data_old)) {
               File::delete($data_old);
           }else{
               File::delete('storage/app/public/'.$get_item);
           }

       }
     
        $blogPost->update($data);

        if ($request->filled('category_id')) {
            $category_id = json_decode($request->input('category_id', []), true);
            $blogPost->category()->sync($category_id);
        }
        if ($request->filled('tag_id'))
        {
            $tag_id = json_decode($request->input('tag_id', []), true);
            $blogPost->tag()->sync($tag_id);
        }
        
        
        return  response()->json(['data' =>  new PostResource($blogPost->refresh())]);       
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BlogPost  $blogPost
     * @return \Illuminate\Http\Response
     */
    public function destroy(BlogPost $blogPost)
    {

       $this->authorize($blogPost);

       
        $get_item = $blogPost['thumbnail'];

        $data = 'storage/'.$get_item;

        if (File::exists($data)) {
            File::delete($data);
        }else{
            File::delete('storage/app/public/'.$get_item);
        };

        $blogPost->forceDelete();

        
        return response()->json(['message' => 'post was succesfuly deleted']);
    }
}
