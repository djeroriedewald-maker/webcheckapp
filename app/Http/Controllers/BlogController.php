<?php

namespace App\Http\Controllers;

class BlogController extends Controller
{
    private const ARTICLES = [
        'what-is-owasp-top-10' => [
            'title'       => 'What Is the OWASP Top 10? A Complete Guide for 2025',
            'description' => 'Learn about the OWASP Top 10 web application security risks, why they matter, and how to protect your website against each vulnerability.',
            'category'    => 'Security',
            'date'        => '2026-03-15',
            'read_time'   => 8,
        ],
        'ssl-certificate-best-practices' => [
            'title'       => 'SSL Certificate Best Practices: The Complete Checklist',
            'description' => 'Everything you need to know about SSL/TLS certificates: setup, HSTS, cipher suites, auto-renewal, and common mistakes to avoid.',
            'category'    => 'SSL',
            'date'        => '2026-03-20',
            'read_time'   => 6,
        ],
        'security-headers-explained' => [
            'title'       => 'HTTP Security Headers Explained: What They Are and How to Set Them',
            'description' => 'A practical guide to Content-Security-Policy, X-Frame-Options, HSTS, and other HTTP security headers that protect your website.',
            'category'    => 'Headers',
            'date'        => '2026-03-25',
            'read_time'   => 7,
        ],
        'email-spoofing-prevention' => [
            'title'       => 'How to Prevent Email Spoofing: SPF, DKIM, and DMARC Setup Guide',
            'description' => 'Protect your domain from email spoofing and phishing attacks with SPF, DKIM, and DMARC. Step-by-step configuration guide.',
            'category'    => 'DNS',
            'date'        => '2026-04-01',
            'read_time'   => 6,
        ],
        'website-security-checklist' => [
            'title'       => 'The Ultimate Website Security Checklist for 2025',
            'description' => 'A comprehensive security checklist covering SSL, headers, DNS, exposed files, malware, and more. Scan your site and fix issues in order of priority.',
            'category'    => 'Security',
            'date'        => '2026-04-03',
            'read_time'   => 10,
        ],
    ];

    public function index()
    {
        $articles = collect(self::ARTICLES)->map(fn($a, $slug) => array_merge($a, ['slug' => $slug]));

        return view('blog.index', compact('articles'));
    }

    public function show(string $slug)
    {
        $article = self::ARTICLES[$slug] ?? null;

        if (! $article) {
            abort(404);
        }

        $article['slug'] = $slug;

        // Get related articles (same category, excluding current)
        $related = collect(self::ARTICLES)
            ->filter(fn($a, $s) => $s !== $slug && $a['category'] === $article['category'])
            ->take(2)
            ->map(fn($a, $s) => array_merge($a, ['slug' => $s]));

        return view("blog.articles.{$slug}", compact('article', 'related'));
    }

    public static function getArticles(): array
    {
        return self::ARTICLES;
    }
}
