<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Product;
use App\Services\Storefront\ActivityTiles;
use App\Services\Storefront\BestSellers;
use App\Support\Storefront;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(BestSellers $bestSellers, ActivityTiles $activityTiles): View
    {
        // Data hanya dimuat untuk seksi yang ditampilkan (Admin › Tampilan Beranda).
        $sections = Storefront::sections();
        $on = fn (string $section) => in_array($section, $sections, true);

        $activities = $on('activities') || config('shop.storefront.hero_rail', true)
            ? $activityTiles->forHome()
            : ['kind' => 'categories', 'tiles' => new Collection];

        return view('shop.home', [
            'activities' => $activities['tiles'],
            'activitiesKind' => $activities['kind'],
            'banners' => $on('banners') ? Banner::visible()->take(8)->get() : new Collection,
            'bestSellers' => $on('best_sellers') ? $bestSellers->forHome() : new Collection,
            'lookbook' => $on('lookbook')
                ? Product::active()->with(['images', 'activeVariants'])->has('images')->orderByDesc('is_featured')->latest()->take(6)->get()
                : new Collection,
            'onSale' => $on('promo') ? Product::active()->with('category')->onSale()->latest()->take(4)->get() : new Collection,
            'latestArticles' => $on('articles')
                ? Article::published()->with('category:id,name,slug')->latest('published_at')->take(3)->get()
                : new Collection,
        ]);
    }
}
