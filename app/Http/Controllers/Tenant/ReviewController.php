<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['service', 'customer']);
        if ($request->filled('status')) {
            $query->where('is_published', $request->status === 'published');
        }
        if ($request->filled('rating')) $query->where('rating', $request->rating);
        $reviews = $query->latest()->paginate(20)->withQueryString();

        $summary = [
            'avg' => Review::where('is_published', true)->avg('rating') ?? 0,
            'count' => Review::where('is_published', true)->count(),
            'pending' => Review::where('is_published', false)->count(),
        ];

        return view('reviews.index', compact('reviews', 'summary'));
    }

    public function publish(Review $review)
    {
        $review->update(['is_published' => true]);
        ActivityLog::record('review.publish', $review, "Publish review dari {$review->reviewer_name}");
        return back()->with('success', 'Review dipublikasikan.');
    }

    public function unpublish(Review $review)
    {
        $review->update(['is_published' => false]);
        return back()->with('success', 'Review di-unpublish.');
    }

    public function reply(Request $request, Review $review)
    {
        $validated = $request->validate(['admin_reply' => 'required|string|max:1000']);
        $review->update($validated);
        ActivityLog::record('review.reply', $review, "Reply review dari {$review->reviewer_name}");
        return back()->with('success', 'Balasan disimpan.');
    }

    public function destroy(Review $review)
    {
        ActivityLog::record('review.delete', $review, "Delete review {$review->id}");
        $review->delete();
        return back()->with('success', 'Review dihapus.');
    }
}
