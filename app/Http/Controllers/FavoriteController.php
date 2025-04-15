<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;

class FavoriteController extends Controller
{
    public function toggleFavorite(Request $request, User $user, $targetUserId)
    {
        $targetUser = User::findOrFail($targetUserId);

        if ($user->favorites()->where('favorite_user_id', $targetUserId)->exists()) {
            $user->favorites()->detach($targetUserId);
            return ResponseHelper::withSuccess('User unfavorited.');
        }

        $user->favorites()->attach($targetUserId);
        return ResponseHelper::withSuccess('User favorited.');
    }

    public function listFavorites(User $user)
    {
        $favorites = $user->favorites()->with('personalProfile')->get(); // include extra info if needed
        return ResponseHelper::withSuccess('Favorite users retrieved.', $favorites);
    }
}
