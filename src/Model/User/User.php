<?php

declare(strict_types=1);

namespace Gnumoksha\FreeIpa\Model\User;

final class User
{
    public string $loginshell;
    public string $krbprincipalname;
    public int $uid;
    public int $nsaccountlock;
    public string $homedirectory;
    public int $uidnumber;
    public int $gidnumber;
    public string $sn;
    public string $dn;
}
