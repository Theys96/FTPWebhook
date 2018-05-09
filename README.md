# FTP Webhook
A web hook written in PHP that creates an endpoint for (GitHub) Webhooks. This way, for example, a web server can be updated (through FTP) by simply pushing to the master branch of a git reposity.

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