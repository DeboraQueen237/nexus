<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Poll;
use App\Models\KbArticle;
use App\Models\Meeting;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'messages' => auth()->user()->messages()->count(),
            'polls' => auth()->user()->polls()->count(),
            'articles' => auth()->user()->articles()->count(),
            'meetings' => auth()->user()->meetings()->count(),
        ];

        $recentActivity = [
            'messages' => auth()->user()->messages()->latest()->limit(5)->get(),
            'polls' => auth()->user()->polls()->latest()->limit(5)->get(),
            'articles' => auth()->user()->articles()->latest()->limit(5)->get(),
            'meetings' => auth()->user()->meetings()->latest()->limit(5)->get(),
        ];

        return view('dashboard.index', compact('stats', 'recentActivity'));
    }
}