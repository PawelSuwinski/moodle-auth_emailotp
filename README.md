# Email One-Time Password Authentication

Validates credentials and password if exists in current session or generates
ones for session time on empty password which is treated as one-time password
request and sends it to an email. Matches only valid email from allowed domains
using global `allowemailaddresses` and `denyemailaddresses` settings if set.

Additional security can be set:
- *revoke threshold*: login failures limit causing revoke of the generated password
- *minimum request period*: a time in seconds after which another password can be generated

Signup and user creation on first login if not prevented (global setting
`authpreventaccountcreation`) takes place only in case of using email as
username (not to be confused with the `authloginviaemail` global setting) and
parts of email address may be mapped to profile fields using PCRE expressions.

Auth instruction setting (global `auth_instructions`) is recommended depending
on the adopted user account policy and plugin configuration.

See also: `fieldsmapping_help` setting form for [mapping usage example](lang/en/auth_emailotp.php).
