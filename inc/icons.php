<?php
declare(strict_types=1);
namespace App;

function icon_list(): array {
    return [
        'link'     => '/assets/icons/link.svg',
        'github'   => '/assets/icons/github.svg',
        'linkedin' => '/assets/icons/linkedin.svg',
        'youtube'  => '/assets/icons/youtube.svg',
        'x'        => '/assets/icons/x.svg',
        'facebook' => '/assets/icons/facebook.svg',
        'instagram'=> '/assets/icons/instagram.svg',
        'tiktok'   => '/assets/icons/tiktok.svg',
        'mastodon' => '/assets/icons/mastodon.svg',
        'threads'  => '/assets/icons/threads.svg',
        'substack' => '/assets/icons/substack.svg',
        'bluesky'  => '/assets/icons/bluesky.svg',
        'apple-podcasts'    => '/assets/icons/apple-podcasts.svg',
        'spotify'  => '/assets/icons/spotify.svg',
        'website'  => '/assets/icons/website.svg',
        'email'    => '/assets/icons/email.svg',
        'phone'    => '/assets/icons/phone.svg',
    ];
}

function render_icon_svg(string $slug): string {
    $map = icon_list();
    if (!isset($map[$slug])) return '';
    $path = $_SERVER['DOCUMENT_ROOT'] . $map[$slug];
    if (!is_file($path)) return '';
    $svg = file_get_contents($path);
    // Minimal sanitization: strip script tags
    $svg = preg_replace('#<\s*script[^>]*>.*?<\s*/\s*script\s*>#is', '', $svg);
    return $svg ?: '';
}
