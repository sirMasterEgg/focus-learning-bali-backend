<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ResponseBuilder;
use App\Http\Requests\Donation\CreateDonationRequest;
use App\Http\Requests\Donation\UpdateDonationRequest;
use App\Http\Resources\Admin\DonationHistoryDetailsResource;
use App\Http\Resources\Admin\DonationHistoryResource;
use App\Http\Resources\Admin\DonationResource;
use App\Loggable;
use App\Models\Donation;
use App\Models\User;
use App\Models\UserDonation;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class DonationController extends Controller
{
    use Loggable;

    public function index()
    {
        try {
            $query = request()->query('q');
            $size = request()->query('size') ?? 10;
            $from = request()->query('from');
            $to = request()->query('to');
            $donations = Donation::withTrashed();
            if ($query) {
                $donations = $donations->whereLike('title', "%$query%");
            }
            if ($to && $from) {
                $donations = $donations->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
            }
            $donations = $donations
                ->orderByRaw("deleted_at IS NULL DESC")
                ->orderByRaw("IF(deleted_at IS NULL, created_at, deleted_at) DESC")
                ->paginate($size);

            $this->logInfo('Fetched donation list', ['query' => $query]);

            return ResponseBuilder::build(
                result: DonationResource::collection($donations),
                message: 'Success get donations',
                meta: [
                    'total' => $donations->total(),
                    'current_page' => $donations->currentPage(),
                    'per_page' => $donations->perPage(),
                    'last_page' => $donations->lastPage(),
                ],
            );
        } catch (\Throwable $e) {
            $this->logError('Error fetching donation list', [], $e);
            throw $e;
        }
    }

    public function store(CreateDonationRequest $request)
    {
        try {
            \DB::beginTransaction();

            $donation = Donation::create([
                'title' => $request->input('title'),
                'recipient' => $request->input('recipient'),
                'description' => $request->input('description'),
                'thumbnail' => '',
                'program_image' => '',
                'target' => $request->input('target'),
                'is_active' => $request->input('is_active'),
            ]);

            $filenameBase = $donation->human_readable_id;

            if ($request->hasFile('program_image')) {
                $programImage = $request->file('program_image');
                $programImagePath = $programImage->storePubliclyAs(
                    'donations',
                    $filenameBase . '_program_image.' . $programImage->extension(),
                    'public'
                );
                $donation->program_image = \URL::to('/') . \Storage::url($programImagePath);
            }

            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $thumbnailPath = $thumbnail->storePubliclyAs(
                    'donations',
                    $filenameBase . '_thumbnail.' . $thumbnail->extension(),
                    'public'
                );
                $donation->thumbnail = \URL::to('/') . \Storage::url($thumbnailPath);
            }

            $donation->save();

            \DB::commit();

            $donation->refresh();

            $this->logInfo('Donation created', ['donation_id' => $donation->id]);

            return ResponseBuilder::build(
                new DonationResource($donation),
                'Success create donation',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            \DB::rollBack();
            $this->logError('Failed to create donation', ['request' => $request->all()], $e);
            return ResponseBuilder::build(
                null,
                'Failed create donation',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show(Donation $donation)
    {
        $this->logInfo("Fetched donation with ID $donation->id");
        return ResponseBuilder::build(
            new DonationResource($donation),
            "Success get donation with id $donation->id"
        );
    }

    public function update(UpdateDonationRequest $request, Donation $donation)
    {
        try {
            \DB::beginTransaction();

            $updateData = $request->only(['title', 'recipient', 'description', 'target', 'is_active']);

            if ($request->hasFile('program_image')) {
                $programImage = $request->file('program_image');
                $programImagePath = $programImage->storePubliclyAs(
                    'donations',
                    $donation->human_readable_id . '_program_image.' . $programImage->extension(),
                    'public'
                );
                $updateData['program_image'] = \URL::to('/') . \Storage::url($programImagePath);
            }

            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $thumbnailPath = $thumbnail->storePubliclyAs(
                    'donations',
                    $donation->human_readable_id . '_thumbnail.' . $thumbnail->extension(),
                    'public'
                );
                $updateData['thumbnail'] = \URL::to('/') . \Storage::url($thumbnailPath);
            }

            $donation->update($updateData);

            \DB::commit();

            $this->logInfo("Donation updated", ['donation_id' => $donation->id]);

            return ResponseBuilder::build(
                new DonationResource($donation),
                "Success update donation with id $donation->id"
            );
        } catch (\Throwable $e) {
            \DB::rollBack();
            $this->logError("Failed to update donation", ['donation_id' => $donation->id], $e);
            return ResponseBuilder::build(
                null,
                'Failed create donation',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function destroy(Donation $donation)
    {
        $donation->delete();

        $this->logInfo("Donation soft deleted", ['donation_id' => $donation->id]);

        return ResponseBuilder::build(
            null,
            'Successfully delete donation',
            Response::HTTP_NO_CONTENT
        );
    }

    public function getDonationHistories()
    {
        try {
            $query = request()->query('q');
            $size = request()->query('size') ?? 10;
            $from = request()->query('from');
            $to = request()->query('to');
            $donations = UserDonation::with(['donation']);
            if ($query) {
                $donations = $donations->whereLike('users_donations.human_readable_id', "%$query%");
            }
            if ($to && $from) {
                $donations = $donations->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
            }
            $donations = $donations
                ->orderByRaw("created_at DESC")
                ->paginate($size);

            $this->logInfo('Fetched donation histories');

            return ResponseBuilder::build(
                result: DonationHistoryResource::collection($donations),
                message: 'Success get donation histories',
                meta: [
                    'total' => $donations->total(),
                    'current_page' => $donations->currentPage(),
                    'per_page' => $donations->perPage(),
                    'last_page' => $donations->lastPage(),
                ],
            );
        } catch (\Throwable $e) {
            $this->logError('Error fetching donation histories', [], $e);
            throw $e;
        }
    }

    public function getDonationHistoryDetails(string $userDonation)
    {
        try {
            $detailsDonation = UserDonation::with(['donation'])
                ->where('human_readable_id', $userDonation)
                ->firstOrFail();

            $this->logInfo('Fetched donation history details', ['human_readable_id' => $userDonation]);

            return ResponseBuilder::build(
                result: new DonationHistoryDetailsResource($detailsDonation),
                message: 'Success get donation history details',
            );
        } catch (\Throwable $e) {
            $this->logError('Failed to get donation history details', ['human_readable_id' => $userDonation], $e);
            throw $e;
        }
    }

    public function getDonationStatistics()
    {
        try {
            $from = request()->query('from');
            $to = request()->query('to');
            $successDonations = UserDonation::where('payment_status', 'success');
            $activeDonations = Donation::where('is_active', true);
            $users = User::where('role', 'user');
            if ($to && $from) {
                $successDonations = $successDonations->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
                $activeDonations = $activeDonations->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
                $users = $users->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
            }

            $this->logInfo('Fetched donation statistics');

            return ResponseBuilder::build(
                result: [
                    'total_donations' => $successDonations->sum('users_donations.amount'),
                    'total_users' => str($users->count()),
                    'total_active_programs' => str($activeDonations->count()),
                ],
                message: 'Success get donation histories statistics',
            );
        } catch (\Throwable $e) {
            $this->logCritical('Error fetching donation statistics', [], $e);
            throw $e;
        }
    }
}
