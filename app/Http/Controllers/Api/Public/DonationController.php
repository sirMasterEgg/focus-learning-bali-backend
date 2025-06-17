<?php

namespace App\Http\Controllers\Api\Public;

use App\Classes\ResponseBuilder;
use App\Http\Resources\Public\DetailDonationResource;
use App\Http\Resources\Public\DonationCardResource;
use App\Loggable;
use App\Models\Donation;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class DonationController extends Controller
{
    use Loggable;

    public function getAllDonationForUsers()
    {
        $size = request()->query('size') ?? 10;

        $donations = Donation::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate($size);

        $this->logInfo('Fetched all active donations', [
            'size' => $size,
            'total' => $donations->total()
        ]);

        return ResponseBuilder::build(
            result: DonationCardResource::collection($donations),
            message: 'Success get donations',
            meta: [
                'total' => $donations->total(),
                'current_page' => $donations->currentPage(),
                'per_page' => $donations->perPage(),
                'last_page' => $donations->lastPage(),
            ],
        );
    }

    public function getDonationDetailForUsers(Donation $donation)
    {
        try {
            $this->logInfo('Fetched donation detail', [
                'donation_id' => $donation->id,
                'human_readable_id' => $donation->human_readable_id,
            ]);

            return ResponseBuilder::build(
                new DetailDonationResource($donation),
                "Success get donation with id $donation->human_readable_id"
            );
        } catch (\Exception $e) {
            $this->logError('Failed to get donation detail', [
                'donation_id' => $donation->id ?? null
            ], $e);

            return ResponseBuilder::build(
                null,
                "Donation with id $donation->human_readable_id not found",
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
