@php
    use YtHub\Lang;
    $idPrefix = $idPrefix ?? 'bu';
    $catOptions = [
        '' => 'yt.cat_default',
        '22' => 'yt.cat_22',
        '24' => 'yt.cat_24',
        '20' => 'yt.cat_20',
        '10' => 'yt.cat_10',
        '27' => 'yt.cat_27',
        '28' => 'yt.cat_28',
        '25' => 'yt.cat_25',
        '26' => 'yt.cat_26',
    ];
@endphp
<details class="yt-meta-advanced" id="{{ $idPrefix }}-meta-wrap">
    <summary class="yt-meta-summary">{{ Lang::t('yt.meta_advanced_title') }}</summary>
    <p class="yt-meta-help">{!! Lang::tRich('yt.meta_help_studio_audio') !!}</p>

    <div class="bu-field yt-meta-field">
        <label for="{{ $idPrefix }}-meta-default-lang">{{ Lang::t('yt.meta_default_language') }}</label>
        <input id="{{ $idPrefix }}-meta-default-lang" name="meta_default_language" type="text" maxlength="24" placeholder="de" autocomplete="off" pattern="[a-zA-Z]{2,8}(-[a-zA-Z0-9]{1,8})*" title="BCP-47">
    </div>
    <div class="bu-field yt-meta-field">
        <label for="{{ $idPrefix }}-meta-audio-lang">{{ Lang::t('yt.meta_default_audio') }}</label>
        <input id="{{ $idPrefix }}-meta-audio-lang" name="meta_default_audio_language" type="text" maxlength="24" placeholder="de-DE" autocomplete="off" pattern="[a-zA-Z]{2,8}(-[a-zA-Z0-9]{1,8})*" title="BCP-47">
    </div>
    <div class="bu-field yt-meta-field">
        <label for="{{ $idPrefix }}-meta-tags">{{ Lang::t('yt.meta_tags') }}</label>
        <input id="{{ $idPrefix }}-meta-tags" name="meta_tags" type="text" maxlength="500" placeholder="tag1, tag2" autocomplete="off">
        <span class="yt-meta-hint">{{ Lang::t('yt.meta_tags_hint') }}</span>
    </div>
    <div class="bu-field yt-meta-field">
        <label for="{{ $idPrefix }}-meta-cat">{{ Lang::t('yt.meta_category') }}</label>
        <select id="{{ $idPrefix }}-meta-cat" name="meta_category_id">
            @foreach($catOptions as $val => $msgKey)
                <option value="{{ $val }}">{{ Lang::t($msgKey) }}</option>
            @endforeach
        </select>
    </div>

    <fieldset class="yt-meta-loc">
        <legend>{{ Lang::t('yt.meta_localizations_title') }}</legend>
        <p class="yt-meta-hint">{{ Lang::t('yt.meta_localizations_hint') }}</p>
        <div id="{{ $idPrefix }}-loc-rows" class="yt-loc-rows">
            <div class="yt-loc-row">
                <label class="yt-loc-lbl">{{ Lang::t('yt.meta_loc_locale') }}
                    <input name="meta_loc_locale[]" type="text" maxlength="24" placeholder="en" pattern="[a-zA-Z]{2,8}(-[a-zA-Z0-9]{1,8})*">
                </label>
                <label class="yt-loc-lbl">{{ Lang::t('yt.meta_loc_title') }}
                    <input name="meta_loc_title[]" type="text" maxlength="500">
                </label>
                <label class="yt-loc-lbl yt-loc-desc">{{ Lang::t('yt.meta_loc_desc') }}
                    <textarea name="meta_loc_desc[]" rows="2" maxlength="5000"></textarea>
                </label>
            </div>
        </div>
        <button type="button" class="bu-btn bu-btn-ghost yt-loc-add" id="{{ $idPrefix }}-loc-add">{{ Lang::t('yt.meta_loc_add') }}</button>
    </fieldset>

    <fieldset class="yt-meta-cap">
        <legend>{{ Lang::t('yt.meta_caption_title') }}</legend>
        <p class="yt-meta-hint">{{ Lang::t('yt.meta_caption_hint') }}</p>
        <div class="bu-field yt-meta-field">
            <label for="{{ $idPrefix }}-caption">{{ Lang::t('yt.meta_caption_file') }}</label>
            <input id="{{ $idPrefix }}-caption" name="caption" type="file" accept=".srt,.sbv,.sub,.vtt,application/octet-stream">
        </div>
        <div class="bu-field yt-meta-field">
            <label for="{{ $idPrefix }}-cap-lang">{{ Lang::t('yt.meta_caption_lang') }}</label>
            <input id="{{ $idPrefix }}-cap-lang" name="meta_caption_language" type="text" maxlength="24" placeholder="de" autocomplete="off">
        </div>
        <div class="bu-field yt-meta-field">
            <label for="{{ $idPrefix }}-cap-name">{{ Lang::t('yt.meta_caption_name') }}</label>
            <input id="{{ $idPrefix }}-cap-name" name="meta_caption_name" type="text" maxlength="100" placeholder="" autocomplete="off">
        </div>
        <div class="bu-check">
            <input id="{{ $idPrefix }}-cap-sync" type="checkbox" name="meta_caption_sync" value="1">
            <label for="{{ $idPrefix }}-cap-sync">{{ Lang::t('yt.meta_caption_sync') }}</label>
        </div>
    </fieldset>
</details>
