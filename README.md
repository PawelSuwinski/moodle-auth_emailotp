# Email One-Time Password Authentication

Matches only valid email from allowed domain as username. Validates client
credentials and password if exists in current session or generates ones for
session time on empty password treated as one-time password request.

On first login account is created if not prevented on global level 
and parts of email address may be mapped to profile fields using 
PCRE expressions.

See [setting form help](settings.php) for mapping usage example.
