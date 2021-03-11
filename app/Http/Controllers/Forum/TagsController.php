<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Thread;
use App\Models\User;

class TagsController extends Controller
{
    public function show(Tag $tag)
    {
        $threads = [];
        $filter = (string) request('filter') ?: 'recent';

        if ($filter === 'recent') {
            $threads = Thread::feedByTagPaginated($tag);
        }

        if ($filter === 'resolved') {
            $threads = Thread::feedByTagQuery($tag)
                ->resolved()
                ->paginate(20);
        }

        if ($filter === 'active') {
            $threads = Thread::feedByTagQuery($tag)
                ->active()
                ->paginate(20);
        }

        $tags = Tag::orderBy('name')->get();
        $mostSolutions = User::mostSolutions()->take(3)->get();

        return view('forum.overview', compact('threads', 'filter', 'tags', 'mostSolutions') + ['activeTag' => $tag]);
    }
}
