# FTP Webhook
Repository for testing git and webhooks.

`gitWebhook2.php` is the current version of the webhook.

The configuration array must be filled in the following way:
```
$configs = array(
	"<user>/<repo>" => array(
		"refs/heads/<branch>" => array(
			"<ftpserver>",
			"<ftpusername>",
			"<ftppassword>",
			"<ftpbasedir>",
			"<user>/<repo>",
		),
		"refs/heads/<branch>" => array(etc..)
	),
	"<user>/<repo>" => array(etc..)
);
```