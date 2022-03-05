<?php declare(strict_types=1);

$session = Smr\Session::getInstance();
$account = $session->getAccount();

$message = trim(Smr\Request::get('message'));
if (Smr\Request::get('action') == 'Preview announcement') {
	$container = Page::create('skeleton.php', 'admin/announcement_create.php');
	$container['preview'] = $message;
	$container->go();
}

// put the msg into the database
$db = Smr\Database::getInstance();
$db->write('INSERT INTO announcement (time, admin_id, msg) VALUES(' . $db->escapeNumber(Smr\Epoch::time()) . ', ' . $db->escapeNumber($account->getAccountID()) . ', ' . $db->escapeString($message) . ')');

Page::create('skeleton.php', 'admin/admin_tools.php')->go();