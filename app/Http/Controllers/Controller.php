<?php

namespace App\Http\Controllers;

use App\Traits\CreatesNotifications;

abstract class Controller
{
    use CreatesNotifications;
}
