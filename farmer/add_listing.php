<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('farmer');
$farmerId = current_user()['id'];
$listing = null;

require __DIR__ . '/listing_form.inc.php';
