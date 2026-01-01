<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * User API Controller
 *
 * Handles user profile operations for authenticated users.
 *
 * @package App\Http\Controllers\Api\V1
 */
class UserController extends Controller
{
    /**
     * Display the authenticated user's profile.
     *
     * @param Request $request The HTTP request with authenticated user
     * @return UserResource The user resource with profile data
     */
    public function show(Request $request): UserResource
    {
        $user = $request->user();
        $user->load('loyaltyTier');

        return new UserResource($user);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param UpdateUserRequest $request The validated request
     * @return UserResource The updated user resource
     *
     * @bodyParam name string optional The user's name. Max 255 characters.
     * @bodyParam company_name string optional The company name. Max 255 characters.
     * @bodyParam phone string optional The phone number. Max 20 characters.
     */
    public function update(UpdateUserRequest $request): UserResource
    {
        $user = $request->user();

        $user->update($request->validated());
        $user->load('loyaltyTier');

        return new UserResource($user);
    }
}
