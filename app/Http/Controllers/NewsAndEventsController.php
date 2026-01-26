<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Event;
use App\Models\Notice;

class NewsAndEventsController extends Controller
{

    public function newsEvents()
    {
        return view('navlinks.newsAndEvents', [
            'news' => News::orderBy('created_at', 'desc')->paginate(4),
            'most_viewed_news' => News::orderBy('news_views', 'desc')->take(6)->get(),
            'events' => Event::orderBy('created_at', 'desc')->take(4)->get(),
            'notice' => Notice::orderBy('created_at', 'desc')->take(7)->get(),
        ]);
    }
    public function showSingleNews(News $news_id)
    {
        $cookie_name = 'news_viewed_' . $news_id->id;
        if (!isset($_COOKIE[$cookie_name])) {
            $news_id->increment('news_views');
            setcookie($cookie_name, true, time() + (6 * 60 * 60), "/");
        }
        return view('navlinks.news.single_news', [
            'news' => $news_id,
            'latest_posts' => News::orderBy('created_at', 'desc')->take(6)->get(),


        ]);
    }

}
