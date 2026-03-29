<?php

namespace App\Services\Scanners;

class AccessibilityScanner
{
    use HasSafeCall;
    private const TIMEOUT = 8;

    public function scan(string $host): array
    {
        $checks   = [];
        $score    = 0;
        $maxScore = 0;

        $html = $this->safe(fn() => $this->fetchPage($host), '');

        // 1. HTML lang attribute
        $maxScore += 15;
        if ($html && preg_match('/<html[^>]+lang=["\'][a-zA-Z-]+["\']/i', $html)) {
            $score += 15;
            $checks[] = [
                'id'          => 'a11y_lang',
                'label'       => 'HTML lang attribute',
                'status'      => 'pass',
                'description' => 'The <html> element has a lang attribute — helps screen readers use the correct language.',
            ];
        } else {
            $checks[] = [
                'id'             => 'a11y_lang',
                'label'          => 'HTML lang attribute',
                'status'         => 'fail',
                'description'    => 'No lang attribute found on the <html> element.',
                'recommendation' => 'Add a lang attribute: <html lang="en"> or <html lang="nl">.',
            ];
        }

        // 2. Meta viewport
        $maxScore += 10;
        if ($html && preg_match('/<meta[^>]+name=["\']viewport["\']/i', $html)) {
            $score += 10;
            $checks[] = [
                'id'          => 'a11y_viewport',
                'label'       => 'Viewport meta tag',
                'status'      => 'pass',
                'description' => 'A viewport meta tag is present — enables correct rendering on mobile devices.',
            ];
        } else {
            $checks[] = [
                'id'             => 'a11y_viewport',
                'label'          => 'Viewport meta tag',
                'status'         => 'fail',
                'description'    => 'No viewport meta tag found.',
                'recommendation' => 'Add <meta name="viewport" content="width=device-width, initial-scale=1"> to your <head>.',
            ];
        }

        // 3. Page title
        $maxScore += 15;
        if ($html && preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m) && strlen(trim($m[1])) > 0) {
            $score += 15;
            $title = htmlspecialchars(substr(trim($m[1]), 0, 80));
            $checks[] = [
                'id'          => 'a11y_title',
                'label'       => 'Page title',
                'status'      => 'pass',
                'description' => "A descriptive page title is present: \"{$title}\"",
            ];
        } else {
            $checks[] = [
                'id'             => 'a11y_title',
                'label'          => 'Page title',
                'status'         => 'fail',
                'description'    => 'No <title> tag found or title is empty.',
                'recommendation' => 'Add a descriptive <title> tag to the <head> section.',
            ];
        }

        // 4. Meta description
        $maxScore += 10;
        $hasDesc = $html && (
            preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html) ||
            preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/i', $html)
        );
        if ($hasDesc) {
            $score += 10;
            $checks[] = [
                'id'          => 'a11y_meta_desc',
                'label'       => 'Meta description',
                'status'      => 'pass',
                'description' => 'A meta description is present — improves SEO and search result previews.',
            ];
        } else {
            $checks[] = [
                'id'             => 'a11y_meta_desc',
                'label'          => 'Meta description',
                'status'         => 'warn',
                'description'    => 'No meta description found.',
                'recommendation' => 'Add <meta name="description" content="..."> to improve SEO and accessibility.',
            ];
        }

        // 5. Images without alt attributes
        $maxScore += 20;
        $imgTotal = $html ? preg_match_all('/<img\b[^>]*>/i', $html, $imgs) : 0;
        if ($imgTotal > 0) {
            $imgNoAlt = 0;
            foreach ($imgs[0] as $img) {
                if (! preg_match('/\balt=["\'][^"\']*["\']/i', $img)) {
                    $imgNoAlt++;
                }
            }
            if ($imgNoAlt === 0) {
                $score += 20;
                $checks[] = [
                    'id'          => 'a11y_img_alt',
                    'label'       => 'Image alt attributes',
                    'status'      => 'pass',
                    'description' => "All {$imgTotal} images have alt attributes.",
                ];
            } else {
                $ratio = ($imgTotal - $imgNoAlt) / $imgTotal;
                $score += (int) round($ratio * 20);
                $checks[] = [
                    'id'             => 'a11y_img_alt',
                    'label'          => 'Image alt attributes',
                    'status'         => $imgNoAlt > 3 ? 'fail' : 'warn',
                    'description'    => "{$imgNoAlt} of {$imgTotal} images are missing alt attributes.",
                    'recommendation' => 'Add descriptive alt attributes to all <img> tags. Use alt="" for decorative images.',
                ];
            }
        } else {
            $score += 20;
            $checks[] = [
                'id'          => 'a11y_img_alt',
                'label'       => 'Image alt attributes',
                'status'      => 'pass',
                'description' => 'No images detected in the initial HTML.',
            ];
        }

        // 6. H1 heading
        $maxScore += 15;
        $h1Count = $html ? preg_match_all('/<h1\b[^>]*>/i', $html) : 0;
        if ($h1Count === 1) {
            $score += 15;
            $checks[] = [
                'id'          => 'a11y_h1',
                'label'       => 'Single H1 heading',
                'status'      => 'pass',
                'description' => 'Exactly one H1 heading found — correct for accessibility and SEO.',
            ];
        } elseif ($h1Count > 1) {
            $score += 7;
            $checks[] = [
                'id'             => 'a11y_h1',
                'label'          => 'Single H1 heading',
                'status'         => 'warn',
                'description'    => "{$h1Count} H1 headings found. Multiple H1s can confuse screen readers and hurt SEO.",
                'recommendation' => 'Use only one H1 per page. Use H2–H6 for sub-headings.',
            ];
        } else {
            $checks[] = [
                'id'             => 'a11y_h1',
                'label'          => 'Single H1 heading',
                'status'         => 'fail',
                'description'    => 'No H1 heading found on the page.',
                'recommendation' => 'Add a single <h1> tag that describes the main topic of the page.',
            ];
        }

        // 7. Form inputs with labels
        $maxScore += 15;
        $inputCount = $html ? preg_match_all(
            '/<input\b(?![^>]*type=["\'](?:hidden|submit|button|image|reset)["\'])[^>]*>/i',
            $html,
            $inputs
        ) : 0;
        if ($inputCount > 0) {
            $unlabeled = 0;
            foreach ($inputs[0] as $input) {
                $hasId   = preg_match('/\bid=["\']([^"\']+)["\']/i', $input, $idMatch);
                $hasAria = preg_match('/\baria-label(?:ledby)?=["\'][^"\']+["\']/i', $input);
                if ($hasId) {
                    $id = $idMatch[1];
                    if (! preg_match('/<label\b[^>]*for=["\']' . preg_quote($id, '/') . '["\']/i', $html) && ! $hasAria) {
                        $unlabeled++;
                    }
                } elseif (! $hasAria) {
                    $unlabeled++;
                }
            }
            if ($unlabeled === 0) {
                $score += 15;
                $checks[] = [
                    'id'          => 'a11y_labels',
                    'label'       => 'Form labels',
                    'status'      => 'pass',
                    'description' => "All {$inputCount} form inputs appear to have associated labels or ARIA attributes.",
                ];
            } else {
                $checks[] = [
                    'id'             => 'a11y_labels',
                    'label'          => 'Form labels',
                    'status'         => 'warn',
                    'description'    => "{$unlabeled} of {$inputCount} form inputs may be missing accessible labels.",
                    'recommendation' => 'Associate each <input> with a <label for="..."> or use aria-label/aria-labelledby.',
                ];
            }
        } else {
            $score += 15;
            $checks[] = [
                'id'          => 'a11y_labels',
                'label'       => 'Form labels',
                'status'      => 'pass',
                'description' => 'No form inputs requiring labels detected on this page.',
            ];
        }

        return [
            'category' => 'Accessibility',
            'icon'     => 'eye',
            'score'    => $maxScore > 0 ? (int) round(($score / $maxScore) * 100) : 0,
            'checks'   => $checks,
        ];
    }

    private function fetchPage(string $host): string
    {
        $ch = curl_init("https://{$host}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_RANGE          => '0-131071',
            CURLOPT_USERAGENT      => 'Mozilla/5.0 WebCheckApp/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        return $body ?: '';
    }

}
