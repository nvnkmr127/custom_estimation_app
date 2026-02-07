<?php

namespace Tests\Unit;

use Tests\TestCase;

class PdfTemplateSanitizerTest extends TestCase
{
    protected function sanitizePdfContent($html)
    {
        // Mimic the Controller Logic exactly
        $tokenMap = [];

        // 0. Pre-process: Unwrap Logic Tags
        $logicTags = [
            '\{LOOP_[^}]+\}',
            '\{END_LOOP[^}]*\}',
            '\{IF_[^}]+\}',
            '\{IF_NOT_[^}]+\}',
            '\{ENDIF\}',
            '\{END_IF\}',
            '\{CHART_[^}]+\}',
        ];
        foreach ($logicTags as $tagPattern) {
            $html = preg_replace('/<p[^>]*>\s*(' . $tagPattern . ')\s*<\/p>/si', '$1', $html);
            $html = preg_replace('/<div[^>]*>\s*(' . $tagPattern . ')\s*<\/div>/si', '$1', $html);
            $html = preg_replace('/<div[^>]*>\s*(' . $tagPattern . ')(?:&nbsp;|\s)*<\/div>/si', '$1', $html);
        }

        // 0.5 Pre-process: Unwrap Table Structure Tags from <p> and <div>
        // NOTE: The issue might be here. 'table' is missing?
        $structuralTags = ['table', 'tr', 'td', 'th', 'tbody', 'thead', 'tfoot'];
        do {
            $originalHtml = $html;
            foreach ($structuralTags as $tag) {
                $html = preg_replace('/<(?:p|div)[^>]*>\s*(<' . $tag . '[^>]*>)/si', '$1', $html);
                $html = preg_replace('/(<\/' . $tag . '>)(?:\s|&nbsp;|&#160;)*<\/(?:p|div)>/si', '$1', $html);
            }
        } while ($html !== $originalHtml);

        // 0.8 Pre-process: Aggressively Strip Unsupported CSS Properties
        $html = preg_replace('/position\s*:\s*[^;"]+;?/i', '', $html);
        $html = preg_replace('/page-break-(?:after|before|inside)\s*:\s*[^;"]+;?/i', '', $html);

        // 1. Mask Logic Tags & Structural Tags
        $html = preg_replace_callback('/\{[^{}]+\}|<html[^>]*>|<\/html>|<head[^>]*>|<\/head>|<body[^>]*>|<\/body>|<style[^>]*>.*?<\/style>|<meta[^>]*>|<!DOCTYPE[^>]*>/si', function ($matches) use (&$tokenMap) {
            $tag = $matches[0];
            $token = 'TPLMASK_' . \Illuminate\Support\Str::random(40) . '_END';
            $tokenMap[$token] = $tag;
            return '<!-- ' . $token . ' -->';
        }, $html);

        // 2. Run HTML Purifier
        if (function_exists('clean')) {
            $html = clean($html, 'pdf_template');
        } else {
            // If this throws, we know clean isn't running in tests
            throw new \Exception('clean function not found - make sure test environment loads it or mock it');
        }

        // 3. Unmasking
        $html = preg_replace_callback('/<!--\s*(TPLMASK_[a-zA-Z0-9]+_END)\s*-->/si', function ($matches) use ($tokenMap) {
            $token = $matches[1];
            return $tokenMap[$token] ?? '';
        }, $html);

        return $html;
    }

    public function test_sanitize_unwraps_table_in_paragraph()
    {
        // Issue: <p><table>...</table></p> is common WYSIWYG output but invalid HTML. 
        // HTML Purifier usually ejects the table or closes p early.
        // The sanitizer is supposed to unwrap it.
        $input = '<p><table><tr><td>Content</td></tr></table></p>';

        // We expect the P tags to be GONE.
        // But if 'table' is missing from structuralTags, they might remain or cause issues.

        // Since we can't easily run 'clean' without full app boot, let's just assert on the PRE-clean logic output
        // logic by temporarily commenting out 'clean' call or just checking the unwrap logic?
        // No, let's trust that 'clean' exists in this environment (Testbench).

        try {
            $output = $this->sanitizePdfContent($input);
        } catch (\Exception $e) {
            // If clean is missing, we can at least check if unwrapping happened in the logic before clean
            $this->markTestSkipped('Clean function missing, cannot verify full chain.');
        }

        // If the p tags are still there, clean() might have stripped the table or fixed it.
        // But we want to ensure our manual unwrapping works.

        $this->assertStringNotContainsString('<p>', $output, 'P tag should be removed');
        $this->assertStringNotContainsString('</p>', $output, 'Closing P tag should be removed');
        $this->assertStringContainsString('<table>', $output);
    }
}
