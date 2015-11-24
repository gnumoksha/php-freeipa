# php-freeipa
A PHP library for connect and use some features of the freeIPA / Red Hat Identity Management

[![Build Status](https://travis-ci.org/gnumoksha/php-freeipa.svg)](https://travis-ci.org/gnumoksha/php-freeipa)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/gnumoksha/php-freeipa/badges/quality-score.png)](https://scrutinizer-ci.com/g/gnumoksha/php-freeipa/)
[![License](https://poser.pugx.org/gnumoksha/php-freeipa/license)](https://packagist.org/packages/gnumoksha/php-freeipa)
[![Latest Stable Version](https://poser.pugx.org/gnumoksha/php-freeipa/v/stable)](https://packagist.org/packages/gnumoksha/php-freeipa)


## Quick start guide

Get CA certificate from server. Example:
```bash
# The certificate can be obtained in https://$host/ipa/config/ca.crt
wget --no-check-certificate https://ipa.demo1.freeipa.org/ipa/config/ca.crt -O certs/ipa.demo1.freeipa.org_ca.crt
```

Creates an instance
```php
require_once('./bootstrap.php');
$host = 'ipa.demo1.freeipa.org';
$certificate = __DIR__ . "./certs/ipa.demo1.freeipa.org_ca.crt";
$ipa = new \FreeIPA\APIAccess\Main($host, $certificate);
```

Authenticates with the server
```php
$user = 'admin';
$password = 'Secret123';
$auth = $ipa->connection()->authenticate($user, $password);
if ($auth) {
    print 'Logged in';
} else {
    $auth_info = $ipa->connection->getAuthenticationInfo();
    var_dump($auth_info);
}
```

Showing the user information
```php
$r = $ipa->user()->get($user);
var_dump($r);
```

Searching for users
```php
$r = $ipa->user()->findBy('mail', 'user@company.com');
if ($r) {
    $t = count($r);
    print "Found $t usuários. Names: ";
    for ($i = 0; $i < $t; $i++) {
        print $r[$i]->uid[0] . ' | ';
    }
}
```

Insert a new user
```php
$user_data = array(
    'givenname' => 'Richard',
    'sn' => 'Stallman',
    'uid' => "rms",
    'mail' => "rms@fsf.org",
    'userpassword' => 'Secret123',
);
$add_user = $ipa->user()->add($user_data);
if ($add_user) {
    print 'User added';
}
```

Insert a new group
```php
$add_group = $ipa->group()->add("groupXYZ", "description of groupXYZ");
if ($add_group) {
    print 'Group added';
}
```

For more examples see file `examples\all.php`.

## Roadmap

- [] Implements more (all?) API methods
- [] Implements more tests
