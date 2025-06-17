<?php

namespace App\Http\Controllers\Api\Public;

use App\Classes\ResponseBuilder;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\Share\Auth\UserResource;
use App\Loggable;
use Auth;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    use Loggable;

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        $changes = [];

        if (isset($request['name']) && $request['name'] !== $user->name) {
            $changes['name'] = $request['name'];
        }

        if (isset($request['email']) && $request['email'] !== $user->email) {
            $changes['email'] = $request['email'];
        }

        if (isset($request['title']) && $request['title'] !== $user->title) {
            $changes['title'] = $request['title'];
        }

        $user->update($changes);

        $this->logInfo('User profile updated', [
            'user_id' => $user->id,
            'changes' => $changes
        ]);

        return ResponseBuilder::build(
            new UserResource($user),
            'Profile updated successfully'
        );
    }
}
