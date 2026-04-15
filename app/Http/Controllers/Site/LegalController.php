<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\BootstrapsYtHubPublic;
use Illuminate\View\View;
use YtHub\Lang;

final class LegalController extends Controller
{
    use BootstrapsYtHubPublic;

    public function datenschutz(): View
    {
        $this->bootstrapYtHubPublic();

        $legal = require config_path('legal.php');

        $imprintPageUrl = Lang::code() === 'de' ? $legal['imprint_url_de'] : $legal['imprint_url_en'];

        return view('site.legal.datenschutz', [
            'legal' => $legal,
            'imprintPageUrl' => $imprintPageUrl,
            'seoTitle' => Lang::t('legal.ds_html_title').' — '.$legal['company'],
            'seoDescription' => Lang::t('legal.meta_description_ds'),
            'seoCanonicalPath' => 'datenschutz.php',
            'seoIncludeJsonLd' => false,
            'hreflangPage' => 'datenschutz.php',
        ]);
    }

    public function impressum(): View
    {
        $this->bootstrapYtHubPublic();

        $legal = require config_path('legal.php');

        $imprintFullUrl = Lang::code() === 'de' ? $legal['imprint_url_de'] : $legal['imprint_url_en'];
        $imprintEsc = htmlspecialchars((string) $imprintFullUrl, ENT_QUOTES, 'UTF-8');
        $metaHtml = str_replace('##IMPRINT_URL##', $imprintEsc, Lang::t('legal.im_meta_html'));
        $s8Html = str_replace('##IMPRINT_URL##', $imprintEsc, Lang::t('legal.im_s8_p1_html'));

        return view('site.legal.impressum', [
            'legal' => $legal,
            'metaHtml' => $metaHtml,
            's8Html' => $s8Html,
            'seoTitle' => Lang::t('legal.im_html_title').' — '.$legal['company'],
            'seoDescription' => Lang::t('legal.meta_description_im'),
            'seoCanonicalPath' => 'impressum.php',
            'seoIncludeJsonLd' => false,
            'hreflangPage' => 'impressum.php',
        ]);
    }
}
