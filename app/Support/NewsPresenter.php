<?php

namespace App\Support;

use App\Models\News;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/*
 * What a news card says, worked out from the story the way the legacy view
 * did inline, for each of its three kinds of card.
 */
final class NewsPresenter
{
    /** Which photo a card uses: the sizetype the admin gave it when uploading. */
    public const TILE_PHOTO = 0;

    public const LEAD_PHOTO = 1;

    public const PINNED_PHOTO = 2;

    /** Where a card's story breaks into lines (position => the fewest words that earns the break), how many pieces it keeps and the word count above which it ends in "...". */
    private const LEAD_EXCERPT = ['breaks' => [18 => 18], 'take' => 37, 'more' => 36];

    private const PINNED_EXCERPT = ['breaks' => [15 => 15, 31 => 30, 47 => 45], 'take' => 63, 'more' => 60];

    public function __construct(public readonly News $news) {}

    public function title(): string
    {
        return (string) $this->news->content?->title;
    }

    /** The place the story is about; the national "All" place and a story with none are Syria. */
    public function place(): string
    {
        $name = $this->news->place?->name;

        return $name === null || $name === 'All' ? 'Syria' : $name;
    }

    /** The story's page: its id, its place as a slug and its date as digits. */
    public function url(): string
    {
        return '/our-news/'.$this->news->id.'-'.Str::slug(strtolower($this->place())).'-'.($this->date()?->format('Ymd') ?? '00000000');
    }

    private function date(): ?Carbon
    {
        return $this->news->date === null ? null : Carbon::parse($this->news->date);
    }

    /** The date over a story's page: 6/6/2026. */
    public function articleDate(): string
    {
        return $this->date()?->format('j/n/Y') ?? '';
    }

    /**
     * The id of the story's YouTube video, whichever form of address the
     * admin pasted (embed, watch or youtu.be); null for no video or any
     * other host.
     */
    public function videoId(): ?string
    {
        preg_match('~^https://(?:www\.)?(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?v=)|youtu\.be/)([\w-]+)~', trim((string) $this->news->video), $match);

        return $match[1] ?? null;
    }

    /** The name of the attached file, as it shows when the paperclip is pointed at: /files/news/ is cut off. */
    public function fileName(): ?string
    {
        $file = (string) $this->news->file;

        return $file === '' || $file === '0' ? null : substr($file, 12);
    }

    /** The line over a card's title: 06/06/2026 - Homs. */
    public function meta(): string
    {
        return ($this->date()?->format('d/m/Y') ?? '').' - '.$this->place();
    }

    /** How long the story's video runs, for the badge on a card (03:54, or 1:02:03 from an hour up); null for an article. */
    public function duration(): ?string
    {
        $duration = $this->news->duration;

        if (empty($duration)) {
            return null;
        }

        return Carbon::parse($duration)->format(str_starts_with($duration, '00:') ? 'i:s' : 'H:i:s');
    }

    /** The photo of the given kind, or the story's first photo when it has none of that kind. */
    public function photo(int $kind): ?string
    {
        $photos = $this->news->newsImages;

        return ($photos->firstWhere('sizetype', $kind) ?? $photos->first())?->path;
    }

    /** The beginning of a lead story's text, broken into lines. */
    public function leadExcerpt(): HtmlString
    {
        return $this->excerpt(...self::LEAD_EXCERPT);
    }

    /** The beginning of a pinned story's text, broken into lines. */
    public function pinnedExcerpt(): HtmlString
    {
        return $this->excerpt(...self::PINNED_EXCERPT);
    }

    /**
     * The text without its markup, cut to $take pieces, with a line break
     * after the given word counts and "..." when it is longer than $more.
     * Legacy cut on single spaces, so a word followed by a line break in the
     * story counts as one; kept, as the cut points were tuned to it.
     *
     * @param  array<int, int>  $breaks  position to insert a break at => the least words that earns it
     */
    private function excerpt(array $breaks, int $take, int $more): HtmlString
    {
        $words = explode(' ', strip_tags((string) $this->news->content?->content));
        $total = count($words);

        foreach ($breaks as $position => $least) {
            if ($total > $least) {
                array_splice($words, $position, 0, "\0");
            }
        }

        $text = implode(' ', array_slice($words, 0, $take)).($total > $more ? '...' : '');

        // The story's own text is escaped; only the breaks are markup.
        return new HtmlString(str_replace("\0", '<br>', e(html_entity_decode($text))));
    }
}
