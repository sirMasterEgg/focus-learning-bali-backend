<?php

namespace App\Http\Controllers\Api\Admin;

use App\Classes\ResponseBuilder;
use App\Http\Resources\Share\Auth\UserResource;
use App\Loggable;
use App\Models\User;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    use Loggable;

    public function getAllUsers()
    {
        $query = request()->query('q');
        $size = request()->query('size') ?? 10;
        $from = request()->query('from');
        $to = request()->query('to');

        try {
            $users = User::where('role', 'user');
            if ($query) {
                $users = $users->whereLike('name', "%$query%");
            }
            if ($to && $from) {
                $users = $users
                    ->whereDate('created_at', '>=', $from)
                    ->whereDate('created_at', '<=', $to);
            }
            $users = $users->paginate($size);

            $this->logInfo('Successfully fetched users.', [
                'filters' => compact('query', 'from', 'to'),
                'total' => $users->total()
            ]);

            return ResponseBuilder::build(
                result: UserResource::collection($users),
                message: 'Success get donations',
                meta: [
                    'total' => $users->total(),
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'last_page' => $users->lastPage(),
                ],
            );
        } catch (\Throwable $e) {
            $this->logError('Failed to fetch users', ['exception' => $e]);
            throw $e;
        }
    }
}
