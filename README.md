# FTP Webhook
A web hook written in PHP that creates an endpoint for (GitHub) Webhooks. This way, for example, a web server can be updated (through FTP) by simply pushing to the master branch of a git reposity.

## Getting started

Extract the contents of this repository in a folder on a PHP web server. This folder can now be used as the GitHub webhook endpoint.

To add a webhook to a repository, go to https://github.com/[user]/[repo]/settings/hooks. Be sure to set `Content type` to `application/x-www-form-urlencoded` and create a secret hash to secure your webhook (this is mandatory by default).

The configuration array must be filled in the following way on the webhook server:
```
$configs = array(
	"<user>/<repo>" => array(
		"refs/heads/<branch>" => array(
            "ftp_server" => "<ftpserver>",
            "ftp_username" => "<ftpusername>",
            "ftp_password" => "<ftppassword>",
            "ftp_basedir" => "<ftpbasedir>",
            "repo" => "<user>/<repo>",
            "secret" => "<secret>"
		),
		"refs/heads/<branch>" => array(etc..)
	),
	"<user>/<repo>" => array(etc..)
);
```


