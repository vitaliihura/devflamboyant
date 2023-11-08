<?php
  if (!class_exists( 'simple_html_dom')) {
    require_once __DIR__ . '/lib/simple_html_dom.php';
  }

  function trinity_get_clean_text($title, $content, $whitelist_shortcodes) {
    if ($title) $content = $title . TRINITY_AUDIO_DOT . $content;

    global $shortcode_tags;
    $shortcode_tags_keys = array_keys($shortcode_tags);

    $result_shortcodes_tags = array_filter(
      $shortcode_tags_keys,
      function ($value) use ($whitelist_shortcodes) {
        return !in_array($value, $whitelist_shortcodes);
      }
    );

      $regex   = get_shortcode_regex($result_shortcodes_tags);
      $content = preg_replace("/$regex/", '', $content);

      $content = do_shortcode($content);

      $content = html_entity_decode($content);

    // remove tags that was specified by user
    $content = trinity_remove_tags($content);

    // replace all new lines with pause
    $content = preg_replace('/[\n|\r]+/', BREAK_MACRO, $content);

    // get text from HTML
    $content = trinity_get_text_from_html($content);

    // replace all new lines with pause, after we have output from HTML as text
    $content = preg_replace('/[\n|\r]+/', BREAK_MACRO, $content);

    // remove all pause symbols in sequence with one pause
    $content = preg_replace('/\x{23F8}(\s*\x{23F8})*/u', BREAK_MACRO, $content);

    // remove all pause symbols that are in the beginning of the text
    return preg_replace('/^\x{23F8}+/u', '', $content);
  }

  function trinity_remove_tags($text) {
    $trinity_tags_to_skip_from_reading = trinity_get_skip_tags();
    if (empty($trinity_tags_to_skip_from_reading)) return $text;

    $html = str_get_html($text);
    if (!$html) return $text; // returns false in case no content was found

    $instances = $html->find(implode(',', $trinity_tags_to_skip_from_reading));

    foreach ($instances as $element) {
      $element->remove();
    }

    return $html->save(); // return HTML as a string
  }

  function trinity_get_text_from_html($text) {
    if (!$text) return $text;

    $html = str_get_html('<html>' . $text . '</html>'); // wrap to have a root element

    foreach ($html->find('.trinity-skip-it') as $element) {
      $element->remove();
    }

    $cleaned_text = strip_tags($html->firstChild()->plaintext); // make additional strip, in case publisher use wrong tags, e.g. l1 instead of li, just to be sure

    $html->clear(); // Clear memory
    unset($html);

    return $cleaned_text;
  }
