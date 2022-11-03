<?php
$payload = json_decode(file_get_contents('php://input'), true);
$text = isset($payload['data']['contents']['text']) ? $payload['data']['contents']['text'] : '';
$level = htmlspecialchars($payload['data']['configuration']['level']);
?>

<h<?php echo $level ?>>
  <div data-ropi-content-editable="text" data-ropi-content-editable-commands="italic,underline,justifyleft,justifycenter,justifyright,createlink,unlink"><?php echo $text ?></div>
</h<?php echo $level ?>>
