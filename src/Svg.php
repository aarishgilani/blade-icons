<?php

declare(strict_types=1);

namespace BladeUI\Icons;

use BladeUI\Icons\Concerns\RendersAttributes;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

final class Svg implements Htmlable
{
    use RendersAttributes;

    private string $name;

    private string $contents;

    public function __construct(string $name, string $contents, array $attributes = [])
    {
        $this->name = $name;
        $this->contents = $this->deferContent($contents, $attributes['defer'] ?? false);

        unset($attributes['defer']);

        $this->attributes = $attributes;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function contents(): string
    {
        return $this->contents;
    }

    /**
     * This method adds a title element and an aria-labelledby attribute to the SVG.
     * To comply with accessibility standards, SVGs should have a title element.
     * Check accessibility patterns for icons: https://www.deque.com/blog/creating-accessible-svgs/
     */
    public function addTitle(string $title): string
    {
        // generate a random id for the title element
        $titleId = 'svg-inline--title-'.Str::random(10);

        // create title element
        $titleElement = '<title id="'.$titleId.'">'.$title.'</title>';

        // add aria-labelledby attribute to svg element
        $this->attributes['aria-labelledby'] = $titleId;

        // add title element to svg
        return preg_replace('/<svg[^>]*>/', "$0$titleElement", $this->contents);
    }

    public function toHtml(): string
    {
        // Check if the title attribute is set and add a title element to the SVG
        if (array_key_exists('title', $this->attributes)) {
            $this->contents = $this->addTitle($this->attributes['title']);
        }

        return str_replace(
            '<svg',
            sprintf('<svg%s', $this->renderAttributes()),
            $this->contents,
        );
    }

    protected function deferContent(string $contents, $defer = false): string
    {
        if ($defer === false) {
            return $contents;
        }

        $svgContent = strip_tags($contents, ['circle', 'ellipse', 'line', 'path', 'polygon', 'polyline', 'rect', 'g', 'mask', 'defs', 'use']);

        // Force Unix line endings for hash.
        $hashContent = str_replace(PHP_EOL, "\n", $svgContent);
        $hash = 'icon-'.(is_string($defer) ? $defer : md5($hashContent));

        $contents = str_replace($svgContent, strtr('<use href=":href"></use>', [':href' => '#'.$hash]), $contents).PHP_EOL;
        $svgContent = ltrim($svgContent, PHP_EOL);
        $contents .= <<<BLADE
                @once("{$hash}")
                    @push("bladeicons")
                        <g id="{$hash}">
                            {$svgContent}
                        </g>
                    @endpush
                @endonce
            BLADE;

        return $contents;
    }
}
