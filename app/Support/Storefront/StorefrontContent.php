<?php

namespace App\Support\Storefront;

use App\Enums\ContentStatus;
use App\Models\AnnouncementBar;
use App\Models\Category;
use App\Models\FooterSection;
use App\Models\HeroSlide;
use App\Models\HomepageSection;
use App\Models\NavigationMenu;
use App\Models\NavigationMenuItem;
use App\Models\ProductCollection;
use App\Models\PromotionalBanner;
use App\Models\ServiceBenefit;
use App\Models\SocialLink;
use App\Models\StaticPage;
use App\Models\StoreLocation;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorefrontContent
{
    public function announcementBar(): ?AnnouncementBar
    {
        return $this->scheduled(AnnouncementBar::query())
            ->orderBy('sort_order')
            ->latest()
            ->first();
    }

    public function navigationMenu(string $slug = 'primary'): ?NavigationMenu
    {
        return NavigationMenu::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'items' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->with([
                        'children' => fn ($query) => $query
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('label'),
                        'linkable',
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('label'),
            ])
            ->first();
    }

    /**
     * @return Collection<int, FooterSection>
     */
    public function footerSections(): Collection
    {
        return FooterSection::query()
            ->where('is_active', true)
            ->with(['links' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('label')])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, SocialLink>
     */
    public function socialLinks(): Collection
    {
        return SocialLink::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('platform')
            ->get();
    }

    /**
     * @return array{
     *     heroSlides: Collection<int, HeroSlide>,
     *     homepageSections: Collection<int, HomepageSection>,
     *     promotionalBanners: Collection<int, PromotionalBanner>,
     *     serviceBenefits: Collection<int, ServiceBenefit>,
     *     testimonials: Collection<int, Testimonial>,
     *     storeLocations: Collection<int, StoreLocation>
     * }
     */
    public function home(): array
    {
        return [
            'heroSlides' => $this->scheduled(HeroSlide::query())->orderBy('sort_order')->latest()->get(),
            'homepageSections' => $this->scheduled(HomepageSection::query())->orderBy('sort_order')->latest()->get(),
            'promotionalBanners' => $this->scheduled(PromotionalBanner::query())->where('placement', 'home')->orderBy('sort_order')->latest()->get(),
            'serviceBenefits' => ServiceBenefit::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'testimonials' => Testimonial::query()->where('is_active', true)->orderBy('sort_order')->latest()->limit(6)->get(),
            'storeLocations' => StoreLocation::query()->where('is_active', true)->orderBy('sort_order')->limit(4)->get(),
        ];
    }

    public function publishedPage(string $slug): ?StaticPage
    {
        return StaticPage::query()
            ->where('slug', $slug)
            ->where('status', ContentStatus::Published)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->first();
    }

    public function navigationItemUrl(NavigationMenuItem $item): string
    {
        if (filled($item->url)) {
            return (string) $item->url;
        }

        $linkable = $item->linkable;

        if ($linkable instanceof StaticPage) {
            return route('pages.show', ['page' => $linkable->slug]);
        }

        if ($linkable instanceof Category) {
            return url('/categories/'.$linkable->slug);
        }

        if ($linkable instanceof ProductCollection) {
            return url('/collections/'.$linkable->slug);
        }

        return '#';
    }

    public function mediaUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function scheduled(Builder $query): Builder
    {
        return $query
            ->where('status', ContentStatus::Published)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
}
