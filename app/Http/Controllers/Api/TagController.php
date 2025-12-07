<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TagController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/tags",
     *     tags={"Tags"},
     *     summary="Get all user tags",
     *     description="Retrieve all tags belonging to authenticated user with optional search",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in tag name",
     *         required=false,
     *         @OA\Schema(type="string", example="urgent")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tags retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tag::where('user_id', Auth::id());

            // Search filter
            if ($request->has('search') && $request->search) {
                $search = strtolower($request->search);
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            }

            // Order by name
            $query->orderBy('name', 'asc');

            $tags = $query->get();

            return response()->json([
                'success' => true,
                'data' => $tags
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
