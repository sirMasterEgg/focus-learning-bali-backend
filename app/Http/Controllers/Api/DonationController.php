<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponse;
use App\Http\Requests\DonationRequest;
use App\Http\Resources\DonationResource;
use App\Models\Category;
use App\Models\Donation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response;

class DonationController
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Donation::class);

        $donations = Donation::with('categories')->get();

        return ApiResponse::sendResponse(DonationResource::collection($donations), 'Success get donations');
    }

    public function store(DonationRequest $request)
    {
        $this->authorize('create', Donation::class);

        $categories = Category::whereIn('id', $request->category_id)->get();

        if ($categories->count() !== count($request->category_id)) {
            return ApiResponse::sendResponse(null, 'Category not found', Response::HTTP_NOT_FOUND);
        }

        $donations = Donation::create([
            'title' => $request->title,
            'description' => $request->description,
            'recipient' => $request->recipient,
            'quote' => $request->quote,
            'target' => $request->target,
            'banner' => $request->banner,
            'accept_donation' => $request->accept_donation,
        ]);

        $donations->categories()->attach($categories->pluck('id')->toArray(), ['created_at' => now(), 'updated_at' => now()]);
        $donations->load('categories');

        return ApiResponse::sendResponse(new DonationResource($donations), 'Success create donation', Response::HTTP_CREATED);
    }

    public function show(Donation $donation)
    {
        $this->authorize('view', $donation);

        $donation->load('categories');

        return ApiResponse::sendResponse(new DonationResource($donation), "Success get donation with id $donation->id");
    }

    public function update(DonationRequest $request, Donation $donation)
    {
        $this->authorize('update', $donation);

        $donation->update($request->except('category_id'));
        $donation->categories()->sync($request->category_id);
        $donation->load('categories');

        return ApiResponse::sendResponse(new DonationResource($donation), "Success update donation with id $donation->id");
    }

    public function destroy(Donation $donation)
    {
        $this->authorize('delete', $donation);

        $donation->delete();

        return ApiResponse::sendResponse(null, 'Successfully delete donation');
    }
}
