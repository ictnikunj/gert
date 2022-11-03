<?php
/*
file_put_contents(FILE_SPRITE_SVG, '');

function write($s) {
  file_put_contents(FILE_SPRITE_SVG, $s, FILE_APPEND);
}

write('<svg display="none" width="0" height="0" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">');
write('<defs>');

foreach (new DirectoryIterator(DIR_SVG) as $fileInfo) {
    if($fileInfo->isDot() || strpos($fileInfo->getBasename(), 'ic_') !== 0) {
      continue;
    }

    $svg = file_get_contents($fileInfo->getRealPath());

    $id = substr($fileInfo->getBasename(), 3, -9);

    $processedSvg = str_replace(
      '<svg',
      '<symbol id="material-icon-sprite-' . $id . '"',
      $svg
    );

    $processedSvg = str_replace(
      ['xmlns="http://www.w3.org/2000/svg"', 'width="24"', 'height="24"'],
      '',
      $processedSvg
    );

    $processedSvg = str_replace('  ', ' ', $processedSvg);
    $processedSvg = str_replace('  ', ' ', $processedSvg);

    $processedSvg = str_replace('</svg>', '</symbol>', $processedSvg);

    write($processedSvg);
}

write('</defs>');
write('</svg>');
*/